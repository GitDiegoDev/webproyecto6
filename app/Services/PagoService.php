<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Pago;
use App\Models\PromesaPago;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagoService
{
    /**
     * Registers a payment for a cuota.
     * Handles full/partial payment, preserving the official financial values.
     * Condonacion does NOT modify cuotas.punitorios. It is only recorded in pagos.punitorios_perdonados.
     * Pending promises are updated to 'cumplida'.
     *
     * @param array $data Contains keys: cuota_id, user_id, monto_cobrado, punitorios_perdonados, medio_pago, observaciones, fecha_pago
     * @return Pago
     */
    public function registrarPago(array $data): Pago
    {
        return DB::transaction(function () use ($data) {
            $cuota = Cuota::findOrFail($data['cuota_id']);

            $montoCobrado = floatval($data['monto_cobrado']);
            $punitoriosPerdonados = floatval($data['punitorios_perdonados'] ?? 0.00);
            $fechaPago = $data['fecha_pago'] ?? now();

            // Official values snapshot
            $importeOriginalSnapshot = floatval($cuota->importe_original);
            $punitoriosSnapshot = floatval($cuota->punitorios);
            $totalActualizadoSnapshot = floatval($cuota->total_actualizado);

            // Calculate if the payment is cancelatorio
            // A payment cancels the cuota if (monto_cobrado + punitorios_perdonados) >= cuota->saldo_pendiente
            // Or if monto_cobrado >= cuota->saldo_pendiente
            $isCancelatorio = ($montoCobrado + $punitoriosPerdonados) >= floatval($cuota->saldo_pendiente) || $montoCobrado >= floatval($cuota->saldo_pendiente);

            $pago = Pago::create([
                'cuota_id' => $cuota->id,
                'user_id' => $data['user_id'],
                'fecha_pago' => $fechaPago,
                'importe_original_snapshot' => $importeOriginalSnapshot,
                'punitorios_snapshot' => $punitoriosSnapshot,
                'total_actualizado_snapshot' => $totalActualizadoSnapshot,
                'monto_cobrado' => $montoCobrado,
                'punitorios_perdonados' => $punitoriosPerdonados,
                'es_cancelatorio' => $isCancelatorio,
                'medio_pago' => $data['medio_pago'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // Adjust saldo_pendiente
            // Net reduction is monto_cobrado + punitorios_perdonados
            $newSaldo = max(0.00, floatval($cuota->saldo_pendiente) - ($montoCobrado + $punitoriosPerdonados));
            $cuota->saldo_pendiente = $newSaldo;

            // Resolve associated pending promises
            if ($isCancelatorio) {
                $cuota->estado_gestion = 'cobrado';

                // If the entire debt is resolved, we mark pending promises as cumplida
                $cuota->promesasPago()
                    ->where('estado', 'pendiente')
                    ->update([
                        'estado' => 'cumplida',
                        'fecha_resolucion' => $fechaPago,
                    ]);
            } else {
                $cuota->estado_gestion = 'seguimiento';
            }

            $cuota->save();

            // Recalculate priority
            $priorityService = new CuotaPriorityService();
            $priorityService->recalculatePriority($cuota, $fechaPago);

            return $pago;
        });
    }
}

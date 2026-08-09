<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\VisitaCobrador;
use Illuminate\Support\Facades\DB;

class VisitaCobradorService
{
    /**
     * Program a new visit.
     *
     * @param array $data Contains keys: cliente_id, cuota_id (optional), cobrador_id, fecha_programada, observaciones
     * @return VisitaCobrador
     */
    public function programarVisita(array $data): VisitaCobrador
    {
        return DB::transaction(function () use ($data) {
            $cliente = Cliente::findOrFail($data['cliente_id']);

            // Domicilio de la visita copies the current domicilio of the client to protect against future modifications
            $domicilioSnapshot = $cliente->domicilio ?? '';

            $visita = VisitaCobrador::create([
                'cliente_id' => $cliente->id,
                'cuota_id' => $data['cuota_id'] ?? null,
                'cobrador_id' => $data['cobrador_id'],
                'domicilio' => $domicilioSnapshot,
                'fecha_programada' => $data['fecha_programada'],
                'estado' => 'pendiente',
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // If visit is bound to a cuota, update cuota management state
            if ($visita->cuota_id) {
                $cuota = Cuota::find($visita->cuota_id);
                $cuota->estado_gestion = 'visita_solicitada';
                $cuota->save();

                $priorityService = new CuotaPriorityService();
                $priorityService->recalculatePriority($cuota);
            }

            return $visita;
        });
    }

    /**
     * Record the execution outcome of a visit.
     *
     * @param int $id
     * @param array $outcome Keys: resultado, monto_cobrado, observaciones, fecha_realizada
     * @return VisitaCobrador
     */
    public function registrarResultadoVisita(int $id, array $outcome): VisitaCobrador
    {
        return DB::transaction(function () use ($id, $outcome) {
            $visita = VisitaCobrador::findOrFail($id);

            $visita->estado = 'realizada';
            $visita->resultado = $outcome['resultado'];
            $visita->monto_cobrado = $outcome['monto_cobrado'] ?? 0.00;
            $visita->fecha_realizada = $outcome['fecha_realizada'] ?? now();
            $visita->observaciones = $outcome['observaciones'] ?? $visita->observaciones;
            $visita->save();

            // If linked to a cuota and money was collected, we can record a payment as well or update the cuota status
            if ($visita->cuota_id) {
                $cuota = $visita->cuota;

                if ($visita->resultado === 'cobrado' || $visita->resultado === 'cobrado_parcialmente') {
                    // We let PagoService handle the accounting if needed, but we update status
                    $cuota->estado_gestion = 'cobrado';
                } else {
                    $cuota->estado_gestion = 'contactado';
                }
                $cuota->save();

                $priorityService = new CuotaPriorityService();
                $priorityService->recalculatePriority($cuota);
            }

            return $visita;
        });
    }
}

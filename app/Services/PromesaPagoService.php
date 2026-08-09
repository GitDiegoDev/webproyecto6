<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\PromesaPago;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromesaPagoService
{
    /**
     * Create a new payment promise.
     *
     * @param array $data Contains keys: cuota_id, gestion_id, user_id, fecha_prometida, monto_prometido, observaciones
     * @return PromesaPago
     */
    public function crearPromesa(array $data): PromesaPago
    {
        return DB::transaction(function () use ($data) {
            $cuota = Cuota::findOrFail($data['cuota_id']);

            // Cancel other pending promises for this cuota to keep only one active
            $cuota->promesasPago()
                ->where('estado', 'pendiente')
                ->update([
                    'estado' => 'cancelada',
                    'fecha_resolucion' => now(),
                ]);

            $promesa = PromesaPago::create([
                'cuota_id' => $cuota->id,
                'gestion_id' => $data['gestion_id'] ?? null,
                'user_id' => $data['user_id'],
                'fecha_creacion' => $data['fecha_creacion'] ?? now(),
                'fecha_prometida' => $data['fecha_prometida'],
                'monto_prometido' => $data['monto_prometido'] ?? null,
                'estado' => 'pendiente',
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // Update cuota management state
            $cuota->estado_gestion = 'promesa_pendiente';
            $cuota->save();

            // Recalculate priority
            $priorityService = new CuotaPriorityService();
            $priorityService->recalculatePriority($cuota, $promesa->fecha_creacion);

            return $promesa;
        });
    }

    /**
     * Transition a promise state manually.
     *
     * @param int $id
     * @param string $nuevoEstado 'pendiente', 'cumplida', 'incumplida', 'cancelada'
     * @return PromesaPago
     */
    public function cambiarEstado(int $id, string $nuevoEstado): PromesaPago
    {
        return DB::transaction(function () use ($id, $nuevoEstado) {
            $promesa = PromesaPago::findOrFail($id);
            $promesa->estado = $nuevoEstado;
            $promesa->fecha_resolucion = now();
            $promesa->save();

            $cuota = $promesa->cuota;

            // Sync cuota management state if promise breached
            if ($nuevoEstado === 'incumplida') {
                $cuota->estado_gestion = 'promesa_incumplida';
                $cuota->save();
            }

            $priorityService = new CuotaPriorityService();
            $priorityService->recalculatePriority($cuota);

            return $promesa;
        });
    }

    /**
     * Scans and marks overdue pending promises as 'incumplida'.
     *
     * @param mixed $referenceDate
     * @return int Count of marked promises
     */
    public function procesarPromesasVencidas($referenceDate = null): int
    {
        $ref = $referenceDate ? Carbon::parse($referenceDate) : Carbon::today();

        return DB::transaction(function () use ($ref) {
            $vencidas = PromesaPago::where('estado', 'pendiente')
                ->whereDate('fecha_prometida', '<', $ref->toDateString())
                ->get();

            $count = 0;
            foreach ($vencidas as $promesa) {
                $promesa->estado = 'incumplida';
                $promesa->fecha_resolucion = $ref;
                $promesa->save();

                $cuota = $promesa->cuota;
                $cuota->estado_gestion = 'promesa_incumplida';
                $cuota->save();

                $priorityService = new CuotaPriorityService();
                $priorityService->recalculatePriority($cuota, $ref);

                $count++;
            }

            return $count;
        });
    }
}

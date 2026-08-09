<?php

namespace App\Services;

use App\Models\Cuota;
use Carbon\Carbon;

class CuotaStatusService
{
    /**
     * Determine the current management/financial status of a cuota.
     *
     * @param Cuota $cuota
     * @param mixed $referenceDate Carbon instance or date string, defaults to today
     * @return string One of the required statuses
     */
    public function determineStatus(Cuota $cuota, $referenceDate = null): string
    {
        $ref = $referenceDate ? Carbon::parse($referenceDate) : Carbon::today();

        // 1. Pago realizado oficial (financial state)
        if ($cuota->estado_financiero === 'pago_realizado_oficial') {
            return 'pago_realizado_oficial';
        }

        // 2. Pago registrado (fully paid in app, i.e., saldo_pendiente is 0)
        if ($cuota->saldo_pendiente <= 0 || $cuota->estado_gestion === 'cobrado') {
            return 'pago_registrado';
        }

        // 3. Visita de cobrador requested
        if ($cuota->estado_gestion === 'visita_solicitada') {
            return 'visita_cobrador';
        }

        // 4. Promesas de pago logic
        // Find if there is any pending promise
        $pendingPromise = $cuota->promesasPago()
            ->where('estado', 'pendiente')
            ->orderBy('fecha_prometida', 'asc')
            ->first();

        if ($pendingPromise) {
            $fechaPrometida = Carbon::parse($pendingPromise->fecha_prometida);
            if ($fechaPrometida->lt($ref->copy()->startOfDay())) {
                return 'promesa_incumplida';
            }
            return 'promesa_pago';
        }

        // 5. Explicit promesa incumplida state
        if ($cuota->estado_gestion === 'promesa_incumplida') {
            return 'promesa_incumplida';
        }

        // 6. Sin respuesta state
        if ($cuota->estado_gestion === 'sin_respuesta') {
            return 'sin_respuesta';
        }

        // 7. Base on due day (dia_cobro)
        $dayOfCobro = intval($cuota->dia_cobro);
        $refDay = intval($ref->day);

        if ($dayOfCobro < $refDay) {
            return 'vencida';
        } elseif ($dayOfCobro === $refDay) {
            return 'vence_hoy';
        } else {
            return 'proximo_vencimiento';
        }
    }
}

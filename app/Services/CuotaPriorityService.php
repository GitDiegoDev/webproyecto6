<?php

namespace App\Services;

use App\Models\Cuota;
use Carbon\Carbon;

class CuotaPriorityService
{
    /**
     * Recalculates and updates the priority of a single cuota based on its current context.
     *
     * @param Cuota $cuota
     * @param mixed $referenceDate Carbon instance or date string, defaults to today
     * @return string Calculated priority ('critica', 'alta', 'media', 'baja')
     */
    public function recalculatePriority(Cuota $cuota, $referenceDate = null): string
    {
        $ref = $referenceDate ? Carbon::parse($referenceDate) : Carbon::today();
        $statusService = new CuotaStatusService();
        $status = $statusService->determineStatus($cuota, $ref);

        // If paid, priority is low
        if ($status === 'pago_realizado_oficial' || $status === 'pago_registrado') {
            $cuota->update(['prioridad' => 'baja']);
            return 'baja';
        }

        // CRÍTICA cases:
        // - promesa incumplida;
        // - cliente que no responde después de múltiples intentos (meaning state is sin_respuesta AND we have a threshold or sufficient history - e.g. >= 3 gestiones without answer, or if explicitly marked sin_respuesta but we check for sufficient history as per requirement: "No marcar automáticamente como crítico un cliente que simplemente no respondió si todavía no existe suficiente historial para justificarlo.");
        // - visita de cobrador pendiente;
        // - cuota con muchos días de atraso (let's check if the difference is large, we can define a configurable or standard threshold like 15 or 30 days if needed, but let's make it safe/robust or parameterized);

        $hasPendingVisit = $cuota->visitasCobrador()->where('estado', 'pendiente')->exists();

        // Check if promesa is breached
        $hasBreachedPromise = $cuota->promesasPago()->where('estado', 'incumplida')->exists() || $status === 'promesa_incumplida';

        // Check sin_respuesta history
        $unresponsiveGestionesCount = $cuota->gestiones()
            ->whereIn('resultado', ['no_atendio', 'sin_respuesta'])
            ->count();
        $isCriticaUnresponsive = ($status === 'sin_respuesta' && $unresponsiveGestionesCount >= 3);

        // Check days overdue (dia_cobro < refDay)
        $dayOfCobro = intval($cuota->dia_cobro);
        $refDay = intval($ref->day);
        $daysLate = $refDay - $dayOfCobro;
        $isVeryLate = ($daysLate >= 15); // standard threshold for "muchos días de atraso"

        if ($hasBreachedPromise || $isCriticaUnresponsive || $hasPendingVisit || $isVeryLate) {
            $priority = 'critica';
        }
        // ALTA cases:
        // - cuota vencida (daysLate > 0);
        // - cliente que manifestó intención de pagar pero todavía no pagó (estado_gestion 'seguimiento' or 'contactado');
        // - promesa cercana (pending promise within next 2 days);
        // - seguimiento pendiente (proxima_accion_fecha is today or past);
        // - cliente que pidió cobrador (estado_gestion 'visita_solicitada');
        elseif ($status === 'vencida' ||
            $status === 'promesa_pago' ||
            $cuota->estado_gestion === 'visita_solicitada' ||
            $cuota->estado_gestion === 'contactado' ||
            $cuota->estado_gestion === 'seguimiento' ||
            $this->hasPendingActionSoon($cuota, $ref) ||
            $this->hasNearPromise($cuota, $ref)
        ) {
            $priority = 'alta';
        }
        // MEDIA cases:
        // - cuota próxima a vencer (status 'proximo_vencimiento');
        // - cliente que necesita recordatorio;
        // - cliente que solicitó link de pago;
        // - seguimiento preventivo.
        elseif ($status === 'vence_hoy' ||
            $status === 'proximo_vencimiento' ||
            $cuota->estado_gestion === 'link_solicitado' ||
            $cuota->link_pago !== null
        ) {
            $priority = 'media';
        }
        // BAJA cases:
        // - cases preventivos that do not require immediate action.
        else {
            $priority = 'baja';
        }

        $cuota->update(['prioridad' => $priority]);
        return $priority;
    }

    protected function hasPendingActionSoon(Cuota $cuota, Carbon $ref): bool
    {
        $latestGestionWithAction = $cuota->gestiones()
            ->whereNotNull('proxima_accion_fecha')
            ->orderBy('proxima_accion_fecha', 'desc')
            ->first();

        if ($latestGestionWithAction) {
            $actionDate = Carbon::parse($latestGestionWithAction->proxima_accion_fecha);
            return $actionDate->lte($ref->copy()->endOfDay());
        }

        return false;
    }

    protected function hasNearPromise(Cuota $cuota, Carbon $ref): bool
    {
        $nearPromise = $cuota->promesasPago()
            ->where('estado', 'pendiente')
            ->whereDate('fecha_prometida', '<=', $ref->copy()->addDays(2)->toDateString())
            ->exists();

        return $nearPromise;
    }
}

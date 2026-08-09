<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\PromesaPago;
use App\Models\VisitaCobrador;
use App\Models\Gestion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AgendaCobranzaService
{
    /**
     * Retrieve daily tasks and items that require action on HOY or PRÓXIMOS DÍAS.
     *
     * @param mixed $referenceDate
     * @return array
     */
    public function obtenerAgendaDiaria($referenceDate = null): array
    {
        $ref = $referenceDate ? Carbon::parse($referenceDate) : Carbon::today();
        $refStr = $ref->toDateString();

        $statusService = new CuotaStatusService();

        // 1. Promesas para hoy (fecha_prometida = ref AND status = pendiente)
        $promesasHoy = PromesaPago::where('estado', 'pendiente')
            ->whereDate('fecha_prometida', '=', $refStr)
            ->with(['cuota.operacion.cliente'])
            ->get();

        // 2. Promesas incumplidas (estado = incumplida)
        $promesasIncumplidas = PromesaPago::where('estado', 'incumplida')
            ->with(['cuota.operacion.cliente'])
            ->get();

        // 3. Visitas de cobrador programadas para hoy
        $visitasHoy = VisitaCobrador::where('estado', 'pendiente')
            ->whereDate('fecha_programada', '=', $refStr)
            ->with(['cliente', 'cuota'])
            ->get();

        // 4. Seguimientos cuya fecha llegó (latest gestiones with proxima_accion_fecha <= ref)
        // Let's find cuotas where the most recent action date is due.
        $seguimientosHoy = Cuota::whereHas('gestiones', function ($q) use ($refStr) {
            $q->whereNotNull('proxima_accion_fecha')
              ->whereDate('proxima_accion_fecha', '<=', $refStr);
        })
        ->whereNotIn('estado_financiero', ['pago_realizado_oficial'])
        ->whereNotIn('estado_gestion', ['cobrado'])
        ->with(['operacion.cliente', 'gestiones'])
        ->get();

        // 5. Cuotas que vencen hoy
        // 6. Cuotas vencidas
        // 7. Próximos vencimientos (within next 5 days)
        $cuotasVencenHoy = Collection::empty();
        $cuotasVencidas = Collection::empty();
        $proximosVencimientos = Collection::empty();
        $clientesSinRespuesta = Collection::empty();
        $requierenAtencion = Collection::empty();

        $allCuotas = Cuota::whereNotIn('estado_financiero', ['pago_realizado_oficial'])
            ->whereNotIn('estado_gestion', ['cobrado'])
            ->with(['operacion.cliente'])
            ->get();

        foreach ($allCuotas as $cuota) {
            $status = $statusService->determineStatus($cuota, $ref);

            if ($status === 'vence_hoy') {
                $cuotasVencenHoy->push($cuota);
            } elseif ($status === 'vencida') {
                $cuotasVencidas->push($cuota);
            } elseif ($status === 'proximo_vencimiento') {
                // Check if it's within 5 days
                $dayOfCobro = intval($cuota->dia_cobro);
                $refDay = intval($ref->day);
                if ($dayOfCobro > $refDay && ($dayOfCobro - $refDay) <= 5) {
                    $proximosVencimientos->push($cuota);
                }
            }

            if ($status === 'sin_respuesta') {
                $clientesSinRespuesta->push($cuota);
            }

            // Require contact / attention (e.g. no contact yet or priority high/critical)
            if ($cuota->estado_gestion === 'sin_contactar' || in_array($cuota->prioridad, ['critica', 'alta'])) {
                $requierenAtencion->push($cuota);
            }
        }

        // Agenda próximos días (looking at tomorrow and day after, let's say next 5 days)
        $nextDaysStartStr = $ref->copy()->addDay()->toDateString();
        $nextDaysEndStr = $ref->copy()->addDays(5)->toDateString();

        $promesasFuturas = PromesaPago::where('estado', 'pendiente')
            ->whereBetween('fecha_prometida', [$nextDaysStartStr, $nextDaysEndStr])
            ->with(['cuota.operacion.cliente'])
            ->get();

        $seguimientosFuturos = Cuota::whereHas('gestiones', function ($q) use ($nextDaysStartStr, $nextDaysEndStr) {
            $q->whereNotNull('proxima_accion_fecha')
              ->whereBetween('proxima_accion_fecha', [$nextDaysStartStr, $nextDaysEndStr]);
        })
        ->whereNotIn('estado_financiero', ['pago_realizado_oficial'])
        ->whereNotIn('estado_gestion', ['cobrado'])
        ->with(['operacion.cliente'])
        ->get();

        $visitasFuturas = VisitaCobrador::where('estado', 'pendiente')
            ->whereBetween('fecha_programada', [$nextDaysStartStr, $nextDaysEndStr])
            ->with(['cliente', 'cuota'])
            ->get();

        return [
            'hoy' => [
                'promesas_hoy' => $promesasHoy,
                'promesas_incumplidas' => $promesasIncumplidas,
                'visitas_hoy' => $visitasHoy,
                'seguimientos_hoy' => $seguimientosHoy,
                'cuotas_vencen_hoy' => $cuotasVencenHoy,
                'cuotas_vencidas' => $cuotasVencidas,
                'clientes_sin_respuesta' => $clientesSinRespuesta,
                'cuotas_requieren_atencion' => $requierenAtencion,
            ],
            'proximos_dias' => [
                'promesas_futuras' => $promesasFuturas,
                'seguimientos_futuros' => $seguimientosFuturos,
                'visitas_futuras' => $visitasFuturas,
                'proximos_vencimientos' => $proximosVencimientos,
            ],
        ];
    }
}

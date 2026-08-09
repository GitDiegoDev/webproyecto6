<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Gestion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GestionService
{
    /**
     * Register a new gestion for a cuota.
     *
     * @param array $data Contains keys: cuota_id, user_id, tipo, resultado, observacion, proxima_accion, proxima_accion_fecha
     * @return Gestion
     */
    public function registrarGestion(array $data): Gestion
    {
        return DB::transaction(function () use ($data) {
            $cuota = Cuota::findOrFail($data['cuota_id']);

            $gestion = Gestion::create([
                'cuota_id' => $cuota->id,
                'user_id' => $data['user_id'],
                'fecha_hora' => $data['fecha_hora'] ?? now(),
                'tipo' => $data['tipo'],
                'resultado' => $data['resultado'],
                'observacion' => $data['observacion'] ?? null,
                'proxima_accion' => $data['proxima_accion'] ?? null,
                'proxima_accion_fecha' => $data['proxima_accion_fecha'] ?? null,
            ]);

            // Update Cuota estado_gestion based on gestion resultado
            $estadoGestion = $cuota->estado_gestion;
            if ($data['resultado'] === 'prometio_pagar') {
                $estadoGestion = 'promesa_pendiente';
            } elseif ($data['resultado'] === 'no_atendio' || $data['resultado'] === 'sin_respuesta') {
                // Count consecutive non-responses to see if we transition state to sin_respuesta
                $nonResponses = $cuota->gestiones()
                    ->whereIn('resultado', ['no_atendio', 'sin_respuesta'])
                    ->count();
                if ($nonResponses >= 3) {
                    $estadoGestion = 'sin_respuesta';
                } else {
                    $estadoGestion = 'contactado';
                }
            } elseif ($data['resultado'] === 'solicito_cobrador') {
                $estadoGestion = 'visita_solicitada';
            } elseif ($data['resultado'] === 'promesa_incumplida') {
                $estadoGestion = 'promesa_incumplida';
            } elseif ($data['resultado'] === 'seguimiento') {
                $estadoGestion = 'seguimiento';
            } elseif ($data['resultado'] === 'cliente_pago') {
                $estadoGestion = 'cobrado';
            } else {
                $estadoGestion = 'contactado';
            }

            $cuota->estado_gestion = $estadoGestion;
            $cuota->save();

            // Recalculate priority
            $priorityService = new CuotaPriorityService();
            $priorityService->recalculatePriority($cuota, $gestion->fecha_hora);

            return $gestion;
        });
    }
}

<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\PlantillaMensaje;
use App\Models\PromesaPago;
use App\Models\VisitaCobrador;
use Carbon\Carbon;

class MessageTemplateService
{
    /**
     * Get the list of all supported dynamic variables.
     */
    public function getAvailableVariables(): array
    {
        return [
            '{nombre}' => 'Nombre (o Nombre completo de compatibilidad)',
            '{apellido}' => 'Apellido del cliente',
            '{nombre_completo}' => 'Nombre completo del cliente',
            '{telefono}' => 'Teléfono del cliente',
            '{numero_solicitud}' => 'Número de solicitud/crédito',
            '{numero_cuota}' => 'Número de cuota actual',
            '{dia_cobro}' => 'Día del mes establecido para el cobro',
            '{fecha_vencimiento}' => 'Fecha de vencimiento en el período activo',
            '{importe}' => 'Saldo pendiente actual formateado',
            '{importe_original}' => 'Importe original de la cuota',
            '{punitorios}' => 'Punitorios oficiales acumulados',
            '{total_actualizado}' => 'Total oficial actualizado',
            '{monto_pendiente}' => 'Monto pendiente actual de pago',
            '{fecha_prometida}' => 'Fecha de promesa de pago más reciente',
            '{monto_prometido}' => 'Monto acordado en la promesa más reciente',
            '{link_pago}' => 'Link de pago oficial de la cuota',
            '{domicilio}' => 'Domicilio registrado del cliente',
            '{fecha_visita}' => 'Fecha programada para visita del cobrador',
            '{nombre_cobrador}' => 'Nombre del cobrador asignado',
        ];
    }

    /**
     * Parse template body with real data from Cuota.
     */
    public function parseTemplate(string $cuerpo, Cuota $cuota, $referenceDate = null): array
    {
        $ref = $referenceDate ? Carbon::parse($referenceDate) : Carbon::today();
        $cliente = $cuota->operacion?->cliente;

        // Base client and operation values
        $nombreCompleto = trim(($cliente?->nombre ?? '') . ' ' . ($cliente?->apellido ?? ''));
        $nombre = $nombreCompleto; // Compatibility with legacy tests expecting {nombre} to be full name
        $apellido = $cliente?->apellido ?? '';
        $telefono = $cliente?->telefono ?? '';
        $domicilio = $cliente?->domicilio ?? '';
        $numeroSolicitud = $cuota->operacion?->numero_solicitud ?? '';
        $numeroCuota = $cuota->numero_cuota ?? '';
        $diaCobro = $cuota->dia_cobro ?? '';

        // Financial values formatted without leading $ sign to allow ${importe} in templates
        $importe = number_format($cuota->saldo_pendiente ?? 0.00, 2);
        $importeOriginal = number_format($cuota->importe_original ?? 0.00, 2);
        $punitorios = number_format($cuota->punitorios ?? 0.00, 2);
        $totalActualizado = number_format($cuota->total_actualizado ?? 0.00, 2);
        $montoPendiente = number_format($cuota->saldo_pendiente ?? 0.00, 2);

        // Date of Vencimiento calculation
        $periodo = \App\Models\PeriodoCobranza::where('activo', true)->first();
        $mes = $periodo ? $periodo->mes : $ref->month;
        $anio = $periodo ? $periodo->anio : $ref->year;
        $base = Carbon::create($anio, $mes, 1);
        $day = min((int)($cuota->dia_cobro ?? 1), $base->daysInMonth);
        $fechaVencimiento = $base->setDay($day)->format('d/m/Y');

        // Promise values
        $promesa = $cuota->promesasPago()
            ->where('estado', 'pendiente')
            ->orderBy('fecha_prometida', 'asc')
            ->first();
        if (!$promesa) {
            $promesa = $cuota->promesasPago()
                ->orderBy('fecha_creacion', 'desc')
                ->first();
        }
        $fechaPrometida = $promesa ? Carbon::parse($promesa->fecha_prometida)->format('d/m/Y') : '';
        $montoPrometido = $promesa ? number_format($promesa->monto_prometido, 2) : '';

        // Visita values
        $visita = $cuota->visitasCobrador()
            ->where('estado', 'pendiente')
            ->orderBy('fecha_programada', 'asc')
            ->first();
        if (!$visita) {
            $visita = $cuota->visitasCobrador()
                ->orderBy('fecha_programada', 'desc')
                ->first();
        }
        $fechaVisita = $visita ? Carbon::parse($visita->fecha_programada)->format('d/m/Y') : '';
        $nombreCobrador = $visita && $visita->cobrador ? $visita->cobrador->name : '';

        // Link de pago
        $linkPago = $cuota->link_pago ?? '';

        // Replacements mapping
        $replacements = [
            '{nombre}' => $nombre,
            '{apellido}' => $apellido,
            '{nombre_completo}' => $nombreCompleto,
            '{telefono}' => $telefono,
            '{numero_solicitud}' => $numeroSolicitud,
            '{numero_cuota}' => $numeroCuota,
            '{dia_cobro}' => $diaCobro,
            '{fecha_vencimiento}' => $fechaVencimiento,
            '{importe}' => $importe,
            '{importe_original}' => $importeOriginal,
            '{punitorios}' => $punitorios,
            '{total_actualizado}' => $totalActualizado,
            '{monto_pendiente}' => $montoPendiente,
            '{fecha_prometida}' => $fechaPrometida,
            '{monto_prometido}' => $montoPrometido,
            '{link_pago}' => $linkPago,
            '{domicilio}' => $domicilio,
            '{fecha_visita}' => $fechaVisita,
            '{nombre_cobrador}' => $nombreCobrador,
        ];

        // Track warnings for missing values requested in the body
        $warnings = [];
        foreach ($replacements as $variable => $value) {
            if (str_contains($cuerpo, $variable) && empty($value)) {
                if ($variable === '{link_pago}') {
                    $warnings[] = "Esta cuota no tiene un link de pago disponible.";
                } elseif ($variable === '{fecha_prometida}' || $variable === '{monto_prometido}') {
                    $warnings[] = "Esta cuota no registra ninguna promesa de pago activa.";
                } elseif ($variable === '{fecha_visita}' || $variable === '{nombre_cobrador}') {
                    $warnings[] = "Esta cuota no registra ninguna visita de cobrador programada.";
                } else {
                    $cleanVar = str_replace(['{', '}'], '', $variable);
                    $warnings[] = "La variable '{$cleanVar}' está vacía para este cliente/cuota.";
                }
            }
        }

        // Replace variables in text (ensure no literal "null" or "undefined" is printed)
        $parsedText = $cuerpo;
        foreach ($replacements as $variable => $value) {
            $parsedText = str_replace($variable, (string)$value, $parsedText);
        }

        return [
            'text' => $parsedText,
            'warnings' => $warnings,
        ];
    }

    /**
     * Scan template text to find braces like {variable} and verify if they are supported.
     * Returns an array of unrecognized variables.
     */
    public function detectUnknownVariables(string $cuerpo): array
    {
        preg_match_all('/\{[a-zA-Z0-9_]+\}/', $cuerpo, $matches);
        $found = $matches[0] ?? [];
        $supported = array_keys($this->getAvailableVariables());

        $unknown = [];
        foreach ($found as $f) {
            if (!in_array($f, $supported)) {
                $unknown[] = $f;
            }
        }

        return array_unique($unknown);
    }

    /**
     * Suggest the best template category based on the cuota state.
     */
    public function suggestCategoryForCuota(Cuota $cuota, $referenceDate = null): string
    {
        $ref = $referenceDate ? Carbon::parse($referenceDate) : Carbon::today();
        $statusService = app(CuotaStatusService::class);
        $status = $statusService->determineStatus($cuota, $ref);

        // Suggestion rules matching section 12
        if ($status === 'promesa_pago') {
            return 'seguimiento_promesa';
        }
        if ($status === 'promesa_incumplida') {
            return 'seguimiento_promesa';
        }
        if ($status === 'sin_respuesta') {
            return 'sin_respuesta';
        }
        if ($cuota->visitasCobrador()->where('estado', 'pendiente')->exists() || $status === 'visita_cobrador') {
            return 'cobrador';
        }
        if ($status === 'vence_hoy') {
            return 'vence_hoy';
        }
        if ($status === 'vencida') {
            return 'cuota_vencida';
        }
        if ($status === 'proximo_vencimiento') {
            return 'proximo_vencimiento';
        }

        return 'seguimiento_general';
    }

    /**
     * Normalize phone numbers for WhatsApp link generation.
     */
    public function normalizePhoneNumber(string $phone): string
    {
        // Strip everything except numbers
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // Ensure some basic country prefix if missing, but otherwise keep as is
        // For Argentine local numbers, stripping standard leading 15 is sometimes done,
        // but simple non-destructive numerical stripping is safest.
        return $clean;
    }

    /**
     * Generate a WhatsApp click-to-chat URL.
     */
    public function getWhatsappUrl(?string $phone, string $text): string
    {
        if (empty($phone)) {
            return '';
        }
        $normalized = $this->normalizePhoneNumber($phone);
        if (empty($normalized)) {
            return '';
        }
        return "https://wa.me/{$normalized}?text=" . urlencode($text);
    }
}

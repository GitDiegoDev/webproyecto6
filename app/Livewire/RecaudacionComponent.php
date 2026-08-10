<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PeriodoCobranza;
use App\Models\Pago;
use App\Models\Cuota;
use App\Models\PromesaPago;
use App\Models\VisitaCobrador;
use App\Models\User;
use App\Services\CuotaStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class RecaudacionComponent extends Component
{
    // Active Period
    public $activePeriodId = null;

    // Filters for Payments History
    public $filterPeriodo = '';
    public $filterStartDate = '';
    public $filterEndDate = '';
    public $filterClient = '';
    public $filterSolicitud = '';
    public $filterCuota = '';
    public $filterMedioPago = '';
    public $filterUser = '';
    public $filterCobrador = '';

    // Create period form
    public $nuevo_periodo_nombre = '';
    public $nuevo_periodo_mes = '';
    public $nuevo_periodo_anio = '';
    public $nuevo_periodo_objetivo = 1000000.00;

    // View controls
    public $activeTab = 'dashboard'; // 'dashboard', 'historial', 'reportes', 'periodos'

    protected $queryString = [
        'activeTab' => ['except' => 'dashboard'],
        'filterPeriodo' => ['except' => ''],
    ];

    public function mount()
    {
        $this->authorizeAccess();

        $activePeriod = PeriodoCobranza::where('activo', true)->first();
        if ($activePeriod) {
            $this->activePeriodId = $activePeriod->id;
            $this->filterPeriodo = $activePeriod->id;
        } else {
            $latest = PeriodoCobranza::latest()->first();
            if ($latest) {
                $this->activePeriodId = $latest->id;
                $this->filterPeriodo = $latest->id;
            }
        }

        $this->nuevo_periodo_mes = date('n');
        $this->nuevo_periodo_anio = date('Y');
    }

    public function authorizeAccess()
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['administrador', 'gestor'])) {
            abort(403, 'No autorizado para acceder a este módulo.');
        }
    }

    public function getPeriodsProperty()
    {
        return PeriodoCobranza::orderBy('anio', 'desc')->orderBy('mes', 'desc')->get();
    }

    public function getCobradoresProperty()
    {
        return User::where('role', 'cobrador')->get();
    }

    public function getUsersProperty()
    {
        return User::whereIn('role', ['administrador', 'gestor'])->get();
    }

    /**
     * Set active period (by admin)
     */
    public function setActivePeriod($id)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'administrador') {
            session()->flash('error', 'Solo los administradores pueden cambiar el período activo.');
            return;
        }

        DB::transaction(function () use ($id) {
            PeriodoCobranza::query()->update(['activo' => false]);
            PeriodoCobranza::where('id', $id)->update(['activo' => true]);
        });

        $this->activePeriodId = $id;
        $this->filterPeriodo = $id;
        session()->flash('success', 'Período activo actualizado correctamente.');
    }

    /**
     * Close a period and toggle active
     */
    public function closePeriod($id)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'administrador') {
            session()->flash('error', 'Solo los administradores pueden realizar el cierre de período.');
            return;
        }

        $period = PeriodoCobranza::findOrFail($id);
        $period->activo = false;
        $period->save();

        session()->flash('success', "El período {$period->nombre} ha sido cerrado. Todos los registros históricos de cuotas, gestiones, promesas, pagos y visitas fueron completamente preservados.");
    }

    /**
     * Create a new period of collection
     */
    public function crearPeriodo()
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'administrador') {
            session()->flash('error', 'Solo los administradores pueden crear nuevos períodos.');
            return;
        }

        $this->validate([
            'nuevo_periodo_nombre' => 'required|string|max:100',
            'nuevo_periodo_mes' => 'required|integer|between:1,12',
            'nuevo_periodo_anio' => 'required|integer|min:2020|max:2100',
            'nuevo_periodo_objetivo' => 'required|numeric|min:0',
        ]);

        $exist = PeriodoCobranza::where('mes', intval($this->nuevo_periodo_mes))
            ->where('anio', intval($this->nuevo_periodo_anio))
            ->first();

        if ($exist) {
            session()->flash('error', 'Ya existe un período de cobranza para el mes y año seleccionados.');
            return;
        }

        DB::transaction(function () {
            // Deactivate others
            PeriodoCobranza::query()->update(['activo' => false]);

            $periodo = PeriodoCobranza::create([
                'nombre' => $this->nuevo_periodo_nombre,
                'mes' => intval($this->nuevo_periodo_mes),
                'anio' => intval($this->nuevo_periodo_anio),
                'activo' => true,
                'objetivo_monto' => floatval($this->nuevo_periodo_objetivo),
            ]);

            $this->activePeriodId = $periodo->id;
            $this->filterPeriodo = $periodo->id;
        });

        $this->nuevo_periodo_nombre = '';
        session()->flash('success', 'Nuevo período de cobranza creado y establecido como activo.');
    }

    /**
     * Get statistics & indicators for the selected filterPeriodo
     */
    public function getMetricsProperty()
    {
        $periodId = $this->filterPeriodo ?: $this->activePeriodId;
        $period = PeriodoCobranza::find($periodId);

        if (!$period) {
            return [
                'objetivo' => 0.00,
                'recaudado' => 0.00,
                'porcentaje_alcanzado' => 0.00,
                'restante' => 0.00,
                'cantidad_pagos' => 0,
                'cuotas_canceladas' => 0,
                'pagos_parciales' => 0,
                'punitorios_perdonados' => 0,
                'recaudacion_hoy' => 0,
                'recaudacion_mes' => 0,
            ];
        }

        $cuotasQuery = Cuota::whereHas('cuotaImportaciones.importacion', function ($q) use ($period) {
            $q->where('periodo_cobranza_id', $period->id);
        });

        $cuotasIds = $cuotasQuery->pluck('id');

        $pagosQuery = Pago::whereIn('cuota_id', $cuotasIds);

        $objetivo = (float) $period->objetivo_monto;
        $recaudado = (float) $pagosQuery->sum('monto_cobrado');
        $porcentajeAlcanzado = $objetivo > 0 ? round(($recaudado / $objetivo) * 100, 2) : 0;
        $restante = max(0.00, $objetivo - $recaudado);

        $cantidadPagos = $pagosQuery->count();
        $cuotasCanceladas = $pagosQuery->clone()->where('es_cancelatorio', true)->count();
        $pagosParciales = $pagosQuery->clone()->where('es_cancelatorio', false)->count();
        $punitoriosPerdonados = (float) $pagosQuery->sum('punitorios_perdonados');

        $today = Carbon::today()->toDateString();
        $recaudacionHoy = (float) $pagosQuery->clone()->whereDate('fecha_pago', $today)->sum('monto_cobrado');

        return [
            'objetivo' => $objetivo,
            'recaudado' => $recaudado,
            'porcentaje_alcanzado' => $porcentajeAlcanzado,
            'restante' => $restante,
            'cantidad_pagos' => $cantidadPagos,
            'cuotas_canceladas' => $cuotasCanceladas,
            'pagos_parciales' => $pagosParciales,
            'punitorios_perdonados' => $punitoriosPerdonados,
            'recaudacion_hoy' => $recaudacionHoy,
            'recaudacion_mes' => $recaudado, // month total
        ];
    }

    /**
     * Breakdown of collection by payment method and by collector
     */
    public function getBreakdownProperty()
    {
        $periodId = $this->filterPeriodo ?: $this->activePeriodId;
        $period = PeriodoCobranza::find($periodId);

        if (!$period) {
            return [
                'by_method' => [],
                'by_collector' => [],
            ];
        }

        $cuotasIds = Cuota::whereHas('cuotaImportaciones.importacion', function ($q) use ($period) {
            $q->where('periodo_cobranza_id', $period->id);
        })->pluck('id');

        $pagos = Pago::whereIn('cuota_id', $cuotasIds)->get();

        // 1. By Method
        $byMethodRaw = $pagos->groupBy('medio_pago');
        $byMethod = [];
        foreach ($byMethodRaw as $method => $items) {
            $byMethod[] = [
                'medio' => $method,
                'monto_cobrado' => $items->sum('monto_cobrado'),
                'punitorios_snapshot' => $items->sum('punitorios_snapshot'),
                'punitorios_perdonados' => $items->sum('punitorios_perdonados'),
                'importe_original' => $items->sum('importe_original_snapshot'),
                'cantidad' => $items->count(),
            ];
        }

        // 2. By Collector
        // Collection managed by each collector (payments where registered by / visited by)
        // Let's filter visits related to those cuotas that are 'realizada' and calculate
        $byCollector = [];
        $cobradores = User::where('role', 'cobrador')->get();

        foreach ($cobradores as $cobrador) {
            // Get all payments recorded with medio_pago = 'cobrador' where the cuota has a visit of this cobrador,
            // or where the visita registered a payment.
            $visitasRealizadas = VisitaCobrador::where('cobrador_id', $cobrador->id)
                ->where('estado', 'realizada')
                ->whereIn('cuota_id', $cuotasIds)
                ->get();

            $totalMonto = $visitasRealizadas->sum('monto_cobrado');
            $cantVisitasConCobro = $visitasRealizadas->where('monto_cobrado', '>', 0)->count();

            // Also check standard payments where user_id belongs to the cobrador, or where associated with cobrador visits
            $directPayments = Pago::whereIn('cuota_id', $cuotasIds)
                ->where(function ($query) use ($cobrador) {
                    $query->where('user_id', $cobrador->id)
                          ->orWhere('medio_pago', 'cobrador');
                })
                ->get();

            $byCollector[] = [
                'cobrador' => $cobrador->name,
                'monto_cobrado_visitas' => $totalMonto,
                'monto_cobrado_pagos' => $directPayments->sum('monto_cobrado'),
                'cantidad_pagos' => $directPayments->count() ?: $cantVisitasConCobro,
            ];
        }

        return [
            'by_method' => $byMethod,
            'by_collector' => $byCollector,
        ];
    }

    /**
     * Daily collection progress for evolution chart/table
     */
    public function getDailyEvolutionProperty()
    {
        $periodId = $this->filterPeriodo ?: $this->activePeriodId;
        $period = PeriodoCobranza::find($periodId);

        if (!$period) {
            return [];
        }

        $cuotasIds = Cuota::whereHas('cuotaImportaciones.importacion', function ($q) use ($period) {
            $q->where('periodo_cobranza_id', $period->id);
        })->pluck('id');

        $pagos = Pago::whereIn('cuota_id', $cuotasIds)
            ->select(DB::raw('DATE(fecha_pago) as fecha'), DB::raw('SUM(monto_cobrado) as total_dia'))
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get();

        $evolution = [];
        $acumulado = 0.00;
        $objetivo = (float) $period->objetivo_monto;

        foreach ($pagos as $pago) {
            $acumulado += (float) $pago->total_dia;
            $dif = $objetivo - $acumulado;
            $evolution[] = [
                'fecha' => $pago->fecha,
                'dia' => Carbon::parse($pago->fecha)->format('d/m'),
                'monto_dia' => $pago->total_dia,
                'acumulado' => $acumulado,
                'objetivo' => $objetivo,
                'diferencia' => $dif,
            ];
        }

        return $evolution;
    }

    /**
     * Historical payment consultation with advanced filters
     */
    public function getPaymentsProperty()
    {
        $periodId = $this->filterPeriodo ?: $this->activePeriodId;
        $period = PeriodoCobranza::find($periodId);

        $query = Pago::with(['cuota.operacion.cliente', 'user']);

        if ($period) {
            $query->whereHas('cuota.cuotaImportaciones.importacion', function ($q) use ($period) {
                $q->where('periodo_cobranza_id', $period->id);
            });
        }

        if ($this->filterStartDate) {
            $query->whereDate('fecha_pago', '>=', $this->filterStartDate);
        }

        if ($this->filterEndDate) {
            $query->whereDate('fecha_pago', '<=', $this->filterEndDate);
        }

        if ($this->filterClient) {
            $clientSearch = mb_strtolower(trim($this->filterClient));
            $query->whereHas('cuota.operacion.cliente', function ($q) use ($clientSearch) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$clientSearch}%"])
                  ->orWhereRaw('LOWER(apellido) LIKE ?', ["%{$clientSearch}%"])
                  ->orWhereRaw('LOWER(documento) LIKE ?', ["%{$clientSearch}%"]);
            });
        }

        if ($this->filterSolicitud) {
            $query->whereHas('cuota.operacion', function ($q) {
                $q->where('numero_solicitud', 'like', "%{$this->filterSolicitud}%");
            });
        }

        if ($this->filterCuota) {
            $query->whereHas('cuota', function ($q) {
                $q->where('numero_cuota', $this->filterCuota);
            });
        }

        if ($this->filterMedioPago) {
            $query->where('medio_pago', $this->filterMedioPago);
        }

        if ($this->filterUser) {
            $query->where('user_id', $this->filterUser);
        }

        if ($this->filterCobrador) {
            // Checks if there's a visit for the cuota with this cobrador
            $query->whereHas('cuota.visitasCobrador', function ($q) {
                $q->where('cobrador_id', $this->filterCobrador);
            });
        }

        return $query->orderBy('fecha_pago', 'desc')->get();
    }

    /**
     * Detailed period status report metrics
     */
    public function getReportMetricsProperty()
    {
        $periodId = $this->filterPeriodo ?: $this->activePeriodId;
        $period = PeriodoCobranza::find($periodId);

        if (!$period) {
            return [];
        }

        $cuotasQuery = Cuota::whereHas('cuotaImportaciones.importacion', function ($q) use ($period) {
            $q->where('periodo_cobranza_id', $period->id);
        })->with(['operacion.cliente', 'promesasPago', 'visitasCobrador', 'pagos']);

        $allCuotas = $cuotasQuery->get();
        $statusService = new CuotaStatusService();
        $ref = Carbon::today();

        $cuotasPagadasCount = 0;
        $cuotasPendientesCount = 0;
        $cuotasVencidasCount = 0;

        foreach ($allCuotas as $cuota) {
            if ($cuota->saldo_pendiente == 0) {
                $cuotasPagadasCount++;
            } else {
                $cuotasPendientesCount++;
                $status = $statusService->determineStatus($cuota, $ref);
                if ($status === 'vencida') {
                    $cuotasVencidasCount++;
                }
            }
        }

        $cuotasIds = $allCuotas->pluck('id');

        $promesas = PromesaPago::whereIn('cuota_id', $cuotasIds)->get();
        $promesasCount = $promesas->count();
        $promesasCumplidasCount = $promesas->where('estado', 'cumplida')->count();
        $promesasIncumplidasCount = $promesas->where('estado', 'incumplida')->count();

        $sinRespuestaCount = $allCuotas->filter(function ($cuota) use ($statusService, $ref) {
            return $statusService->determineStatus($cuota, $ref) === 'sin_respuesta';
        })->count();

        $visitas = VisitaCobrador::whereIn('cuota_id', $cuotasIds)->get();
        $visitasRealizadasCount = $visitas->where('estado', 'realizada')->count();
        $visitasPendientesCount = $visitas->where('estado', 'pendiente')->count();

        $pagos = Pago::whereIn('cuota_id', $cuotasIds)->get();
        $pagosCount = $pagos->count();
        $recaudacion = $pagos->sum('monto_cobrado');
        $punitoriosPerdonados = $pagos->sum('punitorios_perdonados');

        return [
            'nombre_periodo' => $period->nombre,
            'cuotas_pagadas' => $cuotasPagadasCount,
            'cuotas_pendientes' => $cuotasPendientesCount,
            'cuotas_vencidas' => $cuotasVencidasCount,
            'promesas' => $promesasCount,
            'promesas_cumplidas' => $promesasCumplidasCount,
            'promesas_incumplidas' => $promesasIncumplidasCount,
            'sin_respuesta' => $sinRespuestaCount,
            'visitas_realizadas' => $visitasRealizadasCount,
            'visitas_pendientes' => $visitasPendientesCount,
            'pagos_realizados' => $pagosCount,
            'recaudacion' => $recaudacion,
            'punitorios_perdonados' => $punitoriosPerdonados,
        ];
    }

    /**
     * CSV Export of general period reports
     */
    public function exportReportCsv()
    {
        $this->authorizeAccess();

        $metrics = $this->reportMetrics;
        if (empty($metrics)) {
            session()->flash('error', 'No hay datos de reportes para exportar.');
            return;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->setCellValue('A1', 'REPORTE DE COBRANZA - PERIODO ' . $metrics['nombre_periodo']);
        $sheet->setCellValue('A2', 'Generado el: ' . now()->format('d/m/Y H:i:s'));

        // Metric Headers & Values
        $data = [
            ['Métrica / Indicador', 'Valor / Cantidad'],
            ['Cuotas Pagadas', $metrics['cuotas_pagadas']],
            ['Cuotas Pendientes', $metrics['cuotas_pendientes']],
            ['Cuotas Vencidas', $metrics['cuotas_vencidas']],
            ['Total Promesas de Pago', $metrics['promesas']],
            ['Promesas Cumplidas', $metrics['promesas_cumplidas']],
            ['Promesas Incumplidas', $metrics['promesas_incumplidas']],
            ['Clientes Sin Respuesta', $metrics['sin_respuesta']],
            ['Visitas Realizadas por Cobradores', $metrics['visitas_realizadas']],
            ['Visitas Pendientes', $metrics['visitas_pendientes']],
            ['Pagos Totales Registrados', $metrics['pagos_realizados']],
            ['Monto Total Recaudado ($)', number_format($metrics['recaudacion'], 2, '.', '')],
            ['Total Punitorios Perdonados ($)', number_format($metrics['punitorios_perdonados'], 2, '.', '')],
        ];

        $rowNum = 4;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowNum, $row[0]);
            $sheet->setCellValue('B' . $rowNum, $row[1]);
            $rowNum++;
        }

        // Output to CSV
        $writer = new Csv($spreadsheet);

        $fileName = 'Reporte_Cobranza_' . str_replace(' ', '_', $metrics['nombre_periodo']) . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function render()
    {
        return view('livewire.recaudacion-component');
    }
}

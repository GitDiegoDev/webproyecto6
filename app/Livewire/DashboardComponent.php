<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PeriodoCobranza;
use App\Models\Cuota;
use App\Models\PromesaPago;
use App\Models\VisitaCobrador;
use App\Models\Pago;
use App\Models\User;
use App\Services\CuotaStatusService;
use App\Services\CuotaPriorityService;
use App\Services\GestionService;
use App\Services\PromesaPagoService;
use App\Services\VisitaCobradorService;
use App\Services\PagoService;
use Carbon\Carbon;

class DashboardComponent extends Component
{
    // Search & Filter
    public $search = '';
    public $filter = 'todas';

    // Reference date (defaults to today)
    public $referenceDate = null;

    // Active Period
    public $activePeriod = null;

    // Modal states
    public $activeModal = null; // 'gestion', 'promesa', 'cobrador', 'pago', 'visita_resultado'
    public $selectedCuotaId = null;
    public $selectedVisitaId = null;

    // Form inputs: Gestion
    public $gestion_tipo = 'llamada';
    public $gestion_resultado = 'contactado';
    public $gestion_observacion = '';
    public $gestion_proxima_accion = '';
    public $gestion_proxima_accion_fecha = null;

    // Form inputs: Promesa
    public $promesa_fecha_prometida = '';
    public $promesa_monto_prometido = '';
    public $promesa_observaciones = '';

    // Form inputs: Cobrador
    public $cobrador_id = '';
    public $cobrador_fecha_programada = '';
    public $cobrador_observaciones = '';

    // Form inputs: Pago
    public $pago_monto_cobrado = '';
    public $pago_punitorios_perdonados = 0.00;
    public $pago_medio_pago = 'transferencia';
    public $pago_observaciones = '';

    // Form inputs: Visita resultado
    public $visita_resultado_tipo = 'cobrado';
    public $visita_monto_cobrado = 0.00;
    public $visita_observaciones = '';

    public function mount()
    {
        $this->activePeriod = PeriodoCobranza::where('activo', true)->first();
        if (!$this->referenceDate) {
            $this->referenceDate = Carbon::today()->toDateString();
        }
    }

    /**
     * Refreshes dashboard data.
     */
    public function refreshData()
    {
        $this->activePeriod = PeriodoCobranza::where('activo', true)->first();
    }

    /**
     * Authorization check based on role.
     */
    public function authorizeAction($action)
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'No autorizado.');
        }

        if ($user->role === 'administrador') {
            return true;
        }

        if ($user->role === 'gestor') {
            if (in_array($action, ['registrar_resultado_visita', 'registrar_gestion', 'registrar_promesa', 'programar_cobrador', 'registrar_pago'])) {
                return true;
            }
        }

        if ($user->role === 'cobrador') {
            if ($action === 'registrar_resultado_visita') {
                return true;
            }
        }

        abort(403, 'No autorizado para esta acción.');
    }

    // Modal Opening & Reset logic

    public function openGestionModal($cuotaId)
    {
        $this->authorizeAction('registrar_gestion');
        $this->selectedCuotaId = $cuotaId;
        $this->gestion_tipo = 'llamada';
        $this->gestion_resultado = 'contactado';
        $this->gestion_observacion = '';
        $this->gestion_proxima_accion = '';
        $this->gestion_proxima_accion_fecha = null;
        $this->activeModal = 'gestion';
    }

    public function openPromesaModal($cuotaId)
    {
        $this->authorizeAction('registrar_promesa');
        $this->selectedCuotaId = $cuotaId;
        $cuota = Cuota::findOrFail($cuotaId);
        $this->promesa_fecha_prometida = Carbon::tomorrow()->toDateString();
        $this->promesa_monto_prometido = $cuota->saldo_pendiente;
        $this->promesa_observaciones = '';
        $this->activeModal = 'promesa';
    }

    public function openCobradorModal($cuotaId)
    {
        $this->authorizeAction('programar_cobrador');
        $this->selectedCuotaId = $cuotaId;
        $this->cobrador_id = User::where('role', 'cobrador')->first()?->id ?? '';
        $this->cobrador_fecha_programada = Carbon::tomorrow()->toDateString();
        $this->cobrador_observaciones = '';
        $this->activeModal = 'cobrador';
    }

    public function openPagoModal($cuotaId)
    {
        $this->authorizeAction('registrar_pago');
        $this->selectedCuotaId = $cuotaId;
        $cuota = Cuota::findOrFail($cuotaId);
        $this->pago_monto_cobrado = $cuota->saldo_pendiente;
        $this->pago_punitorios_perdonados = 0.00;
        $this->pago_medio_pago = 'transferencia';
        $this->pago_observaciones = '';
        $this->activeModal = 'pago';
    }

    public function openVisitaResultadoModal($visitaId)
    {
        $this->authorizeAction('registrar_resultado_visita');
        $this->selectedVisitaId = $visitaId;
        $visita = VisitaCobrador::findOrFail($visitaId);
        $this->visita_resultado_tipo = 'cobrado';
        $this->visita_monto_cobrado = $visita->cuota ? $visita->cuota->saldo_pendiente : 0.00;
        $this->visita_observaciones = '';
        $this->activeModal = 'visita_resultado';
    }

    public function closeModal()
    {
        $this->activeModal = null;
        $this->selectedCuotaId = null;
        $this->selectedVisitaId = null;
        $this->resetErrorBag();
    }

    // Submission Logic

    public function submitGestion()
    {
        $this->authorizeAction('registrar_gestion');
        $this->validate([
            'gestion_tipo' => 'required|string',
            'gestion_resultado' => 'required|string',
            'gestion_observacion' => 'nullable|string',
            'gestion_proxima_accion' => 'nullable|string',
            'gestion_proxima_accion_fecha' => 'nullable|date',
        ]);

        $service = app(GestionService::class);
        $service->registrarGestion([
            'cuota_id' => $this->selectedCuotaId,
            'user_id' => auth()->id() ?? 1,
            'tipo' => $this->gestion_tipo,
            'resultado' => $this->gestion_resultado,
            'observacion' => $this->gestion_observacion,
            'proxima_accion' => $this->gestion_proxima_accion ?: null,
            'proxima_accion_fecha' => $this->gestion_proxima_accion_fecha ?: null,
        ]);

        $this->closeModal();
        session()->flash('success', 'Gestión registrada correctamente.');
    }

    public function submitPromesa()
    {
        $this->authorizeAction('registrar_promesa');
        $this->validate([
            'promesa_fecha_prometida' => 'required|date|after_or_equal:today',
            'promesa_monto_prometido' => 'required|numeric|min:0.01',
            'promesa_observaciones' => 'nullable|string',
        ]);

        $service = app(PromesaPagoService::class);
        $service->crearPromesa([
            'cuota_id' => $this->selectedCuotaId,
            'user_id' => auth()->id() ?? 1,
            'fecha_prometida' => $this->promesa_fecha_prometida,
            'monto_prometido' => $this->promesa_monto_prometido,
            'observaciones' => $this->promesa_observaciones,
        ]);

        $this->closeModal();
        session()->flash('success', 'Promesa de pago registrada correctamente.');
    }

    public function submitCobrador()
    {
        $this->authorizeAction('programar_cobrador');
        $this->validate([
            'cobrador_id' => 'required|exists:users,id',
            'cobrador_fecha_programada' => 'required|date|after_or_equal:today',
            'cobrador_observaciones' => 'nullable|string',
        ]);

        $cuota = Cuota::findOrFail($this->selectedCuotaId);

        $service = app(VisitaCobradorService::class);
        $service->programarVisita([
            'cliente_id' => $cuota->operacion->cliente_id,
            'cuota_id' => $cuota->id,
            'cobrador_id' => $this->cobrador_id,
            'fecha_programada' => $this->cobrador_fecha_programada,
            'observaciones' => $this->cobrador_observaciones,
        ]);

        $this->closeModal();
        session()->flash('success', 'Visita de cobrador programada correctamente.');
    }

    public function submitPago()
    {
        $this->authorizeAction('registrar_pago');
        $this->validate([
            'pago_monto_cobrado' => 'required|numeric|min:0.01',
            'pago_punitorios_perdonados' => 'required|numeric|min:0',
            'pago_medio_pago' => 'required|string',
            'pago_observaciones' => 'nullable|string',
        ]);

        $service = app(PagoService::class);
        $service->registrarPago([
            'cuota_id' => $this->selectedCuotaId,
            'user_id' => auth()->id() ?? 1,
            'monto_cobrado' => $this->pago_monto_cobrado,
            'punitorios_perdonados' => $this->pago_punitorios_perdonados,
            'medio_pago' => $this->pago_medio_pago,
            'observaciones' => $this->pago_observaciones,
        ]);

        $this->closeModal();
        session()->flash('success', 'Pago registrado correctamente.');
    }

    public function submitVisitaResultado()
    {
        $this->authorizeAction('registrar_resultado_visita');
        $this->validate([
            'visita_resultado_tipo' => 'required|string',
            'visita_monto_cobrado' => 'required|numeric|min:0',
            'visita_observaciones' => 'nullable|string',
        ]);

        $service = app(VisitaCobradorService::class);
        $service->registrarResultadoVisita($this->selectedVisitaId, [
            'resultado' => $this->visita_resultado_tipo,
            'monto_cobrado' => $this->visita_monto_cobrado,
            'observaciones' => $this->visita_observaciones,
        ]);

        $this->closeModal();
        session()->flash('success', 'Resultado de visita registrado correctamente.');
    }

    /**
     * Compute statistics and metrics based on active period & reference date.
     */
    public function getMetricsProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        $refStr = $ref->toDateString();

        if (!$this->activePeriod) {
            return [
                'objetivo' => 0.00,
                'total_potencial_pendiente' => 0.00,
                'recaudado' => 0.00,
                'punitorios_perdonados' => 0.00,
                'cumplimiento_porcentaje' => 0.00,
                'cuotas_pendientes' => 0,
                'vencidas' => 0,
                'promesas_hoy' => 0,
                'promesas_incumplidas' => 0,
                'sin_respuesta' => 0,
                'visitas_hoy' => 0,
                'visitas_pendientes' => 0,
                'visitas_realizadas' => 0,
            ];
        }

        $cuotasQuery = Cuota::whereHas('operacion.cliente')
            ->whereHas('cuotaImportaciones.importacion', function ($q) {
                $q->where('periodo_cobranza_id', $this->activePeriod->id);
            });

        $totalPotencialPendiente = (float) $cuotasQuery->sum('saldo_pendiente');
        $recaudado = (float) Pago::whereIn('cuota_id', $cuotasQuery->pluck('id'))->sum('monto_cobrado');
        $punitoriosPerdonados = (float) Pago::whereIn('cuota_id', $cuotasQuery->pluck('id'))->sum('punitorios_perdonados');

        $objetivo = (float) $this->activePeriod->objetivo_monto;
        $cumplimientoPorcentaje = 0.00;
        if ($objetivo > 0) {
            $cumplimientoPorcentaje = round(($recaudado / $objetivo) * 100, 2);
        }

        $cuotasPendientesCount = $cuotasQuery->clone()->where('saldo_pendiente', '>', 0)->count();

        $statusService = new CuotaStatusService();
        $vencidasCount = 0;
        $sinRespuestaCount = 0;

        $allCuotas = $cuotasQuery->clone()->get();
        foreach ($allCuotas as $cuota) {
            $status = $statusService->determineStatus($cuota, $ref);
            if ($status === 'vencida') {
                $vencidasCount++;
            } elseif ($status === 'sin_respuesta') {
                $sinRespuestaCount++;
            }
        }

        $promesasHoyCount = PromesaPago::where('estado', 'pendiente')
            ->whereDate('fecha_prometida', '=', $refStr)
            ->whereIn('cuota_id', $cuotasQuery->pluck('id'))
            ->count();

        $promesasIncumplidasCount = PromesaPago::where('estado', 'incumplida')
            ->whereIn('cuota_id', $cuotasQuery->pluck('id'))
            ->count();

        $visitasHoyCount = VisitaCobrador::whereDate('fecha_programada', '=', $refStr)
            ->whereIn('cuota_id', $cuotasQuery->pluck('id'))
            ->count();

        $visitasPendientesCount = VisitaCobrador::where('estado', 'pendiente')
            ->whereIn('cuota_id', $cuotasQuery->pluck('id'))
            ->count();

        $visitasRealizadasCount = VisitaCobrador::where('estado', 'realizada')
            ->whereIn('cuota_id', $cuotasQuery->pluck('id'))
            ->count();

        return [
            'objetivo' => $objetivo,
            'total_potencial_pendiente' => $totalPotencialPendiente,
            'recaudado' => $recaudado,
            'punitorios_perdonados' => $punitoriosPerdonados,
            'cumplimiento_porcentaje' => $cumplimientoPorcentaje,
            'cuotas_pendientes' => $cuotasPendientesCount,
            'vencidas' => $vencidasCount,
            'promesas_hoy' => $promesasHoyCount,
            'promesas_incumplidas' => $promesasIncumplidasCount,
            'sin_respuesta' => $sinRespuestaCount,
            'visitas_hoy' => $visitasHoyCount,
            'visitas_pendientes' => $visitasPendientesCount,
            'visitas_realizadas' => $visitasRealizadasCount,
        ];
    }

    /**
     * Get the list of Cuotas based on search, filter, referenceDate, and activePeriod.
     */
    public function getCuotasProperty()
    {
        if (!$this->activePeriod) {
            return collect();
        }

        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        $refStr = $ref->toDateString();

        $cuotasQuery = Cuota::whereHas('operacion.cliente')
            ->whereHas('cuotaImportaciones.importacion', function ($q) {
                $q->where('periodo_cobranza_id', $this->activePeriod->id);
            })
            ->with([
                'operacion.cliente',
                'gestiones',
                'promesasPago',
                'visitasCobrador',
                'pagos'
            ]);

        // Reactive global search
        if (!empty($this->search)) {
            $searchLower = mb_strtolower(trim($this->search));
            $cuotasQuery->where(function ($q) use ($searchLower) {
                $q->whereHas('operacion.cliente', function ($sq) use ($searchLower) {
                    $sq->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchLower}%"])
                       ->orWhereRaw('LOWER(apellido) LIKE ?', ["%{$searchLower}%"])
                       ->orWhereRaw('LOWER(documento) LIKE ?', ["%{$searchLower}%"])
                       ->orWhereRaw('LOWER(telefono) LIKE ?', ["%{$searchLower}%"]);
                })
                ->orWhereHas('operacion', function ($sq) use ($searchLower) {
                    $sq->whereRaw('LOWER(numero_solicitud) LIKE ?', ["%{$searchLower}%"]);
                });
            });
        }

        $statusService = new CuotaStatusService();
        $priorityService = new CuotaPriorityService();

        // Map and compute dynamic status/priority
        $cuotas = $cuotasQuery->get()->map(function ($cuota) use ($statusService, $priorityService, $ref) {
            // Recalculate priority first so it is persisted cleanly
            $prio = $priorityService->recalculatePriority($cuota, $ref);
            // Then calculate status and assign both as virtual attributes
            $cuota->computed_priority = $prio;
            $cuota->computed_status = $statusService->determineStatus($cuota, $ref);
            return $cuota;
        });

        // Filter the collection based on selection
        $cuotas = $cuotas->filter(function ($cuota) use ($ref, $refStr) {
            switch ($this->filter) {
                case 'criticas':
                    return $cuota->computed_priority === 'critica';
                case 'altas':
                    return $cuota->computed_priority === 'alta';
                case 'medias':
                    return $cuota->computed_priority === 'media';
                case 'bajas':
                    return $cuota->computed_priority === 'baja';
                case 'vencidas':
                    return $cuota->computed_status === 'vencida';
                case 'vence_hoy':
                    return $cuota->computed_status === 'vence_hoy';
                case 'promesa_hoy':
                    return $cuota->promesasPago()
                        ->where('estado', 'pendiente')
                        ->whereDate('fecha_prometida', '=', $refStr)
                        ->exists();
                case 'promesa_incumplida':
                    return $cuota->computed_status === 'promesa_incumplida' ||
                           $cuota->promesasPago()->where('estado', 'incumplida')->exists();
                case 'sin_respuesta':
                    return $cuota->computed_status === 'sin_respuesta';
                case 'con_visita':
                    return $cuota->visitasCobrador()->where('estado', 'pendiente')->exists();
                default:
                    return true;
            }
        });

        // Sort based on prompt specification 14
        $cuotas = $cuotas->sort(function ($a, $b) {
            $prioMap = ['critica' => 4, 'alta' => 3, 'media' => 2, 'baja' => 1];
            $prioA = $prioMap[$a->computed_priority] ?? 1;
            $prioB = $prioMap[$b->computed_priority] ?? 1;

            if ($prioA !== $prioB) {
                return $prioB <=> $prioA; // Descending
            }

            $hasBreachedA = $a->promesasPago()->where('estado', 'incumplida')->exists() || $a->computed_status === 'promesa_incumplida' ? 1 : 0;
            $hasBreachedB = $b->promesasPago()->where('estado', 'incumplida')->exists() || $b->computed_status === 'promesa_incumplida' ? 1 : 0;
            if ($hasBreachedA !== $hasBreachedB) {
                return $hasBreachedB <=> $hasBreachedA;
            }

            $diaA = (int) $a->dia_cobro;
            $diaB = (int) $b->dia_cobro;
            if ($diaA !== $diaB) {
                return $diaA <=> $diaB;
            }

            $visA = $a->visitasCobrador()->where('estado', 'pendiente')->exists() ? 1 : 0;
            $visB = $b->visitasCobrador()->where('estado', 'pendiente')->exists() ? 1 : 0;
            if ($visA !== $visB) {
                return $visB <=> $visA;
            }

            return $a->id <=> $b->id;
        });

        return $cuotas;
    }

    public function getPromesasParaHoyProperty()
    {
        if (!$this->activePeriod) return collect();
        $refStr = $this->referenceDate ? Carbon::parse($this->referenceDate)->toDateString() : Carbon::today()->toDateString();
        return PromesaPago::where('estado', 'pendiente')
            ->whereDate('fecha_prometida', '=', $refStr)
            ->whereHas('cuota.cuotaImportaciones.importacion', function ($q) {
                $q->where('periodo_cobranza_id', $this->activePeriod->id);
            })
            ->with(['cuota.operacion.cliente'])
            ->get();
    }

    public function getPromesasIncumplidasProperty()
    {
        if (!$this->activePeriod) return collect();
        return PromesaPago::where('estado', 'incumplida')
            ->whereHas('cuota.cuotaImportaciones.importacion', function ($q) {
                $q->where('periodo_cobranza_id', $this->activePeriod->id);
            })
            ->with(['cuota.operacion.cliente'])
            ->get();
    }

    public function getSinRespuestaProperty()
    {
        if (!$this->activePeriod) return collect();
        $statusService = new CuotaStatusService();
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        return Cuota::whereHas('operacion.cliente')
            ->whereHas('cuotaImportaciones.importacion', function ($q) {
                $q->where('periodo_cobranza_id', $this->activePeriod->id);
            })
            ->with(['operacion.cliente', 'gestiones'])
            ->get()
            ->filter(function ($cuota) use ($statusService, $ref) {
                return $statusService->determineStatus($cuota, $ref) === 'sin_respuesta';
            });
    }

    public function getVisitasDelDiaProperty()
    {
        if (!$this->activePeriod) return collect();
        $refStr = $this->referenceDate ? Carbon::parse($this->referenceDate)->toDateString() : Carbon::today()->toDateString();
        return VisitaCobrador::whereDate('fecha_programada', '=', $refStr)
            ->whereHas('cuota.cuotaImportaciones.importacion', function ($q) {
                $q->where('periodo_cobranza_id', $this->activePeriod->id);
            })
            ->with(['cliente', 'cuota.operacion'])
            ->get();
    }

    public function getCobradoresProperty()
    {
        return User::where('role', 'cobrador')->get();
    }

    public function render()
    {
        return view('livewire.dashboard-component');
    }
}

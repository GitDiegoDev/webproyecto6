<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cuota;
use App\Models\PromesaPago;
use App\Models\VisitaCobrador;
use App\Models\Gestion;
use App\Models\Pago;
use App\Models\User;
use App\Services\AgendaCobranzaService;
use App\Services\CuotaStatusService;
use App\Services\CuotaPriorityService;
use App\Services\GestionService;
use App\Services\PromesaPagoService;
use App\Services\VisitaCobradorService;
use App\Services\PagoService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AgendaCobranzaComponent extends Component
{
    // Toggle HOY vs PRÓXIMOS DÍAS
    public $viewMode = 'hoy'; // 'hoy' or 'proximos_dias'

    // Filtering & Search
    public $search = '';
    public $filter = 'todos'; // 'todos', 'criticos', 'alta', 'media', 'baja', 'vencidos', 'vence_hoy', 'promesas_hoy', 'promesas_incumplidas', 'sin_respuesta', 'con_visita', 'proximas_acciones'

    // Reference Date (defaults to today)
    public $referenceDate = null;

    // Modal state: 'gestion', 'promesa', 'cobrador', 'pago', 'visita_resultado'
    public $activeModal = null;
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
    public $visita_fecha_realizada = '';

    public function mount()
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['administrador', 'gestor', 'cobrador'])) {
            abort(403, 'No autorizado.');
        }

        if (!$this->referenceDate) {
            $this->referenceDate = Carbon::today()->toDateString();
        }

        // Initialize nullable date fields to null
        $this->gestion_proxima_accion_fecha = null;
        $this->visita_fecha_realizada = null;
    }

    /**
     * Authorization checks matching role permissions.
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

    // Modal Triggers

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
        $this->visita_fecha_realizada = Carbon::today()->toDateString();
        $this->activeModal = 'visita_resultado';
    }

    public function closeModal()
    {
        $this->activeModal = null;
        $this->selectedCuotaId = null;
        $this->selectedVisitaId = null;
        $this->resetErrorBag();
    }

    // Modal Submissions

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
            'cobrador_fecha_programada' => 'required|date',
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
            'visita_fecha_realizada' => 'required|date',
        ]);

        $service = app(VisitaCobradorService::class);
        $service->registrarResultadoVisita($this->selectedVisitaId, [
            'resultado' => $this->visita_resultado_tipo,
            'monto_cobrado' => $this->visita_monto_cobrado,
            'observaciones' => $this->visita_observaciones,
            'fecha_realizada' => $this->visita_fecha_realizada ?: now(),
        ]);

        $this->closeModal();
        session()->flash('success', 'Resultado de visita registrado correctamente.');
    }

    /**
     * Compute KPI metrics for the header dynamically.
     */
    public function getMetricsProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);

        $hoy = $agendaData['hoy'];

        // KPIs matching the prompt
        // - Total de gestiones pendientes (total cuotas in "Gestionar Hoy" list)
        $gestionarHoyCuotas = $this->getGestionarHoyCuotas($ref);
        $totalGestionesPendientes = $gestionarHoyCuotas->count();

        // - Promesas para hoy
        $promesasHoy = $hoy['promesas_hoy']->count();

        // - Promesas incumplidas
        $promesasIncumplidas = $hoy['promesas_incumplidas']->count();

        // - Cuotas vencidas
        $cuotasVencidas = $hoy['cuotas_vencidas']->count();

        // - Cuotas que vencen hoy
        $cuotasVencenHoy = $hoy['cuotas_vencen_hoy']->count();

        // - Clientes sin respuesta
        $clientesSinRespuesta = $hoy['clientes_sin_respuesta']->count();

        // - Visitas de cobrador para hoy
        $visitasHoy = $hoy['visitas_hoy']->count();

        return [
            'total_gestiones_pendientes' => $totalGestionesPendientes,
            'promesas_hoy' => $promesasHoy,
            'promesas_incumplidas' => $promesasIncumplidas,
            'cuotas_vencidas' => $cuotasVencidas,
            'cuotas_vencen_hoy' => $cuotasVencenHoy,
            'clientes_sin_respuesta' => $clientesSinRespuesta,
            'visitas_hoy' => $visitasHoy,
        ];
    }

    /**
     * Build unified collection of Cuota models for the main "Gestionar Hoy" list.
     */
    protected function getGestionarHoyCuotas($ref)
    {
        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $hoy = $agendaData['hoy'];

        $cuotas = collect();

        // Accumulate cuotas from all today's action categories
        foreach ($hoy['promesas_hoy'] as $promesa) {
            if ($promesa->cuota) {
                $cuotas->put($promesa->cuota_id, $promesa->cuota);
            }
        }
        foreach ($hoy['promesas_incumplidas'] as $promesa) {
            if ($promesa->cuota) {
                $cuotas->put($promesa->cuota_id, $promesa->cuota);
            }
        }
        foreach ($hoy['visitas_hoy'] as $visita) {
            if ($visita->cuota) {
                $cuotas->put($visita->cuota_id, $visita->cuota);
            }
        }
        foreach ($hoy['seguimientos_hoy'] as $cuota) {
            $cuotas->put($cuota->id, $cuota);
        }
        foreach ($hoy['cuotas_vencen_hoy'] as $cuota) {
            $cuotas->put($cuota->id, $cuota);
        }
        foreach ($hoy['cuotas_vencidas'] as $cuota) {
            $cuotas->put($cuota->id, $cuota);
        }
        foreach ($hoy['clientes_sin_respuesta'] as $cuota) {
            $cuotas->put($cuota->id, $cuota);
        }
        foreach ($hoy['cuotas_requieren_atencion'] as $cuota) {
            $cuotas->put($cuota->id, $cuota);
        }

        // If the user is a cobrador, restrict to cuotas with visits assigned to them
        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $cuotas = $cuotas->filter(function ($cuota) use ($user) {
                return $cuota->visitasCobrador()
                    ->where('estado', 'pendiente')
                    ->where('cobrador_id', $user->id)
                    ->exists();
            });
        }

        // Recalculate computed attributes (status and priority) on the fly
        $statusService = new CuotaStatusService();
        $priorityService = new CuotaPriorityService();

        $cuotas = $cuotas->map(function ($cuota) use ($statusService, $priorityService, $ref) {
            $cuota->computed_priority = $priorityService->recalculatePriority($cuota, $ref);
            $cuota->computed_status = $statusService->determineStatus($cuota, $ref);
            return $cuota;
        });

        return $cuotas;
    }

    /**
     * Filter and search on a collection of cuotas dynamically.
     */
    protected function filterAndSearchCuotas($cuotasCollection, $ref)
    {
        $refStr = $ref->toDateString();

        // Filter
        if ($this->filter !== 'todos') {
            $cuotasCollection = $cuotasCollection->filter(function ($cuota) use ($refStr) {
                switch ($this->filter) {
                    case 'criticos':
                        return $cuota->computed_priority === 'critica';
                    case 'alta':
                        return $cuota->computed_priority === 'alta';
                    case 'media':
                        return $cuota->computed_priority === 'media';
                    case 'baja':
                        return $cuota->computed_priority === 'baja';
                    case 'vencidos':
                        return $cuota->computed_status === 'vencida';
                    case 'vence_hoy':
                        return $cuota->computed_status === 'vence_hoy';
                    case 'promesas_hoy':
                        return $cuota->promesasPago()
                            ->where('estado', 'pendiente')
                            ->whereDate('fecha_prometida', '=', $refStr)
                            ->exists();
                    case 'promesas_incumplidas':
                        return $cuota->computed_status === 'promesa_incumplida' ||
                               $cuota->promesasPago()->where('estado', 'incumplida')->exists();
                    case 'sin_respuesta':
                        return $cuota->computed_status === 'sin_respuesta';
                    case 'con_visita':
                        return $cuota->visitasCobrador()->where('estado', 'pendiente')->exists();
                    case 'proximas_acciones':
                        return $cuota->gestiones()->whereNotNull('proxima_accion_fecha')->exists();
                    default:
                        return true;
                }
            });
        }

        // Search (Reactive global search)
        if (!empty($this->search)) {
            $searchLower = mb_strtolower(trim($this->search));
            $cuotasCollection = $cuotasCollection->filter(function ($cuota) use ($searchLower) {
                $cliente = $cuota->operacion?->cliente;
                $nombreCompleto = $cliente ? mb_strtolower($cliente->nombre . ' ' . $cliente->apellido) : '';
                $nombre = $cliente ? mb_strtolower($cliente->nombre) : '';
                $apellido = $cliente ? mb_strtolower($cliente->apellido) : '';
                $solicitud = mb_strtolower($cuota->operacion?->numero_solicitud ?? '');
                $telefono = mb_strtolower($cliente?->telefono ?? '');
                $documento = mb_strtolower($cliente?->documento ?? '');
                $nroCuota = (string) $cuota->numero_cuota;

                return str_contains($nombreCompleto, $searchLower) ||
                       str_contains($nombre, $searchLower) ||
                       str_contains($apellido, $searchLower) ||
                       str_contains($solicitud, $searchLower) ||
                       str_contains($telefono, $searchLower) ||
                       str_contains($documento, $searchLower) ||
                       $nroCuota === $searchLower;
            });
        }

        // Sort based on Priority Rules
        $cuotasCollection = $cuotasCollection->sort(function ($a, $b) {
            $prioMap = ['critica' => 4, 'alta' => 3, 'media' => 2, 'baja' => 1];
            $prioA = $prioMap[$a->computed_priority] ?? 1;
            $prioB = $prioMap[$b->computed_priority] ?? 1;

            if ($prioA !== $prioB) {
                return $prioB <=> $prioA; // Descending priority
            }

            // Secondary: Breached Promise
            $hasBreachedA = $a->promesasPago()->where('estado', 'incumplida')->exists() || $a->computed_status === 'promesa_incumplida' ? 1 : 0;
            $hasBreachedB = $b->promesasPago()->where('estado', 'incumplida')->exists() || $b->computed_status === 'promesa_incumplida' ? 1 : 0;
            if ($hasBreachedA !== $hasBreachedB) {
                return $hasBreachedB <=> $hasBreachedA;
            }

            // Secondary: Day of Cobro (older due date first)
            $diaA = (int) $a->dia_cobro;
            $diaB = (int) $b->dia_cobro;
            if ($diaA !== $diaB) {
                return $diaA <=> $diaB;
            }

            // Secondary: Pending Visita
            $visA = $a->visitasCobrador()->where('estado', 'pendiente')->exists() ? 1 : 0;
            $visB = $b->visitasCobrador()->where('estado', 'pendiente')->exists() ? 1 : 0;
            if ($visA !== $visB) {
                return $visB <=> $visA;
            }

            return $a->id <=> $b->id;
        });

        return $cuotasCollection;
    }

    // Property getters

    public function getGestionarHoyProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        $cuotas = $this->getGestionarHoyCuotas($ref);
        return $this->filterAndSearchCuotas($cuotas, $ref);
    }

    public function getPromesasParaHoyProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        $refStr = $ref->toDateString();

        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $promesas = $agendaData['hoy']['promesas_hoy'];

        // If cobrador role, restrict to their visits/assigned cuotas
        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $promesas = $promesas->filter(function ($p) use ($user) {
                return $p->cuota && $p->cuota->visitasCobrador()
                    ->where('estado', 'pendiente')
                    ->where('cobrador_id', $user->id)
                    ->exists();
            });
        }

        // Apply filters & search if any
        if (!empty($this->search)) {
            $searchLower = mb_strtolower(trim($this->search));
            $promesas = $promesas->filter(function ($p) use ($searchLower) {
                $cliente = $p->cuota?->operacion?->cliente;
                $nombreCompleto = $cliente ? mb_strtolower($cliente->nombre . ' ' . $cliente->apellido) : '';
                return str_contains($nombreCompleto, $searchLower) ||
                       str_contains(mb_strtolower($p->cuota?->operacion?->numero_solicitud ?? ''), $searchLower);
            });
        }

        return $promesas;
    }

    public function getPromesasIncumplidasProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();

        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $promesas = $agendaData['hoy']['promesas_incumplidas'];

        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $promesas = $promesas->filter(function ($p) use ($user) {
                return $p->cuota && $p->cuota->visitasCobrador()
                    ->where('estado', 'pendiente')
                    ->where('cobrador_id', $user->id)
                    ->exists();
            });
        }

        if (!empty($this->search)) {
            $searchLower = mb_strtolower(trim($this->search));
            $promesas = $promesas->filter(function ($p) use ($searchLower) {
                $cliente = $p->cuota?->operacion?->cliente;
                $nombreCompleto = $cliente ? mb_strtolower($cliente->nombre . ' ' . $cliente->apellido) : '';
                return str_contains($nombreCompleto, $searchLower) ||
                       str_contains(mb_strtolower($p->cuota?->operacion?->numero_solicitud ?? ''), $searchLower);
            });
        }

        return $promesas;
    }

    public function getClientesSinRespuestaProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();

        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $cuotas = $agendaData['hoy']['clientes_sin_respuesta'];

        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $cuotas = $cuotas->filter(function ($cuota) use ($user) {
                return $cuota->visitasCobrador()
                    ->where('estado', 'pendiente')
                    ->where('cobrador_id', $user->id)
                    ->exists();
            });
        }

        // Recalculate computed fields on the fly
        $statusService = new CuotaStatusService();
        $priorityService = new CuotaPriorityService();
        $cuotas = $cuotas->map(function ($cuota) use ($statusService, $priorityService, $ref) {
            $cuota->computed_priority = $priorityService->recalculatePriority($cuota, $ref);
            $cuota->computed_status = $statusService->determineStatus($cuota, $ref);
            return $cuota;
        });

        return $this->filterAndSearchCuotas($cuotas, $ref);
    }

    public function getVisitasDelDiaProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();

        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $visitas = $agendaData['hoy']['visitas_hoy'];

        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $visitas = $visitas->where('cobrador_id', $user->id);
        }

        if (!empty($this->search)) {
            $searchLower = mb_strtolower(trim($this->search));
            $visitas = $visitas->filter(function ($v) use ($searchLower) {
                $cliente = $v->cliente;
                $nombreCompleto = $cliente ? mb_strtolower($cliente->nombre . ' ' . $cliente->apellido) : '';
                return str_contains($nombreCompleto, $searchLower) ||
                       str_contains(mb_strtolower($v->cuota?->operacion?->numero_solicitud ?? ''), $searchLower);
            });
        }

        return $visitas;
    }

    public function getProximasAccionesProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();

        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $cuotas = $agendaData['hoy']['seguimientos_hoy'];

        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $cuotas = $cuotas->filter(function ($cuota) use ($user) {
                return $cuota->visitasCobrador()
                    ->where('estado', 'pendiente')
                    ->where('cobrador_id', $user->id)
                    ->exists();
            });
        }

        $statusService = new CuotaStatusService();
        $priorityService = new CuotaPriorityService();
        $cuotas = $cuotas->map(function ($cuota) use ($statusService, $priorityService, $ref) {
            $cuota->computed_priority = $priorityService->recalculatePriority($cuota, $ref);
            $cuota->computed_status = $statusService->determineStatus($cuota, $ref);
            return $cuota;
        });

        return $this->filterAndSearchCuotas($cuotas, $ref);
    }

    // Future Agenda View Properties

    public function getPromesasFuturasProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $promesas = $agendaData['proximos_dias']['promesas_futuras'];

        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $promesas = $promesas->filter(function ($p) use ($user) {
                return $p->cuota && $p->cuota->visitasCobrador()
                    ->where('estado', 'pendiente')
                    ->where('cobrador_id', $user->id)
                    ->exists();
            });
        }

        if (!empty($this->search)) {
            $searchLower = mb_strtolower(trim($this->search));
            $promesas = $promesas->filter(function ($p) use ($searchLower) {
                $cliente = $p->cuota?->operacion?->cliente;
                $nombreCompleto = $cliente ? mb_strtolower($cliente->nombre . ' ' . $cliente->apellido) : '';
                return str_contains($nombreCompleto, $searchLower);
            });
        }

        return $promesas;
    }

    public function getVisitasFuturasProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $visitas = $agendaData['proximos_dias']['visitas_futuras'];

        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $visitas = $visitas->where('cobrador_id', $user->id);
        }

        if (!empty($this->search)) {
            $searchLower = mb_strtolower(trim($this->search));
            $visitas = $visitas->filter(function ($v) use ($searchLower) {
                $cliente = $v->cliente;
                $nombreCompleto = $cliente ? mb_strtolower($cliente->nombre . ' ' . $cliente->apellido) : '';
                return str_contains($nombreCompleto, $searchLower);
            });
        }

        return $visitas;
    }

    public function getProximosVencimientosProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $cuotas = $agendaData['proximos_dias']['proximos_vencimientos'];

        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $cuotas = $cuotas->filter(function ($cuota) use ($user) {
                return $cuota->visitasCobrador()
                    ->where('estado', 'pendiente')
                    ->where('cobrador_id', $user->id)
                    ->exists();
            });
        }

        $statusService = new CuotaStatusService();
        $priorityService = new CuotaPriorityService();
        $cuotas = $cuotas->map(function ($cuota) use ($statusService, $priorityService, $ref) {
            $cuota->computed_priority = $priorityService->recalculatePriority($cuota, $ref);
            $cuota->computed_status = $statusService->determineStatus($cuota, $ref);
            return $cuota;
        });

        return $this->filterAndSearchCuotas($cuotas, $ref);
    }

    public function getSeguimientosFuturosProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        $agendaData = app(AgendaCobranzaService::class)->obtenerAgendaDiaria($ref);
        $cuotas = $agendaData['proximos_dias']['seguimientos_futuros'];

        $user = auth()->user();
        if ($user && $user->role === 'cobrador') {
            $cuotas = $cuotas->filter(function ($cuota) use ($user) {
                return $cuota->visitasCobrador()
                    ->where('estado', 'pendiente')
                    ->where('cobrador_id', $user->id)
                    ->exists();
            });
        }

        $statusService = new CuotaStatusService();
        $priorityService = new CuotaPriorityService();
        $cuotas = $cuotas->map(function ($cuota) use ($statusService, $priorityService, $ref) {
            $cuota->computed_priority = $priorityService->recalculatePriority($cuota, $ref);
            $cuota->computed_status = $statusService->determineStatus($cuota, $ref);
            return $cuota;
        });

        return $this->filterAndSearchCuotas($cuotas, $ref);
    }

    public function getCobradoresProperty()
    {
        return User::where('role', 'cobrador')->get();
    }

    public function render()
    {
        return view('livewire.agenda-cobranza-component');
    }
}

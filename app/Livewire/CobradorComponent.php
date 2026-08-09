<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\VisitaCobrador;
use App\Models\Cuota;
use App\Models\User;
use App\Services\VisitaCobradorService;
use App\Services\PagoService;
use App\Services\GestionService;
use App\Services\CuotaPriorityService;
use App\Services\CuotaStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CobradorComponent extends Component
{
    // Active tabs: 'hoy', 'futuras', 'historial'
    public $activeTab = 'hoy';

    // Reference Date (defaults to today)
    public $referenceDate;

    // Selected visit for the "Ficha Rápida" (mobile view/detail)
    public $selectedVisitaId = null;

    // Selected Cobrador ID (useful for gestores/admins to view a specific cobrador's panel)
    public $selectedCobradorId = null;

    // Double-submission guard
    public $isSaving = false;

    // Active modals: null, 'resultado', 'pago'
    public $activeModal = null;

    // Form inputs: Registrar Resultado
    public $resultado_tipo = 'cobrado';
    public $resultado_monto = 0.00;
    public $resultado_observaciones = '';
    public $resultado_fecha_realizada;

    // Form inputs: Registrar Pago
    public $pago_monto_cobrado = '';
    public $pago_punitorios_perdonados = 0.00;
    public $pago_medio_pago = 'efectivo';
    public $pago_observaciones = '';

    // Filters for Historial
    public $history_date = '';
    public $history_outcome = 'todos';
    public $history_client = '';
    public $history_solicitud = '';
    public $history_status = 'todos'; // realizada, cancelada

    public function mount()
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['administrador', 'gestor', 'cobrador'])) {
            abort(403, 'No autorizado.');
        }

        $this->referenceDate = Carbon::today()->toDateString();
        $this->resultado_fecha_realizada = Carbon::today()->toDateString();

        // Enforce role-based assignment
        if ($user->role === 'cobrador') {
            $this->selectedCobradorId = $user->id;
        } else {
            // Default to the first cobrador or themselves
            $this->selectedCobradorId = User::where('role', 'cobrador')->first()?->id ?? $user->id;
        }
    }

    /**
     * Compute header KPIs for the collector.
     */
    public function getMetricsProperty()
    {
        $todayStr = $this->referenceDate;

        $baseQuery = VisitaCobrador::where('cobrador_id', $this->selectedCobradorId);

        // Visits pending today
        $pendingToday = (clone $baseQuery)
            ->whereDate('fecha_programada', '=', $todayStr)
            ->whereIn('estado', ['pendiente', 'iniciada', 'en_visita'])
            ->count();

        // Visits completed today
        $completedToday = (clone $baseQuery)
            ->whereDate('fecha_programada', '=', $todayStr)
            ->where('estado', 'realizada')
            ->count();

        // Total pending (today & future)
        $totalPending = (clone $baseQuery)
            ->whereIn('estado', ['pendiente', 'iniciada', 'en_visita'])
            ->count();

        // Amount collected today (via completed visits today or pagos registered today)
        $collectedToday = (clone $baseQuery)
            ->whereDate('fecha_programada', '=', $todayStr)
            ->where('estado', 'realizada')
            ->sum('monto_cobrado');

        return [
            'pending_today' => $pendingToday,
            'completed_today' => $completedToday,
            'total_pending' => $totalPending,
            'collected_today' => $collectedToday,
        ];
    }

    /**
     * Get and sort today's visits.
     */
    public function getTodayVisitsProperty()
    {
        $todayStr = $this->referenceDate;

        $visitas = VisitaCobrador::with(['cliente', 'cuota.operacion.cliente', 'cuota.promesasPago'])
            ->where('cobrador_id', $this->selectedCobradorId)
            ->whereDate('fecha_programada', '=', $todayStr)
            ->get();

        // Priority sorting logic
        return $visitas->sort(function ($a, $b) {
            // 6. Already completed visits go below pending
            $statusA = in_array($a->estado, ['pendiente', 'iniciada', 'en_visita']) ? 1 : 0;
            $statusB = in_array($b->estado, ['pendiente', 'iniciada', 'en_visita']) ? 1 : 0;

            if ($statusA !== $statusB) {
                return $statusB <=> $statusA; // Pending first
            }

            // Both are pending (or both completed). Sort by Priority if pending.
            if ($statusA === 1) {
                $priorityService = new CuotaPriorityService();
                $ref = Carbon::parse($this->referenceDate);

                $prioA = $a->cuota ? $priorityService->recalculatePriority($a->cuota, $ref) : 'baja';
                $prioB = $b->cuota ? $priorityService->recalculatePriority($b->cuota, $ref) : 'baja';

                $prioMap = ['critica' => 4, 'alta' => 3, 'media' => 2, 'baja' => 1];
                $valA = $prioMap[$prioA] ?? 1;
                $valB = $prioMap[$prioB] ?? 1;

                if ($valA !== $valB) {
                    return $valB <=> $valA; // Descending priority
                }
            }

            // Secondary: order by scheduled time / id
            return $a->id <=> $b->id;
        });
    }

    /**
     * Get future visits (date > referenceDate).
     */
    public function getFutureVisitsProperty()
    {
        $todayStr = $this->referenceDate;

        return VisitaCobrador::with(['cliente', 'cuota.operacion.cliente'])
            ->where('cobrador_id', $this->selectedCobradorId)
            ->whereDate('fecha_programada', '>', $todayStr)
            ->whereIn('estado', ['pendiente', 'iniciada', 'en_visita'])
            ->orderBy('fecha_programada', 'asc')
            ->get();
    }

    /**
     * Get filtered history visits.
     */
    public function getHistoryVisitsProperty()
    {
        $query = VisitaCobrador::with(['cliente', 'cuota.operacion.cliente'])
            ->where('cobrador_id', $this->selectedCobradorId);

        // Exclude completely pending from history unless we filter specifically
        if ($this->history_status === 'todos') {
            $query->whereIn('estado', ['realizada', 'cancelada']);
        } else {
            $query->where('estado', $this->history_status);
        }

        // Filter by Date
        if (!empty($this->history_date)) {
            $query->whereDate('fecha_programada', '=', $this->history_date);
        }

        // Filter by Outcome
        if ($this->history_outcome !== 'todos') {
            $query->where('resultado', $this->history_outcome);
        }

        // Filter by Client name/surname
        if (!empty($this->history_client)) {
            $search = mb_strtolower(trim($this->history_client));
            $query->whereHas('cliente', function ($q) use ($search) {
                $q->where(DB::raw('lower(nombre)'), 'like', "%{$search}%")
                  ->orWhere(DB::raw('lower(apellido)'), 'like', "%{$search}%");
            });
        }

        // Filter by Solicitud Number
        if (!empty($this->history_solicitud)) {
            $sol = trim($this->history_solicitud);
            $query->whereHas('cuota.operacion', function ($q) use ($sol) {
                $q->where('numero_solicitud', $sol);
            });
        }

        return $query->orderBy('fecha_realizada', 'desc')
            ->orderBy('fecha_programada', 'desc')
            ->get();
    }

    /**
     * Helper to load & validate a visit.
     */
    protected function getAuthorizedVisita($id)
    {
        $visita = VisitaCobrador::with(['cliente', 'cuota.operacion.cliente'])->findOrFail($id);
        $user = auth()->user();

        // Security check: Cobrador cannot view other's visits
        if ($user->role === 'cobrador' && $visita->cobrador_id !== $user->id) {
            abort(403, 'No autorizado para esta visita.');
        }

        return $visita;
    }

    public function selectVisita($id)
    {
        $visita = $this->getAuthorizedVisita($id);
        $this->selectedVisitaId = $visita->id;
    }

    public function closeFicha()
    {
        $this->selectedVisitaId = null;
    }

    /**
     * Action: Iniciar visita
     */
    public function iniciarVisita($id)
    {
        $visita = $this->getAuthorizedVisita($id);

        if ($visita->estado !== 'pendiente') {
            session()->flash('warning', 'La visita ya ha sido iniciada o finalizada.');
            return;
        }

        $visita->estado = 'iniciada';
        $visita->save();

        session()->flash('success', 'Visita iniciada correctamente.');
    }

    /**
     * Action: Quick No pude contactar
     */
    public function noPudeContactar($id)
    {
        $visita = $this->getAuthorizedVisita($id);

        if ($visita->estado === 'realizada') {
            session()->flash('warning', 'Esta visita ya fue realizada.');
            return;
        }

        // Register the outcome via service
        $service = app(VisitaCobradorService::class);
        $service->registrarResultadoVisita($visita->id, [
            'resultado' => 'no_se_pudo_contactar',
            'monto_cobrado' => 0.00,
            'observaciones' => 'No se pudo contactar mediante visita rápida.',
            'fecha_realizada' => Carbon::now(),
        ]);

        // Register related gestion if cuota is present
        if ($visita->cuota_id) {
            $gestionService = app(GestionService::class);
            $gestionService->registrarGestion([
                'cuota_id' => $visita->cuota_id,
                'user_id' => auth()->id() ?? 1,
                'tipo' => 'visita_presencial',
                'resultado' => 'no_atendio',
                'observacion' => 'Visita presencial: No se pudo contactar.',
            ]);
        }

        session()->flash('success', 'Visita marcada como No Contactada.');
    }

    /**
     * Action: Cancelar Visita (Only for gestor/admin)
     */
    public function cancelarVisita($id)
    {
        $user = auth()->user();
        if ($user->role === 'cobrador') {
            abort(403, 'No autorizado para cancelar visitas.');
        }

        $visita = $this->getAuthorizedVisita($id);
        $visita->estado = 'cancelada';
        $visita->save();

        if ($visita->cuota_id) {
            $cuota = $visita->cuota;
            $cuota->estado_gestion = 'seguimiento';
            $cuota->save();

            $priorityService = new CuotaPriorityService();
            $priorityService->recalculatePriority($cuota);
        }

        session()->flash('success', 'Visita cancelada correctamente.');
    }

    // "Registrar Resultado" modal

    public function openResultadoModal()
    {
        if (!$this->selectedVisitaId) return;
        $visita = $this->getAuthorizedVisita($this->selectedVisitaId);

        if ($visita->estado === 'realizada') {
            session()->flash('warning', 'Esta visita ya fue realizada.');
            return;
        }

        $this->resultado_tipo = 'no_estaba';
        $this->resultado_monto = 0.00;
        $this->resultado_observaciones = '';
        $this->resultado_fecha_realizada = Carbon::today()->toDateString();
        $this->activeModal = 'resultado';
    }

    public function submitResultado()
    {
        if ($this->isSaving) return;
        $this->isSaving = true;

        $visita = $this->getAuthorizedVisita($this->selectedVisitaId);

        if ($visita->estado === 'realizada') {
            $this->isSaving = false;
            $this->closeModal();
            session()->flash('warning', 'Esta visita ya fue realizada.');
            return;
        }

        $this->validate([
            'resultado_tipo' => 'required|string',
            'resultado_monto' => 'required|numeric|min:0',
            'resultado_observaciones' => 'nullable|string',
            'resultado_fecha_realizada' => 'required|date',
        ]);

        $service = app(VisitaCobradorService::class);
        $service->registrarResultadoVisita($visita->id, [
            'resultado' => $this->resultado_tipo,
            'monto_cobrado' => $this->resultado_monto,
            'observaciones' => $this->resultado_observaciones,
            'fecha_realizada' => $this->resultado_fecha_realizada,
        ]);

        // Register related Gestion
        if ($visita->cuota_id) {
            // Map resultado to matching Gestion resultado
            $mappedResultado = 'seguimiento';
            if ($this->resultado_tipo === 'cobrado' || $this->resultado_tipo === 'cobrado_parcialmente') {
                $mappedResultado = 'cliente_pago';
            } elseif ($this->resultado_tipo === 'prometio_pagar') {
                $mappedResultado = 'prometio_pagar';
            } elseif ($this->resultado_tipo === 'no_estaba' || $this->resultado_tipo === 'no_se_pudo_contactar') {
                $mappedResultado = 'no_atendio';
            }

            $gestionService = app(GestionService::class);
            $gestionService->registrarGestion([
                'cuota_id' => $visita->cuota_id,
                'user_id' => auth()->id() ?? 1,
                'tipo' => 'visita_presencial',
                'resultado' => $mappedResultado,
                'observacion' => 'Resultado Visita: ' . $this->resultado_observaciones,
            ]);
        }

        $this->isSaving = false;
        $this->closeModal();
        session()->flash('success', 'Resultado de visita guardado correctamente.');
    }

    // "Registrar Pago" modal

    public function openPagoModal()
    {
        if (!$this->selectedVisitaId) return;
        $visita = $this->getAuthorizedVisita($this->selectedVisitaId);

        if ($visita->estado === 'realizada') {
            session()->flash('warning', 'Esta visita ya fue realizada.');
            return;
        }

        $this->pago_monto_cobrado = $visita->cuota ? $visita->cuota->saldo_pendiente : '';
        $this->pago_punitorios_perdonados = 0.00;
        $this->pago_medio_pago = 'efectivo';
        $this->pago_observaciones = '';
        $this->activeModal = 'pago';
    }

    public function updatedPagoMontoCobrado($value)
    {
        if (!$this->selectedVisitaId) return;
        $visita = $this->getAuthorizedVisita($this->selectedVisitaId);

        if ($visita->cuota) {
            $monto = floatval($value);
            $saldo = floatval($visita->cuota->saldo_pendiente);
            if ($monto > 0) {
                $this->pago_punitorios_perdonados = max(0.00, $saldo - $monto);
            } else {
                $this->pago_punitorios_perdonados = 0.00;
            }
        }
    }

    public function submitPago()
    {
        if ($this->isSaving) return;
        $this->isSaving = true;

        $visita = $this->getAuthorizedVisita($this->selectedVisitaId);

        if ($visita->estado === 'realizada') {
            $this->isSaving = false;
            $this->closeModal();
            session()->flash('warning', 'Esta visita ya fue realizada.');
            return;
        }

        $this->validate([
            'pago_monto_cobrado' => 'required|numeric|min:0.01',
            'pago_punitorios_perdonados' => 'required|numeric|min:0',
            'pago_medio_pago' => 'required|string',
            'pago_observaciones' => 'nullable|string',
        ]);

        if ($visita->cuota_id) {
            // 1. Register Payment using domain PagoService
            $pagoService = app(PagoService::class);
            $pagoService->registrarPago([
                'cuota_id' => $visita->cuota_id,
                'user_id' => auth()->id() ?? 1,
                'monto_cobrado' => $this->pago_monto_cobrado,
                'punitorios_perdonados' => $this->pago_punitorios_perdonados,
                'medio_pago' => $this->pago_medio_pago,
                'observaciones' => 'Pago de visita: ' . $this->pago_observaciones,
                'fecha_pago' => Carbon::now(),
            ]);

            // Determine if full or partial
            $saldo = floatval($visita->cuota->fresh()->saldo_pendiente);
            $isCompleted = ($saldo <= 0.00);
            $resultadoVisita = $isCompleted ? 'cobrado' : 'cobrado_parcialmente';

            // 2. Mark visit as completed (realizada)
            $visitaService = app(VisitaCobradorService::class);
            $visitaService->registrarResultadoVisita($visita->id, [
                'resultado' => $resultadoVisita,
                'monto_cobrado' => $this->pago_monto_cobrado,
                'observaciones' => 'Pago cobrado desde visita. ' . $this->pago_observaciones,
                'fecha_realizada' => Carbon::now(),
            ]);

            // 3. Register Related Gestion
            $gestionService = app(GestionService::class);
            $gestionService->registrarGestion([
                'cuota_id' => $visita->cuota_id,
                'user_id' => auth()->id() ?? 1,
                'tipo' => 'visita_presencial',
                'resultado' => $isCompleted ? 'cliente_pago' : 'seguimiento',
                'observacion' => 'Pago realizado en visita: $' . number_format($this->pago_monto_cobrado, 2) . ' (' . ($isCompleted ? 'Total' : 'Parcial') . ')',
            ]);

            // Ensure partial payment state is respected (override any collision with services)
            $cuota = $visita->cuota->fresh();
            if (floatval($cuota->saldo_pendiente) > 0) {
                $cuota->estado_gestion = 'seguimiento';
                $cuota->save();

                $priorityService = new CuotaPriorityService();
                $priorityService->recalculatePriority($cuota);
            }
        } else {
            // Visita without cuota: just log payment on visit
            $visitaService = app(VisitaCobradorService::class);
            $visitaService->registrarResultadoVisita($visita->id, [
                'resultado' => 'cobrado',
                'monto_cobrado' => $this->pago_monto_cobrado,
                'observaciones' => 'Cobro registrado sin cuota asociada. ' . $this->pago_observaciones,
                'fecha_realizada' => Carbon::now(),
            ]);
        }

        $this->isSaving = false;
        $this->closeModal();
        session()->flash('success', 'Pago y visita registrados correctamente.');
    }

    public function closeModal()
    {
        $this->activeModal = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $cobradoresList = [];
        if (auth()->user()->role !== 'cobrador') {
            $cobradoresList = User::where('role', 'cobrador')->get();
        }

        return view('livewire.cobrador-component', [
            'cobradores' => $cobradoresList,
        ]);
    }
}

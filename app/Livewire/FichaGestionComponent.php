<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cuota;
use App\Models\User;
use App\Models\VisitaCobrador;
use App\Models\PlantillaMensaje;
use App\Services\CuotaStatusService;
use App\Services\CuotaPriorityService;
use App\Services\GestionService;
use App\Services\PromesaPagoService;
use App\Services\VisitaCobradorService;
use App\Services\PagoService;
use Carbon\Carbon;

class FichaGestionComponent extends Component
{
    public $cuotaId;
    public $referenceDate = null;

    // Modal state: 'gestion', 'promesa', 'cobrador', 'pago', 'visita_resultado', 'plantilla'
    public $activeModal = null;
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

    // Message templates
    public $selectedPlantillaId = '';
    public $previewMensaje = '';

    public function mount($cuotaId)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['administrador', 'gestor', 'cobrador'])) {
            abort(403, 'No autorizado.');
        }

        $this->cuotaId = $cuotaId;
        if (!$this->referenceDate) {
            $this->referenceDate = Carbon::today()->toDateString();
        }

        // Initialize nullable dates to null (per memory guidelines)
        $this->gestion_proxima_accion_fecha = null;
        $this->visita_fecha_realizada = null;
    }

    public function getCuotaProperty()
    {
        return Cuota::with([
            'operacion.cliente',
            'gestiones.user',
            'promesasPago.user',
            'pagos.user',
            'visitasCobrador.cobrador'
        ])->findOrFail($this->cuotaId);
    }

    /**
     * Compute dynamic collection status using CuotaStatusService.
     */
    public function getComputedStatusProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        return app(CuotaStatusService::class)->determineStatus($this->cuota, $ref);
    }

    /**
     * Compute dynamic priority using CuotaPriorityService.
     */
    public function getComputedPriorityProperty()
    {
        $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
        return app(CuotaPriorityService::class)->recalculatePriority($this->cuota, $ref);
    }

    /**
     * Get list of other cuotas from the same operation/client.
     */
    public function getOtrasCuotasProperty()
    {
        $cuota = $this->cuota;
        return Cuota::where('operacion_id', $cuota->operacion_id)
            ->where('id', '!=', $cuota->id)
            ->with('pagos')
            ->get()
            ->map(function ($c) {
                $ref = $this->referenceDate ? Carbon::parse($this->referenceDate) : Carbon::today();
                $c->computed_status = app(CuotaStatusService::class)->determineStatus($c, $ref);
                return $c;
            });
    }

    /**
     * Recommended Next Action.
     */
    public function getProximaAccionRecomendadaProperty()
    {
        $status = $this->computedStatus;
        if (in_array($status, ['pago_realizado_oficial', 'pago_registrado'])) {
            return [
                'accion' => 'Ninguna (Caso Finalizado / Cobrado)',
                'fecha' => null,
                'prioridad' => 'baja'
            ];
        }

        if ($status === 'promesa_pago') {
            $promesa = $this->cuota->promesasPago()->where('estado', 'pendiente')->orderBy('fecha_prometida', 'asc')->first();
            return [
                'accion' => 'Esperar promesa de pago',
                'fecha' => $promesa ? Carbon::parse($promesa->fecha_prometida)->format('d/m/Y') : null,
                'prioridad' => 'media'
            ];
        }

        if ($status === 'promesa_incumplida') {
            return [
                'accion' => 'Revisar promesa incumplida: contactar de forma urgente o escalar a visita',
                'fecha' => null,
                'prioridad' => 'critica'
            ];
        }

        if ($status === 'sin_respuesta') {
            return [
                'accion' => 'Sin respuesta: escalar a visita presencial del cobrador',
                'fecha' => null,
                'prioridad' => 'alta'
            ];
        }

        if ($this->cuota->visitasCobrador()->where('estado', 'pendiente')->exists()) {
            $visita = $this->cuota->visitasCobrador()->where('estado', 'pendiente')->first();
            return [
                'accion' => 'Esperar resultado de visita del cobrador (' . $visita->cobrador->name . ')',
                'fecha' => Carbon::parse($visita->fecha_programada)->format('d/m/Y'),
                'prioridad' => 'alta'
            ];
        }

        if ($status === 'vence_hoy') {
            return [
                'accion' => 'Vence hoy: verificar pago hoy / contactar al cliente',
                'fecha' => Carbon::parse($this->referenceDate)->format('d/m/Y'),
                'prioridad' => 'alta'
            ];
        }

        // Check if there is a next action scheduled in the gestiones
        $latestGestionWithAction = $this->cuota->gestiones()
            ->whereNotNull('proxima_accion_fecha')
            ->orderBy('fecha_hora', 'desc')
            ->first();

        if ($latestGestionWithAction) {
            return [
                'accion' => $latestGestionWithAction->proxima_accion ?: 'Contactar nuevamente',
                'fecha' => Carbon::parse($latestGestionWithAction->proxima_accion_fecha)->format('d/m/Y'),
                'prioridad' => 'media'
            ];
        }

        return [
            'accion' => 'Contactar nuevamente para coordinar pago',
            'fecha' => null,
            'prioridad' => 'media'
        ];
    }

    /**
     * Authorization checks.
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

    // Modal Triggering

    public function openGestionModal()
    {
        $this->authorizeAction('registrar_gestion');
        $this->gestion_tipo = 'llamada';
        $this->gestion_resultado = 'contactado';
        $this->gestion_observacion = '';
        $this->gestion_proxima_accion = '';
        $this->gestion_proxima_accion_fecha = null;
        $this->activeModal = 'gestion';
    }

    public function openPromesaModal()
    {
        $this->authorizeAction('registrar_promesa');
        $this->promesa_fecha_prometida = Carbon::tomorrow()->toDateString();
        $this->promesa_monto_prometido = $this->cuota->saldo_pendiente;
        $this->promesa_observaciones = '';
        $this->activeModal = 'promesa';
    }

    public function openCobradorModal()
    {
        $this->authorizeAction('programar_cobrador');
        $this->cobrador_id = User::where('role', 'cobrador')->first()?->id ?? '';
        $this->cobrador_fecha_programada = Carbon::tomorrow()->toDateString();
        $this->cobrador_observaciones = '';
        $this->activeModal = 'cobrador';
    }

    public function openPagoModal()
    {
        $this->authorizeAction('registrar_pago');
        $this->pago_monto_cobrado = $this->cuota->saldo_pendiente;
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
        $this->visita_monto_cobrado = $this->cuota->saldo_pendiente;
        $this->visita_observaciones = '';
        $this->visita_fecha_realizada = Carbon::today()->toDateString();
        $this->activeModal = 'visita_resultado';
    }

    public function closeModal()
    {
        $this->activeModal = null;
        $this->selectedVisitaId = null;
        $this->resetErrorBag();
    }

    // Action execution

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
            'cuota_id' => $this->cuotaId,
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
            'cuota_id' => $this->cuotaId,
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

        $service = app(VisitaCobradorService::class);
        $service->programarVisita([
            'cliente_id' => $this->cuota->operacion->cliente_id,
            'cuota_id' => $this->cuotaId,
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
            'cuota_id' => $this->cuotaId,
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

    // Message Templates replacements

    public function getPlantillasProperty()
    {
        return PlantillaMensaje::where('activo', true)->get();
    }

    public function updatedSelectedPlantillaId($value)
    {
        if (!$value) {
            $this->previewMensaje = '';
            return;
        }

        $plantilla = PlantillaMensaje::find($value);
        if ($plantilla) {
            $this->previewMensaje = $this->replaceTemplateVariables($plantilla->cuerpo);
        } else {
            $this->previewMensaje = '';
        }
    }

    protected function replaceTemplateVariables($cuerpo)
    {
        $cliente = $this->cuota->operacion->cliente;
        $replacements = [
            '{nombre}' => $cliente->nombre . ' ' . $cliente->apellido,
            '{importe}' => number_format($this->cuota->saldo_pendiente, 2),
            '{fecha}' => $this->cuota->dia_cobro,
            '{numero_cuota}' => $this->cuota->numero_cuota,
            '{link_pago}' => $this->cuota->link_pago ?: 'N/A',
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $cuerpo);
    }

    public function getWhatsappUrlProperty()
    {
        if (!$this->cuota->operacion->cliente->telefono) {
            return '#';
        }
        $phone = preg_replace('/[^0-9]/', '', $this->cuota->operacion->cliente->telefono);
        return "https://wa.me/{$phone}?text=" . urlencode($this->previewMensaje);
    }

    public function render()
    {
        return view('livewire.ficha-gestion-component');
    }
}

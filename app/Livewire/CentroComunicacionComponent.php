<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PlantillaMensaje;
use App\Models\Cuota;
use App\Models\User;
use App\Services\MessageTemplateService;
use App\Services\GestionService;
use Carbon\Carbon;

class CentroComunicacionComponent extends Component
{
    // Search & Filter
    public $search = '';
    public $filterCategory = 'todos';

    // Model management fields
    public $showFormModal = false;
    public $isEditing = false;
    public $editingTemplateId = null;

    public $titulo = '';
    public $categoria = 'proximo_vencimiento';
    public $cuerpo = '';
    public $activo = true;

    // Unknown variables confirmation
    public $unknownVariablesWarning = [];
    public $confirmSaveUnknown = false;

    // Previsualization Fields
    public $selectedCuotaId = '';
    public $selectedPreviewTemplateId = '';
    public $previewMessage = '';
    public $manualMessage = '';
    public $warnings = [];
    public $whatsappUrl = '';

    // Gestion registration fields
    public $showGestionModal = false;
    public $gestion_tipo = 'whatsapp_enviado';
    public $gestion_resultado = 'mensaje_enviado';
    public $gestion_observacion = '';

    // Message suggested state
    public $suggestedCategory = '';

    public function mount()
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['administrador', 'gestor', 'cobrador'])) {
            abort(403, 'No autorizado.');
        }
    }

    public function authorizeAdminAction()
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['administrador', 'gestor'])) {
            abort(403, 'No autorizado para modificar plantillas.');
        }
    }

    // LISTINGS & FILTERS

    public function getPlantillasProperty()
    {
        $query = PlantillaMensaje::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('titulo', 'like', '%' . $this->search . '%')
                  ->orWhere('cuerpo', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterCategory !== 'todos') {
            $query->where('categoria', $this->filterCategory);
        }

        return $query->orderBy('titulo', 'asc')->get();
    }

    public function getCuotasListProperty()
    {
        return Cuota::with('operacion.cliente')
            ->where('saldo_pendiente', '>', 0)
            ->get();
    }

    // TEMPLATE MANAGEMENT ACTIONS

    public function openCreateModal()
    {
        $this->authorizeAdminAction();
        $this->resetForm();
        $this->isEditing = false;
        $this->showFormModal = true;
    }

    public function openEditModal($id)
    {
        $this->authorizeAdminAction();
        $this->resetForm();
        $template = PlantillaMensaje::findOrFail($id);
        $this->editingTemplateId = $template->id;
        $this->titulo = $template->titulo;
        $this->categoria = $template->categoria;
        $this->cuerpo = $template->cuerpo;
        $this->activo = (bool)$template->activo;
        $this->isEditing = true;
        $this->showFormModal = true;
    }

    public function resetForm()
    {
        $this->editingTemplateId = null;
        $this->titulo = '';
        $this->categoria = 'proximo_vencimiento';
        $this->cuerpo = '';
        $this->activo = true;
        $this->unknownVariablesWarning = [];
        $this->confirmSaveUnknown = false;
        $this->resetErrorBag();
    }

    public function updatedCuerpo($value)
    {
        $service = app(MessageTemplateService::class);
        $this->unknownVariablesWarning = $service->detectUnknownVariables($value);
    }

    public function saveTemplate()
    {
        $this->authorizeAdminAction();

        $this->validate([
            'titulo' => 'required|string|max:100',
            'categoria' => 'required|string',
            'cuerpo' => 'required|string',
        ]);

        $service = app(MessageTemplateService::class);
        $unknown = $service->detectUnknownVariables($this->cuerpo);

        if (!empty($unknown) && !$this->confirmSaveUnknown) {
            $this->unknownVariablesWarning = $unknown;
            $this->addError('cuerpo', 'Se detectaron variables no reconocidas. Confirme si desea guardar de todas formas.');
            return;
        }

        $data = [
            'titulo' => $this->titulo,
            'categoria' => $this->categoria,
            'cuerpo' => $this->cuerpo,
            'activo' => $this->activo,
        ];

        if ($this->isEditing) {
            $template = PlantillaMensaje::findOrFail($this->editingTemplateId);
            $template->update($data);
            session()->flash('success', 'Plantilla actualizada correctamente.');
        } else {
            PlantillaMensaje::create($data);
            session()->flash('success', 'Plantilla creada correctamente.');
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function toggleActivo($id)
    {
        $this->authorizeAdminAction();
        $template = PlantillaMensaje::findOrFail($id);
        $template->activo = !$template->activo;
        $template->save();
        session()->flash('success', 'Estado de la plantilla modificado.');
    }

    public function deleteTemplate($id)
    {
        $this->authorizeAdminAction();
        $template = PlantillaMensaje::findOrFail($id);
        $template->delete(); // Soft delete
        session()->flash('success', 'Plantilla eliminada correctamente.');
    }

    // PREVISUALIZATION & GENERATION

    public function updatedSelectedCuotaId($value)
    {
        if (!$value) {
            $this->resetPreview();
            return;
        }

        $cuota = Cuota::find($value);
        if ($cuota) {
            // Suggest best template category
            $service = app(MessageTemplateService::class);
            $this->suggestedCategory = $service->suggestCategoryForCuota($cuota);

            // Auto-select template if matching category exists
            $suggestedTemplate = PlantillaMensaje::where('activo', true)
                ->where('categoria', $this->suggestedCategory)
                ->first();
            if ($suggestedTemplate) {
                $this->selectedPreviewTemplateId = $suggestedTemplate->id;
                $this->generatePreview();
            } else {
                // select first active template
                $firstTemp = PlantillaMensaje::where('activo', true)->first();
                if ($firstTemp) {
                    $this->selectedPreviewTemplateId = $firstTemp->id;
                    $this->generatePreview();
                }
            }
        }
    }

    public function updatedSelectedPreviewTemplateId($value)
    {
        $this->generatePreview();
    }

    public function generatePreview()
    {
        if (!$this->selectedCuotaId || !$this->selectedPreviewTemplateId) {
            $this->resetPreview();
            return;
        }

        $cuota = Cuota::find($this->selectedCuotaId);
        $template = PlantillaMensaje::find($this->selectedPreviewTemplateId);

        if ($cuota && $template) {
            $service = app(MessageTemplateService::class);
            $parsed = $service->parseTemplate($template->cuerpo, $cuota);

            $this->previewMessage = $parsed['text'];
            $this->manualMessage = $parsed['text'];
            $this->warnings = $parsed['warnings'];

            // WhatsApp url
            $cliente = $cuota->operacion?->cliente;
            if ($cliente && $cliente->telefono) {
                $this->whatsappUrl = $service->getWhatsappUrl($cliente->telefono, $this->manualMessage);
            } else {
                $this->whatsappUrl = '';
            }
        }
    }

    public function updatedManualMessage($value)
    {
        // Re-generate WhatsApp URL with custom manually edited message (leaving original template untouched)
        if ($this->selectedCuotaId) {
            $cuota = Cuota::find($this->selectedCuotaId);
            $cliente = $cuota?->operacion?->cliente;
            if ($cliente && $cliente->telefono) {
                $service = app(MessageTemplateService::class);
                $this->whatsappUrl = $service->getWhatsappUrl($cliente->telefono, $value);
            }
        }
    }

    public function resetPreview()
    {
        $this->previewMessage = '';
        $this->manualMessage = '';
        $this->warnings = [];
        $this->whatsappUrl = '';
        $this->suggestedCategory = '';
    }

    // REGISTRAR GESTION

    public function openGestionModal()
    {
        if (!$this->selectedCuotaId) {
            return;
        }
        $this->gestion_tipo = 'whatsapp_enviado';
        $this->gestion_resultado = 'mensaje_enviado';
        $this->gestion_observacion = 'Mensaje enviado utilizando plantilla';
        $this->showGestionModal = true;
    }

    public function submitGestion()
    {
        $this->validate([
            'gestion_tipo' => 'required|string',
            'gestion_resultado' => 'required|string',
            'gestion_observacion' => 'nullable|string',
        ]);

        $service = app(GestionService::class);
        $service->registrarGestion([
            'cuota_id' => $this->selectedCuotaId,
            'user_id' => auth()->id() ?? 1,
            'tipo' => $this->gestion_tipo,
            'resultado' => $this->gestion_resultado,
            'observacion' => $this->gestion_observacion,
        ]);

        $this->showGestionModal = false;
        session()->flash('success', 'Gestión de comunicación registrada correctamente.');
    }

    public function render()
    {
        return view('livewire.centro-comunicacion-component', [
            'plantillas' => $this->plantillas,
            'cuotasList' => $this->cuotasList,
            'availableVars' => app(MessageTemplateService::class)->getAvailableVariables(),
        ]);
    }
}

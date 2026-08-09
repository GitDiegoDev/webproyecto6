<div class="space-y-8" x-data="{ activeTab: 'plantillas' }">
    <!-- Header Summary / Nav tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">✉️ Centro de Comunicación</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Generador de mensajes de cobranza, previsualizador y administración de plantillas.</p>
        </div>
        <div class="flex gap-2">
            <button @click="activeTab = 'plantillas'" :class="activeTab === 'plantillas' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'" class="px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm">
                📋 Administrar Plantillas
            </button>
            <button @click="activeTab = 'generador'" :class="activeTab === 'generador' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'" class="px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm">
                ⚡ Generar y Contactar
            </button>
        </div>
    </div>

    <!-- Alert Banner -->
    @if (session()->has('success'))
        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-200 dark:border-green-800 flex items-center justify-between" role="alert">
            <span class="font-medium">✨ {{ session('success') }}</span>
            <button type="button" class="text-green-800 dark:text-green-400 hover:opacity-75 font-bold" onclick="this.parentElement.remove()">✕</button>
        </div>
    @endif

    <!-- TAB 1: ADMINISTRAR PLANTILLAS -->
    <div x-show="activeTab === 'plantillas'" class="space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <!-- Search & Filters -->
            <div class="flex flex-wrap gap-3 w-full md:w-auto">
                <input wire:model.live="search" type="text" placeholder="Buscar plantilla..." class="text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />

                <select wire:model.live="filterCategory" class="text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="todos">Todas las categorías</option>
                    <option value="proximo_vencimiento">Próximo vencimiento</option>
                    <option value="vence_hoy">Vence hoy</option>
                    <option value="cuota_vencida">Cuota vencida</option>
                    <option value="link_pago">Link de pago</option>
                    <option value="promesa_pago">Promesa de pago</option>
                    <option value="seguimiento_promesa">Seguimiento de promesa</option>
                    <option value="cobrador">Cobrador</option>
                    <option value="sin_respuesta">Sin respuesta</option>
                    <option value="pago_pendiente">Pago pendiente</option>
                    <option value="pago_parcial">Pago parcial</option>
                    <option value="recordatorio_pago">Recordatorio de pago</option>
                    <option value="seguimiento_general">Seguimiento general</option>
                </select>
            </div>

            <!-- Add Button (Visible only to Administrador / Gestor) -->
            @if(in_array(auth()->user()->role, ['administrador', 'gestor']))
                <button wire:click="openCreateModal" class="inline-flex items-center gap-1 bg-indigo-600 text-white text-xs font-bold px-4 py-2.5 rounded-lg hover:bg-indigo-700 transition shadow">
                    ➕ Nueva Plantilla
                </button>
            @endif
        </div>

        <!-- Plantillas Table / List -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700 text-xs font-semibold text-gray-500 uppercase">
                            <th class="p-4">Título</th>
                            <th class="p-4">Categoría</th>
                            <th class="p-4 text-center">Estado</th>
                            <th class="p-4">Última Modificación</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($plantillas as $plantilla)
                            <tr class="hover:bg-gray-50/50">
                                <td class="p-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $plantilla->titulo }}</div>
                                    <div class="text-xs text-gray-400 mt-1 line-clamp-1 italic">{{ $plantilla->cuerpo }}</div>
                                </td>
                                <td class="p-4 font-mono text-xs text-indigo-600 dark:text-indigo-400">
                                    {{ str_replace('_', ' ', $plantilla->categoria) }}
                                </td>
                                <td class="p-4 text-center">
                                    @if($plantilla->activo)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            Activa
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">
                                            Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 text-xs text-gray-500">
                                    {{ $plantilla->updated_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <!-- Test button -->
                                        <button @click="activeTab = 'generador'; $wire.set('selectedPreviewTemplateId', {{ $plantilla->id }});" class="text-xs text-indigo-600 hover:underline">
                                            👁️ Probar
                                        </button>

                                        @if(in_array(auth()->user()->role, ['administrador', 'gestor']))
                                            <button wire:click="toggleActivo({{ $plantilla->id }})" class="text-xs text-amber-600 hover:underline">
                                                {{ $plantilla->activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                            <button wire:click="openEditModal({{ $plantilla->id }})" class="text-xs text-blue-600 hover:underline">
                                                Editar
                                            </button>
                                            <button onclick="confirm('¿Está seguro de eliminar esta plantilla?') || event.stopImmediatePropagation()" wire:click="deleteTemplate({{ $plantilla->id }})" class="text-xs text-red-600 hover:underline">
                                                Eliminar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-500">No se encontraron plantillas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: GENERADOR Y PREVISUALIZACIÓN -->
    <div x-show="activeTab === 'generador'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Input Selector Form -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <h3 class="font-bold text-lg text-gray-900 dark:text-white border-b pb-2">🎯 1. Selección de Destinatario</h3>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1">Seleccionar Cliente / Cuota Activa</label>
                    <select wire:model.live="selectedCuotaId" class="w-full text-xs p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Seleccione una cuota pendiente...</option>
                        @foreach($this->cuotasList as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->operacion->cliente->nombre }} {{ $c->operacion->cliente->apellido }} - Sol. #{{ $c->operacion->numero_solicitud }} (Cuota {{ $c->numero_cuota }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($suggestedCategory)
                    <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800/40 rounded-lg p-3 text-xs text-indigo-800 dark:text-indigo-300 space-y-1">
                        <span class="font-bold">💡 Sugerencia Automática:</span>
                        <p>Según el estado actual de la cuota, sugerimos usar la categoría: <strong class="uppercase text-indigo-900 dark:text-indigo-200 font-mono">{{ str_replace('_', ' ', $suggestedCategory) }}</strong></p>
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <h3 class="font-bold text-lg text-gray-900 dark:text-white border-b pb-2">📄 2. Selección de Plantilla</h3>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1">Plantilla de Mensaje</label>
                    <select wire:model.live="selectedPreviewTemplateId" class="w-full text-xs p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Seleccione una plantilla activa...</option>
                        @foreach($plantillas->where('activo', true) as $p)
                            <option value="{{ $p->id }}">{{ $p->titulo }} ({{ $p->categoria }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Output Preview Panel -->
        <div class="lg:col-span-2 space-y-6">
            @if($previewMessage)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-6">
                    <div class="flex justify-between items-center border-b pb-3">
                        <h3 class="font-extrabold text-lg text-gray-950 dark:text-white">👀 3. Previsualización y Edición</h3>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-green-600 bg-green-50 px-2 py-0.5 rounded border border-green-200">
                            Variables Reemplazadas
                        </span>
                    </div>

                    <!-- Warnings list -->
                    @if(!empty($warnings))
                        <div class="space-y-2">
                            @foreach($warnings as $warn)
                                <div class="bg-amber-50 dark:bg-amber-950/20 border-l-4 border-amber-500 p-3 text-xs text-amber-800 dark:text-amber-400 font-medium">
                                    ⚠️ {{ $warn }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Original Read-Only Preview -->
                    <div>
                        <span class="block text-xs font-semibold text-gray-400 mb-1">Vista previa original (Generada por la plantilla)</span>
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border text-xs text-gray-600 whitespace-pre-line leading-relaxed">
                            {{ $previewMessage }}
                        </div>
                    </div>

                    <!-- Manual Customization Textarea -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1">✍️ Personalizar mensaje (No modifica la plantilla original)</label>
                        <textarea wire:model.live="manualMessage" rows="8" class="w-full text-xs p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-medium focus:border-indigo-500 focus:ring-indigo-500 leading-relaxed"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-3 items-center justify-between pt-4 border-t">
                        <div class="flex gap-2">
                            <!-- Copy Button -->
                            <button onclick="navigator.clipboard.writeText($wire.manualMessage); alert('¡Mensaje personalizado copiado al portapapeles!')" class="inline-flex justify-center items-center gap-1.5 px-4 py-2.5 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                                📋 Copiar Mensaje
                            </button>

                            <!-- WhatsApp Button -->
                            @if($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" class="inline-flex justify-center items-center gap-1.5 px-4 py-2.5 text-xs font-bold text-white bg-green-500 rounded-lg hover:bg-green-600 transition shadow">
                                    💬 Abrir WhatsApp
                                </a>
                            @else
                                <span class="inline-flex justify-center items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-red-800 bg-red-50 border border-red-200 rounded-lg">
                                    ❌ El cliente no tiene un número de teléfono válido.
                                </span>
                            @endif
                        </div>

                        <!-- Record Communication Button -->
                        <button wire:click="openGestionModal" class="inline-flex justify-center items-center gap-1.5 px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow">
                            📝 Registrar Gestión
                        </button>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center text-gray-400">
                    <span class="text-4xl block mb-2">✉️</span>
                    <p class="font-medium text-sm">Seleccione un cliente y una plantilla a la izquierda para previsualizar el mensaje.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL OVERLAY: CREATE/EDIT TEMPLATE -->
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" wire:key="form-modal">
            <div class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700 flex flex-col md:flex-row h-[90vh] md:h-auto max-h-[90vh]">

                <!-- Main Form Body -->
                <div class="flex-1 p-6 overflow-y-auto space-y-4">
                    <div class="flex justify-between items-center border-b pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $isEditing ? '📝 Editar Plantilla de Mensaje' : '➕ Crear Nueva Plantilla' }}
                        </h3>
                        <button wire:click="showFormModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg">✕</button>
                    </div>

                    <form wire:submit.prevent="saveTemplate" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título de la Plantilla</label>
                            <input wire:model="titulo" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Ej: Aviso Vence Hoy de Pago">
                            @error('titulo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</label>
                                <select wire:model="categoria" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="proximo_vencimiento">Próximo vencimiento</option>
                                    <option value="vence_hoy">Vence hoy</option>
                                    <option value="cuota_vencida">Cuota vencida</option>
                                    <option value="link_pago">Link de pago</option>
                                    <option value="promesa_pago">Promesa de pago</option>
                                    <option value="seguimiento_promesa">Seguimiento de promesa</option>
                                    <option value="cobrador">Cobrador</option>
                                    <option value="sin_respuesta">Sin respuesta</option>
                                    <option value="pago_pendiente">Pago pendiente</option>
                                    <option value="pago_parcial">Pago parcial</option>
                                    <option value="recordatorio_pago">Recordatorio de pago</option>
                                    <option value="seguimiento_general">Seguimiento general</option>
                                </select>
                            </div>
                            <div class="flex items-center pt-6 pl-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="activo" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400 font-medium">Plantilla Activa para uso</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cuerpo del Mensaje (Soporta Variables)</label>
                            <textarea wire:model.live="cuerpo" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono text-xs" placeholder="Escriba el mensaje utilizando {nombre}, {importe}, etc."></textarea>
                            @error('cuerpo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Warnings on Unknown Variables -->
                        @if(!empty($unknownVariablesWarning))
                            <div class="bg-amber-50 dark:bg-amber-950/20 border-l-4 border-amber-500 p-3 text-xs text-amber-800 dark:text-amber-400 space-y-2">
                                <p class="font-bold">⚠️ Se detectaron variables no reconocidas:</p>
                                <ul class="list-disc pl-4 font-mono">
                                    @foreach($unknownVariablesWarning as $un)
                                        <li>{{ $un }}</li>
                                    @endforeach
                                </ul>
                                <label class="inline-flex items-center cursor-pointer mt-2 bg-amber-100 p-2 rounded">
                                    <input type="checkbox" wire:model="confirmSaveUnknown" class="rounded border-gray-300 text-amber-600">
                                    <span class="ml-2 font-bold text-amber-950">Confirmar guardar con estas variables de todas formas</span>
                                </label>
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button" wire:click="showFormModal = false" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Guardar Plantilla</button>
                        </div>
                    </form>
                </div>

                <!-- Copiable Variables Sidebar -->
                <div class="w-full md:w-80 bg-gray-50 dark:bg-gray-900 p-6 border-t md:border-t-0 md:border-l border-gray-100 dark:border-gray-700 overflow-y-auto">
                    <h4 class="font-bold text-sm text-gray-800 dark:text-gray-300 mb-3 uppercase tracking-wider">💡 Variables Disponibles</h4>
                    <p class="text-[10px] text-gray-500 mb-4">Haga clic en una variable para insertarla o copiarla.</p>
                    <div class="space-y-3 text-xs">
                        @foreach($availableVars as $var => $desc)
                            <div class="p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 cursor-pointer hover:bg-indigo-50/50 transition group" onclick="navigator.clipboard.writeText('{{ $var }}'); alert('¡Copiado: {{ $var }}!');">
                                <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 block group-hover:underline">{{ $var }}</span>
                                <span class="text-[10px] text-gray-500 block mt-0.5">{{ $desc }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL OVERLAY: RECORD GESTION -->
    @if($showGestionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" wire:key="gestion-modal">
            <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">📝 Registrar Gestión de Comunicación</h3>
                    <button wire:click="showGestionModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg">✕</button>
                </div>

                <div class="p-6">
                    <form wire:submit.prevent="submitGestion" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Gestión</label>
                            <select wire:model="gestion_tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="whatsapp_enviado">WhatsApp enviado</option>
                                <option value="whatsapp_respondido">WhatsApp respondido</option>
                                <option value="llamada">Llamada realizada</option>
                                <option value="no_atendio">No atendió</option>
                                <option value="sin_respuesta">Cliente sin respuesta</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resultado</label>
                            <select wire:model="gestion_resultado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="mensaje_enviado">Mensaje enviado</option>
                                <option value="contactado">Contactado</option>
                                <option value="no_atendio">No atendió</option>
                                <option value="sin_respuesta">Sin respuesta</option>
                                <option value="prometio_pagar">Prometió pagar</option>
                                <option value="solicito_link_pago">Solicitó link de pago</option>
                                <option value="solicito_cobrador">Solicitó cobrador</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observación</label>
                            <textarea wire:model="gestion_observacion" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Ej: Enviado aviso de vencimiento por WhatsApp..."></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button" wire:click="showGestionModal = false" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Registrar Gestión</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

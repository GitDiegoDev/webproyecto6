<div class="space-y-8" x-data="{ activeTab: 'gestionar' }">
    <!-- Header Controls -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Panel Principal de Cobranzas</h2>
            <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                <span>📅 Fecha Actual: <strong class="text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($referenceDate)->format('d/m/Y') }}</strong></span>
                <span class="hidden md:inline">•</span>
                <span>👤 Usuario: <strong class="text-gray-700 dark:text-gray-300">{{ auth()->user()?->name ?? 'John Gestor' }} ({{ auth()->user()?->role ?? 'gestor' }})</strong></span>
                <span class="hidden md:inline">•</span>
                <span>📂 Período:
                    @if($activePeriod)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-400">
                            {{ $activePeriod->nombre }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400">
                            No hay período activo
                        </span>
                    @endif
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="refreshData" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                🔄 Actualizar
            </button>
            <a href="/importar" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition shadow">
                📥 Importar
            </a>
        </div>
    </div>

    <!-- Alert Success / Fail -->
    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-200 dark:border-green-800 flex items-center justify-between" role="alert">
            <span class="font-medium">✨ {{ session('success') }}</span>
            <button type="button" class="text-green-800 dark:text-green-400 hover:opacity-75 font-bold" onclick="this.parentElement.remove()">✕</button>
        </div>
    @endif

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Monthly Collection Card -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-start">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Cobranza del Mes</span>
                <span class="text-2xl">📊</span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($this->metrics['recaudado'], 2) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">recaudado de ${{ number_format($this->metrics['objetivo'], 2) }}</div>
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full mt-3 overflow-hidden">
                    <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $this->metrics['cumplimiento_porcentaje']) }}%"></div>
                </div>
                <div class="flex justify-between items-center mt-2 text-xs font-semibold">
                    <span class="text-indigo-600 dark:text-indigo-400">{{ $this->metrics['cumplimiento_porcentaje'] }}% del objetivo</span>
                    <span class="text-gray-600 dark:text-gray-300">Pendiente: ${{ number_format($this->metrics['total_potencial_pendiente'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Pending / Overdue Cuotas Card -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-start">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Cuotas & Vencidos</span>
                <span class="text-2xl">⏳</span>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Pendientes Periodo:</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->metrics['cuotas_pendientes'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-red-600 dark:text-red-400 font-medium">Vencidas:</span>
                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ $this->metrics['vencidas'] }}</span>
                </div>
                <div class="flex justify-between items-center text-xs text-amber-600 dark:text-amber-400 pt-1 border-t border-gray-100 dark:border-gray-700">
                    <span>Punitorios Perdonados:</span>
                    <span class="font-bold">${{ number_format($this->metrics['punitorios_perdonados'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Promises Card -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-start">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Promesas de Pago</span>
                <span class="text-2xl">🤝</span>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">Para Hoy:</span>
                    <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $this->metrics['promesas_hoy'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-red-600 dark:text-red-400 font-medium">Incumplidas:</span>
                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ $this->metrics['promesas_incumplidas'] }}</span>
                </div>
                <div class="flex justify-between items-center pt-1 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500">Sin Respuesta:</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->metrics['sin_respuesta'] }}</span>
                </div>
            </div>
        </div>

        <!-- Collector Visits Card -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-start">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Visitas del Cobrador</span>
                <span class="text-2xl">🚴</span>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Programadas Hoy:</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->metrics['visitas_hoy'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Pendientes:</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->metrics['visitas_pendientes'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-emerald-600 dark:text-emerald-400">Realizadas:</span>
                    <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $this->metrics['visitas_realizadas'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-gray-200 dark:border-gray-700">
        <button @click="activeTab = 'gestionar'" :class="activeTab === 'gestionar' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition">
            📋 Gestionar Hoy
        </button>
        <button @click="activeTab = 'promesas'" :class="activeTab === 'promesas' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition flex items-center gap-2">
            🤝 Promesas para Hoy
            @if($this->promesasParaHoy->count() > 0)
                <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $this->promesasParaHoy->count() }}</span>
            @endif
        </button>
        <button @click="activeTab = 'incumplidas'" :class="activeTab === 'incumplidas' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition flex items-center gap-2">
            ⚠️ Promesas Incumplidas
            @if($this->promesasIncumplidas->count() > 0)
                <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $this->promesasIncumplidas->count() }}</span>
            @endif
        </button>
        <button @click="activeTab = 'sin_respuesta'" :class="activeTab === 'sin_respuesta' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition flex items-center gap-2">
            🔇 Sin Respuesta
            @if($this->sinRespuesta->count() > 0)
                <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $this->sinRespuesta->count() }}</span>
            @endif
        </button>
        <button @click="activeTab = 'cobrador'" :class="activeTab === 'cobrador' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition flex items-center gap-2">
            🚴 Cobrador
            @if($this->visitasDelDia->count() > 0)
                <span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $this->visitasDelDia->count() }}</span>
            @endif
        </button>
    </div>

    <!-- Active Tab Panel -->
    <div>
        <!-- TAB: Gestionar Hoy -->
        <div x-show="activeTab === 'gestionar'" class="space-y-6">
            <!-- Search and Filter Bar -->
            <div class="flex flex-col lg:flex-row gap-4 justify-between items-center bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <!-- Global Search -->
                <div class="w-full lg:max-w-md relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">🔍</span>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por nombre, documento, teléfono, solicitud..." class="w-full pl-9 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Filters -->
                <div class="w-full lg:w-auto flex flex-wrap gap-2 items-center justify-end">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400 mr-2">Filtrar:</span>
                    <select wire:model.live="filter" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="todas">Todas</option>
                        <option value="criticas">🔴 Críticas</option>
                        <option value="altas">🟠 Altas</option>
                        <option value="medias">🟡 Medias</option>
                        <option value="bajas">🟢 Bajas</option>
                        <option value="vencidas">Vencidas</option>
                        <option value="vence_hoy">Vence Hoy</option>
                        <option value="promesa_hoy">Promesa Hoy</option>
                        <option value="promesa_incumplida">Promesa Incumplida</option>
                        <option value="sin_respuesta">Sin Respuesta</option>
                        <option value="con_visita">Con Visita Cobrador</option>
                    </select>
                </div>
            </div>

            <!-- List Grid/Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <th class="p-4">Prioridad</th>
                                <th class="p-4">Cliente / Solicitud / Cuota</th>
                                <th class="p-4">Día de Cobro</th>
                                <th class="p-4 text-right">Imp. Original</th>
                                <th class="p-4 text-right">Punitorios</th>
                                <th class="p-4 text-right">Saldo Pendiente</th>
                                <th class="p-4">Estado</th>
                                <th class="p-4 text-center">Acciones Rápidas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($this->cuotas as $cuota)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <!-- Priority Badge -->
                                    <td class="p-4 whitespace-nowrap">
                                        @if($cuota->computed_priority === 'critica')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400">🔴 Crítica</span>
                                        @elseif($cuota->computed_priority === 'alta')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400">🟠 Alta</span>
                                        @elseif($cuota->computed_priority === 'media')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400">🟡 Media</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400">🟢 Baja</span>
                                        @endif
                                    </td>
                                    <!-- Client Details -->
                                    <td class="p-4">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ $cuota->operacion->cliente->nombre }} {{ $cuota->operacion->cliente->apellido }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Sol. #{{ $cuota->operacion->numero_solicitud }} • Cuota {{ $cuota->numero_cuota }}
                                        </div>
                                        @if($cuota->operacion->cliente->telefono)
                                            <div class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                                                📞 {{ $cuota->operacion->cliente->telefono }}
                                            </div>
                                        @endif
                                    </td>
                                    <!-- Due Day -->
                                    <td class="p-4 whitespace-nowrap font-medium">
                                        Día {{ $cuota->dia_cobro }}
                                    </td>
                                    <!-- Original Imp -->
                                    <td class="p-4 whitespace-nowrap text-right font-semibold text-gray-900 dark:text-white">
                                        ${{ number_format($cuota->importe_original, 2) }}
                                    </td>
                                    <!-- Punitorios -->
                                    <td class="p-4 whitespace-nowrap text-right text-red-600 dark:text-red-400 font-medium">
                                        ${{ number_format($cuota->punitorios, 2) }}
                                    </td>
                                    <!-- Saldo Pendiente -->
                                    <td class="p-4 whitespace-nowrap text-right font-bold text-indigo-600 dark:text-indigo-400">
                                        ${{ number_format($cuota->saldo_pendiente, 2) }}
                                    </td>
                                    <!-- State -->
                                    <td class="p-4 whitespace-nowrap">
                                        @if($cuota->computed_status === 'vencida')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400">Vencida</span>
                                        @elseif($cuota->computed_status === 'vence_hoy')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400">Vence Hoy</span>
                                        @elseif($cuota->computed_status === 'promesa_pago')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400">Promesa Activa</span>
                                        @elseif($cuota->computed_status === 'promesa_incumplida')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-200 text-red-900 dark:bg-red-900 dark:text-red-300">Promesa Incumplida</span>
                                        @elseif($cuota->computed_status === 'sin_respuesta')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Sin Respuesta</span>
                                        @elseif($cuota->computed_status === 'pago_realizado_oficial' || $cuota->computed_status === 'pago_registrado')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400">Cobrado</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $cuota->computed_status }}</span>
                                        @endif
                                    </td>
                                    <!-- Actions -->
                                    <td class="p-4 text-center">
                                        <div class="flex flex-wrap justify-center gap-1.5">
                                            <!-- WhatsApp -->
                                            @if($cuota->operacion->cliente->telefono)
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $cuota->operacion->cliente->telefono) }}" target="_blank" title="Enviar WhatsApp" class="px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded shadow transition">
                                                    💬 WA
                                                </a>
                                            @endif

                                            <!-- Registrar Gestión -->
                                            <button wire:click="openGestionModal({{ $cuota->id }})" title="Registrar Gestión" class="px-2 py-1 bg-gray-600 hover:bg-gray-700 text-white text-xs font-semibold rounded shadow transition">
                                                📝 Gestión
                                            </button>

                                            <!-- Registrar Promesa -->
                                            @if($cuota->saldo_pendiente > 0)
                                                <button wire:click="openPromesaModal({{ $cuota->id }})" title="Registrar Promesa" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded shadow transition">
                                                    🤝 Promesa
                                                </button>
                                            @endif

                                            <!-- Programar Cobrador -->
                                            @if($cuota->saldo_pendiente > 0)
                                                <button wire:click="openCobradorModal({{ $cuota->id }})" title="Programar Cobrador" class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded shadow transition">
                                                    🚴 Cobrador
                                                </button>
                                            @endif

                                            <!-- Registrar Pago -->
                                            @if($cuota->saldo_pendiente > 0)
                                                <button wire:click="openPagoModal({{ $cuota->id }})" title="Registrar Pago" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded shadow transition">
                                                    💵 Pago
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        <span class="text-3xl block mb-2">🎉</span> No hay clientes pendientes de atención en este momento.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: Promesas para Hoy -->
        <div x-show="activeTab === 'promesas'" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Promesas para Hoy</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <th class="p-4">Cliente</th>
                                <th class="p-4">Solicitud / Cuota</th>
                                <th class="p-4 text-right">Monto Prometido</th>
                                <th class="p-4">Fecha Prometida</th>
                                <th class="p-4">Observaciones</th>
                                <th class="p-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($this->promesasParaHoy as $promesa)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4 font-bold text-gray-900 dark:text-white">
                                        {{ $promesa->cuota->operacion->cliente->nombre }} {{ $promesa->cuota->operacion->cliente->apellido }}
                                    </td>
                                    <td class="p-4 text-gray-500 dark:text-gray-400">
                                        Sol. #{{ $promesa->cuota->operacion->numero_solicitud }} • Cuota {{ $promesa->cuota->numero_cuota }}
                                    </td>
                                    <td class="p-4 text-right font-bold text-indigo-600 dark:text-indigo-400">
                                        ${{ number_format($promesa->monto_prometido, 2) }}
                                    </td>
                                    <td class="p-4 text-gray-700 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($promesa->fecha_prometida)->format('d/m/Y') }}
                                    </td>
                                    <td class="p-4 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                        {{ $promesa->observaciones }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button wire:click="openPagoModal({{ $promesa->cuota_id }})" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded shadow transition">Marcar Pago</button>
                                            <button wire:click="openGestionModal({{ $promesa->cuota_id }})" class="px-2 py-1 bg-gray-600 hover:bg-gray-700 text-white text-xs font-semibold rounded shadow transition">Registrar Gestión</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        🎉 No hay promesas de pago programadas para hoy.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: Promesas Incumplidas -->
        <div x-show="activeTab === 'incumplidas'" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Compromisos Incumplidos</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <th class="p-4">Cliente</th>
                                <th class="p-4">Solicitud / Cuota</th>
                                <th class="p-4">Fecha Prometida</th>
                                <th class="p-4 text-right">Monto Prometido</th>
                                <th class="p-4 text-right">Días transcurridos</th>
                                <th class="p-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($this->promesasIncumplidas as $promesa)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4 font-bold text-gray-900 dark:text-white">
                                        {{ $promesa->cuota->operacion->cliente->nombre }} {{ $promesa->cuota->operacion->cliente->apellido }}
                                    </td>
                                    <td class="p-4 text-gray-500 dark:text-gray-400">
                                        Sol. #{{ $promesa->cuota->operacion->numero_solicitud }} • Cuota {{ $promesa->cuota->numero_cuota }}
                                    </td>
                                    <td class="p-4 text-red-600 dark:text-red-400">
                                        {{ \Carbon\Carbon::parse($promesa->fecha_prometida)->format('d/m/Y') }}
                                    </td>
                                    <td class="p-4 text-right font-bold">
                                        ${{ number_format($promesa->monto_prometido, 2) }}
                                    </td>
                                    <td class="p-4 text-right font-mono font-bold text-red-600">
                                        {{ max(0, \Carbon\Carbon::parse($promesa->fecha_prometida)->diffInDays(\Carbon\Carbon::parse($referenceDate))) }} días
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button wire:click="openGestionModal({{ $promesa->cuota_id }})" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded shadow transition">Volver a contactar</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        🎉 No se registran promesas incumplidas en este período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: Sin Respuesta -->
        <div x-show="activeTab === 'sin_respuesta'" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Clientes Sin Respuesta</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <th class="p-4">Cliente</th>
                                <th class="p-4">Teléfono</th>
                                <th class="p-4">Solicitud / Cuota</th>
                                <th class="p-4 text-center">Intentos de Gestión</th>
                                <th class="p-4">Último Contacto</th>
                                <th class="p-4 text-center">Acción Recomendada</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($this->sinRespuesta as $cuota)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4 font-bold text-gray-900 dark:text-white">
                                        {{ $cuota->operacion->cliente->nombre }} {{ $cuota->operacion->cliente->apellido }}
                                    </td>
                                    <td class="p-4 font-mono">
                                        {{ $cuota->operacion->cliente->telefono ?? 'Sin teléfono' }}
                                    </td>
                                    <td class="p-4 text-gray-500 dark:text-gray-400">
                                        Sol. #{{ $cuota->operacion->numero_solicitud }} • Cuota {{ $cuota->numero_cuota }}
                                    </td>
                                    <td class="p-4 text-center font-bold">
                                        {{ $cuota->gestiones->count() }}
                                    </td>
                                    <td class="p-4 text-gray-500">
                                        @if($cuota->gestiones->last())
                                            {{ \Carbon\Carbon::parse($cuota->gestiones->last()->fecha_hora)->format('d/m/Y H:i') }}
                                        @else
                                            No contactado
                                        @endif
                                    </td>
                                    <td class="p-4 text-center">
                                        <button wire:click="openCobradorModal({{ $cuota->id }})" class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded shadow transition">
                                            🚴 Programar cobrador
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        🎉 Todos los clientes están respondiendo activamente.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: Cobrador -->
        <div x-show="activeTab === 'cobrador'" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Visitas de Cobrador Programadas</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <th class="p-4">Cliente</th>
                                <th class="p-4">Domicilio</th>
                                <th class="p-4">Cobrador Asignado</th>
                                <th class="p-4">Estado</th>
                                <th class="p-4 text-right">Monto Pendiente</th>
                                <th class="p-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($this->visitasDelDia as $visita)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4 font-bold text-gray-900 dark:text-white">
                                        {{ $visita->cliente->nombre }} {{ $visita->cliente->apellido }}
                                    </td>
                                    <td class="p-4 text-xs text-gray-600 dark:text-gray-300">
                                        {{ $visita->domicilio }}
                                    </td>
                                    <td class="p-4 font-medium">
                                        {{ $visita->cobrador->name }}
                                    </td>
                                    <td class="p-4">
                                        @if($visita->estado === 'pendiente')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Pendiente</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">Realizada</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right font-bold text-indigo-600">
                                        ${{ number_format($visita->cuota ? $visita->cuota->saldo_pendiente : 0, 2) }}
                                    </td>
                                    <td class="p-4 text-center">
                                        @if($visita->estado === 'pendiente')
                                            <button wire:click="openVisitaResultadoModal({{ $visita->id }})" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded shadow transition">Registrar resultado</button>
                                        @else
                                            <span class="text-xs text-gray-500">Resultado: {{ $visita->resultado }} (${{ number_format($visita->monto_cobrado, 2) }})</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        🎉 No hay visitas programadas para la fecha seleccionada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL OVERLAYS (Alpine + Tailwind) -->
    @if($activeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" wire:key="modal-container">
            <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <!-- Modal Header -->
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        @if($activeModal === 'gestion') Registrar Gestión
                        @elseif($activeModal === 'promesa') Registrar Promesa de Pago
                        @elseif($activeModal === 'cobrador') Programar Visita de Cobrador
                        @elseif($activeModal === 'pago') Registrar Pago Recibido
                        @elseif($activeModal === 'visita_resultado') Registrar Resultado de Visita
                        @endif
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 font-bold text-lg">✕</button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Dynamic form based on activeModal -->

                    <!-- 1. GESTIÓN -->
                    @if($activeModal === 'gestion')
                        <form wire:submit.prevent="submitGestion" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Gestión</label>
                                <select wire:model="gestion_tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="whatsapp_enviado">WhatsApp enviado</option>
                                    <option value="whatsapp_respondido">WhatsApp respondido</option>
                                    <option value="llamada">Llamada realizada</option>
                                    <option value="no_atendio">No atendió</option>
                                    <option value="sin_respuesta">Cliente sin respuesta</option>
                                    <option value="visita_cobrador">Visita de cobrador</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resultado</label>
                                <select wire:model="gestion_resultado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="contactado">Contactado</option>
                                    <option value="prometio_pagar">Cliente prometió pagar</option>
                                    <option value="no_atendio">No atendió</option>
                                    <option value="sin_respuesta">Sin respuesta</option>
                                    <option value="solicito_cobrador">Solicitó cobrador</option>
                                    <option value="promesa_incumplida">Promesa incumplida</option>
                                    <option value="seguimiento">Seguimiento</option>
                                    <option value="cliente_pago">Cliente pagó</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observación</label>
                                <textarea wire:model="gestion_observacion" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Detalles de la gestión..."></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Próxima Acción (opcional)</label>
                                    <input wire:model="gestion_proxima_accion" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Ej: Volver a llamar">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Próxima Acción</label>
                                    <input wire:model="gestion_proxima_accion_fecha" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Guardar</button>
                            </div>
                        </form>
                    @endif

                    <!-- 2. PROMESA -->
                    @if($activeModal === 'promesa')
                        <form wire:submit.prevent="submitPromesa" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Prometida</label>
                                <input wire:model="promesa_fecha_prometida" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('promesa_fecha_prometida') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto Prometido</label>
                                <input wire:model="promesa_monto_prometido" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('promesa_monto_prometido') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                <textarea wire:model="promesa_observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Ej: Pasará por la oficina..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Guardar</button>
                            </div>
                        </form>
                    @endif

                    <!-- 3. COBRADOR -->
                    @if($activeModal === 'cobrador')
                        <form wire:submit.prevent="submitCobrador" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seleccionar Cobrador</label>
                                <select wire:model="cobrador_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Seleccione un cobrador...</option>
                                    @foreach($this->cobradores as $cob)
                                        <option value="{{ $cob->id }}">{{ $cob->name }}</option>
                                    @endforeach
                                </select>
                                @error('cobrador_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Programada</label>
                                <input wire:model="cobrador_fecha_programada" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('cobrador_fecha_programada') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                <textarea wire:model="cobrador_observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Indicaciones para el domicilio..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Programar</button>
                            </div>
                        </form>
                    @endif

                    <!-- 4. PAGO -->
                    @if($activeModal === 'pago')
                        <form wire:submit.prevent="submitPago" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto Cobrado (Efectivo/Transferido)</label>
                                <input wire:model="pago_monto_cobrado" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('pago_monto_cobrado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Punitorios Condonados / Perdonados</label>
                                <input wire:model="pago_punitorios_perdonados" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('pago_punitorios_perdonados') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Medio de Pago</label>
                                <select wire:model="pago_medio_pago" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="transferencia">Transferencia</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="cobrador">Cobrador</option>
                                    <option value="tarjeta">Tarjeta</option>
                                </select>
                                @error('pago_medio_pago') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                <textarea wire:model="pago_observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Nro de comprobante u otros detalles..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Guardar Pago</button>
                            </div>
                        </form>
                    @endif

                    <!-- 5. RESULTADO DE VISITA -->
                    @if($activeModal === 'visita_resultado')
                        <form wire:submit.prevent="submitVisitaResultado" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resultado de la Visita</label>
                                <select wire:model="visita_resultado_tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="cobrado">Cobrado</option>
                                    <option value="cobrado_parcialmente">Cobrado parcialmente</option>
                                    <option value="no_estaba">No estaba</option>
                                    <option value="no_se_pudo_contactar">No se pudo contactar</option>
                                    <option value="reprogramar">Reprogramar</option>
                                    <option value="se_nego_a_pagar">Se negó a pagar</option>
                                    <option value="domicilio_incorrecto">Domicilio incorrecto</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto Cobrado (si corresponde)</label>
                                <input wire:model="visita_monto_cobrado" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('visita_monto_cobrado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                <textarea wire:model="visita_observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Detalles de la visita..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Guardar Resultado</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<div class="space-y-8" x-data="{ activeTab: 'gestionar' }">
    <!-- Header Controls -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>🗓️</span> Agenda de Cobranza
            </h1>
            <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                <span>📅 Fecha Actual: <strong class="text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($referenceDate)->format('d/m/Y') }}</strong></span>
                <span class="hidden sm:inline">•</span>
                <span>👤 Usuario: <strong class="text-gray-700 dark:text-gray-300">{{ auth()->user()?->name ?? 'John Gestor' }} ({{ auth()->user()?->role ?? 'gestor' }})</strong></span>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
            <!-- HOY vs PRÓXIMOS DÍAS Toggle -->
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <button type="button" wire:click="$set('viewMode', 'hoy')" class="px-4 py-2 text-sm font-semibold rounded-l-lg border transition-all duration-150 {{ $viewMode === 'hoy' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600' }}">
                    HOY
                </button>
                <button type="button" wire:click="$set('viewMode', 'proximos_dias')" class="px-4 py-2 text-sm font-semibold rounded-r-lg border transition-all duration-150 {{ $viewMode === 'proximos_dias' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600' }}">
                    PRÓXIMOS DÍAS
                </button>
            </div>
        </div>
    </div>

    <!-- Alert Success / Fail -->
    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-200 dark:border-green-800 flex items-center justify-between" role="alert">
            <span class="font-medium">✨ {{ session('success') }}</span>
            <button type="button" class="text-green-800 dark:text-green-400 hover:opacity-75 font-bold" onclick="this.parentElement.remove()">✕</button>
        </div>
    @endif

    <!-- Dynamic KPI Metrics Cards (Requirement 3) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
        <!-- Metric Card 1 -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Gestiones Pendientes</span>
            <div class="flex items-baseline justify-between mt-2">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->metrics['total_gestiones_pendientes'] }}</span>
                <span class="text-lg">📋</span>
            </div>
        </div>
        <!-- Metric Card 2 -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Promesas Hoy</span>
            <div class="flex items-baseline justify-between mt-2">
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $this->metrics['promesas_hoy'] }}</span>
                <span class="text-lg">🤝</span>
            </div>
        </div>
        <!-- Metric Card 3 -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Incumplidas</span>
            <div class="flex items-baseline justify-between mt-2">
                <span class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->metrics['promesas_incumplidas'] }}</span>
                <span class="text-lg">⚠️</span>
            </div>
        </div>
        <!-- Metric Card 4 -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Cuotas Vencidas</span>
            <div class="flex items-baseline justify-between mt-2">
                <span class="text-2xl font-bold text-amber-600 dark:text-amber-500">{{ $this->metrics['cuotas_vencidas'] }}</span>
                <span class="text-lg">⏳</span>
            </div>
        </div>
        <!-- Metric Card 5 -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Vencen Hoy</span>
            <div class="flex items-baseline justify-between mt-2">
                <span class="text-2xl font-bold text-orange-500">{{ $this->metrics['cuotas_vencen_hoy'] }}</span>
                <span class="text-lg">📅</span>
            </div>
        </div>
        <!-- Metric Card 6 -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Sin Respuesta</span>
            <div class="flex items-baseline justify-between mt-2">
                <span class="text-2xl font-bold text-gray-500 dark:text-gray-400">{{ $this->metrics['clientes_sin_respuesta'] }}</span>
                <span class="text-lg">🔇</span>
            </div>
        </div>
        <!-- Metric Card 7 -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Visitas Hoy</span>
            <div class="flex items-baseline justify-between mt-2">
                <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-500">{{ $this->metrics['visitas_hoy'] }}</span>
                <span class="text-lg">🚴</span>
            </div>
        </div>
    </div>

    <!-- Layout Container (Always keep list of filters and global reactive search active) -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col lg:flex-row gap-4 justify-between items-center">
            <!-- Global Search Box with Debounce -->
            <div class="w-full lg:max-w-md relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">🔍</span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por nombre, solicitud, teléfono, cuota..." class="w-full pl-9 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Reactive Filters Selector -->
            <div class="w-full lg:w-auto flex flex-wrap gap-2 items-center justify-end">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 mr-2">Filtrar:</span>
                <select wire:model.live="filter" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="todos">Todos</option>
                    <option value="criticos">🔴 Críticos</option>
                    <option value="alta">🟠 Alta prioridad</option>
                    <option value="media">🟡 Media prioridad</option>
                    <option value="baja">🟢 Baja prioridad</option>
                    <option value="vencidos">⏳ Vencidos</option>
                    <option value="vence_hoy">📅 Vencen hoy</option>
                    <option value="promesas_hoy">🤝 Promesas para hoy</option>
                    <option value="promesas_incumplidas">⚠️ Promesas incumplidas</option>
                    <option value="sin_respuesta">🔇 Sin respuesta</option>
                    <option value="con_visita">🚴 Con visita de cobrador</option>
                    <option value="proximas_acciones">📅 Próximas acciones</option>
                </select>
                <!-- Loading Indicator -->
                <div wire:loading class="text-indigo-600 dark:text-indigo-400 font-medium text-xs flex items-center gap-1">
                    <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Procesando...
                </div>
            </div>
        </div>
    </div>

    @if($viewMode === 'hoy')
        <!-- ==========================================
             VIEW MODE: HOY (Default Workday View)
             ========================================== -->

        <!-- Section: Navigation tabs for specific groupings -->
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
                @if($this->clientesSinRespuesta->count() > 0)
                    <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $this->clientesSinRespuesta->count() }}</span>
                @endif
            </button>
            <button @click="activeTab = 'cobrador'" :class="activeTab === 'cobrador' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition flex items-center gap-2">
                🚴 Visitas de Hoy
                @if($this->visitasDelDia->count() > 0)
                    <span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $this->visitasDelDia->count() }}</span>
                @endif
            </button>
            <button @click="activeTab = 'proximas_acciones'" :class="activeTab === 'proximas_acciones' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition flex items-center gap-2">
                📅 Segumientos Pendientes
                @if($this->proximasAcciones->count() > 0)
                    <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $this->proximasAcciones->count() }}</span>
                @endif
            </button>
        </div>

        <div>
            <!-- TAB: Gestionar Hoy -->
            <div x-show="activeTab === 'gestionar'" class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <!-- Responsive Layout: Grid Cards on Mobile, Table on Desktop -->
                    <div class="block lg:hidden divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($this->gestionarHoy as $cuota)
                            <div class="p-4 space-y-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">{{ $cuota->operacion->cliente->nombre }} {{ $cuota->operacion->cliente->apellido }}</h4>
                                        <span class="text-xs text-gray-500">Sol. #{{ $cuota->operacion->numero_solicitud }} • Cuota {{ $cuota->numero_cuota }}</span>
                                    </div>
                                    <!-- Priority Badge -->
                                    <div>
                                        @if($cuota->computed_priority === 'critica')
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-red-100 text-red-800">🔴 Crítica</span>
                                        @elseif($cuota->computed_priority === 'alta')
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-amber-100 text-amber-800">🟠 Alta</span>
                                        @elseif($cuota->computed_priority === 'media')
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-blue-100 text-blue-800">🟡 Media</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-green-100 text-green-800">🟢 Baja</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div><strong>Día de Cobro:</strong> Día {{ $cuota->dia_cobro }}</div>
                                    <div><strong>Monto Original:</strong> ${{ number_format($cuota->importe_original, 2) }}</div>
                                    <div><strong>Punitorios:</strong> ${{ number_format($cuota->punitorios, 2) }}</div>
                                    <div class="text-indigo-600 font-semibold"><strong>Total:</strong> ${{ number_format($cuota->saldo_pendiente, 2) }}</div>
                                </div>
                                <div class="flex flex-wrap gap-1.5 pt-2">
                                    <a href="/ficha-gestion/{{ $cuota->id }}" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded shadow">Ver Ficha</a>
                                    <button wire:click="openGestionModal({{ $cuota->id }})" class="px-2.5 py-1 bg-gray-600 text-white text-xs font-bold rounded shadow">Gestión</button>
                                    <button wire:click="openPromesaModal({{ $cuota->id }})" class="px-2.5 py-1 bg-indigo-600 text-white text-xs font-bold rounded shadow">Promesa</button>
                                    <button wire:click="openCobradorModal({{ $cuota->id }})" class="px-2.5 py-1 bg-amber-500 text-white text-xs font-bold rounded shadow">Cobrador</button>
                                    <button wire:click="openPagoModal({{ $cuota->id }})" class="px-2.5 py-1 bg-emerald-600 text-white text-xs font-bold rounded shadow">Pago</button>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">No hay gestiones pendientes para hoy.</div>
                        @endforelse
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden lg:block overflow-x-auto">
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
                                @forelse($this->gestionarHoy as $cuota)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-4 whitespace-nowrap">
                                            @if($cuota->computed_priority === 'critica')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-red-100 text-red-800">🔴 Crítica</span>
                                            @elseif($cuota->computed_priority === 'alta')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800">🟠 Alta</span>
                                            @elseif($cuota->computed_priority === 'media')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800 font-semibold">🟡 Media</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-green-100 text-green-800">🟢 Baja</span>
                                            @endif
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-gray-900 dark:text-white">
                                                {{ $cuota->operacion->cliente->nombre }} {{ $cuota->operacion->cliente->apellido }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Sol. #{{ $cuota->operacion->numero_solicitud }} • Cuota {{ $cuota->numero_cuota }}
                                            </div>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">Día {{ $cuota->dia_cobro }}</td>
                                        <td class="p-4 whitespace-nowrap text-right">${{ number_format($cuota->importe_original, 2) }}</td>
                                        <td class="p-4 whitespace-nowrap text-right text-red-500">${{ number_format($cuota->punitorios, 2) }}</td>
                                        <td class="p-4 whitespace-nowrap text-right font-bold text-indigo-600">${{ number_format($cuota->saldo_pendiente, 2) }}</td>
                                        <td class="p-4 whitespace-nowrap">
                                            @if($cuota->computed_status === 'vencida')
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">Vencida</span>
                                            @elseif($cuota->computed_status === 'vence_hoy')
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Vence Hoy</span>
                                            @elseif($cuota->computed_status === 'promesa_pago')
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800">Promesa Activa</span>
                                            @elseif($cuota->computed_status === 'promesa_incumplida')
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-red-200 text-red-900">Promesa Incumplida</span>
                                            @elseif($cuota->computed_status === 'sin_respuesta')
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">Sin Respuesta</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-gray-200 text-gray-600">{{ $cuota->computed_status }}</span>
                                            @endif
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex flex-wrap justify-center gap-1.5">
                                                <a href="/ficha-gestion/{{ $cuota->id }}" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded shadow">🔍 Ficha</a>
                                                <button wire:click="openGestionModal({{ $cuota->id }})" class="px-2 py-1 bg-gray-600 hover:bg-gray-700 text-white text-xs font-bold rounded shadow">📝 Gestión</button>
                                                <button wire:click="openPromesaModal({{ $cuota->id }})" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded shadow">🤝 Promesa</button>
                                                <button wire:click="openCobradorModal({{ $cuota->id }})" class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded shadow">🚴 Cobrador</button>
                                                <button wire:click="openPagoModal({{ $cuota->id }})" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded shadow">💵 Pago</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-8 text-center text-gray-500">No hay gestiones pendientes para hoy.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Promesas para Hoy (Requirement 8) -->
            <div x-show="activeTab === 'promesas'" class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Promesas de Pago para Hoy</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="p-4">Cliente</th>
                                    <th class="p-4">Solicitud / Cuota</th>
                                    <th class="p-4 text-right">Monto Prometido</th>
                                    <th class="p-4">Fecha Prometida</th>
                                    <th class="p-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @forelse($this->promesasParaHoy as $promesa)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $promesa->cuota->operacion->cliente->nombre }} {{ $promesa->cuota->operacion->cliente->apellido }}</td>
                                        <td class="p-4 text-gray-500">Sol. #{{ $promesa->cuota->operacion->numero_solicitud }} • Cuota {{ $promesa->cuota->numero_cuota }}</td>
                                        <td class="p-4 text-right font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($promesa->monto_prometido, 2) }}</td>
                                        <td class="p-4">{{ \Carbon\Carbon::parse($promesa->fecha_prometida)->format('d/m/Y') }}</td>
                                        <td class="p-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button wire:click="openPagoModal({{ $promesa->cuota_id }})" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded shadow">💵 Pago</button>
                                                <button wire:click="openGestionModal({{ $promesa->cuota_id }})" class="px-2 py-1 bg-gray-600 hover:bg-gray-700 text-white text-xs font-bold rounded shadow">📝 Seguimiento</button>
                                                <a href="/ficha-gestion/{{ $promesa->cuota_id }}" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded shadow">🔍 Ficha</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-gray-500">No hay promesas pendientes para hoy.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Promesas Incumplidas (Requirement 9) -->
            <div x-show="activeTab === 'incumplidas'" class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Promesas Incumplidas</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="p-4">Días pasados</th>
                                    <th class="p-4">Cliente</th>
                                    <th class="p-4">Cuota</th>
                                    <th class="p-4 text-right">Monto Prometido</th>
                                    <th class="p-4">Fecha Prometida</th>
                                    <th class="p-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @forelse($this->promesasIncumplidas as $promesa)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="p-4 font-bold text-red-600">
                                            {{ max(0, \Carbon\Carbon::parse($promesa->fecha_prometida)->diffInDays(\Carbon\Carbon::parse($referenceDate))) }} días
                                        </td>
                                        <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $promesa->cuota->operacion->cliente->nombre }} {{ $promesa->cuota->operacion->cliente->apellido }}</td>
                                        <td class="p-4 text-gray-500">Cuota {{ $promesa->cuota->numero_cuota }}</td>
                                        <td class="p-4 text-right font-bold text-red-500">${{ number_format($promesa->monto_prometido, 2) }}</td>
                                        <td class="p-4 text-gray-500">{{ \Carbon\Carbon::parse($promesa->fecha_prometida)->format('d/m/Y') }}</td>
                                        <td class="p-4 text-center">
                                            <button wire:click="openGestionModal({{ $promesa->cuota_id }})" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded shadow">
                                                🚨 Gestionar ahora
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-gray-500">No hay promesas incumplidas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Sin Respuesta (Requirement 10) -->
            <div x-show="activeTab === 'sin_respuesta'" class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Clientes Sin Respuesta</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="p-4">Cliente</th>
                                    <th class="p-4">Cuota</th>
                                    <th class="p-4 text-center">Intentos de Gestión</th>
                                    <th class="p-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @forelse($this->clientesSinRespuesta as $cuota)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $cuota->operacion->cliente->nombre }} {{ $cuota->operacion->cliente->apellido }}</td>
                                        <td class="p-4 text-gray-500">Cuota {{ $cuota->numero_cuota }}</td>
                                        <td class="p-4 text-center font-bold">{{ $cuota->gestiones->count() }} intentos</td>
                                        <td class="p-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button wire:click="openGestionModal({{ $cuota->id }})" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded shadow">📞 Volver a contactar</button>
                                                <button wire:click="openCobradorModal({{ $cuota->id }})" class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded shadow">🚴 Programar cobrador</button>
                                                <a href="/ficha-gestion/{{ $cuota->id }}" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded shadow">🔍 Ver Ficha</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-gray-500">No hay clientes sin respuesta.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Visitas del Cobrador (Requirement 11) -->
            <div x-show="activeTab === 'cobrador'" class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Visitas de Hoy</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="p-4">Cliente</th>
                                    <th class="p-4">Domicilio</th>
                                    <th class="p-4">Cobrador Asignado</th>
                                    <th class="p-4">Estado / Resultado</th>
                                    <th class="p-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @forelse($this->visitasDelDia as $visita)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $visita->cliente->nombre }} {{ $visita->cliente->apellido }}</td>
                                        <td class="p-4 text-xs">{{ $visita->domicilio }}</td>
                                        <td class="p-4">{{ $visita->cobrador->name }}</td>
                                        <td class="p-4">
                                            @if($visita->estado === 'pendiente')
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Pendiente</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">Realizada: {{ $visita->resultado }}</span>
                                            @endif
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button wire:click="openVisitaResultadoModal({{ $visita->id }})" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded shadow">📝 Registrar resultado</button>
                                                @if($visita->cuota_id)
                                                    <button wire:click="openPagoModal({{ $visita->cuota_id }})" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded shadow">💵 Registrar pago</button>
                                                    <a href="/ficha-gestion/{{ $visita->cuota_id }}" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded shadow">🔍 Ver Ficha</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-gray-500">No hay visitas programadas para hoy.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Próximas Acciones (Requirement 12) -->
            <div x-show="activeTab === 'proximas_acciones'" class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Seguimientos de Hoy</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="p-4">Cliente</th>
                                    <th class="p-4">Acción</th>
                                    <th class="p-4">Fecha Acción</th>
                                    <th class="p-4">Prioridad</th>
                                    <th class="p-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @forelse($this->proximasAcciones as $cuota)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $cuota->operacion->cliente->nombre }} {{ $cuota->operacion->cliente->apellido }}</td>
                                        <td class="p-4 font-medium text-indigo-600">
                                            @php
                                                $latestAction = $cuota->gestiones()->whereNotNull('proxima_accion_fecha')->orderBy('fecha_hora', 'desc')->first();
                                            @endphp
                                            {{ $latestAction ? $latestAction->proxima_accion : 'Seguimiento' }}
                                        </td>
                                        <td class="p-4">
                                            {{ $latestAction ? \Carbon\Carbon::parse($latestAction->proxima_accion_fecha)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-blue-100 text-blue-800 capitalize">{{ $cuota->computed_priority }}</span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <a href="/ficha-gestion/{{ $cuota->id }}" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded shadow">
                                                🔍 Ver ficha
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-gray-500">No hay seguimientos para hoy.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- ==========================================
             VIEW MODE: PRÓXIMOS DÍAS (Requirement 13)
             ========================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Future Promises -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Promesas de Pago Futuras</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase">
                                <th class="p-3">Cliente</th>
                                <th class="p-3">Fecha Prometida</th>
                                <th class="p-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($this->promesasFuturas as $promesa)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="p-3 font-bold">{{ $promesa->cuota->operacion->cliente->nombre }} {{ $promesa->cuota->operacion->cliente->apellido }}</td>
                                    <td class="p-3">{{ \Carbon\Carbon::parse($promesa->fecha_prometida)->format('d/m/Y') }}</td>
                                    <td class="p-3 text-right font-bold text-indigo-600">${{ number_format($promesa->monto_prometido, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-4 text-center text-gray-400">No hay promesas futuras.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Future Visits -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Visitas de Cobrador Programadas</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase">
                                <th class="p-3">Cliente</th>
                                <th class="p-3">Domicilio</th>
                                <th class="p-3">Fecha Programada</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($this->visitasFuturas as $visita)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="p-3 font-bold">{{ $visita->cliente->nombre }} {{ $visita->cliente->apellido }}</td>
                                    <td class="p-3 text-xs">{{ $visita->domicilio }}</td>
                                    <td class="p-3">{{ \Carbon\Carbon::parse($visita->fecha_programada)->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-4 text-center text-gray-400">No hay visitas futuras programadas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Future Trackings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Seguimientos Programados</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase">
                                <th class="p-3">Cliente</th>
                                <th class="p-3">Acción</th>
                                <th class="p-3">Fecha Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($this->seguimientosFuturos as $cuota)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="p-3 font-bold">{{ $cuota->operacion->cliente->nombre }} {{ $cuota->operacion->cliente->apellido }}</td>
                                    <td class="p-3 text-indigo-600 font-medium">
                                        @php
                                            $action = $cuota->gestiones()->whereNotNull('proxima_accion_fecha')->orderBy('fecha_hora', 'desc')->first();
                                        @endphp
                                        {{ $action ? $action->proxima_accion : 'Seguimiento' }}
                                    </td>
                                    <td class="p-3">{{ $action ? \Carbon\Carbon::parse($action->proxima_accion_fecha)->format('d/m/Y') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-4 text-center text-gray-400">No hay seguimientos futuros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Future Due Dates -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Próximos Vencimientos de Cuota</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase">
                                <th class="p-3">Cliente</th>
                                <th class="p-3">Día de Cobro</th>
                                <th class="p-3 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($this->proximosVencimientos as $cuota)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="p-3 font-bold">{{ $cuota->operacion->cliente->nombre }} {{ $cuota->operacion->cliente->apellido }}</td>
                                    <td class="p-3 font-medium">Día {{ $cuota->dia_cobro }}</td>
                                    <td class="p-3 text-right font-bold text-gray-900">${{ number_format($cuota->saldo_pendiente, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-4 text-center text-gray-400">No hay próximos vencimientos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL OVERLAYS (Alpine + Tailwind) -->
    @if($activeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" wire:key="modal-container-agenda">
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
                    <!-- 1. GESTIÓN -->
                    @if($activeModal === 'gestion')
                        <form wire:submit.prevent="submitGestion" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Gestión</label>
                                <select wire:model="gestion_tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
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
                                <select wire:model="gestion_resultado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
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
                                <textarea wire:model="gestion_observacion" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white" placeholder="Detalles de la gestión..."></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Próxima Acción (opcional)</label>
                                    <input wire:model="gestion_proxima_accion" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white" placeholder="Ej: Volver a llamar">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Próxima Acción</label>
                                    <input wire:model="gestion_proxima_accion_fecha" type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Guardar</button>
                            </div>
                        </form>
                    @endif

                    <!-- 2. PROMESA -->
                    @if($activeModal === 'promesa')
                        <form wire:submit.prevent="submitPromesa" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Prometida</label>
                                <input wire:model="promesa_fecha_prometida" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                                @error('promesa_fecha_prometida') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto Prometido</label>
                                <input wire:model="promesa_monto_prometido" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                                @error('promesa_monto_prometido') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                <textarea wire:model="promesa_observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white" placeholder="Ej: Pasará por la oficina..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Guardar</button>
                            </div>
                        </form>
                    @endif

                    <!-- 3. COBRADOR -->
                    @if($activeModal === 'cobrador')
                        <form wire:submit.prevent="submitCobrador" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seleccionar Cobrador</label>
                                <select wire:model="cobrador_id" class="mt-1 block w-full rounded-md border border-gray-300 dark:bg-gray-700 dark:text-white">
                                    <option value="">Seleccione un cobrador...</option>
                                    @foreach($this->cobradores as $cob)
                                        <option value="{{ $cob->id }}">{{ $cob->name }}</option>
                                    @endforeach
                                </select>
                                @error('cobrador_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Programada</label>
                                <input wire:model="cobrador_fecha_programada" type="date" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                                @error('cobrador_fecha_programada') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                <textarea wire:model="cobrador_observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white" placeholder="Indicaciones para el domicilio..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Programar</button>
                            </div>
                        </form>
                    @endif

                    <!-- 4. PAGO -->
                    @if($activeModal === 'pago')
                        <form wire:submit.prevent="submitPago" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto Cobrado (Efectivo/Transferido)</label>
                                <input wire:model="pago_monto_cobrado" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                                @error('pago_monto_cobrado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Punitorios Condonados / Perdonados</label>
                                <input wire:model="pago_punitorios_perdonados" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                                @error('pago_punitorios_perdonados') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Medio de Pago</label>
                                <select wire:model="pago_medio_pago" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                                    <option value="transferencia">Transferencia</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="cobrador">Cobrador</option>
                                    <option value="tarjeta">Tarjeta</option>
                                </select>
                                @error('pago_medio_pago') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                <textarea wire:model="pago_observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white" placeholder="Nro de comprobante..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Guardar Pago</button>
                            </div>
                        </form>
                    @endif

                    <!-- 5. RESULTADO DE VISITA -->
                    @if($activeModal === 'visita_resultado')
                        <form wire:submit.prevent="submitVisitaResultado" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resultado de la Visita</label>
                                <select wire:model="visita_resultado_tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
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
                                <input wire:model="visita_monto_cobrado" type="number" step="0.01" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                                @error('visita_monto_cobrado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Realizada</label>
                                <input wire:model="visita_fecha_realizada" type="date" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                                @error('visita_fecha_realizada') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                <textarea wire:model="visita_observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white" placeholder="Detalles de la visita..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                                <button type="submit" class="px-4 py-2 rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Guardar Resultado</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

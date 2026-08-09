<div class="space-y-6">
    <!-- Notifications / Success / Warning Messages -->
    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-100 dark:bg-green-900/30 dark:text-green-400 font-medium" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('warning'))
        <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400 font-medium" role="alert">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Cobrador Selector for Gestores/Admins -->
    @if(count($cobradores) > 0)
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">Modo Gestor: Seleccionar Cobrador</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Visualizando la agenda y el recorrido de otro cobrador</p>
            </div>
            <div>
                <select wire:model.live="selectedCobradorId" class="w-full sm:w-64 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach($cobradores as $cob)
                        <option value="{{ $cob->id }}">{{ $cob->name }} (Cobrador)</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <!-- Mobile-First Header Info -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 dark:from-indigo-700 dark:to-indigo-900 text-white rounded-2xl p-6 shadow-md">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider bg-white/20 px-2.5 py-1 rounded-full">
                    Panel del Cobrador
                </span>
                <h1 class="text-2xl font-black mt-2">
                    {{ \App\Models\User::find($selectedCobradorId)?->name ?? 'Cobrador' }}
                </h1>
                <p class="text-sm text-indigo-100 mt-1 flex items-center gap-1.5">
                    📅 {{ \Carbon\Carbon::parse($referenceDate)->translatedFormat('l d \d\e F, Y') }}
                </p>
            </div>
            <!-- Quick stats grid -->
            <div class="grid grid-cols-2 gap-4 w-full sm:w-auto">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <span class="block text-xs text-indigo-100">Hoy Pendientes</span>
                    <span class="text-xl font-extrabold">{{ $this->metrics['pending_today'] }}</span>
                </div>
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <span class="block text-xs text-indigo-100">Hoy Realizadas</span>
                    <span class="text-xl font-extrabold">{{ $this->metrics['completed_today'] }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-white/10">
            <div>
                <span class="block text-xs text-indigo-200 uppercase tracking-wider">Total Pendientes</span>
                <span class="text-lg font-bold">{{ $this->metrics['total_pending'] }} visitas</span>
            </div>
            <div class="text-right">
                <span class="block text-xs text-indigo-200 uppercase tracking-wider">Monto Cobrado Hoy</span>
                <span class="text-lg font-bold text-emerald-300">${{ number_format($this->metrics['collected_today'], 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (Optimized for Mobile Touch) -->
    <div class="flex bg-gray-100 dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700">
        <button wire:click="$set('activeTab', 'hoy')" class="flex-1 py-3 text-sm font-bold rounded-lg transition-all duration-150 {{ $activeTab === 'hoy' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800' }}">
            📌 Hoy
        </button>
        <button wire:click="$set('activeTab', 'futuras')" class="flex-1 py-3 text-sm font-bold rounded-lg transition-all duration-150 {{ $activeTab === 'futuras' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800' }}">
            📅 Próximas
        </button>
        <button wire:click="$set('activeTab', 'historial')" class="flex-1 py-3 text-sm font-bold rounded-lg transition-all duration-150 {{ $activeTab === 'historial' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800' }}">
            📜 Historial
        </button>
    </div>

    <!-- TAB 1: VISITAS DE HOY -->
    @if($activeTab === 'hoy')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Visitas de Hoy</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Priorizadas por Gravedad</span>
            </div>

            @if($this->todayVisits->isEmpty())
                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <span class="text-3xl">🎉</span>
                    <h3 class="mt-2 text-sm font-bold text-gray-900 dark:text-white">¡No hay visitas para hoy!</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Estás al día con tus recorridos.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($this->todayVisits as $visita)
                        @php
                            $cuota = $visita->cuota;
                            $cliente = $visita->cliente;
                            $priority = $cuota ? app(App\Services\CuotaPriorityService::class)->recalculatePriority($cuota, \Carbon\Carbon::parse($referenceDate)) : 'baja';
                            $status = $cuota ? app(App\Services\CuotaStatusService::class)->determineStatus($cuota, \Carbon\Carbon::parse($referenceDate)) : 'pendiente';

                            $prioColors = [
                                'critica' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                                'alta' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                                'media' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                'baja' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                            ];
                            $statusColors = [
                                'vence_hoy' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'vencida' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                'promesa_pago' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                'promesa_incumplida' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400',
                                'sin_respuesta' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                'pago_realizado_oficial' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                                'pago_registrado' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            ];
                        @endphp
                        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border {{ $selectedVisitaId === $visita->id ? 'border-2 border-indigo-500' : 'border-gray-100 dark:border-gray-700' }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 block">
                                        SOLICITUD #{{ $cuota?->operacion?->numero_solicitud ?? 'N/A' }} | CUOTA {{ $cuota?->numero_cuota ?? 'N/A' }}
                                    </span>
                                    <h3 class="text-lg font-black text-gray-950 dark:text-white mt-1">
                                        {{ $cliente?->nombre }} {{ $cliente?->apellido }}
                                    </h3>
                                </div>
                                <div class="flex flex-col items-end gap-1.5">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-bold {{ $prioColors[$priority] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($priority) }}
                                    </span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-medium {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ str_replace('_', ' ', ucfirst($status)) }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300 border-t border-b border-gray-100 dark:border-gray-700/60 py-3">
                                <p class="flex items-start gap-1.5">
                                    <span class="text-gray-400 dark:text-gray-500 select-none">📍</span>
                                    <span class="font-semibold">{{ $visita->domicilio ?: 'Sin dirección' }}</span>
                                </p>
                                <p class="flex items-center gap-1.5">
                                    <span class="text-gray-400 dark:text-gray-500 select-none">📞</span>
                                    <span>{{ $cliente?->telefono ?: 'Sin teléfono' }}</span>
                                </p>
                                @if($cuota)
                                    <p class="flex items-center justify-between text-xs font-semibold bg-gray-50 dark:bg-gray-900/60 p-2 rounded-lg">
                                        <span>Total Actualizado: <strong class="text-gray-900 dark:text-white">${{ number_format($cuota->total_actualizado, 2, ',', '.') }}</strong></span>
                                        <span>Saldo: <strong class="text-indigo-600 dark:text-indigo-400">${{ number_format($cuota->saldo_pendiente, 2, ',', '.') }}</strong></span>
                                    </p>
                                @endif
                                @if($visita->estado === 'realizada')
                                    <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 p-2.5 rounded-lg text-xs font-semibold mt-2">
                                        ✅ Visita Realizada: <strong>{{ ucfirst($visita->resultado) }}</strong>
                                        @if($visita->monto_cobrado > 0)
                                            <span class="block mt-0.5">Cobrado: ${{ number_format($visita->monto_cobrado, 2, ',', '.') }}</span>
                                        @endif
                                    </div>
                                @elseif($visita->estado === 'iniciada')
                                    <div class="bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 p-2.5 rounded-lg text-xs font-semibold mt-2">
                                        ⚡ Visita en Curso...
                                    </div>
                                @endif
                            </div>

                            <!-- Mobile Quick Actions Row -->
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button wire:click="selectVisita({{ $visita->id }})" class="flex-1 min-w-[120px] px-3 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl text-xs font-bold text-center transition-colors">
                                    🔍 Ficha Rápida
                                </button>

                                @if($visita->estado === 'pendiente')
                                    <button wire:click="iniciarVisita({{ $visita->id }})" class="flex-1 min-w-[120px] px-3 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors">
                                        Iniciar Visita
                                    </button>
                                @endif

                                @if(in_array($visita->estado, ['pendiente', 'iniciada']))
                                    <button wire:click="selectVisita({{ $visita->id }}); openResultadoModal()" class="flex-1 min-w-[120px] px-3 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold transition-colors">
                                        Registrar Resultado
                                    </button>
                                    <button wire:click="selectVisita({{ $visita->id }}); openPagoModal()" class="flex-1 min-w-[120px] px-3 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors">
                                        Registrar Pago
                                    </button>
                                    <button wire:click="noPudeContactar({{ $visita->id }})" class="flex-1 min-w-[120px] px-3 py-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-xl text-xs font-bold transition-colors">
                                        No pude contactar
                                    </button>
                                @endif

                                @if(auth()->user()->role !== 'cobrador')
                                    <button wire:click="cancelarVisita({{ $visita->id }})" class="px-3 py-2.5 bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-xl text-xs font-bold transition-colors">
                                        Cancelar
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 2: PRÓXIMAS VISITAS -->
    @if($activeTab === 'futuras')
        <div class="space-y-4">
            <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Próximas Visitas Programadas</h2>

            @if($this->futureVisits->isEmpty())
                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <span class="text-3xl">📅</span>
                    <h3 class="mt-2 text-sm font-bold text-gray-900 dark:text-white">No hay visitas futuras</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No se registran visitas programadas para los próximos días.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($this->futureVisits as $visita)
                        @php
                            $cuota = $visita->cuota;
                            $cliente = $visita->cliente;
                        @endphp
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 px-2 py-1 rounded">
                                        📅 {{ \Carbon\Carbon::parse($visita->fecha_programada)->format('d/m/Y') }}
                                    </span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        #{{ $cuota?->operacion?->numero_solicitud ?? 'N/A' }} (Cuota {{ $cuota?->numero_cuota ?? 'N/A' }})
                                    </span>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-white">
                                    {{ $cliente?->nombre }} {{ $cliente?->apellido }}
                                </h3>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-1">
                                    📍 {{ $visita->domicilio }}
                                </p>
                            </div>
                            @if(auth()->user()->role !== 'cobrador')
                                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                                    <button wire:click="cancelarVisita({{ $visita->id }})" class="text-xs text-red-600 hover:underline">
                                        Cancelar Visita
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 3: HISTORIAL -->
    @if($activeTab === 'historial')
        <div class="space-y-4">
            <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Historial de Recorridos</h2>

            <!-- History Filters Card -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Fecha Programada</label>
                        <input type="date" wire:model.live="history_date" class="w-full text-xs px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Resultado</label>
                        <select wire:model.live="history_outcome" class="w-full text-xs px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg">
                            <option value="todos">Todos los Resultados</option>
                            <option value="cobrado">Cobrado</option>
                            <option value="cobrado_parcialmente">Cobrado Parcialmente</option>
                            <option value="no_estaba">No Estaba</option>
                            <option value="se_nego_a_pagar">Se Negó a Pagar</option>
                            <option value="domicilio_incorrecto">Domicilio Incorrecto</option>
                            <option value="no_se_pudo_contactar">No se pudo Contactar</option>
                            <option value="reprogramar">Reprogramar</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Cliente</label>
                        <input type="text" wire:model.live="history_client" placeholder="Nombre o apellido..." class="w-full text-xs px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Solicitud</label>
                        <input type="text" wire:model.live="history_solicitud" placeholder="Número de solicitud..." class="w-full text-xs px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg">
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('history_date', ''); $set('history_outcome', 'todos'); $set('history_client', ''); $set('history_solicitud', ''); $set('history_status', 'todos');" class="text-xs text-indigo-600 hover:underline">
                        Limpiar Filtros
                    </button>
                </div>
            </div>

            <!-- History List -->
            @if($this->historyVisits->isEmpty())
                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <span class="text-3xl">📜</span>
                    <h3 class="mt-2 text-sm font-bold text-gray-900 dark:text-white">Sin resultados en el historial</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Intenta modificando los filtros de búsqueda.</p>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-3">Fecha</th>
                                    <th class="px-4 py-3">Cliente</th>
                                    <th class="px-4 py-3">Domicilio</th>
                                    <th class="px-4 py-3">Resultado</th>
                                    <th class="px-4 py-3">Cobrado</th>
                                    <th class="px-4 py-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($this->historyVisits as $his)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                        <td class="px-4 py-3 whitespace-nowrap text-xs">
                                            <span class="font-bold block">{{ \Carbon\Carbon::parse($his->fecha_programada)->format('d/m/Y') }}</span>
                                            @if($his->fecha_realizada)
                                                <span class="text-[10px] text-gray-400 block">Realizada: {{ \Carbon\Carbon::parse($his->fecha_realizada)->format('d/m/Y H:i') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">
                                            {{ $his->cliente?->nombre }} {{ $his->cliente?->apellido }}
                                        </td>
                                        <td class="px-4 py-3 text-xs max-w-xs truncate">
                                            {{ $his->domicilio }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs">
                                            <span class="px-2 py-0.5 rounded-full font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                {{ $his->resultado ? str_replace('_', ' ', ucfirst($his->resultado)) : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                            ${{ number_format($his->monto_cobrado, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs">
                                            <span class="px-2 py-0.5 rounded-full font-bold {{ $his->estado === 'realizada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($his->estado) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- QUICK DETAIL FICHA RÁPIDA (Mobile slide-over or prominent card) -->
    @if($selectedVisitaId)
        @php
            $selVisita = \App\Models\VisitaCobrador::with(['cliente', 'cuota.operacion.cliente', 'cuota.gestiones', 'cuota.promesasPago'])->find($selectedVisitaId);
        @endphp
        @if($selVisita)
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex justify-end z-50">
                <div class="w-full max-w-md bg-white dark:bg-gray-900 h-full overflow-y-auto p-6 flex flex-col justify-between shadow-2xl">
                    <div class="space-y-6">
                        <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-800 pb-4">
                            <div>
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 tracking-wider uppercase block">Ficha Rápida de la Visita</span>
                                <h2 class="text-xl font-extrabold text-gray-950 dark:text-white mt-1">
                                    {{ $selVisita->cliente?->nombre }} {{ $selVisita->cliente?->apellido }}
                                </h2>
                            </div>
                            <button wire:click="closeFicha" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg">
                                ✕
                            </button>
                        </div>

                        <!-- Cliente Details -->
                        <div class="bg-gray-50 dark:bg-gray-800/40 p-4 rounded-xl space-y-2.5">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide">Datos del Cliente</h3>
                            <p class="text-sm">📞 <strong class="text-gray-900 dark:text-white">Teléfono:</strong> {{ $selVisita->cliente?->telefono ?: 'No registrado' }}</p>
                            <p class="text-sm">📍 <strong class="text-gray-900 dark:text-white">Domicilio:</strong> {{ $selVisita->domicilio }}</p>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($selVisita->domicilio) }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 font-bold text-xs rounded-lg transition-colors w-full justify-center">
                                🗺️ ¿Cómo llegar? (Abrir Mapas)
                            </a>
                        </div>

                        <!-- Crédito Details -->
                        @if($selVisita->cuota)
                            <div class="bg-gray-50 dark:bg-gray-800/40 p-4 rounded-xl space-y-2.5">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide">Estado del Crédito</h3>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <span class="block text-gray-400">Solicitud</span>
                                        <span class="font-bold text-gray-900 dark:text-white">#{{ $selVisita->cuota->operacion?->numero_solicitud }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-400">Cuota</span>
                                        <span class="font-bold text-gray-900 dark:text-white">Nro {{ $selVisita->cuota->numero_cuota }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-400">Importe Original</span>
                                        <span class="font-bold text-gray-900 dark:text-white">${{ number_format($selVisita->cuota->importe_original, 2, ',', '.') }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-400">Punitorios</span>
                                        <span class="font-bold text-gray-900 dark:text-white">${{ number_format($selVisita->cuota->punitorios, 2, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="border-t border-gray-100 dark:border-gray-800/80 pt-2.5 flex justify-between items-center">
                                    <span class="text-sm font-semibold">Total Actualizado:</span>
                                    <span class="text-base font-black text-indigo-600 dark:text-indigo-400">${{ number_format($selVisita->cuota->total_actualizado, 2, ',', '.') }}</span>
                                </div>
                            </div>

                            <!-- Gestión Details -->
                            <div class="bg-gray-50 dark:bg-gray-800/40 p-4 rounded-xl space-y-2.5">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide">Últimas Interacciones</h3>
                                @php
                                    $lastGestion = $selVisita->cuota->gestiones()->orderBy('fecha_hora', 'desc')->first();
                                    $lastPromise = $selVisita->cuota->promesasPago()->orderBy('created_at', 'desc')->first();
                                @endphp
                                @if($lastGestion)
                                    <div class="text-xs">
                                        <span class="block text-gray-400 font-medium">Última Gestión ({{ \Carbon\Carbon::parse($lastGestion->fecha_hora)->format('d/m/Y') }}):</span>
                                        <span class="font-bold text-gray-900 dark:text-white">{{ str_replace('_', ' ', ucfirst($lastGestion->tipo)) }} - {{ ucfirst($lastGestion->resultado) }}</span>
                                        @if($lastGestion->observacion)
                                            <span class="block italic text-gray-500 mt-0.5">"{{ $lastGestion->observacion }}"</span>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-xs text-gray-500 italic">No hay gestiones anteriores registradas.</p>
                                @endif

                                @if($lastPromise)
                                    <div class="text-xs border-t border-gray-100 dark:border-gray-800/80 pt-2">
                                        <span class="block text-gray-400 font-medium">Última Promesa:</span>
                                        <span class="font-bold text-purple-700 dark:text-purple-400">${{ number_format($lastPromise->monto_prometido, 2, ',', '.') }} para el {{ \Carbon\Carbon::parse($lastPromise->fecha_prometida)->format('d/m/Y') }}</span>
                                        <span class="block text-xs uppercase font-extrabold text-[9px] mt-0.5 {{ $lastPromise->estado === 'incumplida' ? 'text-red-600' : 'text-green-600' }}">({{ $lastPromise->estado }})</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Close & Actions bottom bar -->
                    <div class="pt-6 border-t border-gray-100 dark:border-gray-800 mt-6 space-y-3">
                        <div class="flex gap-2">
                            @if(in_array($selVisita->estado, ['pendiente', 'iniciada']))
                                <button wire:click="openResultadoModal" class="flex-1 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-colors text-center">
                                    Registrar Resultado
                                </button>
                                <button wire:click="openPagoModal" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-colors text-center">
                                    Registrar Pago
                                </button>
                            @endif
                        </div>
                        <button wire:click="closeFicha" class="w-full py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold">
                            Cerrar Ficha
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- MODAL 1: REGISTRAR RESULTADO -->
    @if($activeModal === 'resultado')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
                <button wire:click="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    ✕
                </button>
                <h3 class="text-lg font-extrabold text-gray-950 dark:text-white mb-4">
                    Registrar Resultado de la Visita
                </h3>

                <form wire:submit.prevent="submitResultado" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase mb-1">Resultado de Visita</label>
                        <select wire:model="resultado_tipo" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-sm">
                            <option value="cobrado">Pago realizado</option>
                            <option value="prometio_pagar">Prometió pagar</option>
                            <option value="no_estaba">No estaba en domicilio</option>
                            <option value="se_nego_a_pagar">Cliente se negó a pagar</option>
                            <option value="domicilio_incorrecto">Domicilio incorrecto</option>
                            <option value="no_se_pudo_contactar">No se pudo contactar</option>
                            <option value="reprogramar">Solicitó volver otro día</option>
                            <option value="otro">Otro</option>
                        </select>
                        @error('resultado_tipo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase mb-1">Monto Cobrado (opcional)</label>
                        <input type="number" step="0.01" wire:model="resultado_monto" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-sm">
                        @error('resultado_monto') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase mb-1">Fecha Realización</label>
                        <input type="date" wire:model="resultado_fecha_realizada" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-sm">
                        @error('resultado_fecha_realizada') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase mb-1">Observaciones / Comentarios</label>
                        <textarea wire:model="resultado_observaciones" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-sm" placeholder="Ingrese detalles de lo conversado..."></textarea>
                        @error('resultado_observaciones') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-bold text-gray-500 bg-gray-100 rounded-xl hover:bg-gray-200" {{ $isSaving ? 'disabled' : '' }}>
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 flex items-center gap-1.5" wire:loading.attr="disabled" {{ $isSaving ? 'disabled' : '' }}>
                            <span>Guardar Resultado</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 2: REGISTRAR PAGO -->
    @if($activeModal === 'pago')
        @php
            $pVisita = \App\Models\VisitaCobrador::with('cuota')->find($selectedVisitaId);
        @endphp
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
                <button wire:click="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    ✕
                </button>
                <h3 class="text-lg font-extrabold text-gray-950 dark:text-white mb-2">
                    Registrar Cobro / Pago Directo
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Se aplicará la condonación automática según el monto recibido.
                </p>

                @if($pVisita && $pVisita->cuota)
                    <div class="mb-4 bg-indigo-50 dark:bg-indigo-950/40 p-3 rounded-xl border border-indigo-100 dark:border-indigo-900/60 text-xs space-y-1">
                        <p class="flex justify-between"><span>Importe Original:</span> <span class="font-bold">${{ number_format($pVisita->cuota->importe_original, 2, ',', '.') }}</span></p>
                        <p class="flex justify-between"><span>Punitorios Acumulados:</span> <span class="font-bold">${{ number_format($pVisita->cuota->punitorios, 2, ',', '.') }}</span></p>
                        <p class="flex justify-between text-sm pt-1 border-t border-indigo-100 dark:border-indigo-900/40 font-black">
                            <span>Total Actualizado:</span>
                            <span class="text-indigo-600 dark:text-indigo-400">${{ number_format($pVisita->cuota->total_actualizado, 2, ',', '.') }}</span>
                        </p>
                    </div>
                @endif

                <form wire:submit.prevent="submitPago" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase mb-1">Monto Cobrado</label>
                        <input type="number" step="0.01" wire:model.live="pago_monto_cobrado" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-sm font-bold">
                        @error('pago_monto_cobrado') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Punitorios Perdonados</label>
                            <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50 dark:bg-indigo-950 px-1.5 py-0.5 rounded">Auto-calculado</span>
                        </div>
                        <input type="number" step="0.01" wire:model="pago_punitorios_perdonados" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-sm text-indigo-600 font-semibold" placeholder="0.00">
                        @error('pago_punitorios_perdonados') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase mb-1">Medio de Pago</label>
                        <select wire:model="pago_medio_pago" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-sm">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                            <option value="otro">Otro</option>
                        </select>
                        @error('pago_medio_pago') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase mb-1">Observaciones</label>
                        <textarea wire:model="pago_observaciones" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-sm" placeholder="Detalles de cobro o condonación..."></textarea>
                        @error('pago_observaciones') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-bold text-gray-500 bg-gray-100 rounded-xl hover:bg-gray-200" {{ $isSaving ? 'disabled' : '' }}>
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 flex items-center gap-1.5" wire:loading.attr="disabled" {{ $isSaving ? 'disabled' : '' }}>
                            <span>Registrar Cobro</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

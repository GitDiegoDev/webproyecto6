<div class="space-y-6">
    <!-- Header with Tabs -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 dark:border-gray-700 pb-4 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Recaudación y Control</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Control de recaudación mensual, objetivos, historial de pagos, reportes y cierre de período.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Filtrar Período:</span>
            <select wire:model.live="filterPeriodo" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2">
                @foreach($this->periods as $p)
                    <option value="{{ $p->id }}">{{ $p->nombre }} {{$p->activo ? '(Activo)' : ''}}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 font-medium" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 font-medium" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
            <li class="me-2">
                <button wire:click="$set('activeTab', 'dashboard')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg gap-2 {{ $activeTab === 'dashboard' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-500 dark:border-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                    📊 Dashboard Mensual
                </button>
            </li>
            <li class="me-2">
                <button wire:click="$set('activeTab', 'historial')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg gap-2 {{ $activeTab === 'historial' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-500 dark:border-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                    🔎 Historial de Pagos
                </button>
            </li>
            <li class="me-2">
                <button wire:click="$set('activeTab', 'reportes')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg gap-2 {{ $activeTab === 'reportes' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-500 dark:border-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                    📁 Reportes y Exportación
                </button>
            </li>
            @if(auth()->user()->role === 'administrador')
                <li class="me-2">
                    <button wire:click="$set('activeTab', 'periodos')" class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg gap-2 {{ $activeTab === 'periodos' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-500 dark:border-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                        ⚙️ Cierre y Períodos
                    </button>
                </li>
            @endif
        </ul>
    </div>

    <!-- TAB: Dashboard Mensual -->
    @if($activeTab === 'dashboard')
        <div class="space-y-6">
            <!-- KPIS grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- KPI 1: Objetivo -->
                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Objetivo Mensual</span>
                        <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">
                            ${{ number_format($this->metrics['objetivo'], 2) }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Monto fijo del período de cobranza</div>
                </div>

                <!-- KPI 2: Recaudado -->
                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Monto Recaudado</span>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                            ${{ number_format($this->metrics['recaudado'], 2) }}
                        </div>
                    </div>
                    <div class="text-xs text-emerald-500 font-bold mt-2">
                        {{ $this->metrics['porcentaje_alcanzado'] }}% alcanzado
                    </div>
                </div>

                <!-- KPI 3: Restante -->
                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Restante para Objetivo</span>
                        <div class="text-3xl font-extrabold text-rose-500 mt-1">
                            ${{ number_format($this->metrics['restante'], 2) }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Falta para llegar a la meta</div>
                </div>

                <!-- KPI 4: Punitorios Perdonados -->
                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Punitorios Condonados</span>
                        <div class="text-3xl font-extrabold text-indigo-500 mt-1">
                            ${{ number_format($this->metrics['punitorios_perdonados'], 2) }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">No se interpretan como dinero cobrado</div>
                </div>
            </div>

            <!-- More Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Caja del Día</span>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                        ${{ number_format($this->metrics['recaudacion_hoy'], 2) }}
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Pagos reales registrados hoy</p>
                </div>
                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Transacciones Realizadas</span>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                        {{ $this->metrics['cantidad_pagos'] }}
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Pagos totales en el período</p>
                </div>
                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Detalle del Cobro</span>
                    <div class="flex justify-between text-sm mt-2 text-gray-700 dark:text-gray-300">
                        <span>Cuotas Canceladas:</span>
                        <span class="font-bold">{{ $this->metrics['cuotas_canceladas'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm mt-1 text-gray-700 dark:text-gray-300">
                        <span>Pagos Parciales:</span>
                        <span class="font-bold">{{ $this->metrics['pagos_parciales'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Breakdown section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Breakdown by Method -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Recaudación por Medio de Pago</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 uppercase">
                                <tr>
                                    <th class="px-4 py-3">Medio de Pago</th>
                                    <th class="px-4 py-3 text-right">Cant. Pagos</th>
                                    <th class="px-4 py-3 text-right">Imp. Original</th>
                                    <th class="px-4 py-3 text-right">Punitorios Perd.</th>
                                    <th class="px-4 py-3 text-right">Monto Recaudado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->breakdown['by_method'] as $method)
                                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white">
                                        <td class="px-4 py-3 font-semibold uppercase">{{ $method['medio'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $method['cantidad'] }}</td>
                                        <td class="px-4 py-3 text-right">${{ number_format($method['importe_original'], 2) }}</td>
                                        <td class="px-4 py-3 text-right text-rose-500">${{ number_format($method['punitorios_perdonados'], 2) }}</td>
                                        <td class="px-4 py-3 text-right font-extrabold text-emerald-600">${{ number_format($method['monto_cobrado'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">No se registran cobros en este período.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Breakdown by Collector -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Gestión de Cobradores a Domicilio</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 uppercase">
                                <tr>
                                    <th class="px-4 py-3">Cobrador</th>
                                    <th class="px-4 py-3 text-right">Pagos Realizados</th>
                                    <th class="px-4 py-3 text-right">Recaudado Visitas</th>
                                    <th class="px-4 py-3 text-right">Total Gestión</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->breakdown['by_collector'] as $col)
                                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white">
                                        <td class="px-4 py-3 font-semibold">{{ $col['cobrador'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $col['cantidad_pagos'] }}</td>
                                        <td class="px-4 py-3 text-right text-gray-500">${{ number_format($col['monto_cobrado_visitas'], 2) }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($col['monto_cobrado_pagos'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">No se registran gestiones de cobrador en este período.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Daily Evolution -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Evolución Diaria del Período</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 uppercase">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3 text-right">Recaudado en el Día</th>
                                <th class="px-4 py-3 text-right">Acumulado Mensual</th>
                                <th class="px-4 py-3 text-right">Objetivo Mensual</th>
                                <th class="px-4 py-3 text-right">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->dailyEvolution as $day)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white">
                                    <td class="px-4 py-3 font-semibold">{{ Carbon\Carbon::parse($day['fecha'])->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-600 font-bold">${{ number_format($day['monto_dia'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($day['acumulado'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-500">${{ number_format($day['objetivo'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-500 font-medium">${{ number_format($day['diferencia'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">No hay transacciones registradas todavía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB: Historial de Pagos -->
    @if($activeTab === 'historial')
        <div class="space-y-6">
            <!-- Advanced Filters -->
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Filtros de Búsqueda</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Client -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Cliente</label>
                        <input type="text" wire:model.live="filterClient" placeholder="Nombre, apellido o DNI" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white">
                    </div>
                    <!-- Solicitud -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Solicitud</label>
                        <input type="text" wire:model.live="filterSolicitud" placeholder="Número de solicitud" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white">
                    </div>
                    <!-- Cuota -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Cuota</label>
                        <input type="number" wire:model.live="filterCuota" placeholder="N°" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white">
                    </div>
                    <!-- Medio de pago -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Medio de Pago</label>
                        <select wire:model.live="filterMedioPago" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white">
                            <option value="">Todos</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="cobrador">Cobrador</option>
                            <option value="tarjeta">Tarjeta</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <!-- Start Date -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Fecha Desde</label>
                        <input type="date" wire:model.live="filterStartDate" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white">
                    </div>
                    <!-- End Date -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Fecha Hasta</label>
                        <input type="date" wire:model.live="filterEndDate" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white">
                    </div>
                    <!-- Registrar (User) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Registrado por</label>
                        <select wire:model.live="filterUser" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white">
                            <option value="">Todos</option>
                            @foreach($this->users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Collector -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Cobrador</label>
                        <select wire:model.live="filterCobrador" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white">
                            <option value="">Todos</option>
                            @foreach($this->cobradores as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Payments List -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pagos Registrados</h3>
                    <span class="text-xs text-gray-500">{{ $this->payments->count() }} pagos encontrados</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 uppercase">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Solicitud/Cuota</th>
                                <th class="px-4 py-3 text-right">Imp. Original</th>
                                <th class="px-4 py-3 text-right">Punitorios</th>
                                <th class="px-4 py-3 text-right">Total Actualiz.</th>
                                <th class="px-4 py-3 text-right text-emerald-600">Monto Cobrado</th>
                                <th class="px-4 py-3 text-right text-rose-500">Punit. Perd.</th>
                                <th class="px-4 py-3">Medio Pago</th>
                                <th class="px-4 py-3">Registró</th>
                                <th class="px-4 py-3">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->payments as $pago)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white">
                                    <td class="px-4 py-3 font-medium whitespace-nowrap">{{ Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 font-semibold">
                                        {{ $pago->cuota->operacion->cliente->apellido }}, {{ $pago->cuota->operacion->cliente->nombre }}
                                    </td>
                                    <td class="px-4 py-3">
                                        Sol. {{ $pago->cuota->operacion->numero_solicitud }} — Cuota {{ $pago->cuota->numero_cuota }}
                                    </td>
                                    <td class="px-4 py-3 text-right">${{ number_format($pago->importe_original_snapshot, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($pago->punitorios_snapshot, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($pago->total_actualizado_snapshot, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-600 font-extrabold">${{ number_format($pago->monto_cobrado, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-500 font-bold">${{ number_format($pago->punitorios_perdonados, 2) }}</td>
                                    <td class="px-4 py-3 font-medium uppercase text-xs">{{ $pago->medio_pago }}</td>
                                    <td class="px-4 py-3 text-xs">{{ $pago->user->name }}</td>
                                    <td class="px-4 py-3 text-xs italic text-gray-500 truncate max-w-xs" title="{{ $pago->observaciones }}">
                                        {{ $pago->observaciones ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-8 text-center text-gray-400">Ningún pago coincide con los filtros aplicados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB: Reportes y Exportación -->
    @if($activeTab === 'reportes')
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Situación General del Período</h3>
                        <p class="text-xs text-gray-500 mt-1">Métricas analíticas consolidadas de la cartera activa.</p>
                    </div>
                    <button wire:click="exportReportCsv" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm px-4 py-2.5 rounded-xl transition duration-150">
                        📥 Exportar Reporte a CSV
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                    <!-- Cuotas -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl space-y-2">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Cartera y Cuotas</span>
                        <div class="flex justify-between text-sm">
                            <span>Cuotas Totalmente Pagadas:</span>
                            <span class="font-bold text-emerald-600">{{ $this->reportMetrics['cuotas_pagadas'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Cuotas Pendientes:</span>
                            <span class="font-bold text-indigo-500">{{ $this->reportMetrics['cuotas_pendientes'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Cuotas Vencidas en Mora:</span>
                            <span class="font-bold text-rose-500">{{ $this->reportMetrics['cuotas_vencidas'] }}</span>
                        </div>
                    </div>

                    <!-- Promesas -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl space-y-2">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Compromisos y Promesas</span>
                        <div class="flex justify-between text-sm">
                            <span>Total Promesas Registradas:</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $this->reportMetrics['promesas'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Promesas Cumplidas:</span>
                            <span class="font-bold text-emerald-600">{{ $this->reportMetrics['promesas_cumplidas'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Promesas Incumplidas:</span>
                            <span class="font-bold text-rose-500">{{ $this->reportMetrics['promesas_incumplidas'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-gray-200 dark:border-gray-600 pt-1">
                            <span>Clientes Sin Respuesta:</span>
                            <span class="font-bold text-amber-500">{{ $this->reportMetrics['sin_respuesta'] }}</span>
                        </div>
                    </div>

                    <!-- Visitas y Caja -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl space-y-2">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Visitas de Cobradores</span>
                        <div class="flex justify-between text-sm">
                            <span>Visitas Realizadas:</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $this->reportMetrics['visitas_realizadas'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Visitas Pendientes:</span>
                            <span class="font-bold text-indigo-500">{{ $this->reportMetrics['visitas_pendientes'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-gray-200 dark:border-gray-600 pt-1">
                            <span>Monto Recaudado:</span>
                            <span class="font-bold text-emerald-600">${{ number_format($this->reportMetrics['recaudacion'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Punitorios Perdonados:</span>
                            <span class="font-bold text-rose-500">${{ number_format($this->reportMetrics['punitorios_perdonados'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB: Cierre y Períodos (Admin only) -->
    @if($activeTab === 'periodos' && auth()->user()->role === 'administrador')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Create period Form -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Crear Período de Cobranza</h3>
                <form wire:submit.prevent="crearPeriodo" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Nombre del Período</label>
                        <input type="text" wire:model="nuevo_periodo_nombre" placeholder="ej: Agosto 2026" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white" required>
                        @error('nuevo_periodo_nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Mes</label>
                            <select wire:model="nuevo_periodo_mes" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ $m }}</option>
                                @endfor
                            </select>
                            @error('nuevo_periodo_mes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Año</label>
                            <input type="number" wire:model="nuevo_periodo_anio" min="2020" max="2100" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white" required>
                            @error('nuevo_periodo_anio') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Objetivo del Período ($)</label>
                        <input type="number" step="0.01" wire:model="nuevo_periodo_objetivo" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm p-2 text-gray-900 dark:text-white" required>
                        @error('nuevo_periodo_objetivo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-2.5 rounded-xl transition duration-150">
                        ✨ Crear y Activar Período
                    </button>
                </form>
            </div>

            <!-- List and Actions -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Administración Histórica de Períodos</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 uppercase">
                            <tr>
                                <th class="px-4 py-3">Nombre</th>
                                <th class="px-4 py-3">Calendario</th>
                                <th class="px-4 py-3 text-right">Objetivo</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->periods as $p)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white">
                                    <td class="px-4 py-3 font-semibold">{{ $p->nombre }}</td>
                                    <td class="px-4 py-3">{{ $p->mes }}/{{ $p->anio }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($p->objetivo_monto, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($p->activo)
                                            <span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 text-xs px-2 py-1 rounded-full font-bold">ACTIVO</span>
                                        @else
                                            <span class="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded-full">INACTIVO</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center space-x-2">
                                        @if(!$p->activo)
                                            <button wire:click="setActivePeriod({{ $p->id }})" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-400 text-xs font-bold px-2 py-1 rounded">
                                                Activar
                                            </button>
                                        @else
                                            <button wire:click="closePeriod({{ $p->id }})" class="bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 dark:text-rose-400 text-xs font-bold px-2 py-1 rounded" onclick="confirm('¿Está seguro de cerrar este período de cobranza? Esta acción desactivará el período pero conservará todo su historial intacto.') || event.stopImmediatePropagation()">
                                                Cerrar Período
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

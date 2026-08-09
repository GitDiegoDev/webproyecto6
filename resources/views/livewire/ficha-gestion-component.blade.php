<div class="space-y-8" x-data="{ activeSection: 'gestiones' }">
    <!-- Back to Dashboard and Status/Priority bar -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <a href="/dashboard" class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">
                ⬅️ Volver al Dashboard
            </a>
            <span class="text-gray-300">|</span>
            <span class="text-xs text-gray-500 font-mono">ID Cuota: #{{ $this->cuota->id }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <!-- Computed Status Badge -->
            @php $status = $this->computedStatus; @endphp
            @if($status === 'vencida')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400">🚨 Vencida</span>
            @elseif($status === 'vence_hoy')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400">⏳ Vence Hoy</span>
            @elseif($status === 'promesa_pago')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400">🤝 Promesa Activa</span>
            @elseif($status === 'promesa_incumplida')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-200 text-red-900 dark:bg-red-900 dark:text-red-300">⚠️ Promesa Incumplida</span>
            @elseif($status === 'sin_respuesta')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300">🔇 Sin Respuesta</span>
            @elseif($status === 'pago_realizado_oficial' || $status === 'pago_registrado')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400">✅ Cobrado</span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $status }}</span>
            @endif

            <!-- Computed Priority Badge -->
            @php $priority = $this->computedPriority; @endphp
            @if($priority === 'critica')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400">🔴 Prioridad Crítica</span>
            @elseif($priority === 'alta')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400">🟠 Prioridad Alta</span>
            @elseif($priority === 'media')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400">🟡 Prioridad Media</span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400">🟢 Prioridad Baja</span>
            @endif
        </div>
    </div>

    <!-- Alert Success / Fail -->
    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-200 dark:border-green-800 flex items-center justify-between" role="alert">
            <span class="font-medium">✨ {{ session('success') }}</span>
            <button type="button" class="text-green-800 dark:text-green-400 hover:opacity-75 font-bold" onclick="this.parentElement.remove()">✕</button>
        </div>
    @endif

    <!-- GRID LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- LEFT & CENTER COLUMNS (MAIN INFO) -->
        <div class="lg:col-span-2 space-y-8">

            <!-- ENCABEZADO DE LA FICHA -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-6">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Información del Cliente</h2>
                    <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">
                        {{ $this->cuota->operacion->cliente->nombre }} {{ $this->cuota->operacion->cliente->apellido }}
                    </h1>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 text-sm">
                    <div>
                        <span class="block text-xs font-medium text-gray-400">Documento</span>
                        <strong class="text-gray-950 dark:text-white">{{ $this->cuota->operacion->cliente->documento ?: 'No disponible' }}</strong>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-400">Teléfono</span>
                        <strong class="text-gray-950 dark:text-white">{{ $this->cuota->operacion->cliente->telefono ?: 'No disponible' }}</strong>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-400">Domicilio actual</span>
                        <strong class="text-gray-950 dark:text-white">{{ $this->cuota->operacion->cliente->domicilio ?: 'No disponible' }}</strong>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-400">Número de solicitud</span>
                        <strong class="text-indigo-600 dark:text-indigo-400">#{{ $this->cuota->operacion->numero_solicitud }}</strong>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-400">Número de cuota</span>
                        <strong class="text-gray-950 dark:text-white">{{ $this->cuota->numero_cuota }}</strong>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-400">Día de cobro mensual</span>
                        <strong class="text-gray-950 dark:text-white">Día {{ $this->cuota->dia_cobro }}</strong>
                    </div>
                </div>
            </div>

            <!-- INFORMACIÓN FINANCIERA (Oficial vs Gestión) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-white flex items-center gap-2">
                        💳 Situación Financiera de la Cuota
                    </h3>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        Preserva Datos Oficiales
                    </span>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-sm mb-6 pb-6 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <span class="block text-xs font-medium text-gray-400 mb-1">Importe Original</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($this->cuota->importe_original, 2) }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-400 mb-1">Punitorios Oficiales</span>
                            <span class="text-lg font-semibold text-red-600 dark:text-red-400">${{ number_format($this->cuota->punitorios, 2) }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-400 mb-1">Total Oficial Actualizado</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($this->cuota->total_actualizado, 2) }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-400 mb-1">Saldo Pendiente</span>
                            <span class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400">${{ number_format($this->cuota->saldo_pendiente, 2) }}</span>
                        </div>
                    </div>

                    <!-- Pagos y condonaciones resúmen -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div class="bg-gray-50 dark:bg-gray-700/20 p-4 rounded-lg space-y-2">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-300">Resumen de Pagos en App</h4>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Monto efectivamente cobrado:</span>
                                <span class="font-bold text-green-600">${{ number_format($this->cuota->pagos->sum('monto_cobrado'), 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Punitorios perdonados:</span>
                                <span class="font-bold text-amber-600">${{ number_format($this->cuota->pagos->sum('punitorios_perdonados'), 2) }}</span>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/20 p-4 rounded-lg space-y-2">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-300">Historial de Último Pago</h4>
                            @if($this->cuota->pagos->isNotEmpty())
                                @php $ultimoPago = $this->cuota->pagos->sortByDesc('fecha_pago')->first(); @endphp
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Monto del último pago:</span>
                                    <span class="font-bold">${{ number_format($ultimoPago->monto_cobrado, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Fecha del último pago:</span>
                                    <span class="font-medium">{{ \Carbon\Carbon::parse($ultimoPago->fecha_pago)->format('d/m/Y') }}</span>
                                </div>
                            @else
                                <div class="text-center text-xs text-gray-400 py-3">No hay pagos registrados aún.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRÓXIMA ACCIÓN RECOMENDADA -->
            @php $proxAccion = $this->proximaAccionRecomendada; @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Próxima Acción Recomendada</span>
                    <h4 class="text-lg font-extrabold text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                        💡 {{ $proxAccion['accion'] }}
                    </h4>
                    @if($proxAccion['fecha'])
                        <p class="text-xs text-gray-500">Programada para: <strong class="text-gray-700 dark:text-gray-300">{{ $proxAccion['fecha'] }}</strong></p>
                    @endif
                </div>
                @if($proxAccion['prioridad'] === 'critica')
                    <span class="bg-red-100 text-red-800 text-xs font-bold px-3 py-1 rounded-full uppercase">Crítica</span>
                @elseif($proxAccion['prioridad'] === 'alta')
                    <span class="bg-amber-100 text-amber-800 text-xs font-bold px-3 py-1 rounded-full uppercase">Alta</span>
                @else
                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full uppercase">Standard</span>
                @endif
            </div>

            <!-- TABBED HISTORIES -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <!-- Navigation Tabs -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <button @click="activeSection = 'gestiones'" :class="activeSection === 'gestiones' ? 'border-indigo-500 text-indigo-600 bg-white dark:bg-gray-800' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition">
                        📋 Gestiones ({{ $this->cuota->gestiones->count() }})
                    </button>
                    <button @click="activeSection = 'promesas'" :class="activeSection === 'promesas' ? 'border-indigo-500 text-indigo-600 bg-white dark:bg-gray-800' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition">
                        🤝 Promesas ({{ $this->cuota->promesasPago->count() }})
                    </button>
                    <button @click="activeSection = 'pagos'" :class="activeSection === 'pagos' ? 'border-indigo-500 text-indigo-600 bg-white dark:bg-gray-800' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition">
                        💵 Pagos ({{ $this->cuota->pagos->count() }})
                    </button>
                    <button @click="activeSection = 'visitas'" :class="activeSection === 'visitas' ? 'border-indigo-500 text-indigo-600 bg-white dark:bg-gray-800' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition">
                        🚴 Visitas ({{ $this->cuota->visitasCobrador->count() }})
                    </button>
                </div>

                <div class="p-6">
                    <!-- SECTION: GESTIONES -->
                    <div x-show="activeSection === 'gestiones'" class="space-y-6">
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @forelse($this->cuota->gestiones->sortByDesc('fecha_hora') as $gIdx => $gestion)
                                    <li>
                                        <div class="relative pb-8">
                                            @if($gIdx !== $this->cuota->gestiones->count() - 1)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center ring-8 ring-white text-white">
                                                        💬
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0 pt-1.5">
                                                    <div class="text-sm text-gray-500">
                                                        <strong class="font-semibold text-gray-900 dark:text-white">
                                                            {{ str_replace('_', ' ', ucwords($gestion->tipo)) }}
                                                        </strong>
                                                        por <span class="font-medium text-gray-700 dark:text-gray-300">{{ $gestion->user->name ?? 'John Gestor' }}</span>
                                                        <span class="whitespace-nowrap font-mono text-xs text-gray-400">
                                                            {{ \Carbon\Carbon::parse($gestion->fecha_hora)->format('d/m/Y H:i') }}
                                                        </span>
                                                    </div>
                                                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                                        <p class="font-bold text-xs uppercase text-amber-600 mb-1">
                                                            Resultado: {{ str_replace('_', ' ', $gestion->resultado) }}
                                                        </p>
                                                        @if($gestion->observacion)
                                                            <p class="italic text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/30 p-2.5 rounded-lg mt-1 border border-gray-100">
                                                                "{{ $gestion->observacion }}"
                                                            </p>
                                                        @endif
                                                        @if($gestion->proxima_accion)
                                                            <div class="mt-2 text-xs font-semibold text-indigo-600">
                                                                👉 Próxima Acción: {{ $gestion->proxima_accion }}
                                                                @if($gestion->proxima_accion_fecha)
                                                                    ({{ \Carbon\Carbon::parse($gestion->proxima_accion_fecha)->format('d/m/Y') }})
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <div class="text-center py-6 text-gray-500 text-sm">
                                        No hay gestiones registradas para esta cuota.
                                    </div>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <!-- SECTION: PROMESAS -->
                    <div x-show="activeSection === 'promesas'" class="space-y-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700 text-xs font-semibold text-gray-500 uppercase">
                                        <th class="p-3">Fecha Reg.</th>
                                        <th class="p-3">Fecha Prometida</th>
                                        <th class="p-3 text-right">Monto Prometido</th>
                                        <th class="p-3">Estado</th>
                                        <th class="p-3">Resolución</th>
                                        <th class="p-3">Observaciones / Usuario</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($this->cuota->promesasPago->sortByDesc('fecha_creacion') as $promesa)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="p-3 font-mono text-xs">{{ \Carbon\Carbon::parse($promesa->fecha_creacion)->format('d/m/Y') }}</td>
                                            <td class="p-3 font-semibold">{{ \Carbon\Carbon::parse($promesa->fecha_prometida)->format('d/m/Y') }}</td>
                                            <td class="p-3 text-right font-bold text-indigo-600">${{ number_format($promesa->monto_prometido, 2) }}</td>
                                            <td class="p-3">
                                                @if($promesa->estado === 'pendiente')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300">Pendiente ★</span>
                                                @elseif($promesa->estado === 'cumplida')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">Cumplida</span>
                                                @elseif($promesa->estado === 'incumplida')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">Incumplida</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-500">Cancelada</span>
                                                @endif
                                            </td>
                                            <td class="p-3 text-xs text-gray-500">
                                                {{ $promesa->fecha_resolucion ? \Carbon\Carbon::parse($promesa->fecha_resolucion)->format('d/m/Y') : '-' }}
                                            </td>
                                            <td class="p-3 text-xs">
                                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $promesa->observaciones }}</div>
                                                <div class="text-gray-400 font-mono">{{ $promesa->user->name ?? 'Gestor' }}</div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="p-4 text-center text-gray-500">No hay promesas registradas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- SECTION: PAGOS -->
                    <div x-show="activeSection === 'pagos'" class="space-y-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700 text-xs font-semibold text-gray-500 uppercase">
                                        <th class="p-3">Fecha</th>
                                        <th class="p-3 text-right">Snap Original</th>
                                        <th class="p-3 text-right">Snap Puni.</th>
                                        <th class="p-3 text-right">Snap Total</th>
                                        <th class="p-3 text-right">Monto Cobrado</th>
                                        <th class="p-3 text-right">Punitorios Perd.</th>
                                        <th class="p-3">Tipo / Medio</th>
                                        <th class="p-3">Obs. / Usuario</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($this->cuota->pagos->sortByDesc('fecha_pago') as $pago)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="p-3 font-mono text-xs">{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
                                            <td class="p-3 text-right text-xs text-gray-500">${{ number_format($pago->importe_original_snapshot, 2) }}</td>
                                            <td class="p-3 text-right text-xs text-gray-500">${{ number_format($pago->punitorios_snapshot, 2) }}</td>
                                            <td class="p-3 text-right text-xs text-gray-500">${{ number_format($pago->total_actualizado_snapshot, 2) }}</td>
                                            <td class="p-3 text-right font-bold text-green-600">${{ number_format($pago->monto_cobrado, 2) }}</td>
                                            <td class="p-3 text-right font-semibold text-amber-600">${{ number_format($pago->punitorios_perdonados, 2) }}</td>
                                            <td class="p-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $pago->es_cancelatorio ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                                    {{ $pago->es_cancelatorio ? 'Completo' : 'Parcial' }}
                                                </span>
                                                <div class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mt-0.5">{{ $pago->medio_pago }}</div>
                                            </td>
                                            <td class="p-3 text-xs">
                                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $pago->observaciones }}</div>
                                                <div class="text-gray-400 font-mono">{{ $pago->user->name ?? 'Cobrador' }}</div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="p-4 text-center text-gray-500">No hay pagos registrados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- SECTION: VISITAS -->
                    <div x-show="activeSection === 'visitas'" class="space-y-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700 text-xs font-semibold text-gray-500 uppercase">
                                        <th class="p-3">Cobrador</th>
                                        <th class="p-3">Domicilio Congelado</th>
                                        <th class="p-3">Fecha Prog.</th>
                                        <th class="p-3">Fecha Realizada</th>
                                        <th class="p-3">Estado</th>
                                        <th class="p-3">Resultado</th>
                                        <th class="p-3 text-right">Monto Cobrado</th>
                                        <th class="p-3">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($this->cuota->visitasCobrador->sortByDesc('fecha_programada') as $visita)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="p-3 font-semibold">{{ $visita->cobrador->name }}</td>
                                            <td class="p-3 text-xs italic">{{ $visita->domicilio }}</td>
                                            <td class="p-3 font-mono text-xs">{{ \Carbon\Carbon::parse($visita->fecha_programada)->format('d/m/Y') }}</td>
                                            <td class="p-3 font-mono text-xs">{{ $visita->fecha_realizada ? \Carbon\Carbon::parse($visita->fecha_realizada)->format('d/m/Y') : '-' }}</td>
                                            <td class="p-3">
                                                @if($visita->estado === 'pendiente')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Pendiente</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">Realizada</span>
                                                @endif
                                            </td>
                                            <td class="p-3 text-xs font-bold uppercase text-gray-600">{{ $visita->resultado ?: '-' }}</td>
                                            <td class="p-3 text-right font-bold">${{ number_format($visita->monto_cobrado, 2) }}</td>
                                            <td class="p-3 text-xs text-gray-500">{{ $visita->observaciones }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="p-4 text-center text-gray-500">No hay visitas de cobrador programadas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RELACIÓN SOLICITUD / CUOTA -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🔗 Otras Cuotas de esta Solicitud / Crédito
                </h3>
                <p class="text-xs text-gray-500">
                    La solicitud <strong class="text-indigo-600">#{{ $this->cuota->operacion->numero_solicitud }}</strong> puede poseer múltiples cuotas con compromisos independientes.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Current Cuota -->
                    <div class="border-2 border-indigo-500 bg-indigo-50/20 p-4 rounded-lg relative">
                        <span class="absolute top-2 right-2 text-xs font-extrabold text-indigo-600 bg-indigo-100 px-1.5 py-0.5 rounded">Viendo</span>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Cuota {{ $this->cuota->numero_cuota }}</h4>
                        <p class="text-xs text-gray-500 mt-1">Saldo: ${{ number_format($this->cuota->saldo_pendiente, 2) }}</p>
                        <p class="text-xs font-semibold text-indigo-600 mt-2">Día de cobro: {{ $this->cuota->dia_cobro }}</p>
                    </div>

                    @forelse($this->otrasCuotas as $oCuota)
                        <div class="border border-gray-100 dark:border-gray-700 p-4 rounded-lg hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='/ficha-gestion/{{ $oCuota->id }}'">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Cuota {{ $oCuota->numero_cuota }}</h4>
                            <p class="text-xs text-gray-500 mt-1">Saldo: ${{ number_format($oCuota->saldo_pendiente, 2) }}</p>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Estado</span>
                                @if($oCuota->computed_status === 'pago_registrado' || $oCuota->computed_status === 'pago_realizado_oficial')
                                    <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded">Cobrado</span>
                                @else
                                    <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded">{{ $oCuota->computed_status }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-1 sm:col-span-2 md:col-span-3 text-center py-4 text-xs text-gray-400 italic">
                            No hay otras cuotas vinculadas a esta solicitud de crédito.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN (ACCIONES RÁPIDAS & HERRAMIENTAS) -->
        <div class="space-y-8">

            <!-- ACCIONES RÁPIDAS PANEL -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <h3 class="font-extrabold text-lg text-gray-950 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-700">
                    ⚡ Registrar Nueva Acción
                </h3>

                <div class="grid grid-cols-1 gap-3">
                    <!-- Registrar Gestión -->
                    <button wire:click="openGestionModal" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 border border-transparent rounded-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow">
                        📝 Registrar Gestión
                    </button>

                    <!-- Registrar Promesa -->
                    @if($this->cuota->saldo_pendiente > 0)
                        <button wire:click="openPromesaModal" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 border border-transparent rounded-lg text-sm font-bold text-white bg-indigo-500 hover:bg-indigo-600 transition shadow">
                            🤝 Registrar Promesa de Pago
                        </button>
                    @endif

                    <!-- Programar Cobrador -->
                    @if($this->cuota->saldo_pendiente > 0)
                        <button wire:click="openCobradorModal" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 border border-transparent rounded-lg text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 transition shadow">
                            🚴 Programar Visita Cobrador
                        </button>
                    @endif

                    <!-- Registrar Pago -->
                    @if($this->cuota->saldo_pendiente > 0)
                        <button wire:click="openPagoModal" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 border border-transparent rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow">
                            💵 Registrar Pago / Cobro
                        </button>
                    @endif

                    <!-- Registrar Resultado Visitas (list of pending visits) -->
                    @if($this->cuota->visitasCobrador->where('estado', 'pendiente')->isNotEmpty())
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-2 space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Registrar Visitas Pendientes</span>
                            @foreach($this->cuota->visitasCobrador->where('estado', 'pendiente') as $pVisita)
                                <button wire:click="openVisitaResultadoModal({{ $pVisita->id }})" class="w-full inline-flex justify-between items-center gap-2 px-3 py-2 border border-amber-300 rounded-lg text-xs font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 transition">
                                    <span>🚴 Resultado: {{ $pVisita->cobrador->name }}</span>
                                    <span>➔</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- LINK DE PAGO -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🔗 Link de Pago Oficial
                </h3>
                @if($this->cuota->link_pago)
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-100 dark:border-gray-600 font-mono text-xs text-gray-700 dark:text-gray-300 break-all select-all">
                        {{ $this->cuota->link_pago }}
                    </div>
                    <div class="grid grid-cols-1 gap-2">
                        <button onclick="navigator.clipboard.writeText('{{ $this->cuota->link_pago }}'); alert('¡Link de pago copiado al portapapeles!')" class="inline-flex justify-center items-center gap-2 px-3 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            📋 Copiar Link de Pago
                        </button>
                    </div>
                @else
                    <div class="text-center py-3 text-xs text-gray-400 italic">
                        Esta cuota no posee un link de pago asociado.
                    </div>
                @endif
            </div>

            <!-- MENSAJES DE COBRANZA -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    ✉️ Plantillas de Mensajes de Cobranza
                </h3>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1">Seleccionar Plantilla Activa</label>
                    <select wire:model.live="selectedPlantillaId" class="w-full text-xs p-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Seleccione una plantilla...</option>
                        @foreach($this->plantillas as $plantilla)
                            <option value="{{ $plantilla->id }}">{{ $plantilla->titulo }} ({{ $plantilla->categoria }})</option>
                        @endforeach
                    </select>
                </div>

                @if($previewMensaje)
                    <div class="space-y-3">
                        <span class="block text-xs font-semibold text-gray-400">Mensaje con Variables Dinámicas</span>
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-100 dark:border-gray-600 text-xs text-gray-800 dark:text-gray-300 break-words whitespace-pre-line font-medium leading-relaxed">
                            {{ $previewMensaje }}
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="navigator.clipboard.writeText($wire.previewMensaje); alert('¡Mensaje copiado al portapapeles!')" class="inline-flex justify-center items-center gap-1.5 px-2.5 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                📋 Copiar Mensaje
                            </button>
                            @if($this->cuota->operacion->cliente->telefono)
                                <a href="{{ $this->whatsappUrl }}" target="_blank" class="inline-flex justify-center items-center gap-1.5 px-2.5 py-2 text-xs font-bold text-white bg-green-500 rounded-lg hover:bg-green-600 transition shadow">
                                    💬 Abrir WhatsApp
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-4 text-xs text-gray-400 italic">
                        Seleccione una plantilla para autogenerar el texto con las variables del cliente.
                    </div>
                @endif
            </div>

            <!-- CLIENTES SIN RESPUESTA AUDIT PANEL -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🔇 Auditoría de Contacto (Sin Respuesta)
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                        <span class="text-gray-500 text-xs">Intentos de contacto:</span>
                        <strong class="font-bold text-gray-900 dark:text-white">{{ $this->cuota->gestiones->count() }} intentos</strong>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                        <span class="text-gray-500 text-xs">Último intento:</span>
                        <strong class="font-medium text-gray-950 dark:text-white">
                            @if($this->cuota->gestiones->isNotEmpty())
                                {{ \Carbon\Carbon::parse($this->cuota->gestiones->sortByDesc('fecha_hora')->first()->fecha_hora)->format('d/m/Y') }}
                            @else
                                Sin gestiones
                            @endif
                        </strong>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                        <span class="text-gray-500 text-xs">Último resultado:</span>
                        <strong class="font-bold uppercase text-xs text-amber-600">
                            @if($this->cuota->gestiones->isNotEmpty())
                                {{ $this->cuota->gestiones->sortByDesc('fecha_hora')->first()->resultado }}
                            @else
                                Sin contacto
                            @endif
                        </strong>
                    </div>
                    <div class="flex justify-between items-center pb-1">
                        <span class="text-gray-500 text-xs">Visita programada:</span>
                        @if($this->cuota->visitasCobrador()->where('estado', 'pendiente')->exists())
                            <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded">Programada ✔</span>
                        @else
                            <span class="text-xs font-medium text-red-600 bg-red-50 px-2 py-0.5 rounded">No programada</span>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL OVERLAYS -->
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
                                    @foreach(User::where('role', 'cobrador')->get() as $cob)
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 font-bold text-red-600">Confirmar Domicilio Congelado (Snapshot)</label>
                                <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-200 text-xs italic text-gray-700 font-mono">
                                    {{ $this->cuota->operacion->cliente->domicilio ?: 'Sin domicilio cargado' }}
                                </div>
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
                        <form wire:submit.prevent="submitPago" class="space-y-4" x-data="{
                            pagoMonto: @entangle('pago_monto_cobrado'),
                            punitoriosPerd: @entangle('pago_punitorios_perdonados'),
                            puniMax: {{ $this->cuota->punitorios }},
                            saldoMax: {{ $this->cuota->saldo_pendiente }},
                            original: {{ $this->cuota->importe_original }},
                            totalAct: {{ $this->cuota->total_actualizado }}
                        }">
                            <div class="bg-amber-50 p-4 rounded-lg border border-amber-200 text-xs text-amber-800 space-y-2 mb-4">
                                <h4 class="font-bold">Antes de confirmar, verifique las cifras:</h4>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>Importe Original: <strong>${{ number_format($this->cuota->importe_original, 2) }}</strong></div>
                                    <div>Punitorios Actuales: <strong>${{ number_format($this->cuota->punitorios, 2) }}</strong></div>
                                    <div>Total Actualizado: <strong>${{ number_format($this->cuota->total_actualizado, 2) }}</strong></div>
                                    <div>Saldo Pendiente: <strong class="text-indigo-600">${{ number_format($this->cuota->saldo_pendiente, 2) }}</strong></div>
                                </div>
                                <div class="border-t border-amber-200 pt-2 font-semibold">
                                    Monto a registrar: $<span x-text="pagoMonto"></span> (Condonación de punitorios: $<span x-text="punitoriosPerd"></span>)
                                </div>
                            </div>

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
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Confirmar y Registrar Pago</button>
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Realización</label>
                                <input wire:model="visita_fecha_realizada" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('visita_fecha_realizada') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
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

<div class="max-w-7xl mx-auto p-4 sm:p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 mt-6">

    {{-- Tabs de navegación --}}
    <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
        <button wire:click="switchTab('import')" class="py-3 px-6 font-semibold text-sm border-b-2 transition duration-150 focus:outline-none flex items-center gap-2 {{ $activeTab === 'import' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            📥 Importar Cartera
        </button>
        <button wire:click="switchTab('history')" class="py-3 px-6 font-semibold text-sm border-b-2 transition duration-150 focus:outline-none flex items-center gap-2 {{ $activeTab === 'history' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            📜 Historial y Auditoría
        </button>
    </div>

    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-800 rounded-lg flex items-start gap-3 shadow-sm">
            <span class="text-xl">⚠️</span>
            <div>
                <strong class="font-bold">Atención:</strong>
                <p class="text-sm mt-0.5">{{ $errorMessage }}</p>
            </div>
        </div>
    @endif

    @if(session()->has('period_success'))
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-800 rounded-lg flex items-start gap-3 shadow-sm">
            <span class="text-xl">✅</span>
            <div>
                <strong class="font-bold">Excelente:</strong>
                <p class="text-sm mt-0.5">{{ session('period_success') }}</p>
            </div>
        </div>
    @endif

    {{-- VISTA DE IMPORTACIÓN --}}
    @if($activeTab === 'import')
        {{-- PASO 1: SUBIR ARCHIVO Y SELECCIONAR PERIODO --}}
        @if($step === 'upload')
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- Panel izquierdo: Período --}}
                <div class="lg:col-span-5 p-6 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200 flex items-center gap-2">
                        <span>📅</span> 1. Período de Cobranza
                    </h3>

                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Seleccionar Período de Trabajo</label>
                        <select wire:model="periodo_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">-- Seleccionar período --</option>
                            @foreach($periodos as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->mes }}/{{ $p->anio }}) {{ $p->activo ? '[ACTIVO]' : '' }}</option>
                            @endforeach
                        </select>
                        @error('periodo_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 my-5 pt-5">
                        <h4 class="text-sm font-bold mb-4 text-gray-700 dark:text-gray-300 flex items-center gap-1">
                            <span>➕</span> Crear nuevo período para este mes:
                        </h4>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Nombre descriptivo</label>
                                <input type="text" wire:model="nuevo_periodo_nombre" placeholder="Ej: Agosto 2026" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('nuevo_periodo_nombre') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Mes (1-12)</label>
                                    <input type="number" wire:model="nuevo_periodo_mes" min="1" max="12" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('nuevo_periodo_mes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Año</label>
                                    <input type="number" wire:model="nuevo_periodo_anio" min="2020" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('nuevo_periodo_anio') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Monto Objetivo de Recupero ($)</label>
                                <input type="number" wire:model="nuevo_periodo_objetivo" step="0.01" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('nuevo_periodo_objetivo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <button type="button" wire:click="crearPeriodo" class="w-full bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2.5 px-4 rounded-lg shadow-md transition duration-150">
                                Crear y Seleccionar Período
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Panel derecho: Archivo --}}
                <div class="lg:col-span-7 p-6 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <span>📂</span> 2. Cargar Planilla
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Suba el archivo de cartera (.xlsx o .csv) provisto por los sistemas oficiales. Los datos de gestión manual serán protegidos y preservados intactos.</p>

                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer relative transition duration-150">
                            <input type="file" wire:model="file" accept=".xlsx,.csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <svg class="mx-auto h-12 w-12 text-indigo-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 font-medium">Haga clic o arrastre el archivo aquí</p>
                            <p class="text-xs text-gray-400 mt-1">Soporta formatos XLSX y CSV</p>
                        </div>

                        <div wire:loading wire:target="file" class="mt-4 text-indigo-600 dark:text-indigo-400 text-sm font-semibold text-center w-full animate-pulse">
                            🔄 Leyendo y analizando estructura del archivo...
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- PASO 2: MAPEO DE COLUMNAS --}}
        @if($step === 'mapping')
            <div>
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-400 text-yellow-800 dark:text-yellow-200 rounded-lg mb-6 text-sm flex gap-3 items-center">
                    <span class="text-xl">💡</span>
                    <div>
                        <strong>Estructura Detectada:</strong> El archivo contiene <span class="font-bold">{{ count($availableHeaders) }} columnas</span>. Por favor asocie las columnas requeridas por el sistema con los campos de su planilla.
                    </div>
                </div>

                <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200 border-b dark:border-gray-700 pb-2 flex items-center gap-2">
                    <span>🔄</span> 3. Asociar Columnas de Cartera
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    @foreach($mapping as $systemField => $mappedValue)
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5 capitalize">
                                {{ str_replace('_', ' ', $systemField) }}
                                @if(in_array($systemField, ['numero_solicitud', 'numero_cuota', 'nombre', 'apellido', 'importe_original']))
                                    <span class="text-red-500 font-bold">*</span>
                                @endif
                            </label>
                            <select wire:model="mapping.{{ $systemField }}" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- No mapeado (Opcional) --</option>
                                @foreach($availableHeaders as $header)
                                    <option value="{{ $header }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between mt-6">
                    <button type="button" wire:click="resetImport" class="bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-bold py-2.5 px-6 rounded-lg shadow-md transition duration-150">
                        ↩ Volver a Subir
                    </button>
                    <button type="button" wire:click="previsualizar" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition duration-150 flex items-center gap-1">
                        👁️ Siguiente (Previsualizar)
                    </button>
                </div>
            </div>
        @endif

        {{-- PASO 3: PREVISUALIZACIÓN --}}
        @if($step === 'preview')
            <div>
                <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200 border-b dark:border-gray-700 pb-2">
                    📊 Vista Previa de la Sincronización
                </h3>

                {{-- Archivo cargado info --}}
                <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg flex flex-col sm:flex-row sm:justify-between sm:items-center border border-indigo-100 dark:border-indigo-800 gap-4 text-sm">
                    <div>
                        <p class="font-bold text-indigo-900 dark:text-indigo-300">📄 Archivo: <span class="font-normal">{{ $fileName }}</span></p>
                        <p class="text-xs text-indigo-700 dark:text-indigo-400 mt-1">Tamaño: {{ number_format($fileSize / 1024, 2) }} KB</p>
                    </div>
                    <div>
                        <p class="font-bold text-indigo-900 dark:text-indigo-300">📅 Período de Cobranza: <span class="font-normal">{{ \App\Models\PeriodoCobranza::find($periodo_id)?->nombre }}</span></p>
                    </div>
                </div>

                {{-- Métricas Proyectadas --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl text-center border dark:border-gray-700">
                        <span class="block text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ $previewTotalRows }}</span>
                        <span class="text-[11px] text-gray-500 uppercase font-bold block mt-1">Filas Totales</span>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl text-center border border-green-200 dark:border-green-800">
                        <span class="block text-3xl font-extrabold text-green-600 dark:text-green-400">{{ $previewNewRows }}</span>
                        <span class="text-[11px] text-green-500 uppercase font-bold block mt-1">Nuevas Cuotas</span>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl text-center border border-blue-200 dark:border-blue-800">
                        <span class="block text-3xl font-extrabold text-blue-600 dark:text-blue-400">{{ $previewUpdatedRows }}</span>
                        <span class="text-[11px] text-blue-500 uppercase font-bold block mt-1">Actualizaciones</span>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-xl text-center border border-yellow-200 dark:border-yellow-800">
                        <span class="block text-3xl font-extrabold text-yellow-600 dark:text-yellow-400">{{ $previewValidRows - $previewNewRows - $previewUpdatedRows }}</span>
                        <span class="text-[11px] text-yellow-500 uppercase font-bold block mt-1">Sin Cambios</span>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-xl text-center border border-red-200 dark:border-red-800">
                        <span class="block text-3xl font-extrabold text-red-600 dark:text-red-400">{{ $previewErrorRows }}</span>
                        <span class="text-[11px] text-red-500 uppercase font-bold block mt-1">Errores</span>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-xl text-center border border-purple-200 dark:border-purple-800">
                        <span class="block text-3xl font-extrabold text-purple-600 dark:text-purple-400">{{ $previewAbsentRows }}</span>
                        <span class="text-[11px] text-purple-500 uppercase font-bold block mt-1">Ausentes</span>
                    </div>
                </div>

                {{-- Alerta crítica sobre registros ausentes --}}
                @if($previewAbsentRows > 0)
                    <div class="p-5 bg-amber-50 dark:bg-amber-950/40 border-l-4 border-amber-500 text-amber-900 dark:text-amber-200 rounded-xl mb-6 shadow-sm">
                        <h4 class="font-bold text-base flex items-center gap-2">
                            <span>⚠️</span> Alerta de Registros Ausentes
                        </h4>
                        <p class="text-sm mt-1.5">
                            La nueva planilla contiene <span class="font-bold">{{ $previewAbsentRows }} registros menos</span> que la cartera actual. Los registros ausentes pueden ser marcados como pagados oficialmente según las reglas de sincronización.
                        </p>
                        <div class="mt-4">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-amber-950 dark:text-amber-100 cursor-pointer">
                                <input type="checkbox" wire:model="confirm_absents" class="rounded border-amber-400 text-amber-600 focus:ring-amber-500 h-4 w-4">
                                <span>Entiendo y confirmo de forma explícita que deseo procesar los registros ausentes y cambiar su estado financiero a "Ausente en última importación".</span>
                            </label>
                        </div>
                    </div>
                @endif

                {{-- Primeras filas como vista previa --}}
                <div class="mb-6">
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1">
                        <span>📋</span> Primeras 10 filas de muestra:
                    </h4>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-900">
                        <table class="w-full text-left border-collapse text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-b dark:border-gray-700">
                                    <th class="py-3 px-4 font-semibold">Línea</th>
                                    <th class="py-3 px-4 font-semibold">Solicitud</th>
                                    <th class="py-3 px-4 font-semibold">Cuota</th>
                                    <th class="py-3 px-4 font-semibold">Cliente</th>
                                    <th class="py-3 px-4 font-semibold text-right">Monto</th>
                                    <th class="py-3 px-4 font-semibold text-right">Punitorios</th>
                                    <th class="py-3 px-4 font-semibold text-center">Día Cobro</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-600 dark:text-gray-400">
                                @forelse($previewSampleRows as $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-850">
                                        <td class="py-2.5 px-4 font-medium text-gray-500">{{ $row['numero_linea'] }}</td>
                                        <td class="py-2.5 px-4 font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ $row['numero_solicitud'] }}</td>
                                        <td class="py-2.5 px-4 text-center">{{ $row['numero_cuota'] }}</td>
                                        <td class="py-2.5 px-4">{{ $row['nombre'] }} {{ $row['apellido'] }}</td>
                                        <td class="py-2.5 px-4 text-right font-medium">$ {{ number_format(floatval($row['importe_original']), 2) }}</td>
                                        <td class="py-2.5 px-4 text-right text-amber-600 font-medium">$ {{ number_format(floatval($row['punitorios']), 2) }}</td>
                                        <td class="py-2.5 px-4 text-center">{{ $row['dia_cobro'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-gray-400">Ningún registro válido de muestra para mostrar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Listado de errores de previsualización --}}
                @if(count($previewErrors) > 0)
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-red-600 dark:text-red-400 mb-3 flex items-center gap-1">
                            <span>❌</span> Muestra de Errores detectados en la validación previa (No se importarán):
                        </h4>
                        <div class="bg-red-50 dark:bg-red-950/20 rounded-xl border border-red-100 dark:border-red-900 p-4 max-h-60 overflow-y-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-red-200 dark:border-red-800 text-red-800 dark:text-red-300">
                                        <th class="py-1.5 w-1/12">Fila</th>
                                        <th class="py-1.5 w-7/12">Detalle del Error</th>
                                        <th class="py-1.5 w-4/12">Datos Crudos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-red-100 dark:divide-red-950 text-red-700 dark:text-red-400">
                                    @foreach($previewErrors as $err)
                                        <tr>
                                            <td class="py-2 font-bold whitespace-nowrap">Línea {{ $err['numero_linea'] }}</td>
                                            <td class="py-2 pr-4">{{ $err['detalles_error'] }}</td>
                                            <td class="py-2 text-gray-500 dark:text-gray-400 font-mono text-[10px] truncate max-w-xs" title="{{ $err['datos_crudos'] }}">{{ $err['datos_crudos'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row justify-between mt-8 gap-4">
                    <button type="button" wire:click="$set('step', 'mapping')" class="bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-bold py-3 px-6 rounded-lg shadow transition duration-150 order-2 sm:order-1">
                        ↩ Cambiar Mapeo
                    </button>
                    <button type="button" wire:click="procesarImportacion" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-10 rounded-lg shadow-lg transition duration-150 order-1 sm:order-2 flex justify-center items-center gap-2">
                        <span wire:loading wire:target="procesarImportacion" class="animate-spin border-2 border-white border-t-transparent rounded-full h-4 w-4"></span>
                        <span>Confirmar e Importar Cartera</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- PASO 4: RESULTADO DE LA IMPORTACION --}}
        @if($step === 'results')
            <div>
                <div class="p-6 bg-green-50 dark:bg-green-950/30 border-l-4 border-green-500 text-green-800 dark:text-green-200 rounded-xl mb-6 shadow-sm flex gap-4 items-start">
                    <span class="text-2xl">🎉</span>
                    <div>
                        <h3 class="font-bold text-lg">¡Importación Completada de Forma Segura!</h3>
                        <p class="text-sm mt-0.5">La cartera ha sido sincronizada con el motor de base de datos oficial de cobranzas. Los cambios financieros han sido auditados.</p>
                    </div>
                </div>

                <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200 border-b dark:border-gray-700 pb-2">Estadísticas de la Ejecución</h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl text-center border dark:border-gray-700">
                        <span class="block text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ $importResults['cantidad_registros'] }}</span>
                        <span class="text-xs text-gray-500 uppercase font-bold block mt-1">Registros Exitosos</span>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl text-center border border-green-200 dark:border-green-800">
                        <span class="block text-3xl font-extrabold text-green-600 dark:text-green-400">{{ $importResults['registros_nuevos'] }}</span>
                        <span class="text-xs text-green-500 uppercase font-bold block mt-1">Nuevas Cuotas</span>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl text-center border border-blue-200 dark:border-blue-800">
                        <span class="block text-3xl font-extrabold text-blue-600 dark:text-blue-400">{{ $importResults['registros_actualizados'] }}</span>
                        <span class="text-xs text-blue-500 uppercase font-bold block mt-1">Actualizadas</span>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-xl text-center border border-yellow-200 dark:border-yellow-800">
                        <span class="block text-3xl font-extrabold text-yellow-600 dark:text-yellow-400">{{ $importResults['registros_sin_cambios'] }}</span>
                        <span class="text-xs text-yellow-500 uppercase font-bold block mt-1">Sin Cambios</span>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-xl text-center border border-purple-200 dark:border-purple-800">
                        <span class="block text-3xl font-extrabold text-purple-600 dark:text-purple-400">{{ $importResults['registros_ausentes'] }}</span>
                        <span class="text-xs text-purple-500 uppercase font-bold block mt-1">Ausentes Marcados</span>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-xl text-center border border-red-200 dark:border-red-800">
                        <span class="block text-3xl font-extrabold text-red-600 dark:text-red-400">{{ $importResults['registros_errores'] }}</span>
                        <span class="text-xs text-red-500 uppercase font-bold block mt-1">Registros con Error</span>
                    </div>
                </div>

                @if(count($importErrors) > 0)
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-red-600 mb-3 flex items-center gap-1">
                            <span>❌</span> Detalles de Filas con Error (No Procesadas):
                        </h4>
                        <div class="bg-red-50 dark:bg-red-950/20 rounded-xl border border-red-100 dark:border-red-900 p-4 max-h-60 overflow-y-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-red-200 dark:border-red-800 text-red-800 dark:text-red-300">
                                        <th class="py-1.5 w-1/12">Fila</th>
                                        <th class="py-1.5 w-6/12">Descripción del Error</th>
                                        <th class="py-1.5 w-5/12">Datos de la Fila</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-red-100 dark:divide-red-950 text-red-750 dark:text-red-400">
                                    @foreach($importErrors as $err)
                                        <tr>
                                            <td class="py-2.5 font-bold whitespace-nowrap">Fila {{ $err['numero_linea'] }}</td>
                                            <td class="py-2.5 pr-2">{{ $err['detalles_error'] }}</td>
                                            <td class="py-2.5 text-gray-500 font-mono text-[10px] truncate max-w-xs" title="{{ $err['datos_crudos'] }}">{{ $err['datos_crudos'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="flex justify-center mt-8">
                    <button type="button" wire:click="resetImport" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-10 rounded-lg shadow-lg transition duration-150">
                        📥 Importar Nueva Cartera
                    </button>
                </div>
            </div>
        @endif
    @endif

    {{-- VISTA DE HISTORIAL Y AUDITORÍA --}}
    @if($activeTab === 'history')
        @if(!$selectedImportId)
            {{-- Panel de Filtros del Historial --}}
            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1.5">
                    <span>🔍</span> Filtrar Historial
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Período</label>
                        <select wire:model.live="filterPeriodo" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            @foreach($periodos as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Fecha</label>
                        <input type="date" wire:model.live="filterFecha" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Usuario</label>
                        <select wire:model.live="filterUsuario" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Estado</label>
                        <select wire:model.live="filterEstado" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="procesando">Procesando</option>
                            <option value="completada">Completada</option>
                            <option value="fallida">Fallida</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Tipo de Archivo</label>
                        <select wire:model.live="filterTipoArchivo" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="csv">CSV</option>
                            <option value="xlsx">XLSX</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Tabla de Historial --}}
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-900">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-b dark:border-gray-700">
                            <th class="py-3 px-4 font-semibold">Fecha y Hora</th>
                            <th class="py-3 px-4 font-semibold">Usuario</th>
                            <th class="py-3 px-4 font-semibold">Archivo / Tipo</th>
                            <th class="py-3 px-4 font-semibold">Período</th>
                            <th class="py-3 px-4 font-semibold text-center">Registros</th>
                            <th class="py-3 px-4 font-semibold text-center text-green-600">Nuevos</th>
                            <th class="py-3 px-4 font-semibold text-center text-blue-600">Actualiz.</th>
                            <th class="py-3 px-4 font-semibold text-center text-purple-600">Ausentes</th>
                            <th class="py-3 px-4 font-semibold text-center text-red-600">Errores</th>
                            <th class="py-3 px-4 font-semibold text-center">Estado</th>
                            <th class="py-3 px-4 font-semibold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-600 dark:text-gray-400">
                        @forelse($importaciones as $imp)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-850">
                                <td class="py-3.5 px-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($imp->fecha_hora)->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3.5 px-4 font-medium">{{ $imp->user?->name ?? 'Sistema' }}</td>
                                <td class="py-3.5 px-4 max-w-xs truncate" title="{{ $imp->nombre_archivo }}">
                                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ $imp->nombre_archivo }}</span>
                                    <span class="block text-[10px] text-gray-400 uppercase font-semibold">{{ $imp->tipo_archivo }}</span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">{{ $imp->periodoCobranza?->nombre }}</td>
                                <td class="py-3.5 px-4 text-center font-bold">{{ $imp->cantidad_registros }}</td>
                                <td class="py-3.5 px-4 text-center text-green-600 font-bold bg-green-50/20">{{ $imp->registros_nuevos }}</td>
                                <td class="py-3.5 px-4 text-center text-blue-600 font-bold bg-blue-50/20">{{ $imp->registros_actualizados }}</td>
                                <td class="py-3.5 px-4 text-center text-purple-600 font-bold bg-purple-50/20">{{ $imp->registros_ausentes }}</td>
                                <td class="py-3.5 px-4 text-center text-red-600 font-bold bg-red-50/20">{{ $imp->registros_errores }}</td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    @if($imp->estado === 'completada')
                                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">COMPLETADA</span>
                                    @elseif($imp->estado === 'procesando')
                                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 animate-pulse">PROCESANDO</span>
                                    @else
                                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">FALLIDA</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button wire:click="viewImportDetails({{ $imp->id }})" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60 font-bold py-1 px-3 rounded text-xs transition duration-150">
                                        🔍 Auditar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-12 text-center text-gray-400 dark:text-gray-500">Ninguna importación registrada en el historial.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            {{-- DETALLE / DIAGNÓSTICO DE UNA IMPORTACIÓN --}}
            <div>
                {{-- Botón Volver --}}
                <div class="mb-5 flex justify-between items-center">
                    <button wire:click="closeImportDetails" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-bold py-2 px-5 rounded-lg text-sm transition duration-150 flex items-center gap-1">
                        ⬅ Volver al Historial
                    </button>
                    <span class="text-xs text-gray-500 font-medium">Auditoría Avanzada ID: #{{ $selectedImport->id }}</span>
                </div>

                {{-- Tarjeta de Resumen del Detalle --}}
                <div class="p-5 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-800 rounded-xl mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-xs sm:text-sm">
                    <div>
                        <p class="text-gray-500">Planilla Oficial</p>
                        <p class="font-bold text-gray-900 dark:text-white mt-0.5 truncate">{{ $selectedImport->nombre_archivo }}</p>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mt-1">{{ $selectedImport->tipo_archivo }} ({{ number_format($selectedImport->cantidad_registros) }} registros)</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Ejecutado por</p>
                        <p class="font-bold text-gray-900 dark:text-white mt-0.5">{{ $selectedImport->user?->name ?? 'Sistema' }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">{{ \Carbon\Carbon::parse($selectedImport->fecha_hora)->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Período de Trabajo</p>
                        <p class="font-bold text-gray-900 dark:text-white mt-0.5">{{ $selectedImport->periodoCobranza?->nombre }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Estado de la Sincronización</p>
                        <p class="mt-1">
                            @if($selectedImport->estado === 'completada')
                                <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">COMPLETADA</span>
                            @else
                                <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">FALLIDA</span>
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Panel de Filtros para Detalles --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 mb-6 text-xs sm:text-sm">
                    <h3 class="font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1.5">
                        <span>🔍</span> Filtrar Detalles del Logs
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Acción / Estado</label>
                            <select wire:model.live="filterDetailAction" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-indigo-500">
                                <option value="">Todas</option>
                                <option value="creado">Creado (Nueva Cuota)</option>
                                <option value="actualizado_importe">Importe Base Actualizado</option>
                                <option value="actualizado_punitorios">Punitorios Actualizados</option>
                                <option value="actualizado_importe_y_punitorios">Importe y Punitorios Actualizados</option>
                                <option value="actualizado">Actualizado Genérico</option>
                                <option value="marcado_ausente">Marcado Ausente (Pago Directo)</option>
                                <option value="error">Error de Validación</option>
                                <option value="duplicado_benigno">Duplicado Omitido</option>
                                <option value="sin_cambios">Sin Cambios</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Nro Solicitud</label>
                            <input type="text" wire:model.live="filterDetailSolicitud" placeholder="Buscar por Solicitud" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Nro Cuota</label>
                            <input type="text" wire:model.live="filterDetailCuota" placeholder="Buscar por Cuota" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                {{-- Tabla de Logs de Auditoría --}}
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-900">
                    <table class="w-full text-left border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-b dark:border-gray-700">
                                <th class="py-3 px-4 font-semibold w-1/12 text-center">Fila</th>
                                <th class="py-3 px-4 font-semibold w-2/12">Acción</th>
                                <th class="py-3 px-4 font-semibold w-2/12">Cuota Afectada</th>
                                <th class="py-3 px-4 font-semibold w-4/12">Valores Sincronizados y Historial</th>
                                <th class="py-3 px-4 font-semibold w-3/12">Errores / Datos Crudos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-600 dark:text-gray-400">
                            @forelse($detalles as $det)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-850">
                                    <td class="py-3 px-4 text-center font-medium text-gray-500">
                                        {{ $det->numero_linea > 0 ? $det->numero_linea : '-' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($det->accion === 'creado')
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">NUEVA CUOTA</span>
                                        @elseif($det->accion === 'marcado_ausente')
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">MARCADO AUSENTE</span>
                                        @elseif(str_contains($det->accion, 'actualizado'))
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">ACTUALIZACIÓN</span>
                                        @elseif($det->accion === 'sin_cambios')
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-gray-100 text-gray-850 dark:bg-gray-700 dark:text-gray-300">SIN CAMBIOS</span>
                                        @elseif($det->accion === 'duplicado_benigno')
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">DUPLICADO</span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">ERROR</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono">
                                        @php
                                            $parsedRow = $this->getParsedRowData($det);
                                        @endphp
                                        @if($parsedRow['solicitud'] !== '-')
                                            <div class="font-bold text-gray-900 dark:text-white">Solicitud: {{ $parsedRow['solicitud'] }}</div>
                                            <div class="text-xs text-gray-500">Cuota Nº {{ $parsedRow['cuota'] }}</div>
                                            @if($parsedRow['cuota_id'])
                                                <a href="/ficha-gestion/{{ $parsedRow['cuota_id'] }}" target="_blank" class="text-indigo-600 hover:underline text-[11px] block mt-1">Ir a Ficha →</a>
                                            @endif
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-xs">
                                        @php
                                            $changes = $this->getChangeDetails($det);
                                        @endphp
                                        @if($changes)
                                            <div class="space-y-1.5">
                                                @if($changes['prev_importe'] !== null && $changes['prev_importe'] != $changes['new_importe'])
                                                    <div>
                                                        <span class="text-gray-400">Importe Base:</span>
                                                        <span class="line-through text-red-500">$ {{ number_format($changes['prev_importe'], 2) }}</span>
                                                        <span class="font-bold text-green-600">$ {{ number_format($changes['new_importe'], 2) }}</span>
                                                    </div>
                                                @else
                                                    <div><span class="text-gray-400 font-medium">Importe Base:</span> <span class="font-semibold text-gray-800 dark:text-gray-200">$ {{ number_format($changes['new_importe'], 2) }}</span></div>
                                                @endif

                                                @if($changes['prev_punitorios'] !== null && $changes['prev_punitorios'] != $changes['new_punitorios'])
                                                    <div>
                                                        <span class="text-amber-500">Punitorios:</span>
                                                        <span class="line-through text-red-500">$ {{ number_format($changes['prev_punitorios'], 2) }}</span>
                                                        <span class="font-bold text-green-600">$ {{ number_format($changes['new_punitorios'], 2) }}</span>
                                                    </div>
                                                @else
                                                    <div><span class="text-gray-400 font-medium">Punitorios:</span> <span class="font-semibold text-amber-600">$ {{ number_format($changes['new_punitorios'], 2) }}</span></div>
                                                @endif

                                                @if($changes['prev_total'] !== null && $changes['prev_total'] != $changes['new_total'])
                                                    <div>
                                                        <span class="text-gray-400">Total:</span>
                                                        <span class="line-through text-red-500">$ {{ number_format($changes['prev_total'], 2) }}</span>
                                                        <span class="font-bold text-green-600">$ {{ number_format($changes['new_total'], 2) }}</span>
                                                    </div>
                                                @endif

                                                @if($changes['prev_presence'] !== null && $changes['prev_presence'] !== $changes['new_presence'])
                                                    <div>
                                                        <span class="text-gray-400">Presencia:</span>
                                                        <span class="font-semibold text-gray-500 capitalize">{{ $changes['prev_presence'] }}</span> ➔
                                                        <span class="font-bold text-indigo-600 capitalize">{{ $changes['new_presence'] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            @if($det->accion === 'creado')
                                                <div class="text-gray-600"><span class="text-gray-400 font-medium">Importe Inicial:</span> <span class="font-semibold">$ {{ number_format(floatval($parsedRow['importe']) ?: 0, 2) }}</span></div>
                                                <div class="text-amber-600"><span class="text-gray-400 font-medium">Punitorios Iniciales:</span> <span class="font-semibold">$ {{ number_format(floatval($parsedRow['punitorios']) ?: 0, 2) }}</span></div>
                                            @elseif($det->accion === 'sin_cambios')
                                                <div class="text-gray-500">Todos los valores coinciden con el registro de la cartera actual. No hubo cambios financieros.</div>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-xs">
                                        @if($det->detalles_error)
                                            <div class="text-red-600 font-bold max-w-sm" style="word-break: break-word;">
                                                {{ $det->detalles_error }}
                                            </div>
                                        @endif
                                        <div class="text-[10px] text-gray-400 font-mono mt-1 max-w-sm truncate" title="{{ $det->datos_crudos }}">
                                            {{ $det->datos_crudos }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-gray-400 dark:text-gray-500">Ningún registro detallado de auditoría coincide con los filtros aplicados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>

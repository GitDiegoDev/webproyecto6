<div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-md mt-6">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Módulo de Importación y Sincronización de Cartera</h2>

    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
            <strong>Error:</strong> {{ $errorMessage }}
        </div>
    @endif

    @if(session()->has('period_success'))
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
            {{ session('period_success') }}
        </div>
    @endif

    {{-- Paso 1: Subir Archivo y Seleccionar Período --}}
    @if($step === 'upload')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Panel de Selección/Creación de Período --}}
            <div class="p-6 bg-gray-50 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold mb-4 text-gray-700">1. Seleccionar Período de Cobranza</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Período de Trabajo</label>
                    <select wire:model="periodo_id" class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Seleccionar período --</option>
                        @foreach($periodos as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->mes }}/{{ $p->anio }}) {{ $p->activo ? '[ACTIVO]' : '' }}</option>
                        @endforeach
                    </select>
                    @error('periodo_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="border-t border-gray-200 my-4 pt-4">
                    <h4 class="text-md font-medium mb-3 text-gray-600">O crear un nuevo período:</h4>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Nombre del Período</label>
                            <input type="text" wire:model="nuevo_periodo_nombre" placeholder="Ej: Agosto 2026" class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('nuevo_periodo_nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Mes (1-12)</label>
                                <input type="number" wire:model="nuevo_periodo_mes" min="1" max="12" class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('nuevo_periodo_mes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Año</label>
                                <input type="number" wire:model="nuevo_periodo_anio" min="2020" class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('nuevo_periodo_anio') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Monto Objetivo ($)</label>
                            <input type="number" wire:model="nuevo_periodo_objetivo" step="0.01" class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('nuevo_periodo_objetivo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <button type="button" wire:click="crearPeriodo" class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-2 px-4 rounded shadow">
                            Crear Período
                        </button>
                    </div>
                </div>
            </div>

            {{-- Panel de Carga de Archivo --}}
            <div class="p-6 bg-gray-50 rounded-lg border border-gray-200 flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-gray-700">2. Subir Planilla de Cartera</h3>
                    <p class="text-sm text-gray-500 mb-6">Suba el archivo de cartera provisto por el sistema oficial. Formatos permitidos: .xlsx, .csv</p>

                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center bg-white hover:bg-gray-50 cursor-pointer relative">
                        <input type="file" wire:model="file" accept=".xlsx,.csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <p class="mt-1 text-sm text-gray-600">Haga clic o arrastre el archivo aquí</p>
                        <p class="text-xs text-gray-400 mt-1">XLSX o CSV de hasta 10MB</p>
                    </div>

                    <div wire:loading wire:target="file" class="mt-4 text-indigo-600 text-sm font-semibold text-center w-full">
                        Cargando y analizando estructura del archivo...
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Paso 2: Mapeo de Columnas --}}
    @if($step === 'mapping')
        <div>
            <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded mb-6 text-sm">
                <strong>Estructura Detectada:</strong> El archivo contiene {{ count($availableHeaders) }} columnas. Verifique y ajuste el mapeo de columnas del archivo hacia los campos correspondientes del sistema.
            </div>

            <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">3. Mapear Columnas de Cartera</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                @foreach($mapping as $systemField => $mappedValue)
                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                        <label class="block text-sm font-semibold text-gray-700 mb-1 capitalize">
                            {{ str_replace('_', ' ', $systemField) }}
                            @if(in_array($systemField, ['numero_solicitud', 'numero_cuota', 'nombre', 'apellido', 'importe_original']))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <select wire:model="mapping.{{ $systemField }}" class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- No mapeado (Opcional) --</option>
                            @foreach($availableHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between mt-6">
                <button type="button" wire:click="resetImport" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-6 rounded shadow">
                    Volver a Subir
                </button>
                <button type="button" wire:click="procesarImportacion" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded shadow">
                    Comenzar Importación
                </button>
            </div>
        </div>
    @endif

    {{-- Paso 3: Resultados de la Importación --}}
    @if($step === 'results')
        <div>
            <div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded mb-6">
                <h3 class="font-bold text-lg">¡Importación Completada!</h3>
                <p class="text-sm">La cartera ha sido procesada de manera segura. A continuación se presentan las estadísticas detalladas.</p>
            </div>

            <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">Estadísticas Finales</h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div class="bg-gray-50 p-4 rounded text-center border">
                    <span class="block text-2xl font-extrabold text-indigo-600">{{ $importResults['cantidad_registros'] }}</span>
                    <span class="text-xs text-gray-500 uppercase font-semibold">Procesadas con Éxito</span>
                </div>
                <div class="bg-green-50 p-4 rounded text-center border border-green-200">
                    <span class="block text-2xl font-extrabold text-green-600">{{ $importResults['registros_nuevos'] }}</span>
                    <span class="text-xs text-green-500 uppercase font-semibold">Nuevas Cuotas</span>
                </div>
                <div class="bg-blue-50 p-4 rounded text-center border border-blue-200">
                    <span class="block text-2xl font-extrabold text-blue-600">{{ $importResults['registros_actualizados'] }}</span>
                    <span class="text-xs text-blue-500 uppercase font-semibold">Actualizadas</span>
                </div>
                <div class="bg-yellow-50 p-4 rounded text-center border border-yellow-200">
                    <span class="block text-2xl font-extrabold text-yellow-600">{{ $importResults['registros_sin_cambios'] }}</span>
                    <span class="text-xs text-yellow-500 uppercase font-semibold">Sin Cambios</span>
                </div>
                <div class="bg-purple-50 p-4 rounded text-center border border-purple-200">
                    <span class="block text-2xl font-extrabold text-purple-600">{{ $importResults['registros_ausentes'] }}</span>
                    <span class="text-xs text-purple-500 uppercase font-semibold">Ausentes (No Elim.)</span>
                </div>
                <div class="bg-red-50 p-4 rounded text-center border border-red-200">
                    <span class="block text-2xl font-extrabold text-red-600">{{ $importResults['registros_errores'] }}</span>
                    <span class="text-xs text-red-500 uppercase font-semibold">Filas con Errores</span>
                </div>
                <div class="bg-gray-100 p-4 rounded text-center border col-span-2">
                    <span class="block text-2xl font-extrabold text-gray-600">{{ $importResults['benign_duplicates'] }}</span>
                    <span class="text-xs text-gray-500 uppercase font-semibold">Duplicados Benignos Omitidos</span>
                </div>
            </div>

            @if(count($importErrors) > 0)
                <div class="mb-6">
                    <h4 class="text-md font-bold text-red-600 mb-2">Detalle de Errores por Fila:</h4>
                    <div class="bg-red-50 rounded border border-red-200 p-4 max-h-60 overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-red-200 text-red-700">
                                    <th class="py-1 w-1/12 min-w-[60px]">Fila</th>
                                    <th class="py-1 w-6/12 min-w-[200px]">Descripción del Error</th>
                                    <th class="py-1 w-5/12 min-w-[200px]">Datos Crudos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($importErrors as $err)
                                    <tr class="border-b border-red-100 text-red-800 last:border-0">
                                        <td class="py-1.5 font-bold whitespace-nowrap">Fila {{ $err['numero_linea'] }}</td>
                                        <td class="py-1.5 pr-2">{{ $err['detalles_error'] }}</td>
                                        <td class="py-1.5 text-gray-500 font-mono text-[10px] truncate max-w-xs" title="{{ $err['datos_crudos'] }}">{{ $err['datos_crudos'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="flex justify-center mt-6">
                <button type="button" wire:click="resetImport" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-8 rounded shadow">
                    Importar Otro Archivo
                </button>
            </div>
        </div>
    @endif
</div>

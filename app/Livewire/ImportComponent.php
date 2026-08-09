<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\PeriodoCobranza;
use App\Models\Importacion;
use App\Models\DetalleImportacion;
use App\Models\Cuota;
use App\Models\CuotaImportacion;
use App\Models\Operacion;
use App\Models\User;
use App\Services\Import\ImportService;
use App\Services\Import\ImportValidator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class ImportComponent extends Component
{
    use WithFileUploads;

    // Tabs & View
    public $activeTab = 'import'; // import, history

    // File upload
    public $file;
    public $periodo_id = '';
    public $fileName = '';
    public $fileSize = 0;

    // Create new period form
    public $nuevo_periodo_nombre = '';
    public $nuevo_periodo_mes = '';
    public $nuevo_periodo_anio = '';
    public $nuevo_periodo_objetivo = 1000000.00;

    // Mapping step
    public $availableHeaders = [];
    public $mapping = [
        'numero_solicitud' => '',
        'numero_cuota' => '',
        'nombre' => '',
        'apellido' => '',
        'documento' => '',
        'codigo_cliente_oficial' => '',
        'dia_cobro' => '',
        'importe_original' => '',
        'punitorios' => '',
        'telefono' => '',
        'domicilio' => '',
        'email' => '',
    ];

    // UI state
    public $step = 'upload'; // upload, mapping, preview, results
    public $errorMessage = '';
    public $importResults = null;
    public $importErrors = [];

    // Preview stats
    public $previewTotalRows = 0;
    public $previewValidRows = 0;
    public $previewErrorRows = 0;
    public $previewNewRows = 0;
    public $previewUpdatedRows = 0;
    public $previewAbsentRows = 0;
    public $previewSampleRows = [];
    public $previewErrors = [];
    public $previewDetectedColumns = [];
    public $confirm_absents = false;

    // Double submit protection
    public $isProcessing = false;

    // History Filters
    public $filterPeriodo = '';
    public $filterFecha = '';
    public $filterUsuario = '';
    public $filterEstado = '';
    public $filterTipoArchivo = '';

    // History Detail
    public $selectedImportId = null;
    public $filterDetailAction = '';
    public $filterDetailCuota = '';
    public $filterDetailSolicitud = '';

    // Validation rules
    protected $rules = [
        'periodo_id' => 'required',
    ];

    public function mount()
    {
        $this->authorizeImport();

        $this->nuevo_periodo_mes = date('n');
        $this->nuevo_periodo_anio = date('Y');

        $activePeriod = PeriodoCobranza::where('activo', true)->first();
        if ($activePeriod) {
            $this->periodo_id = $activePeriod->id;
        }
    }

    /**
     * Authorization check. Only administrador is allowed to import.
     */
    public function authorizeImport()
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'No autorizado.');
        }

        if ($user->role !== 'administrador') {
            abort(403, 'No autorizado para importar carteras.');
        }

        return true;
    }

    /**
     * Switch view/tabs.
     */
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedImportId = null;
        $this->errorMessage = '';
    }

    /**
     * Triggered when file is uploaded. Parses headers and advances to mapping step.
     */
    public function updatedFile()
    {
        $this->errorMessage = '';
        $this->authorizeImport();

        if (!$this->file) {
            return;
        }

        try {
            $this->fileName = $this->file->getClientOriginalName();
            $this->fileSize = $this->file->getSize();

            $extension = pathinfo($this->fileName, PATHINFO_EXTENSION);
            if (!in_array(strtolower($extension), ['csv', 'xlsx'])) {
                throw new Exception("Extensión de archivo inválida. Sólo se permiten .csv y .xlsx");
            }

            if ($this->fileSize === 0) {
                throw new Exception("El archivo seleccionado está vacío.");
            }

            $path = $this->file->getRealPath();
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows) || empty($rows[0])) {
                throw new Exception("El archivo seleccionado está vacío.");
            }

            // Extract non-empty headers
            $this->availableHeaders = array_filter(array_map(function ($h) {
                return $h !== null ? trim($h) : '';
            }, $rows[0]));

            if (empty($this->availableHeaders)) {
                throw new Exception("El archivo seleccionado está vacío.");
            }

            $this->autoMapHeaders();
            $this->step = 'mapping';

        } catch (Exception $e) {
            $this->errorMessage = "Error al leer el archivo: " . $e->getMessage();
            $this->file = null;
        }
    }

    /**
     * Auto-maps system fields to best-fit file headers using fuzzy logic.
     */
    protected function autoMapHeaders()
    {
        $mappingRules = [
            'numero_solicitud' => ['solicitud', 'numero_solicitud', 'nro_solicitud', 'credito', 'nro_credito', 'solicitud id', 'id_solicitud', 'id solicitud', 'operacion', 'operación'],
            'numero_cuota' => ['cuota', 'numero_cuota', 'nro_cuota', 'cuota_numero', 'nro cuota', 'numero cuota'],
            'nombre' => ['nombre', 'nombres', 'primer_nombre', 'primer nombre'],
            'apellido' => ['apellido', 'apellidos'],
            'documento' => ['documento', 'dni', 'documento_identidad', 'dni/cuil', 'cuil', 'cuit', 'nro_documento', 'nro documento', 'identificacion', 'identificación'],
            'codigo_cliente_oficial' => ['codigo_cliente', 'codigo_cliente_oficial', 'id_cliente', 'cliente_id', 'cod_cliente', 'cuenta', 'cod cliente', 'código cliente', 'id cliente'],
            'dia_cobro' => ['dia_cobro', 'dia_de_cobro', 'fecha_cobro', 'vencimiento', 'dia cobro', 'dia de cobro'],
            'importe_original' => ['importe', 'importe_original', 'importe_base', 'cuota_original', 'importe original', 'monto', 'monto_original', 'monto original', 'valor'],
            'punitorios' => ['punitorios', 'interes_punitorio', 'punitorio', 'recargo', 'intereses', 'interes', 'punitorios_actuales', 'interés punitorio'],
            'telefono' => ['telefono', 'celular', 'telefono_contacto', 'tel', 'cel', 'teléfono'],
            'domicilio' => ['domicilio', 'direccion', 'dirección', 'calle', 'direccion_gestion'],
            'email' => ['email', 'correo', 'mail', 'correo_electronico', 'correo electrónico'],
        ];

        foreach ($mappingRules as $systemField => $aliases) {
            $found = '';
            foreach ($this->availableHeaders as $header) {
                $lowerHeader = strtolower(trim($header));
                if (in_array($lowerHeader, $aliases)) {
                    $found = $header;
                    break;
                }
            }
            if ($found === '') {
                foreach ($this->availableHeaders as $header) {
                    $lowerHeader = strtolower(trim($header));
                    foreach ($aliases as $alias) {
                        if (str_contains($lowerHeader, $alias) || str_contains($alias, $lowerHeader)) {
                            $found = $header;
                            break 2;
                        }
                    }
                }
            }
            $this->mapping[$systemField] = $found;
        }
    }

    /**
     * Create a new PeriodoCobranza on the fly.
     */
    public function crearPeriodo()
    {
        $this->authorizeImport();

        $this->validate([
            'nuevo_periodo_nombre' => 'required|string|max:100',
            'nuevo_periodo_mes' => 'required|integer|between:1,12',
            'nuevo_periodo_anio' => 'required|integer|min:2020|max:2100',
            'nuevo_periodo_objetivo' => 'required|numeric|min:0',
        ]);

        // Check if there is already a period with the same mes and anio to avoid duplicates
        $exist = PeriodoCobranza::where('mes', intval($this->nuevo_periodo_mes))
            ->where('anio', intval($this->nuevo_periodo_anio))
            ->first();

        if ($exist) {
            $this->periodo_id = $exist->id;
            session()->flash('period_success', 'El período ya existía, se ha seleccionado automáticamente.');
            return;
        }

        // Deactivate other periods if this is active
        PeriodoCobranza::where('activo', true)->update(['activo' => false]);

        $periodo = PeriodoCobranza::create([
            'nombre' => $this->nuevo_periodo_nombre,
            'mes' => intval($this->nuevo_periodo_mes),
            'anio' => intval($this->nuevo_periodo_anio),
            'activo' => true,
            'objetivo_monto' => floatval($this->nuevo_periodo_objetivo),
        ]);

        $this->periodo_id = $periodo->id;
        $this->nuevo_periodo_nombre = '';

        session()->flash('period_success', 'Período creado exitosamente.');
    }

    /**
     * Generate dry-run preview statistics.
     */
    public function previsualizar(ImportValidator $importValidator)
    {
        $this->errorMessage = '';
        $this->authorizeImport();

        if (empty($this->periodo_id)) {
            $this->errorMessage = "Debe seleccionar un período de cobranza.";
            return;
        }

        if (!$this->file) {
            $this->errorMessage = "No se ha cargado ningún archivo.";
            return;
        }

        try {
            $headerErrors = $importValidator->validateHeaders($this->mapping);
            if (!empty($headerErrors)) {
                throw new Exception("Estructura de mapeo inválida: " . implode(" ", $headerErrors));
            }

            $path = $this->file->getRealPath();
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows) || count($rows) <= 1) {
                throw new Exception("El archivo de importación está vacío o solo contiene cabecera.");
            }

            $headers = array_map(function ($h) {
                return $h !== null ? trim($h) : '';
            }, $rows[0]);

            $indexMapping = [];
            foreach ($this->mapping as $systemField => $headerName) {
                if ($headerName === '') {
                    continue;
                }
                $index = array_search($headerName, $headers);
                if ($index === false) {
                    throw new Exception("La columna mapeada '{$headerName}' para el campo '{$systemField}' no existe en el archivo.");
                }
                $indexMapping[$systemField] = $index;
            }

            // Reset preview stats
            $this->previewTotalRows = 0;
            $this->previewValidRows = 0;
            $this->previewErrorRows = 0;
            $this->previewNewRows = 0;
            $this->previewUpdatedRows = 0;
            $this->previewAbsentRows = 0;
            $this->previewSampleRows = [];
            $this->previewErrors = [];
            $this->previewDetectedColumns = array_filter($this->mapping);
            $this->confirm_absents = false;

            $processedCuotaIds = [];
            $seenRows = [];

            $rowCount = count($rows);
            for ($i = 1; $i < $rowCount; $i++) {
                $rawRow = $rows[$i];

                // Skip completely empty rows
                $isEmpty = true;
                foreach ($rawRow as $cell) {
                    if ($cell !== null && trim($cell) !== '') {
                        $isEmpty = false;
                        break;
                    }
                }
                if ($isEmpty) {
                    continue;
                }

                $this->previewTotalRows++;
                $lineNumber = $i + 1;

                $mappedRow = [];
                foreach ($indexMapping as $systemField => $colIndex) {
                    $val = $rawRow[$colIndex] ?? null;
                    $mappedRow[$systemField] = $val !== null ? trim($val) : null;
                }

                $rowErrors = $importValidator->validateRow($mappedRow);
                if (!empty($rowErrors)) {
                    $this->previewErrorRows++;
                    if (count($this->previewErrors) < 100) {
                        $this->previewErrors[] = [
                            'numero_linea' => $lineNumber,
                            'detalles_error' => implode(" | ", $rowErrors),
                            'datos_crudos' => json_encode($rawRow),
                        ];
                    }
                    continue;
                }

                $solicitud = $mappedRow['numero_solicitud'];
                $cuotaNum = intval($mappedRow['numero_cuota']);
                $duplicateKey = $solicitud . '_' . $cuotaNum;

                if (isset($seenRows[$duplicateKey])) {
                    $isIdentical = true;
                    foreach ($indexMapping as $systemField => $colIndex) {
                        if (strval($mappedRow[$systemField] ?? '') !== strval($seenRows[$duplicateKey][$systemField] ?? '')) {
                            $isIdentical = false;
                            break;
                        }
                    }

                    if (!$isIdentical) {
                        $this->previewErrorRows++;
                        if (count($this->previewErrors) < 100) {
                            $this->previewErrors[] = [
                                'numero_linea' => $lineNumber,
                                'detalles_error' => "Fila duplicada con datos contradictorios respecto a línea " . $seenRows[$duplicateKey]['line_number'],
                                'datos_crudos' => json_encode($rawRow),
                            ];
                        }
                    }
                    continue;
                }

                $mappedRow['line_number'] = $lineNumber;
                $seenRows[$duplicateKey] = $mappedRow;

                $this->previewValidRows++;

                // Check DB status
                $operacion = Operacion::where('numero_solicitud', $solicitud)->first();
                $cuota = null;
                if ($operacion) {
                    $cuota = Cuota::where('operacion_id', $operacion->id)
                        ->where('numero_cuota', $cuotaNum)
                        ->first();
                }

                if ($cuota) {
                    $processedCuotaIds[] = $cuota->id;

                    $importeOriginal = floatval($mappedRow['importe_original']);
                    $punitorios = isset($mappedRow['punitorios']) && $mappedRow['punitorios'] !== '' ? floatval($mappedRow['punitorios']) : 0.00;
                    $diaCobro = isset($mappedRow['dia_cobro']) && $mappedRow['dia_cobro'] !== '' ? intval($mappedRow['dia_cobro']) : 1;

                    $changedImporte = abs(floatval($cuota->importe_original) - $importeOriginal) > 0.001;
                    $changedPunitorios = abs(floatval($cuota->punitorios) - $punitorios) > 0.001;
                    $changedDiaCobro = intval($cuota->dia_cobro) !== $diaCobro;
                    $wasAbsent = $cuota->estado_financiero === 'ausente_en_ultima_importacion';

                    if ($changedImporte || $changedPunitorios || $changedDiaCobro || $wasAbsent) {
                        $this->previewUpdatedRows++;
                    }
                } else {
                    $this->previewNewRows++;
                }

                if (count($this->previewSampleRows) < 10) {
                    $this->previewSampleRows[] = [
                        'numero_linea' => $lineNumber,
                        'numero_solicitud' => $solicitud,
                        'numero_cuota' => $cuotaNum,
                        'nombre' => $mappedRow['nombre'],
                        'apellido' => $mappedRow['apellido'],
                        'importe_original' => $mappedRow['importe_original'],
                        'punitorios' => $mappedRow['punitorios'] ?? '0.00',
                        'dia_cobro' => $mappedRow['dia_cobro'] ?? '1',
                    ];
                }
            }

            // Absent tracking
            $previousImportations = Importacion::where('periodo_cobranza_id', $this->periodo_id)
                ->where('estado', 'completada')
                ->pluck('id');

            if ($previousImportations->isNotEmpty()) {
                $portfolioCuotaIds = CuotaImportacion::whereIn('importacion_id', $previousImportations)
                    ->where('estado_presencia', 'presente')
                    ->pluck('cuota_id')
                    ->unique();

                $absentCuotaIds = $portfolioCuotaIds->diff($processedCuotaIds);
                $this->previewAbsentRows = $absentCuotaIds->count();
            }

            $this->step = 'preview';

        } catch (Exception $e) {
            $this->errorMessage = "Error al previsualizar el archivo: " . $e->getMessage();
        }
    }

    /**
     * Execute the import using ImportService.
     */
    public function procesarImportacion(ImportService $importService)
    {
        $this->validate();
        $this->errorMessage = '';
        $this->authorizeImport();

        if ($this->isProcessing) {
            return;
        }

        if ($this->previewAbsentRows > 0 && !$this->confirm_absents) {
            $this->errorMessage = "Debe confirmar explícitamente que acepta el procesamiento de los registros ausentes.";
            return;
        }

        $this->isProcessing = true;

        if (!$this->file) {
            $this->errorMessage = "No se ha cargado ningún archivo.";
            $this->isProcessing = false;
            return;
        }

        try {
            $path = $this->file->getRealPath();
            $originalName = $this->file->getClientOriginalName();

            // Execute import service
            $this->importResults = $importService->import(
                $path,
                $originalName,
                intval($this->periodo_id),
                auth()->id(),
                $this->mapping
            );

            // Fetch row-level errors from DetalleImportacion
            $lastImportation = Importacion::where('periodo_cobranza_id', $this->periodo_id)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastImportation) {
                $this->importErrors = DetalleImportacion::where('importacion_id', $lastImportation->id)
                    ->where('accion', 'error')
                    ->orderBy('numero_linea', 'asc')
                    ->get()
                    ->toArray();
            }

            $this->step = 'results';

        } catch (Exception $e) {
            $this->errorMessage = "Error en la importación: " . $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Reset the UI to upload another file.
     */
    public function resetImport()
    {
        $this->file = null;
        $this->fileName = '';
        $this->fileSize = 0;
        $this->availableHeaders = [];
        $this->importResults = null;
        $this->importErrors = [];
        $this->errorMessage = '';
        $this->confirm_absents = false;
        $this->step = 'upload';
    }

    /**
     * View Details of an import from the History tab.
     */
    public function viewImportDetails($id)
    {
        $this->selectedImportId = $id;
        $this->filterDetailAction = '';
        $this->filterDetailCuota = '';
        $this->filterDetailSolicitud = '';
    }

    /**
     * Close Details sub-view.
     */
    public function closeImportDetails()
    {
        $this->selectedImportId = null;
    }

    /**
     * Helper to decode details dynamically for the audit.
     */
    public function getParsedRowData($detalle)
    {
        $data = json_decode($detalle->datos_crudos, true);
        if (!$data) {
            return [
                'solicitud' => '-',
                'cuota' => '-',
                'nombre' => '-',
                'importe' => '-',
                'punitorios' => '-',
                'cuota_id' => null,
            ];
        }

        if (isset($data['numero_solicitud'])) {
            return [
                'solicitud' => $data['numero_solicitud'],
                'cuota' => $data['numero_cuota'] ?? '-',
                'nombre' => (($data['nombre'] ?? '') . ' ' . ($data['apellido'] ?? '')),
                'importe' => $data['importe_original'] ?? '-',
                'punitorios' => $data['punitorios'] ?? '-',
                'cuota_id' => $data['cuota_id'] ?? null,
            ];
        }

        // Numerical indices for error/raw rows
        return [
            'solicitud' => $data[0] ?? '-',
            'cuota' => $data[1] ?? '-',
            'nombre' => ($data[2] ?? '-') . ' ' . ($data[3] ?? ''),
            'importe' => $data[7] ?? '-',
            'punitorios' => $data[8] ?? '-',
            'cuota_id' => null,
        ];
    }

    /**
     * Reconstruct previous vs new values for successful detail imports.
     */
    public function getChangeDetails($detalle)
    {
        $parsed = $this->getParsedRowData($detalle);
        $solicitud = $parsed['solicitud'];
        $cuotaNum = $parsed['cuota'];

        if ($solicitud === '-' || $cuotaNum === '-') {
            return null;
        }

        $cuota = Cuota::whereHas('operacion', function($q) use ($solicitud) {
            $q->where('numero_solicitud', $solicitud);
        })->where('numero_cuota', $cuotaNum)->first();

        if (!$cuota) {
            return null;
        }

        $currentSnapshot = CuotaImportacion::where('cuota_id', $cuota->id)
            ->where('importacion_id', $detalle->importacion_id)
            ->first();

        if (!$currentSnapshot) {
            return null;
        }

        $previousSnapshot = CuotaImportacion::where('cuota_id', $cuota->id)
            ->where('importacion_id', '<', $detalle->importacion_id)
            ->orderBy('importacion_id', 'desc')
            ->first();

        return [
            'cuota' => $cuota,
            'prev_importe' => $previousSnapshot ? $previousSnapshot->importe_original_observado : null,
            'new_importe' => $currentSnapshot->importe_original_observado,
            'prev_punitorios' => $previousSnapshot ? $previousSnapshot->punitorios_observados : null,
            'new_punitorios' => $currentSnapshot->punitorios_observados,
            'prev_total' => $previousSnapshot ? $previousSnapshot->total_actualizado_observado : null,
            'new_total' => $currentSnapshot->total_actualizado_observado,
            'prev_presence' => $previousSnapshot ? $previousSnapshot->estado_presencia : null,
            'new_presence' => $currentSnapshot->estado_presencia,
        ];
    }

    public function render()
    {
        $periodos = PeriodoCobranza::orderBy('anio', 'desc')->orderBy('mes', 'desc')->get();
        $usuarios = User::orderBy('name', 'asc')->get();

        // Query historical imports
        $importationsQuery = Importacion::with(['periodoCobranza', 'user'])
            ->orderBy('id', 'desc');

        if ($this->filterPeriodo) {
            $importationsQuery->where('periodo_cobranza_id', $this->filterPeriodo);
        }
        if ($this->filterFecha) {
            $importationsQuery->whereDate('fecha_hora', $this->filterFecha);
        }
        if ($this->filterUsuario) {
            $importationsQuery->where('user_id', $this->filterUsuario);
        }
        if ($this->filterEstado) {
            $importationsQuery->where('estado', $this->filterEstado);
        }
        if ($this->filterTipoArchivo) {
            $importationsQuery->where('tipo_archivo', $this->filterTipoArchivo);
        }

        $importaciones = $importationsQuery->get();

        // Query detail logs for selected import
        $detalles = collect();
        $selectedImport = null;
        if ($this->selectedImportId) {
            $selectedImport = Importacion::with(['periodoCobranza', 'user'])->find($this->selectedImportId);
            $detailsQuery = DetalleImportacion::where('importacion_id', $this->selectedImportId)
                ->orderBy('id', 'asc');

            if ($this->filterDetailAction) {
                $detailsQuery->where('accion', $this->filterDetailAction);
            }

            if ($this->filterDetailCuota) {
                $detailsQuery->where('datos_crudos', 'like', '%' . $this->filterDetailCuota . '%');
            }

            if ($this->filterDetailSolicitud) {
                $detailsQuery->where('datos_crudos', 'like', '%' . $this->filterDetailSolicitud . '%');
            }

            $detalles = $detailsQuery->get();
        }

        return view('livewire.import-component', [
            'periodos' => $periodos,
            'usuarios' => $usuarios,
            'importaciones' => $importaciones,
            'selectedImport' => $selectedImport,
            'detalles' => $detalles,
        ]);
    }
}

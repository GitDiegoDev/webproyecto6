<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\PeriodoCobranza;
use App\Models\Importacion;
use App\Models\DetalleImportacion;
use App\Services\Import\ImportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class ImportComponent extends Component
{
    use WithFileUploads;

    // File upload
    public $file;
    public $periodo_id = '';

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
    public $step = 'upload'; // upload, mapping, results
    public $errorMessage = '';
    public $importResults = null;
    public $importErrors = [];

    // Validation rules
    protected $rules = [
        'periodo_id' => 'required',
    ];

    public function mount()
    {
        $this->nuevo_periodo_mes = date('n');
        $this->nuevo_periodo_anio = date('Y');

        $activePeriod = PeriodoCobranza::where('activo', true)->first();
        if ($activePeriod) {
            $this->periodo_id = $activePeriod->id;
        }
    }

    /**
     * Triggered when file is uploaded. Parses headers and advances to mapping step.
     */
    public function updatedFile()
    {
        $this->errorMessage = '';

        if (!$this->file) {
            return;
        }

        try {
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
        $this->validate([
            'nuevo_periodo_nombre' => 'required|string|max:100',
            'nuevo_periodo_mes' => 'required|integer|between:1,12',
            'nuevo_periodo_anio' => 'required|integer|min:2020|max:2100',
            'nuevo_periodo_objetivo' => 'required|numeric|min:0',
        ]);

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

        // Reset form
        $this->nuevo_periodo_nombre = '';

        session()->flash('period_success', 'Período creado exitosamente.');
    }

    /**
     * Execute the import using ImportService.
     */
    public function procesarImportacion(ImportService $importService)
    {
        $this->validate();
        $this->errorMessage = '';

        if (!$this->file) {
            $this->errorMessage = "No se ha cargado ningún archivo.";
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
        }
    }

    /**
     * Reset the UI to upload another file.
     */
    public function resetImport()
    {
        $this->file = null;
        $this->availableHeaders = [];
        $this->importResults = null;
        $this->importErrors = [];
        $this->errorMessage = '';
        $this->step = 'upload';
    }

    public function render()
    {
        $periodos = PeriodoCobranza::orderBy('anio', 'desc')->orderBy('mes', 'desc')->get();
        return view('livewire.import-component', [
            'periodos' => $periodos,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\CuotaImportacion;
use App\Models\DetalleImportacion;
use App\Models\Gestion;
use App\Models\Importacion;
use App\Models\Operacion;
use App\Models\PeriodoCobranza;
use App\Models\User;
use App\Livewire\ImportComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use Exception;

class ImportInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $gestorUser;
    protected User $cobradorUser;
    protected PeriodoCobranza $periodo;
    protected array $defaultMapping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->adminUser->role = 'administrador';
        $this->adminUser->save();

        $this->gestorUser = User::create([
            'name' => 'Gestor User',
            'email' => 'gestor@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->gestorUser->role = 'gestor';
        $this->gestorUser->save();

        $this->cobradorUser = User::create([
            'name' => 'Cobrador User',
            'email' => 'cobrador@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->cobradorUser->role = 'cobrador';
        $this->cobradorUser->save();

        $this->periodo = PeriodoCobranza::create([
            'nombre' => 'Agosto 2026',
            'mes' => 8,
            'anio' => 2026,
            'activo' => true,
            'objetivo_monto' => 1000000.00,
        ]);

        $this->defaultMapping = [
            'numero_solicitud' => 'Solicitud',
            'numero_cuota' => 'Cuota',
            'nombre' => 'Nombre',
            'apellido' => 'Apellido',
            'documento' => 'Documento',
            'codigo_cliente_oficial' => 'Codigo Cliente',
            'dia_cobro' => 'Dia Cobro',
            'importe_original' => 'Importe',
            'punitorios' => 'Punitorios',
            'telefono' => 'Telefono',
            'domicilio' => 'Domicilio',
            'email' => 'Email',
        ];
    }

    /**
     * Helper to write a test file (CSV or XLSX) on the fly and wrap in UploadedFile.
     */
    protected function createUploadedFile(array $rows, string $format = 'csv'): UploadedFile
    {
        $mime = $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $file = UploadedFile::fake()->create('cartera.' . $format, 10, $mime);
        $tempFile = $file->getRealPath();

        if ($format === 'csv') {
            $handle = fopen($tempFile, 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        } else {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            foreach ($rows as $rIndex => $row) {
                foreach ($row as $cIndex => $val) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIndex + 1);
                    $sheet->setCellValue($colLetter . ($rIndex + 1), $val);
                }
            }
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempFile);
        }

        return $file;
    }

    /**
     * Test 1: Usuario autorizado puede acceder.
     */
    public function test_1_authorized_user_can_access(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/importar');
        $response->assertStatus(200);
        $response->assertSeeLivewire(ImportComponent::class);
    }

    /**
     * Test 2: Usuario no autorizado no puede importar.
     */
    public function test_2_unauthorized_user_cannot_import(): void
    {
        $response = $this->actingAs($this->gestorUser)->get('/importar');
        $response->assertStatus(403);
    }

    /**
     * Test 3: Cobrador no puede importar si no tiene permiso.
     */
    public function test_3_cobrador_cannot_import(): void
    {
        $response = $this->actingAs($this->cobradorUser)->get('/importar');
        $response->assertStatus(403);
    }

    /**
     * Test 4: Se acepta ".xlsx".
     */
    public function test_4_accepts_xlsx_files(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'xlsx');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->assertSet('step', 'mapping')
            ->assertSet('fileName', 'cartera.xlsx')
            ->assertHasNoErrors();
    }

    /**
     * Test 5: Se acepta ".csv".
     */
    public function test_5_accepts_csv_files(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->assertSet('step', 'mapping')
            ->assertSet('fileName', 'cartera.csv')
            ->assertHasNoErrors();
    }

    /**
     * Test 6: Se rechazan extensiones inválidas.
     */
    public function test_6_rejects_invalid_extensions(): void
    {
        $rows = [['some data']];
        $file = $this->createUploadedFile($rows, 'txt');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->upload('file', [$file])
            ->assertSet('step', 'upload')
            ->assertSee('Extensión de archivo inválida');
    }

    /**
     * Test 7: Se rechaza archivo vacío.
     */
    public function test_7_rejects_empty_file(): void
    {
        $file = $this->createUploadedFile([], 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->upload('file', [$file])
            ->assertSet('step', 'upload')
            ->assertSee('El archivo seleccionado está vacío');
    }

    /**
     * Test 8: Se detectan columnas faltantes.
     */
    public function test_8_detects_missing_mandatory_columns(): void
    {
        $rows = [
            ['WrongHeader1', 'WrongHeader2'],
            ['Val1', 'Val2']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->assertSet('step', 'mapping')
            ->set('mapping', [
                'numero_solicitud' => 'WrongHeader1',
                'numero_cuota' => '', // missing
                'nombre' => '',
                'apellido' => '',
                'importe_original' => '',
            ])
            ->call('previsualizar')
            ->assertSet('step', 'mapping')
            ->assertSee('Estructura de mapeo inválida');
    }

    /**
     * Test 9: Se muestra la vista previa.
     */
    public function test_9_shows_preview_step(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->assertSet('step', 'preview')
            ->assertSet('previewTotalRows', 1)
            ->assertSet('previewValidRows', 1);
    }

    /**
     * Test 10: Se muestran registros nuevos.
     */
    public function test_10_shows_new_records_count(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->assertSet('previewNewRows', 1);
    }

    /**
     * Test 11: Se muestran registros actualizados.
     */
    public function test_11_shows_updated_records_count(): void
    {
        // First, seed a customer, operation, and cuota
        $cliente = Cliente::create(['nombre' => 'Juan', 'apellido' => 'Perez', 'documento' => '30000001']);
        $operacion = Operacion::create(['cliente_id' => $cliente->id, 'numero_solicitud' => '1001']);
        Cuota::create([
            'operacion_id' => $operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 10,
            'importe_original' => 15000.00,
            'punitorios' => 100.00,
            'total_actualizado' => 15100.00,
            'saldo_pendiente' => 15100.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        // Sincronize with updated values (punitorios changed from 100 to 500)
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '500', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->assertSet('previewUpdatedRows', 1);
    }

    /**
     * Test 12: Se muestran registros ausentes.
     */
    public function test_12_shows_absent_records_count(): void
    {
        // Create an completed past import with 2 cuotas
        $importacion = Importacion::create([
            'periodo_cobranza_id' => $this->periodo->id,
            'user_id' => $this->adminUser->id,
            'fecha_hora' => now()->subDay(),
            'nombre_archivo' => 'past.csv',
            'tipo_archivo' => 'csv',
            'cantidad_registros' => 2,
            'registros_nuevos' => 2,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completada',
        ]);

        $cliente = Cliente::create(['nombre' => 'Juan', 'apellido' => 'Perez', 'documento' => '30000001']);
        $operacion = Operacion::create(['cliente_id' => $cliente->id, 'numero_solicitud' => '1001']);
        $cuota1 = Cuota::create(['operacion_id' => $operacion->id, 'numero_cuota' => 1, 'dia_cobro' => 5, 'importe_original' => 1000.00, 'punitorios' => 0, 'total_actualizado' => 1000.00, 'saldo_pendiente' => 1000.00, 'estado_financiero' => 'pendiente', 'estado_gestion' => 'sin_contactar', 'prioridad' => 'media']);
        $cuota2 = Cuota::create(['operacion_id' => $operacion->id, 'numero_cuota' => 2, 'dia_cobro' => 5, 'importe_original' => 1000.00, 'punitorios' => 0, 'total_actualizado' => 1000.00, 'saldo_pendiente' => 1000.00, 'estado_financiero' => 'pendiente', 'estado_gestion' => 'sin_contactar', 'prioridad' => 'media']);

        CuotaImportacion::create(['cuota_id' => $cuota1->id, 'importacion_id' => $importacion->id, 'importe_original_observado' => 1000, 'punitorios_observados' => 0, 'total_actualizado_observado' => 1000, 'estado_presencia' => 'presente']);
        CuotaImportacion::create(['cuota_id' => $cuota2->id, 'importacion_id' => $importacion->id, 'importe_original_observado' => 1000, 'punitorios_observados' => 0, 'total_actualizado_observado' => 1000, 'estado_presencia' => 'presente']);

        // Upload new file containing only cuota 2 (cuota 1 is absent)
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '2', 'Juan', 'Perez', '30000001', 'C001', '5', '1000', '0', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->assertSet('previewAbsentRows', 1);
    }

    /**
     * Test 13: Se muestran errores.
     */
    public function test_13_shows_errors(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', 'INVALID_CUOTA', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->assertSet('previewErrorRows', 1);
    }

    /**
     * Test 14: La confirmación es obligatoria cuando corresponde.
     */
    public function test_14_confirmation_mandatory_when_absents_exist(): void
    {
        // Seed past import
        $importacion = Importacion::create([
            'periodo_cobranza_id' => $this->periodo->id,
            'user_id' => $this->adminUser->id,
            'fecha_hora' => now()->subDay(),
            'nombre_archivo' => 'past.csv',
            'tipo_archivo' => 'csv',
            'cantidad_registros' => 1,
            'registros_nuevos' => 1,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completada',
        ]);
        $cliente = Cliente::create(['nombre' => 'Juan', 'apellido' => 'Perez', 'documento' => '30000001']);
        $operacion = Operacion::create(['cliente_id' => $cliente->id, 'numero_solicitud' => '1001']);
        $cuota = Cuota::create(['operacion_id' => $operacion->id, 'numero_cuota' => 1, 'dia_cobro' => 5, 'importe_original' => 1000.00, 'punitorios' => 0, 'total_actualizado' => 1000.00, 'saldo_pendiente' => 1000.00, 'estado_financiero' => 'pendiente', 'estado_gestion' => 'sin_contactar', 'prioridad' => 'media']);
        CuotaImportacion::create(['cuota_id' => $cuota->id, 'importacion_id' => $importacion->id, 'importe_original_observado' => 1000, 'punitorios_observados' => 0, 'total_actualizado_observado' => 1000, 'estado_presencia' => 'presente']);

        // Upload file with a different new cuota (making the existing cuota absent)
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '2', 'Juan', 'Perez', '30000001', 'C001', '5', '1000', '0', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->assertSet('previewAbsentRows', 1)
            ->set('confirm_absents', false)
            ->call('procesarImportacion')
            ->assertSee('Debe confirmar explícitamente')
            ->set('confirm_absents', true)
            ->call('procesarImportacion')
            ->assertSet('step', 'results');
    }

    /**
     * Test 15: Una importación exitosa genera su registro de auditoría.
     */
    public function test_15_import_generates_audit_and_detalle_records(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $this->assertDatabaseHas('importaciones', [
            'periodo_cobranza_id' => $this->periodo->id,
            'estado' => 'completada'
        ]);

        $this->assertDatabaseHas('detalle_importaciones', [
            'accion' => 'creado',
            'numero_linea' => 2
        ]);
    }

    /**
     * Test 16: Una nueva importación no duplica clientes.
     */
    public function test_16_new_import_does_not_duplicate_clients(): void
    {
        // Import twice with same client
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file1 = $this->createUploadedFile($rows, 'csv');
        $file2 = $this->createUploadedFile($rows, 'csv');

        // First run
        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file1])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $this->assertEquals(1, Cliente::count());

        // Second run
        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file2])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $this->assertEquals(1, Cliente::count());
    }

    /**
     * Test 17: Una nueva importación no duplica operaciones.
     */
    public function test_17_new_import_does_not_duplicate_operations(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com'],
            ['1001', '2', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $this->assertEquals(1, Operacion::count());
        $this->assertEquals(2, Cuota::count());
    }

    /**
     * Test 18: Una nueva cuota no duplica una cuota existente.
     */
    public function test_18_new_cuota_does_not_duplicate_existing_cuota(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file1 = $this->createUploadedFile($rows, 'csv');
        $file2 = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file1])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $this->assertEquals(1, Cuota::count());

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file2])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $this->assertEquals(1, Cuota::count());
    }

    /**
     * Test 19: Se actualizan correctamente los punitorios.
     */
    public function test_19_updates_punitorios_correctly(): void
    {
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file1 = $this->createUploadedFile($rows1, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file1])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $this->assertEquals(100.00, floatval(Cuota::first()->punitorios));

        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '350', '111', 'Calle 1', 'j1@example.com']
        ];
        $file2 = $this->createUploadedFile($rows2, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file2])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $this->assertEquals(350.00, floatval(Cuota::first()->punitorios));
    }

    /**
     * Test 20: Los datos de gestión no son sobrescritos.
     */
    public function test_20_management_data_not_overwritten(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file1 = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file1])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $cuota = Cuota::first();
        $cuota->update([
            'estado_gestion' => 'promesa_pago',
            'prioridad' => 'alta',
        ]);

        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '500', '111', 'Calle 1', 'j1@example.com']
        ];
        $file2 = $this->createUploadedFile($rows2, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file2])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $freshCuota = Cuota::first();
        $this->assertEquals('promesa_pago', $freshCuota->estado_gestion);
        $this->assertEquals('alta', $freshCuota->prioridad);
    }

    /**
     * Test 21: Los registros ausentes son procesados mediante la lógica existente.
     */
    public function test_21_absent_records_processed_correctly(): void
    {
        $importacion = Importacion::create([
            'periodo_cobranza_id' => $this->periodo->id,
            'user_id' => $this->adminUser->id,
            'fecha_hora' => now()->subDay(),
            'nombre_archivo' => 'past.csv',
            'tipo_archivo' => 'csv',
            'cantidad_registros' => 1,
            'registros_nuevos' => 1,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completada',
        ]);
        $cliente = Cliente::create(['nombre' => 'Juan', 'apellido' => 'Perez', 'documento' => '30000001']);
        $operacion = Operacion::create(['cliente_id' => $cliente->id, 'numero_solicitud' => '1001']);
        $cuota = Cuota::create(['operacion_id' => $operacion->id, 'numero_cuota' => 1, 'dia_cobro' => 5, 'importe_original' => 1000.00, 'punitorios' => 0, 'total_actualizado' => 1000.00, 'saldo_pendiente' => 1000.00, 'estado_financiero' => 'pendiente', 'estado_gestion' => 'sin_contactar', 'prioridad' => 'media']);
        CuotaImportacion::create(['cuota_id' => $cuota->id, 'importacion_id' => $importacion->id, 'importe_original_observado' => 1000, 'punitorios_observados' => 0, 'total_actualizado_observado' => 1000, 'estado_presencia' => 'presente']);

        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '2', 'Juan', 'Perez', '30000001', 'C001', '5', '1000', '0', '111', 'Calle 1', 'j1@example.com'] // Cuota 1 is absent
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->set('confirm_absents', true)
            ->call('procesarImportacion');

        $this->assertEquals('ausente_en_ultima_importacion', $cuota->fresh()->estado_financiero);
    }

    /**
     * Test 22: Se conserva el historial de importación.
     */
    public function test_22_conserves_import_history(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $this->assertEquals(1, Importacion::count());
    }

    /**
     * Test 23: Se puede consultar el detalle de una importación.
     */
    public function test_23_can_query_import_details(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar')
            ->call('procesarImportacion');

        $importacion = Importacion::first();

        Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->call('viewImportDetails', $importacion->id)
            ->assertSet('selectedImportId', $importacion->id);
    }

    /**
     * Test 24: Se evita el doble envío.
     */
    public function test_24_avoids_double_submission(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['1001', '1', 'Juan', 'Perez', '30000001', 'C001', '10', '15000', '100', '111', 'Calle 1', 'j1@example.com']
        ];
        $file = $this->createUploadedFile($rows, 'csv');

        $component = Livewire::actingAs($this->adminUser)
            ->test(ImportComponent::class)
            ->set('periodo_id', $this->periodo->id)
            ->upload('file', [$file])
            ->set('mapping', $this->defaultMapping)
            ->call('previsualizar');

        // Simulate first submission setting isProcessing = true
        $component->set('isProcessing', true);
        $component->call('procesarImportacion');

        // Verify that step remained in preview (or has no results because it was blocked)
        $component->assertSet('step', 'preview');
    }
}

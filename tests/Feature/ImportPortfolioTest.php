<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\CuotaImportacion;
use App\Models\DetalleImportacion;
use App\Models\Gestion;
use App\Models\Importacion;
use App\Models\Operacion;
use App\Models\Pago;
use App\Models\PeriodoCobranza;
use App\Models\PromesaPago;
use App\Models\User;
use App\Services\Import\ImportService;
use App\Services\Import\ImportValidator;
use App\Services\Import\ImportProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use Exception;

class ImportPortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected ImportService $importService;
    protected User $user;
    protected PeriodoCobranza $periodo;
    protected array $defaultMapping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importService = new ImportService(new ImportValidator(), new ImportProcessor());

        $this->user = User::create([
            'name' => 'Import Agent',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'administrador',
        ]);

        $this->periodo = PeriodoCobranza::create([
            'nombre' => 'Agosto 2026',
            'mes' => 8,
            'anio' => 2026,
            'activo' => true,
            'objetivo_monto' => 1500000.00,
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
     * Helper to write a test file (CSV or XLSX) on the fly.
     */
    protected function createTestFile(array $rows, string $format = 'csv'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'import_test_') . '.' . $format;

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

        return $tempFile;
    }

    /**
     * Test 1: Importar una operación nueva con una cuota.
     */
    public function test_import_one_new_operation_and_cuota(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '1', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file = $this->createTestFile($rows, 'xlsx');

        $stats = $this->importService->import($file, 'cartera.xlsx', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(1, $stats['registros_nuevos']);
        $this->assertDatabaseHas('clientes', ['documento' => '12345678', 'codigo_cliente_oficial' => 'CLI001']);
        $this->assertDatabaseHas('operaciones', ['numero_solicitud' => '2455']);
        $this->assertDatabaseHas('cuotas', [
            'numero_cuota' => 1,
            'dia_cobro' => 5,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
        ]);
        unlink($file);
    }

    /**
     * Test 2: Importar dos cuotas pertenecientes a la misma solicitud.
     */
    public function test_import_two_cuotas_same_solicitud_creates_one_operation(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '1', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com'],
            ['2455', '2', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '0', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file = $this->createTestFile($rows, 'csv');

        $stats = $this->importService->import($file, 'cartera.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(2, $stats['registros_nuevos']);
        $this->assertEquals(1, Operacion::count());
        $this->assertEquals(2, Cuota::count());
        unlink($file);
    }

    /**
     * Test 3: Importar nuevamente la misma solicitud/cuota. No debe duplicarse.
     */
    public function test_import_duplicate_solicitud_cuota_does_not_duplicate(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file1 = $this->createTestFile($rows, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $file2 = $this->createTestFile($rows, 'csv');
        $stats = $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(0, $stats['registros_nuevos']);
        $this->assertEquals(1, $stats['registros_sin_cambios']);
        $this->assertEquals(1, Cuota::count());
        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 4: Importar una nueva cuota de una operación existente.
     */
    public function test_import_new_cuota_of_existing_operation(): void
    {
        // First import cuota 5
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file1 = $this->createTestFile($rows1, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        // Second import cuota 6 for same solicitud
        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '6', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '0', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file2 = $this->createTestFile($rows2, 'csv');
        $stats = $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(1, $stats['registros_nuevos']);
        $this->assertEquals(1, Operacion::count());
        $this->assertEquals(2, Cuota::count());
        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 5: Importar nuevamente una cuota pendiente. Debe actualizar valores y crear historial.
     */
    public function test_import_existing_cuota_updates_financial_values_and_creates_history(): void
    {
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file1 = $this->createTestFile($rows1, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '3500', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file2 = $this->createTestFile($rows2, 'csv');
        $stats = $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(1, $stats['registros_actualizados']);

        $cuota = Cuota::first();
        $this->assertEquals(3500.00, $cuota->punitorios);
        $this->assertEquals(103500.00, $cuota->total_actualizado);

        // Verify that history has two records
        $this->assertEquals(2, CuotaImportacion::count());
        $this->assertEquals(2000.00, CuotaImportacion::orderBy('id', 'asc')->first()->punitorios_observados);
        $this->assertEquals(3500.00, CuotaImportacion::orderBy('id', 'desc')->first()->punitorios_observados);

        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 6: Verificar que los punitorios históricos se conservan.
     */
    public function test_historical_punitorios_preserved(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file = $this->createTestFile($rows, 'csv');
        $this->importService->import($file, 'cartera.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertDatabaseHas('cuota_importaciones', [
            'punitorios_observados' => 2000.00,
            'estado_presencia' => 'presente'
        ]);
        unlink($file);
    }

    /**
     * Test 7: Una cuota ausente en una importación posterior NO genera automáticamente un pago.
     */
    public function test_absent_cuota_does_not_create_payment(): void
    {
        // First import with 2 cuotas
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com'],
            ['3000', '2', 'Maria', 'Gomez', '87654321', 'CLI002', '10', '80000', '1000', '443322', 'Calle 2 456', 'maria@example.com']
        ];
        $file1 = $this->createTestFile($rows1, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        // Second import with only the second cuota (2455/5 is now absent)
        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['3000', '2', 'Maria', 'Gomez', '87654321', 'CLI002', '10', '80000', '1000', '443322', 'Calle 2 456', 'maria@example.com']
        ];
        $file2 = $this->createTestFile($rows2, 'csv');
        $stats = $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(1, $stats['registros_ausentes']);

        $absentCuota = Cuota::whereHas('operacion', function($q) {
            $q->where('numero_solicitud', '2455');
        })->first();

        $this->assertEquals('ausente_en_ultima_importacion', $absentCuota->estado_financiero);
        $this->assertEquals(0, Pago::count()); // Assert NO payment was created

        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 8: Una cuota ausente NO se elimina.
     */
    public function test_absent_cuota_is_not_deleted(): void
    {
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com'],
            ['3000', '2', 'Maria', 'Gomez', '87654321', 'CLI002', '10', '80000', '1000', '443322', 'Calle 2 456', 'maria@example.com']
        ];
        $file1 = $this->createTestFile($rows1, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['3000', '2', 'Maria', 'Gomez', '87654321', 'CLI002', '10', '80000', '1000', '443322', 'Calle 2 456', 'maria@example.com']
        ];
        $file2 = $this->createTestFile($rows2, 'csv');
        $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(2, Cuota::count()); // Both still exist
        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 9: Las gestiones existentes sobreviven a una importación.
     */
    public function test_gestiones_survive_import(): void
    {
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file1 = $this->createTestFile($rows1, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $cuota = Cuota::first();
        Gestion::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_hora' => now(),
            'tipo' => 'whatsapp_enviado',
            'resultado' => 'no_atendio',
            'observacion' => 'Primer contacto manual',
        ]);

        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '3500', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file2 = $this->createTestFile($rows2, 'csv');
        $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(1, Gestion::count());
        $this->assertEquals('Primer contacto manual', Gestion::first()->observacion);
        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 10: Las promesas existentes sobreviven.
     */
    public function test_promesas_survive_import(): void
    {
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file1 = $this->createTestFile($rows1, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $cuota = Cuota::first();
        PromesaPago::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_creacion' => now(),
            'fecha_prometida' => now()->addDays(5)->toDateString(),
            'monto_prometido' => 102000.00,
            'estado' => 'pendiente',
        ]);

        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '5000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file2 = $this->createTestFile($rows2, 'csv');
        $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(1, PromesaPago::count());
        $this->assertEquals(102000.00, floatval(PromesaPago::first()->monto_prometido));
        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 11: Los pagos existentes sobreviven.
     */
    public function test_pagos_survive_import(): void
    {
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file1 = $this->createTestFile($rows1, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $cuota = Cuota::first();
        Pago::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_pago' => now(),
            'importe_original_snapshot' => 100000.00,
            'punitorios_snapshot' => 2000.00,
            'total_actualizado_snapshot' => 102000.00,
            'monto_cobrado' => 100000.00,
            'punitorios_perdonados' => 2000.00,
            'es_cancelatorio' => true,
            'medio_pago' => 'transferencia',
        ]);

        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '5000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file2 = $this->createTestFile($rows2, 'csv');
        $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(1, Pago::count());
        $this->assertEquals(100000.00, floatval(Pago::first()->monto_cobrado));
        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 12: El teléfono y domicilio gestionados manualmente no son sobrescritos.
     */
    public function test_manual_contact_info_not_overwritten(): void
    {
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file1 = $this->createTestFile($rows1, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $cliente = Cliente::first();
        $cliente->update([
            'telefono' => 'MANUAL_PHONE',
            'domicilio' => 'MANUAL_ADDRESS',
        ]);

        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '5000', 'NEW_PHONE', 'NEW_ADDRESS', 'juan@example.com']
        ];
        $file2 = $this->createTestFile($rows2, 'csv');
        $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals('MANUAL_PHONE', $cliente->fresh()->telefono);
        $this->assertEquals('MANUAL_ADDRESS', $cliente->fresh()->domicilio);
        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 13: Una fila duplicada dentro del archivo no crea duplicados.
     */
    public function test_duplicate_row_within_file_handled(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com'] // Identical benign duplicate
        ];
        $file = $this->createTestFile($rows, 'csv');

        $stats = $this->importService->import($file, 'cartera.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(1, $stats['registros_nuevos']);
        $this->assertEquals(1, $stats['benign_duplicates']);
        $this->assertEquals(1, Cuota::count());
        unlink($file);
    }

    /**
     * Test 14: Una fila inválida queda registrada como error.
     */
    public function test_invalid_row_logged_as_error(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', 'INVALID_CUOTA', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file = $this->createTestFile($rows, 'csv');

        $stats = $this->importService->import($file, 'cartera.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(1, $stats['registros_errores']);
        $this->assertEquals(0, Cuota::count());
        $this->assertDatabaseHas('detalle_importaciones', [
            'numero_linea' => 2,
            'accion' => 'error'
        ]);
        unlink($file);
    }

    /**
     * Test 15: Una estructura de archivo inválida es rechazada correctamente.
     */
    public function test_invalid_file_structure_rejected_completely(): void
    {
        $rows = [
            ['WrongHeader1', 'WrongHeader2'],
            ['val1', 'val2']
        ];
        $file = $this->createTestFile($rows, 'csv');

        $this->expectException(Exception::class);
        $this->importService->import($file, 'cartera.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);
        unlink($file);
    }

    /**
     * Test 16: Se registran correctamente las estadísticas de la importación.
     */
    public function test_import_statistics_recorded_correctly(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file = $this->createTestFile($rows, 'csv');

        $this->importService->import($file, 'cartera.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $importacion = Importacion::first();
        $this->assertEquals(1, $importacion->cantidad_registros);
        $this->assertEquals(1, $importacion->registros_nuevos);
        $this->assertEquals(0, $importacion->registros_actualizados);
        $this->assertEquals(0, $importacion->registros_ausentes);
        $this->assertEquals(0, $importacion->registros_errores);
        $this->assertEquals('completada', $importacion->estado);
        unlink($file);
    }

    /**
     * Test 17: La combinación "cuota_id + importacion_id" no puede duplicarse.
     */
    public function test_cuota_and_importation_unique_constraint(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file = $this->createTestFile($rows, 'csv');
        $this->importService->import($file, 'cartera.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $cuota = Cuota::first();
        $importacion = Importacion::first();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        CuotaImportacion::create([
            'cuota_id' => $cuota->id,
            'importacion_id' => $importacion->id,
            'importe_original_observado' => 100000.00,
            'punitorios_observados' => 2000.00,
            'total_actualizado_observado' => 102000.00,
            'estado_presencia' => 'presente'
        ]);
        unlink($file);
    }

    /**
     * Test 18: Una misma cuota puede aparecer en múltiples importaciones.
     */
    public function test_same_cuota_multiple_importations(): void
    {
        $rows1 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file1 = $this->createTestFile($rows1, 'csv');
        $this->importService->import($file1, 'cartera1.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $rows2 = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '3500', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file2 = $this->createTestFile($rows2, 'csv');
        $this->importService->import($file2, 'cartera2.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $this->assertEquals(2, CuotaImportacion::count());
        $this->assertEquals(2, Importacion::count());
        unlink($file1);
        unlink($file2);
    }

    /**
     * Test 19: El total actualizado se calcula correctamente.
     */
    public function test_total_actualizado_calculated_correctly(): void
    {
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000.50', '2000.25', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file = $this->createTestFile($rows, 'csv');

        $this->importService->import($file, 'cartera.csv', $this->periodo->id, $this->user->id, $this->defaultMapping);

        $cuota = Cuota::first();
        $this->assertEquals(102000.75, floatval($cuota->total_actualizado));
        unlink($file);
    }

    /**
     * Test 20: Una importación fallida no deja datos financieros parcialmente aplicados cuando corresponda una operación atómica.
     */
    public function test_atomic_import_fails_reverts_all_changes(): void
    {
        // We will mock/force an exception inside the import flow to verify transaction rollback
        $rows = [
            ['Solicitud', 'Cuota', 'Nombre', 'Apellido', 'Documento', 'Codigo Cliente', 'Dia Cobro', 'Importe', 'Punitorios', 'Telefono', 'Domicilio', 'Email'],
            ['2455', '5', 'Juan', 'Perez', '12345678', 'CLI001', '5', '100000', '2000', '112233', 'Calle Falsa 123', 'juan@example.com']
        ];
        $file = $this->createTestFile($rows, 'csv');

        // We corrupt the default mapping to point to a missing column, forcing an Exception after transaction begins
        $badMapping = $this->defaultMapping;
        $badMapping['importe_original'] = 'NON_EXISTENT_COLUMN';

        try {
            $this->importService->import($file, 'cartera.csv', $this->periodo->id, $this->user->id, $badMapping);
            $this->fail("Debería haberse lanzado una excepción.");
        } catch (Exception $e) {
            // Expected
        }

        // Assert that NO clients, operations, or cuotas were created in DB because of rollback
        $this->assertEquals(0, Cliente::count());
        $this->assertEquals(0, Operacion::count());
        $this->assertEquals(0, Cuota::count());

        // But the importacion itself was updated to 'fallida' outside the transaction
        $importacion = Importacion::first();
        $this->assertNotNull($importacion);
        $this->assertEquals('fallida', $importacion->estado);

        unlink($file);
    }
}

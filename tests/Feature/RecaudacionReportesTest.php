<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PeriodoCobranza;
use App\Models\Cliente;
use App\Models\Operacion;
use App\Models\Cuota;
use App\Models\Importacion;
use App\Models\CuotaImportacion;
use App\Models\Pago;
use App\Models\PromesaPago;
use App\Models\VisitaCobrador;
use Livewire\Livewire;
use App\Livewire\RecaudacionComponent;
use Carbon\Carbon;

class RecaudacionReportesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $gestor;
    protected $cobrador;
    protected $period;
    protected $cliente;
    protected $operacion;
    protected $cuota;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->admin->role = 'administrador';
        $this->admin->save();

        $this->gestor = User::create([
            'name' => 'Gestor User',
            'email' => 'gestor_test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->gestor->role = 'gestor';
        $this->gestor->save();

        $this->cobrador = User::create([
            'name' => 'Cobrador User',
            'email' => 'cobrador_test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->cobrador->role = 'cobrador';
        $this->cobrador->save();

        // Period
        $this->period = PeriodoCobranza::create([
            'nombre' => 'Agosto 2026',
            'mes' => 8,
            'anio' => 2026,
            'activo' => true,
            'objetivo_monto' => 10000.00,
        ]);

        // Client & Debt
        $this->cliente = Cliente::create([
            'nombre' => 'John',
            'apellido' => 'Doe',
            'documento' => '12345678',
            'telefono' => '11112222',
            'domicilio' => 'Main Street 123',
        ]);

        $this->operacion = Operacion::create([
            'cliente_id' => $this->cliente->id,
            'numero_solicitud' => 'SOL-999',
        ]);

        $this->cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 10,
            'importe_original' => 5000.00,
            'punitorios' => 500.00,
            'total_actualizado' => 5500.00,
            'saldo_pendiente' => 5500.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        // Import association
        $import = Importacion::create([
            'periodo_cobranza_id' => $this->period->id,
            'user_id' => $this->admin->id,
            'fecha_hora' => now(),
            'nombre_archivo' => 'test.xlsx',
            'tipo_archivo' => 'xlsx',
            'cantidad_registros' => 1,
            'registros_nuevos' => 1,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completada',
        ]);

        CuotaImportacion::create([
            'cuota_id' => $this->cuota->id,
            'importacion_id' => $import->id,
            'importe_original_observado' => 5000.00,
            'punitorios_observados' => 500.00,
            'total_actualizado_observado' => 5500.00,
            'dia_cobro_observado' => 10,
            'estado_presencia' => 'presente',
        ]);
    }

    public function test_only_authorized_roles_can_access_recaudacion()
    {
        // Admin can access via HTTP GET
        $this->actingAs($this->admin)->get('/recaudacion')->assertStatus(200);

        // Guest gets redirected or receives unauthorized exception (testing Livewire authorization directly is preferred)
        $this->actingAs($this->cobrador);
        Livewire::test(RecaudacionComponent::class)
            ->assertStatus(403);

        // Gestor can access
        $this->actingAs($this->gestor);
        Livewire::test(RecaudacionComponent::class)
            ->assertStatus(200);

        // Admin can access
        $this->actingAs($this->admin);
        Livewire::test(RecaudacionComponent::class)
            ->assertStatus(200);
    }

    public function test_metric_calculations_for_the_period_are_correct()
    {
        $this->actingAs($this->admin);

        // Register a payment of 3000, 500 forgiven (partial payment)
        Pago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->admin->id,
            'fecha_pago' => Carbon::today(),
            'importe_original_snapshot' => 5000.00,
            'punitorios_snapshot' => 500.00,
            'total_actualizado_snapshot' => 5500.00,
            'monto_cobrado' => 3000.00,
            'punitorios_perdonados' => 500.00,
            'es_cancelatorio' => false,
            'medio_pago' => 'transferencia',
        ]);

        // Second payment of 2000 (completing)
        Pago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->admin->id,
            'fecha_pago' => Carbon::today(),
            'importe_original_snapshot' => 5000.00,
            'punitorios_snapshot' => 500.00,
            'total_actualizado_snapshot' => 5500.00,
            'monto_cobrado' => 2000.00,
            'punitorios_perdonados' => 0.00,
            'es_cancelatorio' => true,
            'medio_pago' => 'transferencia',
        ]);

        $this->cuota->saldo_pendiente = 0.00;
        $this->cuota->save();

        Livewire::test(RecaudacionComponent::class)
            ->assertSet('metrics.objetivo', 10000.00)
            ->assertSet('metrics.recaudado', 5000.00)
            ->assertSet('metrics.porcentaje_alcanzado', 50.00)
            ->assertSet('metrics.restante', 5000.00)
            ->assertSet('metrics.cantidad_pagos', 2)
            ->assertSet('metrics.cuotas_canceladas', 1)
            ->assertSet('metrics.pagos_parciales', 1)
            ->assertSet('metrics.punitorios_perdonados', 500.00);
    }

    public function test_payments_history_filters_correctly()
    {
        $this->actingAs($this->admin);

        $p1 = Pago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->admin->id,
            'fecha_pago' => Carbon::yesterday(),
            'importe_original_snapshot' => 5000.00,
            'punitorios_snapshot' => 500.00,
            'total_actualizado_snapshot' => 5500.00,
            'monto_cobrado' => 1500.00,
            'punitorios_perdonados' => 0.00,
            'es_cancelatorio' => false,
            'medio_pago' => 'efectivo',
        ]);

        $p2 = Pago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->admin->id,
            'fecha_pago' => Carbon::today(),
            'importe_original_snapshot' => 5000.00,
            'punitorios_snapshot' => 500.00,
            'total_actualizado_snapshot' => 5500.00,
            'monto_cobrado' => 3500.00,
            'punitorios_perdonados' => 500.00,
            'es_cancelatorio' => true,
            'medio_pago' => 'transferencia',
        ]);

        // Filter by Cash
        $component1 = Livewire::test(RecaudacionComponent::class)
            ->set('activeTab', 'historial')
            ->set('filterMedioPago', 'efectivo')
            ->assertSee('Doe');

        $pags1 = $component1->get('payments');
        $this->assertCount(1, $pags1);
        $this->assertEquals($p1->id, $pags1->first()->id);

        // Filter by Transfer
        $component2 = Livewire::test(RecaudacionComponent::class)
            ->set('activeTab', 'historial')
            ->set('filterMedioPago', 'transferencia')
            ->assertSee('Doe');

        $pags2 = $component2->get('payments');
        $this->assertCount(1, $pags2);
        $this->assertEquals($p2->id, $pags2->first()->id);
    }

    public function test_period_reports_and_csv_generation()
    {
        $this->actingAs($this->admin);

        Pago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->admin->id,
            'fecha_pago' => Carbon::today(),
            'importe_original_snapshot' => 5000.00,
            'punitorios_snapshot' => 500.00,
            'total_actualizado_snapshot' => 5500.00,
            'monto_cobrado' => 5000.00,
            'punitorios_perdonados' => 500.00,
            'es_cancelatorio' => true,
            'medio_pago' => 'transferencia',
        ]);

        Livewire::test(RecaudacionComponent::class)
            ->set('activeTab', 'reportes')
            ->assertSet('reportMetrics.cuotas_pagadas', 0) // because saldo_pendiente of cuota is still 5500
            ->call('exportReportCsv')
            ->assertFileDownloaded();
    }

    public function test_period_closing_preserves_historical_data()
    {
        $this->actingAs($this->admin);

        // Close current period
        Livewire::test(RecaudacionComponent::class)
            ->set('activeTab', 'periodos')
            ->call('closePeriod', $this->period->id)
            ->assertHasNoErrors();

        $this->assertEquals(0, (int) PeriodoCobranza::find($this->period->id)->activo);

        // Data is still in DB
        $this->assertEquals(1, Cuota::count());
        $this->assertEquals(1, Cliente::count());
    }
}

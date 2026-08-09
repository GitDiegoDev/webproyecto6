<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Operacion;
use App\Models\PeriodoCobranza;
use App\Models\User;
use App\Models\Importacion;
use App\Models\CuotaImportacion;
use App\Models\PromesaPago;
use App\Models\VisitaCobrador;
use App\Models\Pago;
use App\Livewire\DashboardComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $gestor;
    protected User $admin;
    protected User $cobrador;
    protected PeriodoCobranza $periodo;
    protected Importacion $importacion;
    protected Cliente $cliente;
    protected Operacion $operacion;
    protected Cuota $cuota;

    protected function setUp(): void
    {
        parent::setUp();

        // Users
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->admin->role = 'administrador';
        $this->admin->save();

        $this->gestor = User::create([
            'name' => 'Gestor User',
            'email' => 'gestor@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->gestor->role = 'gestor';
        $this->gestor->save();

        $this->cobrador = User::create([
            'name' => 'Cobrador User',
            'email' => 'cobrador@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->cobrador->role = 'cobrador';
        $this->cobrador->save();

        // Active period
        $this->periodo = PeriodoCobranza::create([
            'nombre' => 'Periodo Test',
            'mes' => 8,
            'anio' => 2026,
            'activo' => true,
            'objetivo_monto' => 100000.00,
        ]);

        $this->importacion = Importacion::create([
            'periodo_cobranza_id' => $this->periodo->id,
            'user_id' => $this->admin->id,
            'fecha_hora' => now(),
            'nombre_archivo' => 'cartera.xlsx',
            'tipo_archivo' => 'xlsx',
            'cantidad_registros' => 1,
            'registros_nuevos' => 1,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completado',
        ]);

        // Cliente, Operación, Cuota
        $this->cliente = Cliente::create([
            'nombre' => 'María',
            'apellido' => 'Gómez',
            'documento' => '87654321',
            'telefono' => '155555555',
            'domicilio' => 'Av. de Mayo 500',
        ]);

        $this->operacion = Operacion::create([
            'cliente_id' => $this->cliente->id,
            'numero_solicitud' => '1000',
        ]);

        $this->cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 10000.00,
            'punitorios' => 500.00,
            'total_actualizado' => 10500.00,
            'saldo_pendiente' => 10500.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        // Associate cuota with the active period via importacion
        CuotaImportacion::create([
            'cuota_id' => $this->cuota->id,
            'importacion_id' => $this->importacion->id,
            'importe_original_observado' => 10000.00,
            'punitorios_observados' => 500.00,
            'total_actualizado_observado' => 10500.00,
            'dia_cobro_observado' => 15,
            'estado_presencia' => 'presente',
        ]);
    }

    /**
     * 1. Check that the Dashboard loads successfully.
     */
    public function test_dashboard_page_loads_successfully(): void
    {
        $this->actingAs($this->gestor);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSeeLivewire(DashboardComponent::class);
    }

    /**
     * 2. Check that the active period is shown correctly.
     */
    public function test_dashboard_shows_active_period(): void
    {
        Livewire::actingAs($this->gestor);

        Livewire::test(DashboardComponent::class)
            ->assertSee('Periodo Test');
    }

    /**
     * 3. Calculates metrics: recaudado, total potential, cumplimiento %.
     */
    public function test_calculates_collections_and_compliance(): void
    {
        Livewire::actingAs($this->gestor);

        // Record a payment of 5000.00
        Pago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_pago' => now(),
            'importe_original_snapshot' => 10000.00,
            'punitorios_snapshot' => 500.00,
            'total_actualizado_snapshot' => 10500.00,
            'monto_cobrado' => 5000.00,
            'punitorios_perdonados' => 100.00,
            'es_cancelatorio' => false,
            'medio_pago' => 'transferencia',
        ]);

        $this->cuota->update(['saldo_pendiente' => 5400.00]);

        Livewire::test(DashboardComponent::class)
            ->assertSet('metrics.recaudado', 5000.00)
            ->assertSet('metrics.total_potencial_pendiente', 5400.00)
            ->assertSet('metrics.punitorios_perdonados', 100.00)
            ->assertSet('metrics.cumplimiento_porcentaje', 5.00); // 5000 / 100000 * 100
    }

    /**
     * 4. Verify counts for pending and overdue cuotas.
     */
    public function test_dashboard_shows_cuotas_counts(): void
    {
        Livewire::actingAs($this->gestor);

        // Cuota with due day 15, reference date is 20th => overdue
        Livewire::test(DashboardComponent::class, [
            'referenceDate' => '2026-08-20',
        ])
        ->assertSet('metrics.cuotas_pendientes', 1)
        ->assertSet('metrics.vencidas', 1);
    }

    /**
     * 5. Verify counts for promesas for today and breached promises.
     */
    public function test_dashboard_shows_promises_counts(): void
    {
        Livewire::actingAs($this->gestor);

        // Promesa para hoy
        PromesaPago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_creacion' => '2026-08-19',
            'fecha_prometida' => '2026-08-20',
            'monto_prometido' => 10500.00,
            'estado' => 'pendiente',
        ]);

        Livewire::test(DashboardComponent::class, [
            'referenceDate' => '2026-08-20',
        ])
        ->assertSet('metrics.promesas_hoy', 1);

        // Promesa incumplida
        PromesaPago::query()->update(['estado' => 'incumplida']);

        Livewire::test(DashboardComponent::class, [
            'referenceDate' => '2026-08-20',
        ])
        ->assertSet('metrics.promesas_incumplidas', 1);
    }

    /**
     * 6. Verify client sin respuesta count.
     */
    public function test_dashboard_shows_sin_respuesta_count(): void
    {
        Livewire::actingAs($this->gestor);

        $this->cuota->update(['estado_gestion' => 'sin_respuesta']);

        Livewire::test(DashboardComponent::class)
            ->assertSet('metrics.sin_respuesta', 1);
    }

    /**
     * 7. Verify visits stats.
     */
    public function test_dashboard_shows_visitas_stats(): void
    {
        Livewire::actingAs($this->gestor);

        VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador->id,
            'domicilio' => 'Av. de Mayo 500',
            'fecha_programada' => '2026-08-20',
            'estado' => 'pendiente',
        ]);

        Livewire::test(DashboardComponent::class, [
            'referenceDate' => '2026-08-20',
        ])
        ->assertSet('metrics.visitas_hoy', 1)
        ->assertSet('metrics.visitas_pendientes', 1);
    }

    /**
     * 8. Verify the search bar works.
     */
    public function test_dashboard_reactive_search_works(): void
    {
        Livewire::actingAs($this->gestor);

        // Search for 'María' should find the cuota
        Livewire::test(DashboardComponent::class)
            ->set('search', 'María')
            ->assertCount('cuotas', 1);

        // Search for 'Inexistente' should find nothing
        Livewire::test(DashboardComponent::class)
            ->set('search', 'Inexistente')
            ->assertCount('cuotas', 0);
    }

    /**
     * 9. Verify the filters work correctly.
     */
    public function test_dashboard_filters_work(): void
    {
        Livewire::actingAs($this->gestor);

        // Priority filter
        Livewire::test(DashboardComponent::class)
            ->set('filter', 'criticas')
            ->assertCount('cuotas', 0); // is 'media'

        Livewire::test(DashboardComponent::class)
            ->set('filter', 'todas')
            ->assertCount('cuotas', 1);
    }

    /**
     * 10. Register gestion updates the Dashboard.
     */
    public function test_register_gestion_updates_dashboard(): void
    {
        Livewire::actingAs($this->gestor);

        Livewire::test(DashboardComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('gestion_tipo', 'llamada')
            ->set('gestion_resultado', 'contactado')
            ->set('gestion_observacion', 'Prueba gestión')
            ->call('submitGestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gestiones', [
            'cuota_id' => $this->cuota->id,
            'tipo' => 'llamada',
            'resultado' => 'contactado',
            'observacion' => 'Prueba gestión',
        ]);
    }

    /**
     * 11. Register promise updates the Dashboard.
     */
    public function test_register_promise_updates_dashboard(): void
    {
        Livewire::actingAs($this->gestor);

        Livewire::test(DashboardComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('promesa_fecha_prometida', '2026-08-25')
            ->set('promesa_monto_prometido', 5000.00)
            ->set('promesa_observaciones', 'Paga el 25')
            ->call('submitPromesa')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('promesas_pago', [
            'cuota_id' => $this->cuota->id,
            'fecha_prometida' => '2026-08-25',
            'monto_prometido' => 5000.00,
            'estado' => 'pendiente',
        ]);
    }

    /**
     * 12. Register payment updates metrics and tables.
     */
    public function test_register_payment_updates_metrics(): void
    {
        Livewire::actingAs($this->gestor);

        Livewire::test(DashboardComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('pago_monto_cobrado', 4000.00)
            ->set('pago_punitorios_perdonados', 100.00)
            ->set('pago_medio_pago', 'efectivo')
            ->set('pago_observaciones', 'Abonó a cuenta')
            ->call('submitPago')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pagos', [
            'cuota_id' => $this->cuota->id,
            'monto_cobrado' => 4000.00,
            'punitorios_perdonados' => 100.00,
        ]);
    }

    /**
     * 13. Condonation does not modify cuotas.punitorios.
     */
    public function test_punitorios_perdonados_accounting_preserved(): void
    {
        Livewire::actingAs($this->gestor);

        Livewire::test(DashboardComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('pago_monto_cobrado', 10000.00)
            ->set('pago_punitorios_perdonados', 500.00)
            ->set('pago_medio_pago', 'efectivo')
            ->call('submitPago')
            ->assertHasNoErrors();

        // Check cuotas.punitorios remains 500.00
        $this->assertEquals(500.00, $this->cuota->fresh()->punitorios);

        // Check payments record contains 500.00 as perdonados
        $this->assertDatabaseHas('pagos', [
            'cuota_id' => $this->cuota->id,
            'punitorios_perdonados' => 500.00,
        ]);
    }

    /**
     * 14. Unauthorized roles cannot perform restricted actions.
     */
    public function test_unauthorized_user_restricted(): void
    {
        Livewire::actingAs($this->cobrador);

        // A cobrador cannot program a collector visit or register general payments
        Livewire::test(DashboardComponent::class)
            ->call('openCobradorModal', $this->cuota->id)
            ->assertStatus(403);

        Livewire::test(DashboardComponent::class)
            ->call('openPagoModal', $this->cuota->id)
            ->assertStatus(403);
    }
}

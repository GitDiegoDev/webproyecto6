<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Operacion;
use App\Models\User;
use App\Models\VisitaCobrador;
use App\Models\Pago;
use App\Models\Gestion;
use App\Livewire\CobradorComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Carbon\Carbon;

class CobradorPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $gestor;
    protected User $cobrador1;
    protected User $cobrador2;
    protected Cliente $cliente;
    protected Operacion $operacion;
    protected Cuota $cuota;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Users with distinct roles
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->admin->role = 'administrador';
        $this->admin->save();

        $this->gestor = User::create([
            'name' => 'Gestor User',
            'email' => 'gestor@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->gestor->role = 'gestor';
        $this->gestor->save();

        $this->cobrador1 = User::create([
            'name' => 'Cobrador Uno',
            'email' => 'cob1@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->cobrador1->role = 'cobrador';
        $this->cobrador1->save();

        $this->cobrador2 = User::create([
            'name' => 'Cobrador Dos',
            'email' => 'cob2@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->cobrador2->role = 'cobrador';
        $this->cobrador2->save();

        // Create initial portfolio data
        $this->cliente = Cliente::create([
            'nombre' => 'Juan',
            'apellido' => 'Gomez',
            'documento' => '11222333',
            'telefono' => '3514445555',
            'domicilio' => 'Av Colon 1234, Cordoba',
        ]);

        $this->operacion = Operacion::create([
            'cliente_id' => $this->cliente->id,
            'numero_solicitud' => 'SOL-900',
        ]);

        $this->cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);
    }

    /**
     * Test 1: Cobrador can access their panel.
     */
    public function test_1_cobrador_can_access_their_panel(): void
    {
        $response = $this->actingAs($this->cobrador1)->get('/cobrador');
        $response->assertStatus(200);
    }

    /**
     * Test 2: Gestor/administrador maintain corresponding accesses.
     */
    public function test_2_gestor_and_admin_maintain_corresponding_accesses(): void
    {
        $this->actingAs($this->gestor)->get('/cobrador')->assertStatus(200);
        $this->actingAs($this->admin)->get('/cobrador')->assertStatus(200);
    }

    /**
     * Test 3: Cobrador cannot see visits of another cobrador.
     */
    public function test_3_cobrador_cannot_see_visits_of_another_cobrador(): void
    {
        // Visit assigned to cobrador2
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador2->id,
            'domicilio' => 'Calle Secundaria 777',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        // Accessing as cobrador1
        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->assertDontSee('Calle Secundaria 777')
            ->assertDontSee('Juan Gomez');

        // Attempting to select this visit must abort with 403 (checked in backend)
        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->assertStatus(403);
    }

    /**
     * Test 4: Cobrador can view their visits of today.
     */
    public function test_4_cobrador_can_view_their_visits_of_today(): void
    {
        VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Av Colon 1234, Cordoba',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->assertSee('Juan Gomez')
            ->assertSee('Av Colon 1234, Cordoba')
            ->assertSee('SOL-900')
            ->assertSee('CUOTA 1');
    }

    /**
     * Test 5: The visits are sorted correctly (Priority-based: critical/alta/media/baja, completed at bottom).
     */
    public function test_5_visits_are_sorted_correctly(): void
    {
        // 3 visits today:
        // A: priority critical (cuota priority 'critica')
        // B: priority low (cuota priority 'baja')
        // C: completed (estado 'realizada')

        $cuotaCrit = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 2,
            'dia_cobro' => 5,
            'importe_original' => 50000.00,
            'punitorios' => 1000.00,
            'total_actualizado' => 51000.00,
            'saldo_pendiente' => 51000.00,
            'estado_financiero' => 'vencida',
            'estado_gestion' => 'promesa_incumplida', // This triggers 'critica'
            'prioridad' => 'critica',
        ]);

        $cuotaBaja = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 3,
            'dia_cobro' => 20,
            'importe_original' => 50000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 50000.00,
            'saldo_pendiente' => 50000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'baja',
        ]);

        $vCrit = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $cuotaCrit->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Dir Critica',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $vBaja = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $cuotaBaja->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Dir Baja',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $vComp = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Dir Realizada',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'realizada',
            'resultado' => 'cobrado',
            'monto_cobrado' => 10000.00,
        ]);

        $this->actingAs($this->cobrador1);

        $component = Livewire::test(CobradorComponent::class);
        $visits = $component->get('todayVisits');

        $this->assertCount(3, $visits);

        // First should be critical
        $this->assertEquals($vCrit->id, $visits->first()->id);
        // Last should be the completed one
        $this->assertEquals($vComp->id, $visits->last()->id);
    }

    /**
     * Test 6: Client details are rendered correctly.
     */
    public function test_6_client_details_are_rendered_correctly(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->assertSee('Juan Gomez')
            ->assertSee('3514445555')
            ->assertSee('Calle Falsa 123');
    }

    /**
     * Test 7: Frozen domicile of the visit is preserved and rendered.
     */
    public function test_7_frozen_domicile_is_preserved_and_rendered(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Direccion Congelada 999',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        // Modify the client's current domicile in the master record
        $this->cliente->update(['domicilio' => 'Direccion Nueva Modificada']);

        $this->actingAs($this->cobrador1);

        // Domicilio on the visit MUST still be 'Direccion Congelada 999'
        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->assertSee('Direccion Congelada 999')
            ->assertDontSee('Direccion Nueva Modificada');
    }

    /**
     * Test 8: Cobrador can register visit outcome.
     */
    public function test_8_cobrador_can_register_visit_outcome(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->call('openResultadoModal')
            ->set('resultado_tipo', 'no_estaba')
            ->set('resultado_observaciones', 'Toque timbre y nadie salio')
            ->call('submitResultado')
            ->assertHasNoErrors();

        $this->assertEquals('realizada', $visita->fresh()->estado);
        $this->assertEquals('no_estaba', $visita->fresh()->resultado);
        $this->assertEquals('Toque timbre y nadie salio', $visita->fresh()->observaciones);
    }

    /**
     * Test 9: Registration logs the completed date/time.
     */
    public function test_9_outcome_registration_logs_completed_date(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->call('openResultadoModal')
            ->set('resultado_tipo', 'reprogramar')
            ->set('resultado_fecha_realizada', '2026-10-10')
            ->call('submitResultado');

        $this->assertEquals('2026-10-10', Carbon::parse($visita->fresh()->fecha_realizada)->toDateString());
    }

    /**
     * Test 10: Registration logs corresponding management event (gestion) in database.
     */
    public function test_10_outcome_registration_logs_related_gestion(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->call('openResultadoModal')
            ->set('resultado_tipo', 'se_nego_a_pagar')
            ->set('resultado_observaciones', 'Insulto y cerro la puerta')
            ->call('submitResultado');

        $this->assertDatabaseHas('gestiones', [
            'cuota_id' => $this->cuota->id,
            'tipo' => 'visita_presencial',
            'resultado' => 'seguimiento', // mapped result
            'observacion' => 'Resultado Visita: Insulto y cerro la puerta',
        ]);
    }

    /**
     * Test 11: Cobrador can register a payment.
     */
    public function test_11_cobrador_can_register_payment(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->call('openPagoModal')
            ->set('pago_monto_cobrado', 100000.00)
            ->set('pago_punitorios_perdonados', 2000.00)
            ->set('pago_medio_pago', 'efectivo')
            ->call('submitPago')
            ->assertHasNoErrors();

        // Check if payment was registered
        $this->assertDatabaseHas('pagos', [
            'cuota_id' => $this->cuota->id,
            'monto_cobrado' => 100000.00,
            'punitorios_perdonados' => 2000.00,
        ]);
    }

    /**
     * Test 12: Full payment registration works correctly.
     */
    public function test_12_full_payment_registration_works_correctly(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->call('openPagoModal')
            ->set('pago_monto_cobrado', 102000.00)
            ->set('pago_punitorios_perdonados', 0.00)
            ->call('submitPago');

        $this->assertEquals(0.00, floatval($this->cuota->fresh()->saldo_pendiente));
        $this->assertEquals('cobrado', $this->cuota->fresh()->estado_gestion);
        $this->assertEquals('realizada', $visita->fresh()->estado);
        $this->assertEquals('cobrado', $visita->fresh()->resultado);
    }

    /**
     * Test 13: Partial payment registration works correctly.
     */
    public function test_13_partial_payment_registration_works_correctly(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->call('openPagoModal')
            ->set('pago_monto_cobrado', 50000.00)
            ->set('pago_punitorios_perdonados', 0.00)
            ->call('submitPago');

        // Balances and states are updated
        $this->assertEquals(52000.00, floatval($this->cuota->fresh()->saldo_pendiente));
        $this->assertEquals('seguimiento', $this->cuota->fresh()->estado_gestion);
        $this->assertEquals('realizada', $visita->fresh()->estado);
        $this->assertEquals('cobrado_parcialmente', $visita->fresh()->resultado);
    }

    /**
     * Test 14: Penalties condonacion/forgiveness auto calculation works correctly.
     */
    public function test_14_penalties_forgiveness_calculation_works_correctly(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->call('openPagoModal')
            ->set('pago_monto_cobrado', 100000.00) // triggers reactive updatedPagoMontoCobrado
            ->assertSet('pago_punitorios_perdonados', 2000.00);
    }

    /**
     * Test 15: The condonacion does not retroactively modify the official value of cuotas.punitorios.
     */
    public function test_15_condonacion_does_not_modify_official_punitorios(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->call('openPagoModal')
            ->set('pago_monto_cobrado', 100000.00)
            ->set('pago_punitorios_perdonados', 2000.00)
            ->call('submitPago');

        // Check original punitorios on cuotas table is completely unmodified!
        $this->assertEquals(2000.00, floatval($this->cuota->fresh()->punitorios));
    }

    /**
     * Test 16: A completed visit cannot be registered again.
     */
    public function test_16_completed_visit_cannot_be_registered_again(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'realizada',
            'resultado' => 'cobrado',
            'monto_cobrado' => 100000.00,
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->call('selectVisita', $visita->id)
            ->call('openResultadoModal')
            ->assertSee('Esta visita ya fue realizada.');
    }

    /**
     * Test 17: Future visits appear correctly.
     */
    public function test_17_future_visits_appear_correctly(): void
    {
        VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Dir de la proxima semana',
            'fecha_programada' => Carbon::tomorrow()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->set('activeTab', 'futuras')
            ->assertSee('Dir de la proxima semana');
    }

    /**
     * Test 18: Historial shows only visits of the current cobrador.
     */
    public function test_18_historial_shows_only_visits_of_the_current_cobrador(): void
    {
        // Visita for cobrador1
        VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Casa Cobrador Uno',
            'fecha_programada' => Carbon::yesterday()->toDateString(),
            'estado' => 'realizada',
            'resultado' => 'no_estaba',
        ]);

        // Visita for cobrador2
        VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador2->id,
            'domicilio' => 'Casa Cobrador Dos',
            'fecha_programada' => Carbon::yesterday()->toDateString(),
            'estado' => 'realizada',
            'resultado' => 'cobrado',
        ]);

        $this->actingAs($this->cobrador1);

        Livewire::test(CobradorComponent::class)
            ->set('activeTab', 'historial')
            ->assertSee('Casa Cobrador Uno')
            ->assertDontSee('Casa Cobrador Dos');
    }

    /**
     * Test 19: Authorization checks prevent direct manipulation of other users' visits.
     */
    public function test_19_authorization_restrictions_work_on_requests(): void
    {
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador2->id, // belongs to cobrador2
            'domicilio' => 'Calle Secreta',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1); // acting as cobrador1

        // Attempting to select or update the visit of cobrador2 should abort 403
        Livewire::test(CobradorComponent::class)
            ->call('iniciarVisita', $visita->id)
            ->assertStatus(403);
    }

    /**
     * Test 20: Responsive and mobile layout checks.
     */
    public function test_20_responsive_elements_are_rendered(): void
    {
        VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador1->id,
            'domicilio' => 'Calle Falsa 123',
            'fecha_programada' => Carbon::today()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->cobrador1);

        // Assert that layout tab buttons and mobile cards exist
        Livewire::test(CobradorComponent::class)
            ->assertSee('📌 Hoy')
            ->assertSee('📅 Próximas')
            ->assertSee('📜 Historial')
            ->assertSee('Ficha Rápida');
    }
}

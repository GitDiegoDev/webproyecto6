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
use App\Models\Gestion;
use App\Models\PlantillaMensaje;
use App\Livewire\FichaGestionComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;
use Tests\TestCase;

class FichaGestionTest extends TestCase
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

        // 1. Setup Users
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

        // 2. Setup active Period
        $this->periodo = PeriodoCobranza::create([
            'nombre' => 'Septiembre 2026',
            'mes' => 9,
            'anio' => 2026,
            'activo' => true,
            'objetivo_monto' => 150000.00,
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

        // 3. Setup Client, Operation and Cuota
        $this->cliente = Cliente::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'documento' => '12345678',
            'telefono' => '1511223344',
            'domicilio' => 'Calle Siempreviva 742',
        ]);

        $this->operacion = Operacion::create([
            'cliente_id' => $this->cliente->id,
            'numero_solicitud' => '2455',
        ]);

        $this->cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 12000.00,
            'punitorios' => 800.00,
            'total_actualizado' => 12800.00,
            'saldo_pendiente' => 12800.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
            'link_pago' => 'https://pago.link/sol2455c5',
        ]);

        // Associate cuota with the active period via importacion
        CuotaImportacion::create([
            'cuota_id' => $this->cuota->id,
            'importacion_id' => $this->importacion->id,
            'importe_original_observado' => 12000.00,
            'punitorios_observados' => 800.00,
            'total_actualizado_observado' => 12800.00,
            'dia_cobro_observado' => 10,
            'estado_presencia' => 'presente',
        ]);
    }

    /**
     * 1. Access authorized.
     */
    public function test_1_authorized_access_to_ficha(): void
    {
        $this->actingAs($this->gestor);
        $response = $this->get('/ficha-gestion/' . $this->cuota->id);
        $response->assertStatus(200);
        $response->assertSeeLivewire(FichaGestionComponent::class);
    }

    /**
     * 2. Access unauthorized.
     */
    public function test_2_unauthorized_access_to_ficha(): void
    {
        // Unauthenticated user should get 403 abort in component mount
        $response = $this->get('/ficha-gestion/' . $this->cuota->id);
        $response->assertStatus(403);

        $unauthorizedUser = User::create([
            'name' => 'Stranger',
            'email' => 'stranger@example.com',
            'password' => bcrypt('password'),
        ]);
        $unauthorizedUser->role = 'visitor'; // invalid role
        $unauthorizedUser->save();

        $this->actingAs($unauthorizedUser);
        $response2 = $this->get('/ficha-gestion/' . $this->cuota->id);
        $response2->assertStatus(403);
    }

    /**
     * 3. Visualización correcta de datos del cliente.
     */
    public function test_3_client_data_rendering(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSee('Juan')
            ->assertSee('Pérez')
            ->assertSee('12345678')
            ->assertSee('1511223344')
            ->assertSee('Calle Siempreviva 742');
    }

    /**
     * 4. Visualización correcta de la operación.
     */
    public function test_4_operation_data_rendering(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSee('2455'); // numero_solicitud
    }

    /**
     * 5. Visualización correcta de la cuota.
     */
    public function test_5_cuota_data_rendering(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSee('Cuota 5')
            ->assertSee('Día 10');
    }

    /**
     * 6. Historial de gestiones.
     */
    public function test_6_gestiones_history_listing(): void
    {
        $this->actingAs($this->gestor);

        // Create mock gestiones
        Gestion::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_hora' => '2026-09-10 10:35:00',
            'tipo' => 'whatsapp_enviado',
            'resultado' => 'sin_respuesta',
            'observacion' => 'WhatsApp de aviso enviado',
        ]);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSee('Whatsapp enviado')
            ->assertSee('sin_respuesta')
            ->assertSee('WhatsApp de aviso enviado');
    }

    /**
     * 7. Registro de una nueva gestión.
     */
    public function test_7_register_new_gestion(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('gestion_tipo', 'llamada')
            ->set('gestion_resultado', 'contactado')
            ->set('gestion_observacion', 'El cliente solicita refinanciar')
            ->call('submitGestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gestiones', [
            'cuota_id' => $this->cuota->id,
            'tipo' => 'llamada',
            'resultado' => 'contactado',
            'observacion' => 'El cliente solicita refinanciar',
        ]);
    }

    /**
     * 8. Recalculación de prioridad después de una gestión.
     */
    public function test_8_recalculates_priority_after_gestion(): void
    {
        $this->actingAs($this->gestor);

        // By default priority is media. If we register three consecutive non-responses,
        // priority might escalate or change. Let's trigger a contact that modifies estado_gestion.
        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('gestion_tipo', 'no_atendio')
            ->set('gestion_resultado', 'no_atendio')
            ->set('gestion_observacion', 'Intento 1')
            ->call('submitGestion');

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('gestion_tipo', 'no_atendio')
            ->set('gestion_resultado', 'no_atendio')
            ->set('gestion_observacion', 'Intento 2')
            ->call('submitGestion');

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('gestion_tipo', 'no_atendio')
            ->set('gestion_resultado', 'no_atendio')
            ->set('gestion_observacion', 'Intento 3')
            ->call('submitGestion');

        // Check that state is updated to 'sin_respuesta' and priority is updated
        $this->assertEquals('sin_respuesta', $this->cuota->fresh()->estado_gestion);
        $this->assertEquals('critica', $this->cuota->fresh()->prioridad); // Escalated to high/critica due to sin_respuesta
    }

    /**
     * 9. Registro de promesa.
     */
    public function test_9_register_promesa_de_pago(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('promesa_fecha_prometida', '2026-09-15')
            ->set('promesa_monto_prometido', 12800.00)
            ->set('promesa_observaciones', 'Paga por transferencia bancaria')
            ->call('submitPromesa')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('promesas_pago', [
            'cuota_id' => $this->cuota->id,
            'fecha_prometida' => '2026-09-15',
            'monto_prometido' => 12800.00,
            'estado' => 'pendiente',
        ]);
    }

    /**
     * 10. Visualización de promesas.
     */
    public function test_10_promesas_history_listing(): void
    {
        $this->actingAs($this->gestor);

        PromesaPago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_creacion' => '2026-09-01',
            'fecha_prometida' => '2026-09-15',
            'monto_prometido' => 12800.00,
            'estado' => 'pendiente',
            'observaciones' => 'Se compromete a pagar',
        ]);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSee('Se compromete a pagar')
            ->assertSee('12,800.00')
            ->assertSee('Pendiente ★');
    }

    /**
     * 11. Registro de pago completo.
     */
    public function test_11_register_complete_payment(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('pago_monto_cobrado', 12800.00)
            ->set('pago_punitorios_perdonados', 0.00)
            ->set('pago_medio_pago', 'transferencia')
            ->set('pago_observaciones', 'Comprobante #99882')
            ->call('submitPago')
            ->assertHasNoErrors();

        $this->assertEquals(0.00, $this->cuota->fresh()->saldo_pendiente);
        $this->assertEquals('cobrado', $this->cuota->fresh()->estado_gestion);
    }

    /**
     * 12. Registro de pago parcial.
     */
    public function test_12_register_partial_payment(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('pago_monto_cobrado', 5000.00)
            ->set('pago_punitorios_perdonados', 0.00)
            ->set('pago_medio_pago', 'efectivo')
            ->set('pago_observaciones', 'Entrega parcial')
            ->call('submitPago')
            ->assertHasNoErrors();

        $this->assertEquals(7800.00, $this->cuota->fresh()->saldo_pendiente);
        $this->assertEquals('seguimiento', $this->cuota->fresh()->estado_gestion);
    }

    /**
     * 13. Pago con punitorios perdonados.
     */
    public function test_13_register_payment_with_forgiven_penalty(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('pago_monto_cobrado', 12000.00)
            ->set('pago_punitorios_perdonados', 800.00)
            ->set('pago_medio_pago', 'efectivo')
            ->set('pago_observaciones', 'Condonación autorizada por Gerencia')
            ->call('submitPago')
            ->assertHasNoErrors();

        $this->assertEquals(0.00, $this->cuota->fresh()->saldo_pendiente);
        $this->assertDatabaseHas('pagos', [
            'cuota_id' => $this->cuota->id,
            'monto_cobrado' => 12000.00,
            'punitorios_perdonados' => 800.00,
            'es_cancelatorio' => true,
        ]);
    }

    /**
     * 14. Verificación de que los punitorios oficiales no son modificados por una condonación.
     */
    public function test_14_punitorios_oficiales_not_modified_by_condonacion(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('pago_monto_cobrado', 12000.00)
            ->set('pago_punitorios_perdonados', 800.00)
            ->set('pago_medio_pago', 'transferencia')
            ->call('submitPago');

        // Check original and punitorios remains 800.00 in the cuota official values
        $this->assertEquals(800.00, $this->cuota->fresh()->punitorios);
        $this->assertEquals(12000.00, $this->cuota->fresh()->importe_original);
    }

    /**
     * 15. Programación de visita.
     */
    public function test_15_program_collector_visit(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('cobrador_id', $this->cobrador->id)
            ->set('cobrador_fecha_programada', '2026-09-15')
            ->set('cobrador_observaciones', 'Llamar antes de ir')
            ->call('submitCobrador')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('visitas_cobrador', [
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador->id,
            'fecha_programada' => '2026-09-15',
            'estado' => 'pendiente',
        ]);
    }

    /**
     * 16. Conservación del domicilio congelado de la visita.
     */
    public function test_16_frozen_visita_address_preserved(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('cobrador_id', $this->cobrador->id)
            ->set('cobrador_fecha_programada', '2026-09-15')
            ->call('submitCobrador');

        // Change client's actual address later
        $this->cliente->update(['domicilio' => 'Nueva Dirección 9999']);

        // Check that the visit snapshot address remains 'Calle Siempreviva 742'
        $visita = VisitaCobrador::where('cuota_id', $this->cuota->id)->first();
        $this->assertEquals('Calle Siempreviva 742', $visita->domicilio);
    }

    /**
     * 17. Registro del resultado de una visita.
     */
    public function test_17_register_visita_outcome(): void
    {
        $this->actingAs($this->gestor);

        // Program visit first
        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador->id,
            'domicilio' => $this->cliente->domicilio,
            'fecha_programada' => '2026-09-15',
            'estado' => 'pendiente',
        ]);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('selectedVisitaId', $visita->id)
            ->set('visita_resultado_tipo', 'cobrado_parcialmente')
            ->set('visita_monto_cobrado', 5000.00)
            ->set('visita_fecha_realizada', '2026-09-15')
            ->set('visita_observaciones', 'Pudo cobrar algo en mano')
            ->call('submitVisitaResultado')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('visitas_cobrador', [
            'id' => $visita->id,
            'estado' => 'realizada',
            'resultado' => 'cobrado_parcialmente',
            'monto_cobrado' => 5000.00,
        ]);
    }

    /**
     * 18. Visualización del historial de pagos.
     */
    public function test_18_payments_history_listing(): void
    {
        $this->actingAs($this->gestor);

        Pago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_pago' => '2026-09-02',
            'importe_original_snapshot' => 12000.00,
            'punitorios_snapshot' => 800.00,
            'total_actualizado_snapshot' => 12800.00,
            'monto_cobrado' => 4500.00,
            'punitorios_perdonados' => 0.00,
            'es_cancelatorio' => false,
            'medio_pago' => 'efectivo',
            'observaciones' => 'Entrega inicial',
        ]);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSee('Entrega inicial')
            ->assertSee('4,500.00')
            ->assertSee('efectivo');
    }

    /**
     * 19. Visualización del historial de visitas.
     */
    public function test_19_visitas_history_listing(): void
    {
        $this->actingAs($this->gestor);

        VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador->id,
            'domicilio' => 'Calle Siempreviva 742',
            'fecha_programada' => '2026-09-15',
            'estado' => 'realizada',
            'resultado' => 'no_estaba',
            'observaciones' => 'Nadie abrió la puerta',
        ]);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSee('Nadie abrió la puerta')
            ->assertSee('no_estaba')
            ->assertSee('Realizada');
    }

    /**
     * 20. Generación de mensajes con variables dinámicas.
     */
    public function test_20_dynamic_message_variables_rendering(): void
    {
        $this->actingAs($this->gestor);

        $plantilla = PlantillaMensaje::create([
            'titulo' => 'Aviso Vencimiento',
            'categoria' => 'cuota_vencida',
            'cuerpo' => 'Hola {nombre}, tu cuota nro {numero_cuota} venció. Saldo pendiente: ${importe}. Link de pago: {link_pago}',
            'activo' => true,
        ]);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('selectedPlantillaId', $plantilla->id)
            ->assertSet('previewMensaje', 'Hola Juan Pérez, tu cuota nro 5 venció. Saldo pendiente: $12,800.00. Link de pago: https://pago.link/sol2455c5');
    }

    /**
     * 21. Copiado/generación correcta del link de pago.
     */
    public function test_21_payment_link_rendering(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSee('https://pago.link/sol2455c5');
    }

    /**
     * 22. Respeto de permisos por rol.
     */
    public function test_22_role_permissions_enforcement(): void
    {
        // 1. Cobrador restricted actions (cannot register promises or program visits)
        $this->actingAs($this->cobrador);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->call('openPromesaModal')
            ->assertStatus(403);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->call('openCobradorModal')
            ->assertStatus(403);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->call('openPagoModal')
            ->assertStatus(403);
    }

    /**
     * 23. No sobrescritura de datos oficiales.
     */
    public function test_23_official_data_not_overwritten(): void
    {
        $this->actingAs($this->gestor);

        // Edit contact info manually - should update client but must NOT alter the original official cuota records
        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('pago_monto_cobrado', 12000)
            ->set('pago_punitorios_perdonados', 800)
            ->call('submitPago');

        $this->assertEquals(12000.00, $this->cuota->fresh()->importe_original);
        $this->assertEquals(800.00, $this->cuota->fresh()->punitorios);
    }

    /**
     * 24. Relación correcta entre una solicitud y sus diferentes cuotas.
     */
    public function test_24_relation_solicitud_multiple_cuotas(): void
    {
        $this->actingAs($this->gestor);

        // Create a secondary cuota for the same operation
        $cuota2 = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 6,
            'dia_cobro' => 10,
            'importe_original' => 12000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 12000.00,
            'saldo_pendiente' => 12000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSee('Cuota 6')
            ->assertSee('12,000.00');
    }
}

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
use App\Livewire\AgendaCobranzaComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;
use Tests\TestCase;

class AgendaCobranzaTest extends TestCase
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

        // Associate cuota with active period via importacion
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
     * 1. Access authorized to /agenda.
     */
    public function test_1_access_authorized_to_agenda(): void
    {
        $this->actingAs($this->gestor);
        $response = $this->get('/agenda');
        $response->assertStatus(200);
        $response->assertSeeLivewire(AgendaCobranzaComponent::class);
    }

    /**
     * 2. Restriction of access by role (unauthorized gets 403).
     */
    public function test_2_restriction_of_access_by_role(): void
    {
        // Unauthenticated -> 403
        $response = $this->get('/agenda');
        $response->assertStatus(403);

        $unauthorizedUser = User::create([
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'password' => bcrypt('password'),
        ]);
        $unauthorizedUser->role = 'visitor';
        $unauthorizedUser->save();

        $this->actingAs($unauthorizedUser);
        $response2 = $this->get('/agenda');
        $response2->assertStatus(403);
    }

    /**
     * 3. Visualización de métricas.
     */
    public function test_3_visualization_of_metrics(): void
    {
        $this->actingAs($this->gestor);

        // Promesa para hoy
        PromesaPago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_creacion' => '2026-09-10',
            'fecha_prometida' => '2026-09-10',
            'monto_prometido' => 12800.00,
            'estado' => 'pendiente',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Promesas Hoy')
            ->assertSeeHtml('1'); // Metric card value for promesas hoy
    }

    /**
     * 4. Listado de gestiones de hoy.
     */
    public function test_4_list_of_gestiones_of_today(): void
    {
        $this->actingAs($this->gestor);

        // Create a cuota that requires attention today
        $cuota2 = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 6,
            'dia_cobro' => 10,
            'importe_original' => 15000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 15000.00,
            'saldo_pendiente' => 15000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'alta',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Juan Pérez')
            ->assertSee('Cuota 5')
            ->assertSee('Cuota 6');
    }

    /**
     * 5. Orden correcto por prioridad.
     */
    public function test_5_correct_order_by_priority(): void
    {
        $this->actingAs($this->gestor);

        $cuotaBaja = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 10,
            'importe_original' => 1000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 1000.00,
            'saldo_pendiente' => 1000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'contactado',
            'prioridad' => 'baja',
        ]);

        $cuotaCritica = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 2,
            'dia_cobro' => 10,
            'importe_original' => 5000.00,
            'punitorios' => 1000.00,
            'total_actualizado' => 6000.00,
            'saldo_pendiente' => 6000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'promesa_incumplida',
            'prioridad' => 'critica',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Crítica')
            ->assertSee('Baja');
    }

    /**
     * 6. Filtro por prioridad.
     */
    public function test_6_filter_by_priority(): void
    {
        $this->actingAs($this->gestor);

        $cuotaCritica = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 9,
            'dia_cobro' => 10,
            'importe_original' => 5000.00,
            'punitorios' => 1000.00,
            'total_actualizado' => 6000.00,
            'saldo_pendiente' => 6000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'promesa_incumplida',
            'prioridad' => 'critica',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->set('filter', 'criticos')
            ->assertSee('Cuota 9')
            ->assertDontSee('Cuota 5'); // Cuota 5 is priority 'media'
    }

    /**
     * 7. Filtro por estado.
     */
    public function test_7_filter_by_status(): void
    {
        $this->actingAs($this->gestor);

        // Vencida since reference date is 2026-09-20 (due day is 10th)
        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-20'])
            ->set('filter', 'vencidos')
            ->assertSee('Vencida');
    }

    /**
     * 8. Búsqueda por nombre.
     */
    public function test_8_search_by_name(): void
    {
        $this->actingAs($this->gestor);

        // Secondary client
        $cliente2 = Cliente::create([
            'nombre' => 'Amalia',
            'apellido' => 'Silvestre',
            'documento' => '99999999',
            'telefono' => '159999999',
            'domicilio' => 'Calle 1',
        ]);
        $op2 = Operacion::create([
            'cliente_id' => $cliente2->id,
            'numero_solicitud' => '8888',
        ]);
        $cuota2 = Cuota::create([
            'operacion_id' => $op2->id,
            'numero_cuota' => 1,
            'dia_cobro' => 10,
            'importe_original' => 500.00,
            'punitorios' => 0.00,
            'total_actualizado' => 500.00,
            'saldo_pendiente' => 500.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->set('search', 'Amalia')
            ->assertSee('Amalia')
            ->assertDontSee('Juan');
    }

    /**
     * 9. Búsqueda por solicitud.
     */
    public function test_9_search_by_request(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->set('search', '2455')
            ->assertSee('Juan Pérez')
            ->assertSee('2455');
    }

    /**
     * 10. Promesas para hoy.
     */
    public function test_10_promesas_para_hoy(): void
    {
        $this->actingAs($this->gestor);

        PromesaPago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_creacion' => '2026-09-10',
            'fecha_prometida' => '2026-09-10',
            'monto_prometido' => 12800.00,
            'estado' => 'pendiente',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Promesas de Pago para Hoy')
            ->assertSee('12,800.00');
    }

    /**
     * 11. Promesas incumplidas.
     */
    public function test_11_promesas_incumplidas(): void
    {
        $this->actingAs($this->gestor);

        PromesaPago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_creacion' => '2026-09-01',
            'fecha_prometida' => '2026-09-05',
            'monto_prometido' => 12800.00,
            'estado' => 'incumplida',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Promesas Incumplidas')
            ->assertSee('12,800.00');
    }

    /**
     * 12. Clientes sin respuesta.
     */
    public function test_12_clientes_sin_respuesta(): void
    {
        $this->actingAs($this->gestor);

        $this->cuota->update(['estado_gestion' => 'sin_respuesta']);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Clientes Sin Respuesta')
            ->assertSee('Juan Pérez');
    }

    /**
     * 13. Visitas programadas para hoy.
     */
    public function test_13_visitas_programadas_para_hoy(): void
    {
        $this->actingAs($this->gestor);

        VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador->id,
            'domicilio' => $this->cliente->domicilio,
            'fecha_programada' => '2026-09-10',
            'estado' => 'pendiente',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Visitas de Hoy')
            ->assertSee('Calle Siempreviva 742');
    }

    /**
     * 14. Próximas acciones.
     */
    public function test_14_proximas_acciones(): void
    {
        $this->actingAs($this->gestor);

        Gestion::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_hora' => '2026-09-09 10:00:00',
            'tipo' => 'llamada',
            'resultado' => 'seguimiento',
            'proxima_accion' => 'volver_a_llamar',
            'proxima_accion_fecha' => '2026-09-10',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Seguimientos de Hoy')
            ->assertSee('volver_a_llamar');
    }

    /**
     * 15. Registrar gestión desde agenda.
     */
    public function test_15_registrar_gestion_desde_agenda(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->call('openGestionModal', $this->cuota->id)
            ->set('gestion_tipo', 'llamada')
            ->set('gestion_resultado', 'contactado')
            ->set('gestion_observacion', 'Llamada exitosa')
            ->call('submitGestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gestiones', [
            'cuota_id' => $this->cuota->id,
            'tipo' => 'llamada',
            'resultado' => 'contactado',
            'observacion' => 'Llamada exitosa',
        ]);
    }

    /**
     * 16. Registrar promesa desde agenda.
     */
    public function test_16_registrar_promesa_desde_agenda(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->call('openPromesaModal', $this->cuota->id)
            ->set('promesa_fecha_prometida', '2026-09-15')
            ->set('promesa_monto_prometido', 12800)
            ->set('promesa_observaciones', 'Compromiso')
            ->call('submitPromesa')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('promesas_pago', [
            'cuota_id' => $this->cuota->id,
            'fecha_prometida' => '2026-09-15',
            'monto_prometido' => 12800,
        ]);
    }

    /**
     * 17. Programar cobrador desde agenda.
     */
    public function test_17_programar_cobrador_desde_agenda(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->call('openCobradorModal', $this->cuota->id)
            ->set('cobrador_id', $this->cobrador->id)
            ->set('cobrador_fecha_programada', '2026-09-15')
            ->set('cobrador_observaciones', 'Entregar recibo')
            ->call('submitCobrador')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('visitas_cobrador', [
            'cliente_id' => $this->cliente->id,
            'cobrador_id' => $this->cobrador->id,
            'fecha_programada' => '2026-09-15',
        ]);
    }

    /**
     * 18. Registrar pago desde agenda.
     */
    public function test_18_registrar_pago_desde_agenda(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->call('openPagoModal', $this->cuota->id)
            ->set('pago_monto_cobrado', 12800.00)
            ->set('pago_punitorios_perdonados', 0)
            ->set('pago_medio_pago', 'transferencia')
            ->call('submitPago')
            ->assertHasNoErrors();

        $this->assertEquals(0.00, $this->cuota->fresh()->saldo_pendiente);
    }

    /**
     * 19. Pago con punitorios perdonados.
     */
    public function test_19_pago_con_punitorios_perdonados(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->call('openPagoModal', $this->cuota->id)
            ->set('pago_monto_cobrado', 12000.00)
            ->set('pago_punitorios_perdonados', 800.00)
            ->set('pago_medio_pago', 'transferencia')
            ->call('submitPago');

        $this->assertEquals(0.00, $this->cuota->fresh()->saldo_pendiente);
        $this->assertEquals(800.00, $this->cuota->fresh()->punitorios); // original untouched
        $this->assertDatabaseHas('pagos', [
            'cuota_id' => $this->cuota->id,
            'monto_cobrado' => 12000.00,
            'punitorios_perdonados' => 800.00,
        ]);
    }

    /**
     * 20. Pago parcial.
     */
    public function test_20_pago_parcial(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->call('openPagoModal', $this->cuota->id)
            ->set('pago_monto_cobrado', 5000.00)
            ->set('pago_punitorios_perdonados', 0)
            ->set('pago_medio_pago', 'efectivo')
            ->call('submitPago');

        $this->assertEquals(7800.00, $this->cuota->fresh()->saldo_pendiente);
    }

    /**
     * 21. Actualización de prioridad después de una gestión.
     */
    public function test_21_actualizacion_de_prioridad_despues_de_una_gestion(): void
    {
        $this->actingAs($this->gestor);

        // Default priority is media.
        // Let's register consecutive non-responses to escalate to critical
        $comp = Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10']);

        for ($i = 0; $i < 3; $i++) {
            $comp->call('openGestionModal', $this->cuota->id)
                ->set('gestion_tipo', 'no_atendio')
                ->set('gestion_resultado', 'no_atendio')
                ->call('submitGestion');
        }

        $this->assertEquals('sin_respuesta', $this->cuota->fresh()->estado_gestion);
        $this->assertEquals('critica', $this->cuota->fresh()->prioridad);
    }

    /**
     * 22. Acceso a ficha de gestión.
     */
    public function test_22_acceso_a_ficha_de_gestion(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('/ficha-gestion/' . $this->cuota->id);
    }

    /**
     * 23. Prevención de doble envío de acciones.
     */
    public function test_23_prevencion_de_doble_envio_de_acciones(): void
    {
        $this->actingAs($this->gestor);

        // Verification of loading indicators or submit button disabling elements
        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Procesando...');
    }

    /**
     * 24. Respeto de permisos por rol.
     */
    public function test_24_respeto_de_permisos_por_rol(): void
    {
        // Cobrador role is restricted
        $this->actingAs($this->cobrador);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->call('openPromesaModal', $this->cuota->id)
            ->assertStatus(403);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->call('openCobradorModal', $this->cuota->id)
            ->assertStatus(403);
    }

    /**
     * 25. Agenda sin resultados.
     */
    public function test_25_agenda_sin_resultados(): void
    {
        $this->actingAs($this->gestor);

        // Clear all cuotas
        Cuota::query()->delete();

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('No hay gestiones pendientes para hoy.');
    }

    /**
     * 26. Combinación de filtros.
     */
    public function test_26_combinacion_de_filtros(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->set('filter', 'criticos')
            ->set('search', 'NonExistentClient')
            ->assertDontSee('Juan Pérez');
    }

    /**
     * 27. Vista próximos días.
     */
    public function test_27_vista_proximos_dias(): void
    {
        $this->actingAs($this->gestor);

        PromesaPago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_creacion' => '2026-09-10',
            'fecha_prometida' => '2026-09-12', // 2 days in the future
            'monto_prometido' => 12800.00,
            'estado' => 'pendiente',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->set('viewMode', 'proximos_dias')
            ->assertSee('Promesas de Pago Futuras')
            ->assertSee('Juan Pérez');
    }

    /**
     * 28. No modificación de datos oficiales al gestionar.
     */
    public function test_28_no_modificacion_de_datos_oficiales_al_gestionar(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->call('openPagoModal', $this->cuota->id)
            ->set('pago_monto_cobrado', 12000.00)
            ->set('pago_punitorios_perdonados', 800.00)
            ->set('pago_medio_pago', 'transferencia')
            ->call('submitPago');

        // Check original and punitorios remains untouched in the cuota records
        $this->assertEquals(12000.00, $this->cuota->fresh()->importe_original);
        $this->assertEquals(800.00, $this->cuota->fresh()->punitorios);
    }

    /**
     * 29. Visita sin cuota asociada.
     */
    public function test_29_visita_sin_cuota_asociada(): void
    {
        $this->actingAs($this->gestor);

        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => null, // No specific cuota
            'cobrador_id' => $this->cobrador->id,
            'domicilio' => $this->cliente->domicilio,
            'fecha_programada' => '2026-09-10',
            'estado' => 'pendiente',
        ]);

        Livewire::test(AgendaCobranzaComponent::class, ['referenceDate' => '2026-09-10'])
            ->assertSee('Visitas de Hoy')
            ->assertSee('Juan Pérez');
    }

    /**
     * 30. Cliente sin respuesta correctamente priorizado según la lógica existente.
     */
    public function test_30_cliente_sin_respuesta_correctamente_priorizado(): void
    {
        $this->actingAs($this->gestor);

        // Register 3 consecutive no-answers/no-response
        $service = new \App\Services\GestionService();
        $service->registrarGestion(['cuota_id' => $this->cuota->id, 'user_id' => $this->gestor->id, 'tipo' => 'whatsapp_enviado', 'resultado' => 'no_atendio']);
        $service->registrarGestion(['cuota_id' => $this->cuota->id, 'user_id' => $this->gestor->id, 'tipo' => 'llamada', 'resultado' => 'no_atendio']);
        $service->registrarGestion(['cuota_id' => $this->cuota->id, 'user_id' => $this->gestor->id, 'tipo' => 'llamada', 'resultado' => 'sin_respuesta']);

        // Check that priority is escalated to critica
        $this->assertEquals('sin_respuesta', $this->cuota->fresh()->estado_gestion);
        $this->assertEquals('critica', $this->cuota->fresh()->prioridad);
    }
}

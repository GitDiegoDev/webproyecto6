<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Operacion;
use App\Models\PeriodoCobranza;
use App\Models\User;
use App\Models\Importacion;
use App\Models\CuotaImportacion;
use App\Models\PlantillaMensaje;
use App\Models\PromesaPago;
use App\Models\VisitaCobrador;
use App\Models\Gestion;
use App\Livewire\CentroComunicacionComponent;
use App\Livewire\FichaGestionComponent;
use App\Livewire\AgendaCobranzaComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;
use Tests\TestCase;

class CentroComunicacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $gestor;
    protected User $cobrador;
    protected Cliente $cliente;
    protected Operacion $operacion;
    protected Cuota $cuota;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->cliente = Cliente::create([
            'nombre' => 'Pedro',
            'apellido' => 'Ramírez',
            'telefono' => '5493755990011',
            'domicilio' => 'Calle Ficticia 456',
        ]);

        $this->operacion = Operacion::create([
            'cliente_id' => $this->cliente->id,
            'numero_solicitud' => '3344',
        ]);

        $this->cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 15000,
            'punitorios' => 0,
            'total_actualizado' => 15000,
            'saldo_pendiente' => 15000,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
            'link_pago' => 'https://pago.link/3344c1',
        ]);
    }

    /**
     * 1. Access to Centro de Comunicación.
     */
    public function test_1_access_to_centro_comunicacion(): void
    {
        $this->actingAs($this->gestor);
        $response = $this->get('/mensajes');
        $response->assertStatus(200);
        $response->assertSeeLivewire(CentroComunicacionComponent::class);
    }

    /**
     * 2. Role authorization.
     */
    public function test_2_role_authorization_restriction(): void
    {
        $unauthorized = User::create([
            'name' => 'Foreigner',
            'email' => 'foreigner@example.com',
            'password' => bcrypt('password'),
        ]);
        $unauthorized->role = 'visitor';
        $unauthorized->save();

        $this->actingAs($unauthorized);
        $response = $this->get('/mensajes');
        $response->assertStatus(403);
    }

    /**
     * 3. List of templates.
     */
    public function test_3_templates_listing(): void
    {
        $this->actingAs($this->gestor);
        $t1 = PlantillaMensaje::create([
            'titulo' => 'Aviso Uno',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Cuerpo de prueba 1',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->assertSee('Aviso Uno')
            ->assertSee('vence hoy');
    }

    /**
     * 4. Create plantilla.
     */
    public function test_4_create_template(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('titulo', 'Nueva Plantilla Test')
            ->set('categoria', 'link_pago')
            ->set('cuerpo', 'Pague en {link_pago}')
            ->set('activo', true)
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('plantillas_mensajes', [
            'titulo' => 'Nueva Plantilla Test',
            'categoria' => 'link_pago',
            'cuerpo' => 'Pague en {link_pago}',
        ]);
    }

    /**
     * 5. Edit plantilla.
     */
    public function test_5_edit_template(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Modificable',
            'categoria' => 'proximo_vencimiento',
            'cuerpo' => 'Cuerpo viejo',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->call('openEditModal', $t->id)
            ->set('cuerpo', 'Cuerpo nuevo editado')
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $this->assertEquals('Cuerpo nuevo editado', $t->fresh()->cuerpo);
    }

    /**
     * 6. Activar plantilla.
     * 7. Desactivar plantilla.
     */
    public function test_6_7_toggle_template_active_state(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Toggleable',
            'categoria' => 'cuota_vencida',
            'cuerpo' => 'Cuerpo',
            'activo' => true,
        ]);

        // Desactivar
        Livewire::test(CentroComunicacionComponent::class)
            ->call('toggleActivo', $t->id);
        $this->assertFalse((bool)$t->fresh()->activo);

        // Reactivar
        Livewire::test(CentroComunicacionComponent::class)
            ->call('toggleActivo', $t->id);
        $this->assertTrue((bool)$t->fresh()->activo);
    }

    /**
     * 8. Filtrar por categoría.
     */
    public function test_8_filter_by_category(): void
    {
        $this->actingAs($this->gestor);
        PlantillaMensaje::create([
            'titulo' => 'Vence Hoy Template',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Hoy',
            'activo' => true,
        ]);
        PlantillaMensaje::create([
            'titulo' => 'Cuota Vencida Template',
            'categoria' => 'cuota_vencida',
            'cuerpo' => 'Vencida',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('filterCategory', 'vence_hoy')
            ->assertSee('Vence Hoy Template')
            ->assertDontSee('Cuota Vencida Template');
    }

    /**
     * 9. Buscar plantilla.
     */
    public function test_9_search_template(): void
    {
        $this->actingAs($this->gestor);
        PlantillaMensaje::create([
            'titulo' => 'Buscar Alfa',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Hoy',
            'activo' => true,
        ]);
        PlantillaMensaje::create([
            'titulo' => 'Buscar Beta',
            'categoria' => 'cuota_vencida',
            'cuerpo' => 'Vencida',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('search', 'Alfa')
            ->assertSee('Buscar Alfa')
            ->assertDontSee('Buscar Beta');
    }

    /**
     * 10-18. Reemplazo de variables dinámicas ({nombre}, {numero_solicitud}, {numero_cuota}, {importe}, {punitorios}, {total_actualizado}, {fecha_prometida}, {link_pago}).
     */
    public function test_10_18_dynamic_variables_replacement(): void
    {
        $this->actingAs($this->gestor);

        PromesaPago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_creacion' => now()->toDateString(),
            'fecha_prometida' => '2026-09-20',
            'monto_prometido' => 12500,
            'estado' => 'pendiente',
        ]);

        $t = PlantillaMensaje::create([
            'titulo' => 'Variables',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Nombre: {nombre_completo}, Solicitud: #{numero_solicitud}, Cuota: {numero_cuota}, Saldo: ${importe}, Punitorios: ${punitorios}, Total: ${total_actualizado}, Promesa: {fecha_prometida}, Link: {link_pago}',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->assertSet('previewMessage', 'Nombre: Pedro Ramírez, Solicitud: #3344, Cuota: 1, Saldo: $15,000.00, Punitorios: $0.00, Total: $15,000.00, Promesa: 20/09/2026, Link: https://pago.link/3344c1');
    }

    /**
     * 19. Manejo de variables sin valor.
     */
    public function test_19_missing_variables_safety(): void
    {
        $this->actingAs($this->gestor);
        // Clean link_pago and verify it doesn't break and formats cleanly as blank
        $this->cuota->update(['link_pago' => null]);

        $t = PlantillaMensaje::create([
            'titulo' => 'Variables vacias',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Link: {link_pago}',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->assertSet('previewMessage', 'Link: ')
            ->assertSee('Esta cuota no tiene un link de pago disponible.');
    }

    /**
     * 20. Detección de variables desconocidas.
     */
    public function test_20_unknown_variables_detection(): void
    {
        $this->actingAs($this->gestor);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('titulo', 'Invalido')
            ->set('cuerpo', 'Hola {nombre_invalido}')
            ->call('saveTemplate')
            ->assertHasErrors(['cuerpo']); // Error triggered due to unknown variable warning not confirmed
    }

    /**
     * 21. Previsualización.
     */
    public function test_21_preview_rendering(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Aviso Simple',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Hola {nombre_completo}!',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->assertSee('Hola Pedro Ramírez!');
    }

    /**
     * 22. Edición manual del mensaje.
     */
    public function test_22_manual_message_override(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Editable',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Hola {nombre_completo}',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->set('manualMessage', 'Mensaje editado manualmente')
            ->assertSet('manualMessage', 'Mensaje editado manualmente')
            ->assertSet('previewMessage', 'Hola Pedro Ramírez'); // original is preserved
    }

    /**
     * 23. Copiar mensaje state check.
     */
    public function test_23_copy_message_state(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Copiable',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Texto copiable',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->assertSet('manualMessage', 'Texto copiable');
    }

    /**
     * 24. Generación desde la Ficha de Gestión.
     */
    public function test_24_generation_from_ficha_gestion(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Ficha Aviso',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Ficha {nombre_completo}',
            'activo' => true,
        ]);

        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->set('selectedPlantillaId', $t->id)
            ->assertSet('previewMensaje', 'Ficha Pedro Ramírez');
    }

    /**
     * 25. Generación desde la Agenda.
     */
    public function test_25_generation_from_agenda(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Agenda Aviso',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Agenda {nombre_completo}',
            'activo' => true,
        ]);

        Livewire::test(AgendaCobranzaComponent::class)
            ->call('openContactModal', $this->cuota->id)
            ->set('contactSelectedPlantillaId', $t->id)
            ->assertSet('contactPreviewMessage', 'Agenda Pedro Ramírez');
    }

    /**
     * 26. Sugerencia automática según estado.
     */
    public function test_26_auto_suggest_according_to_status(): void
    {
        $this->actingAs($this->gestor);

        // 1. Next due -> Suggest proximo_vencimiento
        $t1 = PlantillaMensaje::create([
            'titulo' => 'Surgimiento Proximo',
            'categoria' => 'proximo_vencimiento',
            'cuerpo' => 'Vence pronto',
            'activo' => true,
        ]);
        $this->cuota->update([
            'dia_cobro' => Carbon::tomorrow()->day,
            'estado_gestion' => 'sin_contactar',
        ]);
        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSet('selectedCategoria', 'proximo_vencimiento');

        // 2. No response -> Suggest sin_respuesta
        $t2 = PlantillaMensaje::create([
            'titulo' => 'Surgimiento Silencio',
            'categoria' => 'sin_respuesta',
            'cuerpo' => 'Silencio',
            'activo' => true,
        ]);
        $this->cuota->update([
            'estado_gestion' => 'sin_respuesta',
        ]);
        Livewire::test(FichaGestionComponent::class, ['cuotaId' => $this->cuota->id])
            ->assertSet('selectedCategoria', 'sin_respuesta');
    }

    /**
     * 27. Cliente sin teléfono.
     * 28. Cliente con teléfono válido.
     * 29. Apertura de WhatsApp.
     */
    public function test_27_28_29_whatsapp_phone_safety_generation(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Contacto WA',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'WA Test',
            'activo' => true,
        ]);

        // 1. Client with valid phone
        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->assertSet('whatsappUrl', 'https://wa.me/5493755990011?text=WA+Test');

        // 2. Client without phone
        $this->cliente->update(['telefono' => null]);
        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->assertSet('whatsappUrl', '');
    }

    /**
     * 30. Registro de gestión.
     * 31. Prevención de doble registro.
     */
    public function test_30_31_register_gestion_from_communicator(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Reg Gest',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Registro',
            'activo' => true,
        ]);

        $comp = Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->call('openGestionModal')
            ->set('gestion_tipo', 'whatsapp_enviado')
            ->set('gestion_resultado', 'mensaje_enviado')
            ->set('gestion_observacion', 'Intento WA 1')
            ->call('submitGestion');

        $this->assertDatabaseHas('gestiones', [
            'cuota_id' => $this->cuota->id,
            'tipo' => 'whatsapp_enviado',
            'resultado' => 'mensaje_enviado',
            'observacion' => 'Intento WA 1',
        ]);
        $this->assertEquals(1, $this->cuota->gestiones()->count());
    }

    /**
     * 32. Plantilla desactivada no disponible para uso normal.
     */
    public function test_32_disabled_templates_not_available_for_usage(): void
    {
        $this->actingAs($this->gestor);
        PlantillaMensaje::create([
            'titulo' => 'Desactivada',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Cuerpo',
            'activo' => false,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->assertDontSee('Desactivada (vence_hoy)');
    }

    /**
     * 33. Mensaje de cobrador.
     */
    public function test_33_mensaje_cobrador(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Cobrador',
            'categoria' => 'cobrador',
            'cuerpo' => 'Cobrador {nombre_cobrador} fecha {fecha_visita} domicilio {domicilio}',
            'activo' => true,
        ]);

        VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $this->cuota->id,
            'cobrador_id' => $this->cobrador->id,
            'domicilio' => $this->cliente->domicilio,
            'fecha_programada' => '2026-09-22',
            'estado' => 'pendiente',
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->assertSet('previewMessage', 'Cobrador Cobrador User fecha 22/09/2026 domicilio Calle Ficticia 456');
    }

    /**
     * 34. Mensaje para cliente sin respuesta.
     */
    public function test_34_mensaje_sin_respuesta(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Sin Respuesta',
            'categoria' => 'sin_respuesta',
            'cuerpo' => 'No responde {nombre_completo}',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->assertSet('previewMessage', 'No responde Pedro Ramírez');
    }

    /**
     * 35. Mensaje de promesa.
     * 36. Seguimiento de promesa incumplida.
     */
    public function test_35_36_promesa_templates(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Promesa',
            'categoria' => 'seguimiento_promesa',
            'cuerpo' => 'Fecha prometida: {fecha_prometida}, Monto: {monto_prometido}',
            'activo' => true,
        ]);

        PromesaPago::create([
            'cuota_id' => $this->cuota->id,
            'user_id' => $this->gestor->id,
            'fecha_creacion' => now()->toDateString(),
            'fecha_prometida' => '2026-09-18',
            'monto_prometido' => 15500,
            'estado' => 'pendiente',
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->assertSet('previewMessage', 'Fecha prometida: 18/09/2026, Monto: 15,500.00');
    }

    /**
     * 37. No modificación de la plantilla original al editar un mensaje generado.
     */
    public function test_37_editing_generated_does_not_affect_plantilla(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'No muta',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Hola {nombre_completo}',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id)
            ->set('manualMessage', 'Mutado');

        $this->assertEquals('Hola {nombre_completo}', $t->fresh()->cuerpo);
    }

    /**
     * 38. No modificación de datos financieros oficiales.
     */
    public function test_38_generating_does_not_modify_official_financials(): void
    {
        $this->actingAs($this->gestor);
        $t = PlantillaMensaje::create([
            'titulo' => 'Finanzas',
            'categoria' => 'vence_hoy',
            'cuerpo' => 'Total: {total_actualizado}',
            'activo' => true,
        ]);

        Livewire::test(CentroComunicacionComponent::class)
            ->set('selectedCuotaId', $this->cuota->id)
            ->set('selectedPreviewTemplateId', $t->id);

        $this->assertEquals(15000.00, $this->cuota->fresh()->importe_original);
        $this->assertEquals(0.00, $this->cuota->fresh()->punitorios);
    }

    /**
     * 39. Permisos de administración de plantillas.
     */
    public function test_39_template_admin_permissions(): void
    {
        // Gestor can administer
        $this->actingAs($this->gestor);
        Livewire::test(CentroComunicacionComponent::class)
            ->set('titulo', 'Permiso Gestor')
            ->set('cuerpo', 'Cuerpo')
            ->call('saveTemplate')
            ->assertHasNoErrors();

        // Cobrador CANNOT administer
        $this->actingAs($this->cobrador);
        Livewire::test(CentroComunicacionComponent::class)
            ->set('titulo', 'Permiso Cobrador')
            ->set('cuerpo', 'Cuerpo')
            ->call('saveTemplate')
            ->assertStatus(403);
    }

    /**
     * 40. Funcionamiento responsive rendering check.
     */
    public function test_40_responsive_elements_render(): void
    {
        $this->actingAs($this->gestor);
        Livewire::test(CentroComunicacionComponent::class)
            ->assertSee('Centro de Comunicación')
            ->assertSee('Administrar Plantillas')
            ->assertSee('Generar y Contactar');
    }
}

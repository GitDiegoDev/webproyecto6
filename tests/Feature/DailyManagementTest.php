<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Gestion;
use App\Models\Operacion;
use App\Models\Pago;
use App\Models\PromesaPago;
use App\Models\User;
use App\Models\VisitaCobrador;
use App\Services\AgendaCobranzaService;
use App\Services\CuotaPriorityService;
use App\Services\CuotaStatusService;
use App\Services\GestionService;
use App\Services\PagoService;
use App\Services\PromesaPagoService;
use App\Services\VisitaCobradorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class DailyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cliente $cliente;
    protected Operacion $operacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'John Gestor',
            'email' => 'gestor@example.com',
            'password' => bcrypt('password'),
            'role' => 'gestor',
        ]);

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
    }

    // ==========================================
    // ESTADOS (1-5)
    // ==========================================

    /**
     * Scenario 1: Determinar cuota próxima a vencer.
     */
    public function test_determinar_cuota_proxima_a_vencer(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15, // Cobro is 15th
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $statusService = new CuotaStatusService();
        // Today is 10th (before 15th) -> Proximo vencimiento
        $status = $statusService->determineStatus($cuota, '2026-08-10');

        $this->assertEquals('proximo_vencimiento', $status);
    }

    /**
     * Scenario 2: Determinar cuota que vence hoy.
     */
    public function test_determinar_cuota_que_vence_hoy(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $statusService = new CuotaStatusService();
        // Today is 15th -> Vence hoy
        $status = $statusService->determineStatus($cuota, '2026-08-15');

        $this->assertEquals('vence_hoy', $status);
    }

    /**
     * Scenario 3: Determinar cuota vencida.
     */
    public function test_determinar_cuota_vencida(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $statusService = new CuotaStatusService();
        // Today is 20th -> Vencida
        $status = $statusService->determineStatus($cuota, '2026-08-20');

        $this->assertEquals('vencida', $status);
    }

    /**
     * Scenario 4: Mantener estado de cuota pagada oficialmente.
     */
    public function test_mantener_estado_de_cuota_pagada_oficialmente(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 0.00,
            'estado_financiero' => 'pago_realizado_oficial',
            'estado_gestion' => 'cobrado',
            'prioridad' => 'baja',
        ]);

        $statusService = new CuotaStatusService();
        $status = $statusService->determineStatus($cuota, '2026-08-20');

        $this->assertEquals('pago_realizado_oficial', $status);
    }

    /**
     * Scenario 5: No eliminar cuota cuando desaparece de una importación.
     */
    public function test_no_eliminar_cuota_cuando_desaparece_de_una_importacion(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        // Simulating the absent flow where state shifts to ausente_en_ultima_importacion
        $cuota->update(['estado_financiero' => 'ausente_en_ultima_importacion']);

        $this->assertDatabaseHas('cuotas', [
            'id' => $cuota->id,
            'estado_financiero' => 'ausente_en_ultima_importacion',
        ]);
    }

    // ==========================================
    // GESTIONES (6-12)
    // ==========================================

    /**
     * Scenario 6: Registrar WhatsApp.
     */
    public function test_registrar_whatsapp(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $service = new GestionService();
        $gestion = $service->registrarGestion([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'tipo' => 'whatsapp_enviado',
            'resultado' => 'seguimiento',
            'observacion' => 'Enviado recordatorio',
        ]);

        $this->assertDatabaseHas('gestiones', [
            'id' => $gestion->id,
            'tipo' => 'whatsapp_enviado',
            'resultado' => 'seguimiento',
        ]);
        $this->assertEquals('seguimiento', $cuota->fresh()->estado_gestion);
    }

    /**
     * Scenario 7: Registrar llamada.
     */
    public function test_registrar_llamada(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $service = new GestionService();
        $gestion = $service->registrarGestion([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'tipo' => 'llamada_realizada',
            'resultado' => 'no_atendio',
        ]);

        $this->assertDatabaseHas('gestiones', [
            'id' => $gestion->id,
            'tipo' => 'llamada_realizada',
            'resultado' => 'no_atendio',
        ]);
    }

    /**
     * Scenario 8: Registrar cliente sin respuesta.
     */
    public function test_registrar_cliente_sin_respuesta(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $service = new GestionService();

        // Register 3 consecutive no-answers/no-response
        $service->registrarGestion(['cuota_id' => $cuota->id, 'user_id' => $this->user->id, 'tipo' => 'whatsapp_enviado', 'resultado' => 'no_atendio']);
        $service->registrarGestion(['cuota_id' => $cuota->id, 'user_id' => $this->user->id, 'tipo' => 'llamada_realizada', 'resultado' => 'no_atendio']);
        $service->registrarGestion(['cuota_id' => $cuota->id, 'user_id' => $this->user->id, 'tipo' => 'llamada_realizada', 'resultado' => 'sin_respuesta']);

        $this->assertEquals('sin_respuesta', $cuota->fresh()->estado_gestion);
    }

    /**
     * Scenario 9: Registrar promesa.
     */
    public function test_registrar_promesa_thru_gestion(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $gestionService = new GestionService();
        $gestion = $gestionService->registrarGestion([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'tipo' => 'llamada_realizada',
            'resultado' => 'prometio_pagar',
            'observacion' => 'Promete el 25/08',
        ]);

        $promesaService = new PromesaPagoService();
        $promesa = $promesaService->crearPromesa([
            'cuota_id' => $cuota->id,
            'gestion_id' => $gestion->id,
            'user_id' => $this->user->id,
            'fecha_prometida' => '2026-08-25',
            'monto_prometido' => 5000.00,
        ]);

        $this->assertDatabaseHas('promesas_pago', [
            'id' => $promesa->id,
            'estado' => 'pendiente',
            'fecha_prometida' => '2026-08-25',
        ]);
        $this->assertEquals('promesa_pendiente', $cuota->fresh()->estado_gestion);
    }

    /**
     * Scenario 10: Registrar solicitud de cobrador.
     */
    public function test_registrar_solicitud_de_cobrador(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $service = new GestionService();
        $service->registrarGestion([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'tipo' => 'llamada_realizada',
            'resultado' => 'solicito_cobrador',
        ]);

        $this->assertEquals('visita_solicitada', $cuota->fresh()->estado_gestion);
    }

    /**
     * Scenario 11: Registrar próxima acción.
     */
    public function test_registrar_proxima_accion(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $service = new GestionService();
        $gestion = $service->registrarGestion([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'tipo' => 'whatsapp_enviado',
            'resultado' => 'seguimiento',
            'proxima_accion' => 'volver_a_llamar',
            'proxima_accion_fecha' => '2026-08-25',
        ]);

        $this->assertDatabaseHas('gestiones', [
            'id' => $gestion->id,
            'proxima_accion' => 'volver_a_llamar',
            'proxima_accion_fecha' => '2026-08-25',
        ]);
    }

    /**
     * Scenario 12: Recuperar seguimientos cuya fecha llegó.
     */
    public function test_recuperar_seguimientos_cuya_fecha_llego(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $service = new GestionService();
        $service->registrarGestion([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'tipo' => 'whatsapp_enviado',
            'resultado' => 'seguimiento',
            'proxima_accion' => 'volver_a_llamar',
            'proxima_accion_fecha' => '2026-08-20',
        ]);

        $agendaService = new AgendaCobranzaService();
        // Get agenda for 2026-08-20 (same date as proxima_accion_fecha)
        $agenda = $agendaService->obtenerAgendaDiaria('2026-08-20');

        $this->assertCount(1, $agenda['hoy']['seguimientos_hoy']);
        $this->assertEquals($cuota->id, $agenda['hoy']['seguimientos_hoy'][0]->id);
    }

    // ==========================================
    // PROMESAS (13-18)
    // ==========================================

    /**
     * Scenario 13: Crear promesa pendiente.
     */
    public function test_crear_promesa_pendiente(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $promesaService = new PromesaPagoService();
        $promesa = $promesaService->crearPromesa([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_prometida' => '2026-08-20',
            'monto_prometido' => 5000.00,
        ]);

        $this->assertEquals('pendiente', $promesa->estado);
    }

    /**
     * Scenario 14: Detectar promesa para hoy.
     */
    public function test_detectar_promesa_para_hoy(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $promesaService = new PromesaPagoService();
        $promesaService->crearPromesa([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_prometida' => '2026-08-20',
            'monto_prometido' => 5000.00,
        ]);

        $agendaService = new AgendaCobranzaService();
        $agenda = $agendaService->obtenerAgendaDiaria('2026-08-20');

        $this->assertCount(1, $agenda['hoy']['promesas_hoy']);
    }

    /**
     * Scenario 15: Detectar promesa vencida.
     */
    public function test_detectar_promesa_vencida(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $promesaService = new PromesaPagoService();
        $promesa = $promesaService->crearPromesa([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_prometida' => '2026-08-18',
            'monto_prometido' => 5000.00,
        ]);

        // When evaluating on 2026-08-20 (two days past the due date)
        $statusService = new CuotaStatusService();
        $status = $statusService->determineStatus($cuota, '2026-08-20');

        $this->assertEquals('promesa_incumplida', $status);
    }

    /**
     * Scenario 16: Marcar promesa como incumplida.
     */
    public function test_marcar_promesa_como_incumplida(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $promesaService = new PromesaPagoService();
        $promesa = $promesaService->crearPromesa([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_prometida' => '2026-08-18',
            'monto_prometido' => 5000.00,
        ]);

        // Run batch or single manual update
        $promesaService->cambiarEstado($promesa->id, 'incumplida');

        $this->assertEquals('incumplida', $promesa->fresh()->estado);
        $this->assertEquals('promesa_incumplida', $cuota->fresh()->estado_gestion);
    }

    /**
     * Scenario 17: Marcar promesa como cumplida al registrar pago.
     */
    public function test_marcar_promesa_como_cumplida_al_registrar_pago(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $promesaService = new PromesaPagoService();
        $promesa = $promesaService->crearPromesa([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_prometida' => '2026-08-20',
            'monto_prometido' => 5000.00,
        ]);

        $pagoService = new PagoService();
        $pagoService->registrarPago([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'monto_cobrado' => 5000.00,
            'medio_pago' => 'efectivo',
        ]);

        $this->assertEquals('cumplida', $promesa->fresh()->estado);
    }

    /**
     * Scenario 18: Conservar historial de promesas anteriores.
     */
    public function test_conservar_historial_de_promesas_anteriores(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $promesaService = new PromesaPagoService();

        // 1st promise, later breached
        $promesa1 = $promesaService->crearPromesa(['cuota_id' => $cuota->id, 'user_id' => $this->user->id, 'fecha_prometida' => '2026-08-10']);
        $promesaService->cambiarEstado($promesa1->id, 'incumplida');

        // 2nd promise
        $promesa2 = $promesaService->crearPromesa(['cuota_id' => $cuota->id, 'user_id' => $this->user->id, 'fecha_prometida' => '2026-08-20']);

        $this->assertCount(2, $cuota->fresh()->promesasPago);
        $this->assertEquals('incumplida', $promesa1->fresh()->estado);
        $this->assertEquals('pendiente', $promesa2->fresh()->estado);
    }

    // ==========================================
    // COBRADOR (19-24)
    // ==========================================

    /**
     * Scenario 19: Crear visita por mora.
     */
    public function test_crear_visita_por_mora(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $cobrador = User::create([
            'name' => 'Luis Cobrador',
            'email' => 'luis@example.com',
            'password' => bcrypt('password'),
            'role' => 'cobrador',
        ]);

        $visitaService = new VisitaCobradorService();
        $visita = $visitaService->programarVisita([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $cuota->id,
            'cobrador_id' => $cobrador->id,
            'fecha_programada' => '2026-08-20',
            'observaciones' => 'Visita por mora persistente',
        ]);

        $this->assertDatabaseHas('visitas_cobrador', [
            'id' => $visita->id,
            'estado' => 'pendiente',
        ]);
        $this->assertEquals('visita_solicitada', $cuota->fresh()->estado_gestion);
    }

    /**
     * Scenario 20: Crear visita por falta de respuesta.
     */
    public function test_crear_visita_por_falta_de_respuesta(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_respuesta',
            'prioridad' => 'critica',
        ]);

        $cobrador = User::create([
            'name' => 'Luis Cobrador',
            'email' => 'luis2@example.com',
            'password' => bcrypt('password'),
            'role' => 'cobrador',
        ]);

        $visitaService = new VisitaCobradorService();
        $visita = $visitaService->programarVisita([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $cuota->id,
            'cobrador_id' => $cobrador->id,
            'fecha_programada' => '2026-08-20',
            'observaciones' => 'No responde llamadas',
        ]);

        $this->assertDatabaseHas('visitas_cobrador', [
            'id' => $visita->id,
            'cuota_id' => $cuota->id,
        ]);
    }

    /**
     * Scenario 21: Crear visita solicitada voluntariamente por cliente.
     */
    public function test_crear_visita_solicitada_voluntariamente_por_cliente(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $cobrador = User::create([
            'name' => 'Luis Cobrador',
            'email' => 'luis3@example.com',
            'password' => bcrypt('password'),
            'role' => 'cobrador',
        ]);

        $visitaService = new VisitaCobradorService();
        $visita = $visitaService->programarVisita([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $cuota->id,
            'cobrador_id' => $cobrador->id,
            'fecha_programada' => '2026-08-20',
            'observaciones' => 'Solicita que retiremos en su domicilio',
        ]);

        $this->assertEquals('visita_solicitada', $cuota->fresh()->estado_gestion);
    }

    /**
     * Scenario 22: Crear visita para cliente al día.
     */
    public function test_crear_visita_para_cliente_al_dia(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 28, // Next week (at day)
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $cobrador = User::create([
            'name' => 'Luis Cobrador',
            'email' => 'luis4@example.com',
            'password' => bcrypt('password'),
            'role' => 'cobrador',
        ]);

        $visitaService = new VisitaCobradorService();
        $visita = $visitaService->programarVisita([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $cuota->id,
            'cobrador_id' => $cobrador->id,
            'fecha_programada' => '2026-08-20',
        ]);

        $this->assertDatabaseHas('visitas_cobrador', [
            'id' => $visita->id,
            'cuota_id' => $cuota->id,
        ]);
    }

    /**
     * Scenario 23: Crear visita sin cuota específica.
     */
    public function test_crear_visita_sin_cuota_especifica(): void
    {
        $cobrador = User::create([
            'name' => 'Luis Cobrador',
            'email' => 'luis5@example.com',
            'password' => bcrypt('password'),
            'role' => 'cobrador',
        ]);

        $visitaService = new VisitaCobradorService();
        $visita = $visitaService->programarVisita([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => null, // No specific cuota
            'cobrador_id' => $cobrador->id,
            'fecha_programada' => '2026-08-20',
        ]);

        $this->assertNull($visita->cuota_id);
    }

    /**
     * Scenario 24: Conservar domicilio histórico de la visita.
     */
    public function test_conservar_domicilio_historico_de_la_visita(): void
    {
        $cobrador = User::create([
            'name' => 'Luis Cobrador',
            'email' => 'luis6@example.com',
            'password' => bcrypt('password'),
            'role' => 'cobrador',
        ]);

        $visitaService = new VisitaCobradorService();
        $visita = $visitaService->programarVisita([
            'cliente_id' => $this->cliente->id,
            'cobrador_id' => $cobrador->id,
            'fecha_programada' => '2026-08-20',
        ]);

        // Change client address afterwards
        $this->cliente->update(['domicilio' => 'Calle Nueva 1234']);

        $this->assertEquals('Av. de Mayo 500', $visita->fresh()->domicilio);
    }

    // ==========================================
    // PRIORIDADES (25-29)
    // ==========================================

    /**
     * Scenario 25: Cliente con promesa incumplida recibe prioridad elevada (crítica).
     */
    public function test_cliente_con_promesa_incumplida_recibe_prioridad_elevada(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $promesaService = new PromesaPagoService();
        $promesa = $promesaService->crearPromesa([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_prometida' => '2026-08-10',
        ]);

        $promesaService->cambiarEstado($promesa->id, 'incumplida');

        $this->assertEquals('critica', $cuota->fresh()->prioridad);
    }

    /**
     * Scenario 26: Cliente sin respuesta recibe prioridad elevada (crítica).
     */
    public function test_cliente_sin_respuesta_recibe_prioridad_elevada(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $service = new GestionService();
        // 3 consecutive failed attempts
        $service->registrarGestion(['cuota_id' => $cuota->id, 'user_id' => $this->user->id, 'tipo' => 'llamada', 'resultado' => 'no_atendio']);
        $service->registrarGestion(['cuota_id' => $cuota->id, 'user_id' => $this->user->id, 'tipo' => 'llamada', 'resultado' => 'no_atendio']);
        $service->registrarGestion(['cuota_id' => $cuota->id, 'user_id' => $this->user->id, 'tipo' => 'llamada', 'resultado' => 'no_atendio']);

        $this->assertEquals('critica', $cuota->fresh()->prioridad);
    }

    /**
     * Scenario 27: Cliente con visita pendiente recibe prioridad elevada (crítica).
     */
    public function test_cliente_con_visita_pendiente_recibe_prioridad_elevada(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $cobrador = User::create([
            'name' => 'Luis Cobrador',
            'email' => 'luis7@example.com',
            'password' => bcrypt('password'),
            'role' => 'cobrador',
        ]);

        $visitaService = new VisitaCobradorService();
        $visitaService->programarVisita([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => $cuota->id,
            'cobrador_id' => $cobrador->id,
            'fecha_programada' => '2026-08-20',
        ]);

        $this->assertEquals('critica', $cuota->fresh()->prioridad);
    }

    /**
     * Scenario 28: Cuota vencida recibe prioridad superior a una próxima a vencer.
     */
    public function test_cuota_vencida_recibe_prioridad_superior_a_una_proxima_a_vencer(): void
    {
        $cuotaProxima = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 25,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $cuotaVencida = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 2,
            'dia_cobro' => 10,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $priorityService = new CuotaPriorityService();
        // Today is 2026-08-20
        $prioProxima = $priorityService->recalculatePriority($cuotaProxima, '2026-08-20');
        $prioVencida = $priorityService->recalculatePriority($cuotaVencida, '2026-08-20');

        $this->assertEquals('media', $prioProxima);
        $this->assertEquals('alta', $prioVencida);
    }

    /**
     * Scenario 29: No marcar automáticamente como crítico un cliente que simplemente no respondió si todavía no existe suficiente historial para justificarlo.
     */
    public function test_no_marcar_automaticamente_como_critico_sin_suficiente_historial(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $service = new GestionService();
        // Just 1 failed attempt -> should not be critical immediately
        $service->registrarGestion(['cuota_id' => $cuota->id, 'user_id' => $this->user->id, 'tipo' => 'llamada', 'resultado' => 'no_atendio']);

        $this->assertNotEquals('critica', $cuota->fresh()->prioridad);
    }

    // ==========================================
    // PAGOS (30-35)
    // ==========================================

    /**
     * Scenario 30: Registrar pago completo.
     */
    public function test_registrar_pago_completo(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $pagoService = new PagoService();
        $pago = $pagoService->registrarPago([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'monto_cobrado' => 5000.00,
            'medio_pago' => 'transferencia',
        ]);

        $this->assertEquals(0.00, $cuota->fresh()->saldo_pendiente);
        $this->assertEquals('cobrado', $cuota->fresh()->estado_gestion);
        $this->assertTrue($pago->es_cancelatorio);
    }

    /**
     * Scenario 31: Registrar pago parcial.
     */
    public function test_registrar_pago_parcial(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 5000.00,
            'saldo_pendiente' => 5000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $pagoService = new PagoService();
        $pago = $pagoService->registrarPago([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'monto_cobrado' => 2000.00,
            'medio_pago' => 'transferencia',
        ]);

        $this->assertEquals(3000.00, $cuota->fresh()->saldo_pendiente);
        $this->assertEquals('seguimiento', $cuota->fresh()->estado_gestion);
        $this->assertFalse($pago->es_cancelatorio);
    }

    /**
     * Scenario 32: Registrar pago con punitorios.
     */
    public function test_registrar_pago_con_punitorios(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 500.00,
            'total_actualizado' => 5500.00,
            'saldo_pendiente' => 5500.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $pagoService = new PagoService();
        $pago = $pagoService->registrarPago([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'monto_cobrado' => 5500.00,
            'medio_pago' => 'transferencia',
        ]);

        $this->assertEquals(0.00, $cuota->fresh()->saldo_pendiente);
        $this->assertEquals(0.00, $pago->punitorios_perdonados);
    }

    /**
     * Scenario 33: Registrar pago con punitorios perdonados.
     */
    public function test_registrar_pago_con_punitorios_perdonados(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 500.00,
            'total_actualizado' => 5500.00,
            'saldo_pendiente' => 5500.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $pagoService = new PagoService();
        $pago = $pagoService->registrarPago([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'monto_cobrado' => 5000.00,
            'punitorios_perdonados' => 500.00,
            'medio_pago' => 'transferencia',
        ]);

        $this->assertEquals(0.00, $cuota->fresh()->saldo_pendiente);
        $this->assertEquals(500.00, $pago->punitorios_perdonados);
    }

    /**
     * Scenario 34: Verificar que la condonación NO modifica "cuotas.punitorios".
     */
    public function test_verificar_que_la_condonacion_no_modifica_cuotas_punitorios(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 500.00,
            'total_actualizado' => 5500.00,
            'saldo_pendiente' => 5500.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $pagoService = new PagoService();
        $pagoService->registrarPago([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'monto_cobrado' => 5000.00,
            'punitorios_perdonados' => 500.00,
            'medio_pago' => 'transferencia',
        ]);

        $this->assertEquals(500.00, $cuota->fresh()->punitorios);
    }

    /**
     * Scenario 35: Verificar que los valores oficiales quedan preservados como snapshot histórico.
     */
    public function test_verificar_que_los_valores_oficiales_quedan_preservados_como_snapshot_historico(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 15,
            'importe_original' => 5000.00,
            'punitorios' => 500.00,
            'total_actualizado' => 5500.00,
            'saldo_pendiente' => 5500.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $pagoService = new PagoService();
        $pago = $pagoService->registrarPago([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'monto_cobrado' => 5000.00,
            'punitorios_perdonados' => 500.00,
            'medio_pago' => 'transferencia',
        ]);

        $this->assertEquals(5000.00, $pago->importe_original_snapshot);
        $this->assertEquals(500.00, $pago->punitorios_snapshot);
        $this->assertEquals(5500.00, $pago->total_actualizado_snapshot);
    }
}

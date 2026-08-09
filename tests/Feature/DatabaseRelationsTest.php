<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\CuotaImportacion;
use App\Models\Gestion;
use App\Models\Importacion;
use App\Models\Operacion;
use App\Models\Pago;
use App\Models\PeriodoCobranza;
use App\Models\PromesaPago;
use App\Models\User;
use App\Models\VisitaCobrador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cliente $cliente;
    protected Operacion $operacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'administrador',
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'documento' => '12345678',
            'telefono' => '11223344',
            'domicilio' => 'Calle Falsa 123',
        ]);

        $this->operacion = Operacion::create([
            'cliente_id' => $this->cliente->id,
            'numero_solicitud' => '2455',
        ]);
    }

    /**
     * Test 1: Una operación puede tener múltiples cuotas.
     */
    public function test_una_operacion_puede_tener_multiples_cuotas(): void
    {
        $cuota1 = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 5,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $cuota2 = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 2,
            'dia_cobro' => 5,
            'importe_original' => 100000.00,
            'punitorios' => 0.00,
            'total_actualizado' => 100000.00,
            'saldo_pendiente' => 100000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $this->assertCount(2, $this->operacion->fresh()->cuotas);
        $this->assertEquals($cuota1->id, $this->operacion->cuotas[0]->id);
        $this->assertEquals($cuota2->id, $this->operacion->cuotas[1]->id);
    }

    /**
     * Test 2: No se puede crear dos veces la misma combinación "operacion_id + numero_cuota".
     */
    public function test_no_se_puede_crear_dos_veces_la_misma_combinacion_operacion_y_cuota(): void
    {
        Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 3000.00,
            'total_actualizado' => 103000.00,
            'saldo_pendiente' => 103000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'alta',
        ]);
    }

    /**
     * Test 3: Una misma cuota puede aparecer en múltiples importaciones.
     */
    public function test_una_misma_cuota_puede_aparecer_en_multiples_importaciones(): void
    {
        $periodo = PeriodoCobranza::create([
            'nombre' => 'Agosto 2026',
            'mes' => 8,
            'anio' => 2026,
            'activo' => true,
            'objetivo_monto' => 1000000.00,
        ]);

        $importacion1 = Importacion::create([
            'periodo_cobranza_id' => $periodo->id,
            'user_id' => $this->user->id,
            'fecha_hora' => now(),
            'nombre_archivo' => 'cartera_0108.xlsx',
            'tipo_archivo' => 'xlsx',
            'cantidad_registros' => 1,
            'registros_nuevos' => 1,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completada',
        ]);

        $importacion2 = Importacion::create([
            'periodo_cobranza_id' => $periodo->id,
            'user_id' => $this->user->id,
            'fecha_hora' => now()->addDays(5),
            'nombre_archivo' => 'cartera_0508.xlsx',
            'tipo_archivo' => 'xlsx',
            'cantidad_registros' => 1,
            'registros_nuevos' => 0,
            'registros_actualizados' => 1,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completada',
        ]);

        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $ci1 = CuotaImportacion::create([
            'cuota_id' => $cuota->id,
            'importacion_id' => $importacion1->id,
            'importe_original_observado' => 100000.00,
            'punitorios_observados' => 2000.00,
            'total_actualizado_observado' => 102000.00,
            'estado_presencia' => 'presente',
        ]);

        $ci2 = CuotaImportacion::create([
            'cuota_id' => $cuota->id,
            'importacion_id' => $importacion2->id,
            'importe_original_observado' => 100000.00,
            'punitorios_observados' => 3500.00,
            'total_actualizado_observado' => 103500.00,
            'estado_presencia' => 'presente',
        ]);

        $this->assertCount(2, $cuota->fresh()->cuotaImportaciones);
        $this->assertEquals($importacion1->id, $cuota->cuotaImportaciones[0]->importacion_id);
        $this->assertEquals($importacion2->id, $cuota->cuotaImportaciones[1]->importacion_id);
        $this->assertEquals(2000.00, $cuota->cuotaImportaciones[0]->punitorios_observados);
        $this->assertEquals(3500.00, $cuota->cuotaImportaciones[1]->punitorios_observados);
    }

    /**
     * Test 4: No se puede registrar dos veces la misma cuota dentro de una misma importación.
     */
    public function test_no_se_puede_registrar_dos_veces_la_misma_cuota_dentro_de_la_misma_importacion(): void
    {
        $periodo = PeriodoCobranza::create([
            'nombre' => 'Agosto 2026',
            'mes' => 8,
            'anio' => 2026,
            'activo' => true,
            'objetivo_monto' => 1000000.00,
        ]);

        $importacion = Importacion::create([
            'periodo_cobranza_id' => $periodo->id,
            'user_id' => $this->user->id,
            'fecha_hora' => now(),
            'nombre_archivo' => 'cartera.xlsx',
            'tipo_archivo' => 'xlsx',
            'cantidad_registros' => 1,
            'registros_nuevos' => 1,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completada',
        ]);

        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        CuotaImportacion::create([
            'cuota_id' => $cuota->id,
            'importacion_id' => $importacion->id,
            'importe_original_observado' => 100000.00,
            'punitorios_observados' => 2000.00,
            'total_actualizado_observado' => 102000.00,
            'estado_presencia' => 'presente',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        CuotaImportacion::create([
            'cuota_id' => $cuota->id,
            'importacion_id' => $importacion->id,
            'importe_original_observado' => 100000.00,
            'punitorios_observados' => 2500.00,
            'total_actualizado_observado' => 102500.00,
            'estado_presencia' => 'presente',
        ]);
    }

    /**
     * Test 5: Una visita puede existir sin "cuota_id".
     */
    public function test_una_visita_puede_existir_sin_cuota_id(): void
    {
        $cobrador = User::create([
            'name' => 'Cobrador Pedro',
            'email' => 'pedro@example.com',
            'password' => bcrypt('password'),
            'role' => 'cobrador',
        ]);

        $visita = VisitaCobrador::create([
            'cliente_id' => $this->cliente->id,
            'cuota_id' => null,
            'cobrador_id' => $cobrador->id,
            'domicilio' => 'Calle Falsa 123 (Snapshot)',
            'fecha_programada' => now()->addDays(2)->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->assertNull($visita->cuota_id);
        $this->assertEquals($this->cliente->id, $visita->cliente_id);
        $this->assertEquals($cobrador->id, $visita->cobrador_id);
    }

    /**
     * Test 6: Una cuota puede recibir múltiples gestiones.
     */
    public function test_una_cuota_puede_recibir_multiples_gestiones(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        Gestion::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_hora' => now(),
            'tipo' => 'whatsapp_enviado',
            'resultado' => 'no_atendio',
            'observacion' => 'Primer contacto intentado.',
        ]);

        Gestion::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_hora' => now()->addHours(2),
            'tipo' => 'llamada_realizada',
            'resultado' => 'prometio_pagar',
            'observacion' => 'El cliente atendio y prometio pagar el 15.',
        ]);

        $this->assertCount(2, $cuota->fresh()->gestiones);
    }

    /**
     * Test 7: Una cuota puede tener múltiples promesas históricas.
     */
    public function test_una_cuota_puede_tener_multiples_promesas_historicas(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $gestion1 = Gestion::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_hora' => now()->subDays(5),
            'tipo' => 'llamada_realizada',
            'resultado' => 'prometio_pagar',
        ]);

        $promesa1 = PromesaPago::create([
            'cuota_id' => $cuota->id,
            'gestion_id' => $gestion1->id,
            'user_id' => $this->user->id,
            'fecha_creacion' => now()->subDays(5),
            'fecha_prometida' => now()->subDays(2)->toDateString(),
            'monto_prometido' => 102000.00,
            'estado' => 'incumplida',
        ]);

        $gestion2 = Gestion::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_hora' => now(),
            'tipo' => 'llamada_realizada',
            'resultado' => 'prometio_pagar',
        ]);

        $promesa2 = PromesaPago::create([
            'cuota_id' => $cuota->id,
            'gestion_id' => $gestion2->id,
            'user_id' => $this->user->id,
            'fecha_creacion' => now(),
            'fecha_prometida' => now()->addDays(3)->toDateString(),
            'monto_prometido' => 102000.00,
            'estado' => 'pendiente',
        ]);

        $this->assertCount(2, $cuota->fresh()->promesasPago);
        $this->assertEquals('incumplida', $cuota->promesasPago[0]->estado);
        $this->assertEquals('pendiente', $cuota->promesasPago[1]->estado);
    }

    /**
     * Test 8: Una cuota puede recibir múltiples pagos.
     */
    public function test_una_cuota_puede_recibir_multiples_pagos(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        Pago::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_pago' => now(),
            'importe_original_snapshot' => 100000.00,
            'punitorios_snapshot' => 2000.00,
            'total_actualizado_snapshot' => 102000.00,
            'monto_cobrado' => 40000.00,
            'punitorios_perdonados' => 0.00,
            'es_cancelatorio' => false,
            'medio_pago' => 'transferencia',
        ]);

        Pago::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_pago' => now()->addDays(2),
            'importe_original_snapshot' => 100000.00,
            'punitorios_snapshot' => 2000.00,
            'total_actualizado_snapshot' => 102000.00,
            'monto_cobrado' => 62000.00,
            'punitorios_perdonados' => 0.00,
            'es_cancelatorio' => true,
            'medio_pago' => 'transferencia',
        ]);

        $this->assertCount(2, $cuota->fresh()->pagos);
        $this->assertFalse($cuota->pagos[0]->es_cancelatorio);
        $this->assertTrue($cuota->pagos[1]->es_cancelatorio);
    }

    /**
     * Test 9: Un pago puede registrar punitorios perdonados.
     */
    public function test_un_pago_puede_registrar_punitorios_perdonados(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $pago = Pago::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_pago' => now(),
            'importe_original_snapshot' => 100000.00,
            'punitorios_snapshot' => 2000.00,
            'total_actualizado_snapshot' => 102000.00,
            'monto_cobrado' => 100000.00,
            'punitorios_perdonados' => 2000.00,
            'es_cancelatorio' => true,
            'medio_pago' => 'efectivo',
            'observaciones' => 'Se perdonaron punitorios por cancelacion rapida.',
        ]);

        $this->assertEquals(2000.00, $pago->punitorios_perdonados);
        $this->assertEquals(100000.00, $pago->monto_cobrado);
    }

    /**
     * Test 10: Un pago parcial no debe marcarse como cancelatorio.
     */
    public function test_un_pago_parcial_no_debe_marcarse_como_cancelatorio(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $pago = Pago::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_pago' => now(),
            'importe_original_snapshot' => 100000.00,
            'punitorios_snapshot' => 2000.00,
            'total_actualizado_snapshot' => 102000.00,
            'monto_cobrado' => 50000.00,
            'punitorios_perdonados' => 0.00,
            'es_cancelatorio' => false,
            'medio_pago' => 'efectivo',
        ]);

        $this->assertFalse($pago->es_cancelatorio);
        $this->assertEquals(50000.00, $pago->monto_cobrado);
    }

    /**
     * Test 11: Una nueva importación no elimina datos de gestión existentes.
     */
    public function test_una_nueva_importacion_no_elimina_datos_de_gestion_existentes(): void
    {
        $cuota = Cuota::create([
            'operacion_id' => $this->operacion->id,
            'numero_cuota' => 5,
            'dia_cobro' => 10,
            'importe_original' => 100000.00,
            'punitorios' => 2000.00,
            'total_actualizado' => 102000.00,
            'saldo_pendiente' => 102000.00,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]);

        $this->cliente->update([
            'telefono' => '1234567890',
            'domicilio' => 'Nueva Direccion de Gestion 456',
        ]);

        $cuota->update([
            'estado_gestion' => 'promesa_pendiente',
            'prioridad' => 'alta',
        ]);

        $gestion = Gestion::create([
            'cuota_id' => $cuota->id,
            'user_id' => $this->user->id,
            'fecha_hora' => now(),
            'tipo' => 'llamada_realizada',
            'resultado' => 'prometio_pagar',
        ]);

        // Simular importación de actualización donde sólo datos oficiales cambian
        // y los datos de gestión e históricos permanecen intactos en DB:
        $cuota->update([
            'punitorios' => 5000.00,
            'total_actualizado' => 105000.00,
        ]);

        $this->assertEquals('1234567890', $this->cliente->fresh()->telefono);
        $this->assertEquals('Nueva Direccion de Gestion 456', $this->cliente->fresh()->domicilio);
        $this->assertEquals('promesa_pendiente', $cuota->fresh()->estado_gestion);
        $this->assertEquals('alta', $cuota->fresh()->prioridad);
        $this->assertCount(1, $cuota->fresh()->gestiones);
        $this->assertEquals($gestion->id, $cuota->fresh()->gestiones[0]->id);
    }
}

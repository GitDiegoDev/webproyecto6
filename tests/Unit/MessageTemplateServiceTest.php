<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MessageTemplateService;
use App\Models\Cuota;
use App\Models\Cliente;
use App\Models\Operacion;
use App\Models\PromesaPago;
use App\Models\VisitaCobrador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MessageTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MessageTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MessageTemplateService();
    }

    public function test_available_variables_count(): void
    {
        $vars = $this->service->getAvailableVariables();
        $this->assertArrayHasKey('{nombre}', $vars);
        $this->assertArrayHasKey('{apellido}', $vars);
        $this->assertArrayHasKey('{nombre_completo}', $vars);
        $this->assertArrayHasKey('{link_pago}', $vars);
        $this->assertCount(19, $vars);
    }

    public function test_detect_unknown_variables(): void
    {
        $body = 'Hola {nombre}, tu cuota {numero_cuota} venció. Visita {fecha_random} no es válida.';
        $unknown = $this->service->detectUnknownVariables($body);
        $this->assertEquals(['{fecha_random}'], array_values($unknown));
    }

    public function test_parse_template_with_valid_values(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'María',
            'apellido' => 'Gómez',
            'telefono' => '5493755443322',
            'domicilio' => 'Calle 123',
        ]);
        $operacion = Operacion::create([
            'cliente_id' => $cliente->id,
            'numero_solicitud' => '1001',
        ]);
        $cuota = Cuota::create([
            'operacion_id' => $operacion->id,
            'numero_cuota' => 2,
            'dia_cobro' => 15,
            'importe_original' => 10000,
            'punitorios' => 500,
            'total_actualizado' => 10500,
            'saldo_pendiente' => 10500,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
            'link_pago' => 'https://pay.link/1001',
        ]);

        $body = 'Hola {nombre_completo}, tu saldo pendiente es ${importe} para la cuota {numero_cuota} de la solicitud #{numero_solicitud}. Domicilio: {domicilio}. Link: {link_pago}';
        $result = $this->service->parseTemplate($body, $cuota);

        $this->assertStringContainsString('María Gómez', $result['text']);
        $this->assertStringContainsString('$10,500.00', $result['text']);
        $this->assertStringContainsString('https://pay.link/1001', $result['text']);
        $this->assertEmpty($result['warnings']);
    }

    public function test_parse_template_with_missing_values_generates_warnings(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'José',
            'apellido' => 'Pérez',
        ]);
        $operacion = Operacion::create([
            'cliente_id' => $cliente->id,
            'numero_solicitud' => '1002',
        ]);
        $cuota = Cuota::create([
            'operacion_id' => $operacion->id,
            'numero_cuota' => 1,
            'dia_cobro' => 10,
            'importe_original' => 5000,
            'punitorios' => 0,
            'total_actualizado' => 5000,
            'saldo_pendiente' => 5000,
            'estado_financiero' => 'pendiente',
            'estado_gestion' => 'sin_contactar',
            'prioridad' => 'media',
        ]); // link_pago is null

        $body = 'Hola {nombre_completo}, pague en {link_pago} o espere al cobrador {nombre_cobrador}.';
        $result = $this->service->parseTemplate($body, $cuota);

        $this->assertNotEmpty($result['warnings']);
        $this->assertContains('Esta cuota no tiene un link de pago disponible.', $result['warnings']);
        $this->assertContains('Esta cuota no registra ninguna visita de cobrador programada.', $result['warnings']);
    }

    public function test_normalize_phone_number(): void
    {
        $raw = '+54 (9) 3755-112233';
        $normalized = $this->service->normalizePhoneNumber($raw);
        $this->assertEquals('5493755112233', $normalized);
    }
}

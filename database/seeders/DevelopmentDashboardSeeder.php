<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PeriodoCobranza;
use App\Models\Importacion;
use App\Models\Cliente;
use App\Models\Operacion;
use App\Models\Cuota;
use App\Models\CuotaImportacion;

class DevelopmentDashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'gestor@example.com')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'John Gestor',
                'email' => 'gestor@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        $user->role = 'gestor';
        $user->save();

        $periodo = PeriodoCobranza::where('activo', true)->first();
        if (!$periodo) {
            $periodo = PeriodoCobranza::create([
                'nombre' => 'Septiembre 2026',
                'mes' => 9,
                'anio' => 2026,
                'activo' => true,
                'objetivo_monto' => 1500000.00
            ]);
        }

        $importacion = Importacion::create([
            'periodo_cobranza_id' => $periodo->id,
            'user_id' => $user->id,
            'fecha_hora' => now(),
            'nombre_archivo' => 'planilla_seeding.xlsx',
            'tipo_archivo' => 'xlsx',
            'cantidad_registros' => 4,
            'registros_nuevos' => 4,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completado'
        ]);

        $clientesData = [
            ['nombre' => 'Carlos', 'apellido' => 'Ramírez', 'documento' => '11111111', 'telefono' => '3755443322', 'domicilio' => 'Calle Falsa 123'],
            ['nombre' => 'Lucía', 'apellido' => 'Fernández', 'documento' => '22222222', 'telefono' => '3755998877', 'domicilio' => 'Av. Corrientes 1500'],
            ['nombre' => 'Esteban', 'apellido' => 'Soto', 'documento' => '33333333', 'telefono' => '3755112233', 'domicilio' => 'Calle 14 nro 450'],
            ['nombre' => 'Sofía', 'apellido' => 'Mendoza', 'documento' => '44444444', 'telefono' => '3755445566', 'domicilio' => 'Bv. Pellegrini 3000']
        ];

        foreach ($clientesData as $idx => $cData) {
            $cliente = Cliente::create($cData);
            $operacion = Operacion::create(['cliente_id' => $cliente->id, 'numero_solicitud' => 'SOL-' . (1000 + $idx)]);

            $cuota = Cuota::create([
                'operacion_id' => $operacion->id,
                'numero_cuota' => 1,
                'dia_cobro' => ($idx == 0 ? 5 : ($idx == 1 ? 15 : ($idx == 2 ? 10 : 25))),
                'importe_original' => 20000.00,
                'punitorios' => 1000.00,
                'total_actualizado' => 21000.00,
                'saldo_pendiente' => 21000.00,
                'estado_financiero' => 'pendiente',
                'estado_gestion' => 'sin_contactar',
                'prioridad' => 'media'
            ]);

            CuotaImportacion::create([
                'cuota_id' => $cuota->id,
                'importacion_id' => $importacion->id,
                'importe_original_observado' => 20000.00,
                'punitorios_observados' => 1000.00,
                'total_actualizado_observado' => 21000.00,
                'dia_cobro_observado' => $cuota->dia_cobro,
                'estado_presencia' => 'presente'
            ]);
        }
    }
}

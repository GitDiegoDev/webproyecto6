<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PeriodoCobranza;
use App\Models\Cliente;
use App\Models\Operacion;
use App\Models\Cuota;
use App\Models\Importacion;
use App\Models\CuotaImportacion;
use App\Models\Pago;
use Carbon\Carbon;

class SampleRecaudacionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create users
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Diego Administrador',
            'password' => bcrypt('password'),
        ]);
        $admin->role = 'administrador';
        $admin->save();

        $cobrador = User::firstOrCreate([
            'email' => 'cobrador@example.com'
        ], [
            'name' => 'Carlos Cobrador',
            'password' => bcrypt('password'),
        ]);
        $cobrador->role = 'cobrador';
        $cobrador->save();

        // 2. Period
        $periodo = PeriodoCobranza::create([
            'nombre' => 'Agosto 2026',
            'mes' => 8,
            'anio' => 2026,
            'activo' => true,
            'objetivo_monto' => 250000.00,
        ]);

        // 3. Import
        $import = Importacion::create([
            'periodo_cobranza_id' => $periodo->id,
            'user_id' => $admin->id,
            'fecha_hora' => now(),
            'nombre_archivo' => 'cartera_mensual_agosto.xlsx',
            'tipo_archivo' => 'xlsx',
            'cantidad_registros' => 4,
            'registros_nuevos' => 4,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'completada',
        ]);

        // 4. Clients & Debt
        $clientesData = [
            ['nombre' => 'Juan', 'apellido' => 'Pérez', 'documento' => '20111222', 'telefono' => '3514441111', 'domicilio' => 'Av. Colón 1200'],
            ['nombre' => 'María', 'apellido' => 'Rodríguez', 'documento' => '25333444', 'telefono' => '3514442222', 'domicilio' => 'Belgrano 450'],
            ['nombre' => 'Luis', 'apellido' => 'Gómez', 'documento' => '28555666', 'telefono' => '3514443333', 'domicilio' => 'San Martín 780'],
            ['nombre' => 'Ana', 'apellido' => 'Martínez', 'documento' => '30777888', 'telefono' => '3514444444', 'domicilio' => '9 de Julio 150'],
        ];

        foreach ($clientesData as $i => $data) {
            $cli = Cliente::create($data);
            $ope = Operacion::create([
                'cliente_id' => $cli->id,
                'numero_solicitud' => 'SOL-' . (1000 + $i),
            ]);

            $importe = 50000.00;
            $puni = ($i % 2 === 0) ? 5000.00 : 0.00;

            $cuo = Cuota::create([
                'operacion_id' => $ope->id,
                'numero_cuota' => 1,
                'dia_cobro' => 10,
                'importe_original' => $importe,
                'punitorios' => $puni,
                'total_actualizado' => $importe + $puni,
                'saldo_pendiente' => $importe + $puni,
                'estado_financiero' => 'pendiente',
                'estado_gestion' => 'sin_contactar',
                'prioridad' => 'media',
            ]);

            CuotaImportacion::create([
                'cuota_id' => $cuo->id,
                'importacion_id' => $import->id,
                'importe_original_observado' => $importe,
                'punitorios_observados' => $puni,
                'total_actualizado_observado' => $importe + $puni,
                'dia_cobro_observado' => 10,
                'estado_presencia' => 'presente',
            ]);

            // Register some payments to show rich data
            if ($i === 0) {
                // Juan Pérez paid in full with punitorios perdonados
                Pago::create([
                    'cuota_id' => $cuo->id,
                    'user_id' => $admin->id,
                    'fecha_pago' => Carbon::today()->subDays(2),
                    'importe_original_snapshot' => $importe,
                    'punitorios_snapshot' => $puni,
                    'total_actualizado_snapshot' => $importe + $puni,
                    'monto_cobrado' => $importe,
                    'punitorios_perdonados' => $puni,
                    'es_cancelatorio' => true,
                    'medio_pago' => 'transferencia',
                    'observaciones' => 'Condonación de intereses autorizada por administración.',
                ]);
                $cuo->saldo_pendiente = 0.00;
                $cuo->estado_gestion = 'cobrado';
                $cuo->save();
            } elseif ($i === 1) {
                // María paid partial
                Pago::create([
                    'cuota_id' => $cuo->id,
                    'user_id' => $admin->id,
                    'fecha_pago' => Carbon::today()->subDay(),
                    'importe_original_snapshot' => $importe,
                    'punitorios_snapshot' => $puni,
                    'total_actualizado_snapshot' => $importe + $puni,
                    'monto_cobrado' => 20000.00,
                    'punitorios_perdonados' => 0.00,
                    'es_cancelatorio' => false,
                    'medio_pago' => 'efectivo',
                    'observaciones' => 'Entrega a cuenta del saldo total.',
                ]);
                $cuo->saldo_pendiente = 30000.00;
                $cuo->estado_gestion = 'seguimiento';
                $cuo->save();
            } elseif ($i === 2) {
                // Luis paid in full with punitorios paid too!
                Pago::create([
                    'cuota_id' => $cuo->id,
                    'user_id' => $admin->id,
                    'fecha_pago' => Carbon::today(),
                    'importe_original_snapshot' => $importe,
                    'punitorios_snapshot' => $puni,
                    'total_actualizado_snapshot' => $importe + $puni,
                    'monto_cobrado' => $importe + $puni,
                    'punitorios_perdonados' => 0.00,
                    'es_cancelatorio' => true,
                    'medio_pago' => 'efectivo',
                    'observaciones' => 'Pago total en efectivo.',
                ]);
                $cuo->saldo_pendiente = 0.00;
                $cuo->estado_gestion = 'cobrado';
                $cuo->save();
            }
        }
    }
}

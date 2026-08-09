<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlantillaMensaje;

class PlantillasMensajesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plantillas = [
            [
                'titulo' => 'Aviso de Próximo Vencimiento',
                'categoria' => 'proximo_vencimiento',
                'cuerpo' => "Estimado/a {nombre_completo}, le recordamos que su cuota nro {numero_cuota} de la solicitud #{numero_solicitud} vencerá el día {fecha_vencimiento} por un importe de {importe}.\n\nPuede abonar de manera rápida ingresando a su link de pago oficial: {link_pago}.\n\nCualquier duda, estamos a su disposición.",
                'activo' => true,
            ],
            [
                'titulo' => 'Vencimiento Hoy',
                'categoria' => 'vence_hoy',
                'cuerpo' => "Hola {nombre}, le informamos que hoy vence el plazo de pago para su cuota {numero_cuota} (Solicitud #{numero_solicitud}). El importe total es de {total_actualizado}.\n\nEvite recargos abonando hoy mismo desde: {link_pago}.\n\nMuchas gracias por su compromiso.",
                'activo' => true,
            ],
            [
                'titulo' => 'Notificación de Cuota Vencida',
                'categoria' => 'cuota_vencida',
                'cuerpo' => "🚨 ESTIMADO/A {nombre_completo}: Registramos un atraso en su cuota nro {numero_cuota} de la solicitud #{numero_solicitud}.\n\nImporte original: {importe_original}\nPunitorios acumulados: {punitorios}\nTotal actualizado a la fecha: {total_actualizado}\n\nPor favor, realice su pago hoy para regularizar su situación. Link de pago: {link_pago}",
                'activo' => true,
            ],
            [
                'titulo' => 'Envío de Link de Pago',
                'categoria' => 'link_pago',
                'cuerpo' => "Hola {nombre}, a continuación le compartimos el link de pago oficial para saldar su saldo pendiente de {monto_pendiente} (Cuota {numero_cuota} - Solicitud #{numero_solicitud}):\n\n🔗 Link: {link_pago}\n\nUna vez realizado, por favor reenvíenos el comprobante de pago por este medio.",
                'activo' => true,
            ],
            [
                'titulo' => 'Registro de Promesa de Pago',
                'categoria' => 'promesa_pago',
                'cuerpo' => "Muchas gracias {nombre} por su compromiso. Hemos registrado su promesa de pago para el día {fecha_prometida} por un monto de {monto_prometido}.\n\nSu saldo pendiente actual de la cuota {numero_cuota} is {monto_pendiente}.\n\nQuedamos a la espera del comprobante en dicha fecha.",
                'activo' => true,
            ],
            [
                'titulo' => 'Seguimiento de Promesa de Pago',
                'categoria' => 'seguimiento_promesa',
                'cuerpo' => "Hola {nombre_completo}, nos contactamos para recordarle que hoy {fecha_prometida} vence su promesa de pago por un importe de {monto_prometido}.\n\nPor favor, recuerde enviarnos el comprobante para poder acreditarlo a su cuota {numero_cuota}. Link de pago: {link_pago}",
                'activo' => true,
            ],
            [
                'titulo' => 'Ofrecimiento de Cobrador',
                'categoria' => 'cobrador',
                'cuerpo' => "Estimado/a {nombre_completo}, coordinamos la visita del cobrador oficial {nombre_cobrador} para el día {fecha_visita} en su domicilio declarado: {domicilio}.\n\nEl cobrador se acercará para facilitarle el cobro de su cuota {numero_cuota} de forma presencial. El monto a cobrar es de {monto_pendiente}.\n\nPor favor, confirme su disponibilidad en el domicilio.",
                'activo' => true,
            ],
            [
                'titulo' => 'Contacto de Cliente Sin Respuesta',
                'categoria' => 'sin_respuesta',
                'cuerpo' => "Hola {nombre_completo}, hemos intentado comunicarnos con usted en reiteradas ocasiones al teléfono {telefono} respecto a su cuota {numero_cuota} de la solicitud #{numero_solicitud}.\n\nSaldo actual pendiente: {monto_pendiente}.\n\nLe solicitamos que se comunique a la brevedad para evitar recargos adicionales o la derivación de su caso a visita presencial.",
                'activo' => true,
            ],
            [
                'titulo' => 'Recordatorio de Pago Pendiente',
                'categoria' => 'recordatorio_pago',
                'cuerpo' => "Estimado/a {nombre_completo}, le recordamos que posee un pago pendiente de {monto_pendiente} correspondiente a la cuota {numero_cuota} de su solicitud #{numero_solicitud}.\n\nPuede abonar de manera virtual haciendo clic aquí: {link_pago}.\n\nSi ya realizó el pago, por favor desestime este mensaje.",
                'activo' => true,
            ],
            [
                'titulo' => 'Saldo Pendiente / Pago Parcial',
                'categoria' => 'pago_parcial',
                'cuerpo' => "Hola {nombre}, agradecemos el pago parcial realizado. Le informamos que aún resta un saldo pendiente de {monto_pendiente} para cancelar en su totalidad la cuota {numero_cuota} (Solicitud #{numero_solicitud}).\n\nPuede saldar la diferencia ingresando a: {link_pago}.\n\n¡Gracias por su colaboración!",
                'activo' => true,
            ],
        ];

        foreach ($plantillas as $p) {
            PlantillaMensaje::updateOrCreate(
                ['titulo' => $p['titulo']],
                $p
            );
        }
    }
}

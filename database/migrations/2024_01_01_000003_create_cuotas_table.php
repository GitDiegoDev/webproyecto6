<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones')->onDelete('restrict');
            $table->integer('numero_cuota');
            $table->tinyInteger('dia_cobro')->unsigned();
            $table->decimal('importe_original', 15, 2);
            $table->decimal('punitorios', 15, 2);
            $table->decimal('total_actualizado', 15, 2);
            $table->decimal('saldo_pendiente', 15, 2);
            $table->string('estado_financiero', 50); // pendiente, ausente_en_ultima_importacion, pago_realizado_oficial, cancelada
            $table->string('estado_gestion', 50); // sin_contactar, contactado, promesa_pendiente, promesa_incumplida, visita_solicitada, seguimiento, cobrado
            $table->string('prioridad', 50); // critica, alta, media, baja
            $table->string('link_pago', 500)->nullable();
            $table->timestamps();

            $table->unique(['operacion_id', 'numero_cuota']);
            $table->index('estado_financiero');
            $table->index('estado_gestion');
            $table->index('prioridad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas');
    }
};

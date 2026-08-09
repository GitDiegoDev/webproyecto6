<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuota_id')->constrained('cuotas')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->timestamp('fecha_hora');
            $table->string('tipo', 50); // whatsapp_enviado, whatsapp_respondido, llamada_realizada, no_respondio, etc.
            $table->string('resultado', 50); // no_atendio, prometio_pagar, solicito_cobrador, promesa_incumplida, seguimiento, cliente_pago, etc.
            $table->text('observacion')->nullable();
            $table->string('proxima_accion', 100)->nullable();
            $table->date('proxima_accion_fecha')->nullable();
            $table->timestamps();

            $table->index(['cuota_id', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestiones');
    }
};

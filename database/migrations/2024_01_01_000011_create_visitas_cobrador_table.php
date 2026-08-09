<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas_cobrador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('cuota_id')->nullable()->constrained('cuotas')->onDelete('restrict');
            $table->foreignId('cobrador_id')->constrained('users')->onDelete('restrict');
            $table->string('domicilio', 255); // Snapshot histórico
            $table->date('fecha_programada');
            $table->timestamp('fecha_realizada')->nullable();
            $table->string('estado', 50); // pendiente, realizada, cancelada
            $table->string('resultado', 50)->nullable(); // cobrado, cobrado_parcialmente, no_estaba, no_se_pudo_contactar, reprogramar, se_nego_a_pagar, domicilio_incorrecto
            $table->decimal('monto_cobrado', 15, 2)->default(0.00);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['cobrador_id', 'fecha_programada', 'estado'], 'visitas_cobrador_main_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas_cobrador');
    }
};

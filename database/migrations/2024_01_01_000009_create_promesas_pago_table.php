<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promesas_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuota_id')->constrained('cuotas')->onDelete('restrict');
            $table->foreignId('gestion_id')->nullable()->constrained('gestiones')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->timestamp('fecha_creacion');
            $table->date('fecha_prometida');
            $table->decimal('monto_prometido', 15, 2)->nullable();
            $table->string('estado', 50); // pendiente, cumplida, incumplida, cancelada
            $table->timestamp('fecha_resolucion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['fecha_prometida', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promesas_pago');
    }
};

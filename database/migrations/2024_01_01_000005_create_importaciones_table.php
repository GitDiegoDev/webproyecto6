<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_cobranza_id')->constrained('periodos_cobranza')->onDelete('restrict');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('fecha_hora');
            $table->string('nombre_archivo', 255);
            $table->string('tipo_archivo', 10); // csv, xlsx
            $table->integer('cantidad_registros');
            $table->integer('registros_nuevos');
            $table->integer('registros_actualizados');
            $table->integer('registros_ausentes');
            $table->integer('registros_errores');
            $table->string('estado', 50); // procesando, completada, fallida
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};

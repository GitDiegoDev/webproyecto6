<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacion_id')->constrained('importaciones')->onDelete('cascade');
            $table->integer('numero_linea');
            $table->string('accion', 50); // creado, actualizado_punitorios, actualizado_importe, marcado_ausente, error
            $table->text('detalles_error')->nullable();
            $table->text('datos_crudos')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_importaciones');
    }
};

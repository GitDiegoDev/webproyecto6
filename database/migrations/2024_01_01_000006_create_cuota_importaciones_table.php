<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuota_importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuota_id')->constrained('cuotas')->onDelete('cascade');
            $table->foreignId('importacion_id')->constrained('importaciones')->onDelete('cascade');
            $table->decimal('importe_original_observado', 15, 2);
            $table->decimal('punitorios_observados', 15, 2);
            $table->decimal('total_actualizado_observado', 15, 2);
            $table->tinyInteger('dia_cobro_observado')->nullable()->unsigned();
            $table->string('estado_presencia', 50); // presente, ausente_en_importacion
            $table->timestamps();

            $table->unique(['cuota_id', 'importacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuota_importaciones');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_cobranza', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('mes')->unsigned();
            $table->smallInteger('anio')->unsigned();
            $table->boolean('activo');
            $table->decimal('objetivo_monto', 15, 2);
            $table->timestamps();

            $table->unique(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_cobranza');
    }
};

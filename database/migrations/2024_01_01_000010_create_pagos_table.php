<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuota_id')->constrained('cuotas')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->timestamp('fecha_pago');
            $table->decimal('importe_original_snapshot', 15, 2);
            $table->decimal('punitorios_snapshot', 15, 2);
            $table->decimal('total_actualizado_snapshot', 15, 2);
            $table->decimal('monto_cobrado', 15, 2);
            $table->decimal('punitorios_perdonados', 15, 2);
            $table->boolean('es_cancelatorio');
            $table->string('medio_pago', 50); // efectivo, transferencia, cobrador, tarjeta
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};

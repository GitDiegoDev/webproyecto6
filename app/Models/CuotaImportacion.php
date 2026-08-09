<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuotaImportacion extends Model
{
    use HasFactory;

    protected $table = 'cuota_importaciones';

    protected $fillable = [
        'cuota_id',
        'importacion_id',
        'importe_original_observado',
        'punitorios_observados',
        'total_actualizado_observado',
        'dia_cobro_observado',
        'estado_presencia',
    ];

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class, 'cuota_id');
    }

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(Importacion::class, 'importacion_id');
    }
}

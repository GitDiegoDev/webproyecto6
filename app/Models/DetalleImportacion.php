<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleImportacion extends Model
{
    use HasFactory;

    protected $table = 'detalle_importaciones';

    // No default timestamps for detailing log (only created_at as timestamp)
    public $timestamps = false;

    protected $fillable = [
        'importacion_id',
        'numero_linea',
        'accion',
        'detalles_error',
        'datos_crudos',
        'created_at',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(Importacion::class, 'importacion_id');
    }
}

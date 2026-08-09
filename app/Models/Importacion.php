<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Importacion extends Model
{
    use HasFactory;

    protected $table = 'importaciones';

    protected $fillable = [
        'periodo_cobranza_id',
        'user_id',
        'fecha_hora',
        'nombre_archivo',
        'tipo_archivo',
        'cantidad_registros',
        'registros_nuevos',
        'registros_actualizados',
        'registros_ausentes',
        'registros_errores',
        'estado',
    ];

    public function periodoCobranza(): BelongsTo
    {
        return $this->belongsTo(PeriodoCobranza::class, 'periodo_cobranza_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cuotaImportaciones(): HasMany
    {
        return $this->hasMany(CuotaImportacion::class, 'importacion_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleImportacion::class, 'importacion_id');
    }
}

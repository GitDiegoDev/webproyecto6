<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoCobranza extends Model
{
    use HasFactory;

    protected $table = 'periodos_cobranza';

    protected $fillable = [
        'nombre',
        'mes',
        'anio',
        'activo',
        'objetivo_monto',
    ];

    public function importaciones(): HasMany
    {
        return $this->hasMany(Importacion::class, 'periodo_cobranza_id');
    }
}

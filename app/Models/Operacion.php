<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operacion extends Model
{
    use HasFactory;

    protected $table = 'operaciones';

    protected $fillable = [
        'cliente_id',
        'numero_solicitud',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class, 'operacion_id');
    }
}

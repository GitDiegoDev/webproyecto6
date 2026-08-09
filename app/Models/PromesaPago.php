<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromesaPago extends Model
{
    use HasFactory;

    protected $table = 'promesas_pago';

    protected $fillable = [
        'cuota_id',
        'gestion_id',
        'user_id',
        'fecha_creacion',
        'fecha_prometida',
        'monto_prometido',
        'estado',
        'fecha_resolucion',
        'observaciones',
    ];

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class, 'cuota_id');
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class, 'gestion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Gestion extends Model
{
    use HasFactory;

    protected $table = 'gestiones';

    protected $fillable = [
        'cuota_id',
        'user_id',
        'fecha_hora',
        'tipo',
        'resultado',
        'observacion',
        'proxima_accion',
        'proxima_accion_fecha',
    ];

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class, 'cuota_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function promesaPago(): HasOne
    {
        return $this->hasOne(PromesaPago::class, 'gestion_id');
    }
}

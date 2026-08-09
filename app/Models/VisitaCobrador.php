<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitaCobrador extends Model
{
    use HasFactory;

    protected $table = 'visitas_cobrador';

    protected $fillable = [
        'cliente_id',
        'cuota_id',
        'cobrador_id',
        'domicilio',
        'fecha_programada',
        'fecha_realizada',
        'estado',
        'resultado',
        'monto_cobrado',
        'observaciones',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class, 'cuota_id');
    }

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cobrador_id');
    }
}

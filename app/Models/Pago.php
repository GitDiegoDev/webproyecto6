<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'cuota_id',
        'user_id',
        'fecha_pago',
        'importe_original_snapshot',
        'punitorios_snapshot',
        'total_actualizado_snapshot',
        'monto_cobrado',
        'punitorios_perdonados',
        'es_cancelatorio',
        'medio_pago',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'es_cancelatorio' => 'boolean',
        ];
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class, 'cuota_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

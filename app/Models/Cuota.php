<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuota extends Model
{
    use HasFactory;

    protected $table = 'cuotas';

    protected $fillable = [
        'operacion_id',
        'numero_cuota',
        'dia_cobro',
        'importe_original',
        'punitorios',
        'total_actualizado',
        'saldo_pendiente',
        'estado_financiero',
        'estado_gestion',
        'prioridad',
        'link_pago',
    ];

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class, 'operacion_id');
    }

    public function gestiones(): HasMany
    {
        return $this->hasMany(Gestion::class, 'cuota_id');
    }

    public function promesasPago(): HasMany
    {
        return $this->hasMany(PromesaPago::class, 'cuota_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'cuota_id');
    }

    public function cuotaImportaciones(): HasMany
    {
        return $this->hasMany(CuotaImportacion::class, 'cuota_id');
    }

    public function visitasCobrador(): HasMany
    {
        return $this->hasMany(VisitaCobrador::class, 'cuota_id');
    }
}

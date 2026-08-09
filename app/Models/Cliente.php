<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'documento',
        'telefono',
        'domicilio',
        'email',
        'codigo_cliente_oficial',
    ];

    public function operaciones(): HasMany
    {
        return $this->hasMany(Operacion::class, 'cliente_id');
    }

    public function visitasCobrador(): HasMany
    {
        return $this->hasMany(VisitaCobrador::class, 'cliente_id');
    }
}

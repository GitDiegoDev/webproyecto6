<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantillaMensaje extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plantillas_mensajes';

    protected $fillable = [
        'titulo',
        'categoria',
        'cuerpo',
        'activo',
    ];
}

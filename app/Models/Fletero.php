<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fletero extends Model
{
    /** @use HasFactory<\Database\Factories\FleteroFactory> */
    use HasFactory;

    protected $fillable = [
        'numero_fletero',
        'razon_social',
        'direccion',
        'cuit',
        'dni',
        'telefono',
        'contacto',
        'nacionalidad',
    ];
}

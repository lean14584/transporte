<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transporte extends Model
{
    /** @use HasFactory<\Database\Factories\TransporteFactory> */
    use HasFactory;

    protected $fillable = [
        'numero_transporte',
        'nombre',
        'direccion',
        'telefono',
        'dueno',
        'cuit',
        'nacionalidad',
    ];

    public function guias()
    {
        return $this->hasMany(Guia::class);
    }

    public function ordenesPago()
    {
        return $this->hasMany(OrdenPago::class);
    }
}

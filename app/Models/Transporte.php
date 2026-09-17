<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transporte extends Model
{
    /** @use HasFactory<\Database\Factories\TransporteFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'dueno',
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

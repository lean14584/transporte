<?php

namespace App\Models;

use App\Models\Concerns\RegistraAutoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transporte extends Model
{
    /** @use HasFactory<\Database\Factories\TransporteFactory> */
    use HasFactory;
    use RegistraAutoria;

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
}

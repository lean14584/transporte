<?php

namespace App\Models;

use App\Models\Concerns\RegistraAutoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    /** @use HasFactory<\Database\Factories\ClienteFactory> */
    use HasFactory;
    use RegistraAutoria;

    protected $fillable = [
        'numero_cliente',
        'nombre',
        'razon_social',
        'direccion',
        'telefono',
        'dni',
        'cuit',
        'contacto',
        'nacionalidad',
        'condicion_iva',
    ];

    protected function casts(): array
    {
        return [
            'saldo_cta_cte' => 'decimal:2',
        ];
    }

    public function guias()
    {
        return $this->hasMany(Guia::class);
    }

    public function recibos()
    {
        return $this->hasMany(Recibo::class);
    }

    public function movimientosCtaCte()
    {
        return $this->hasMany(CuentaCorrienteMovimiento::class);
    }
}

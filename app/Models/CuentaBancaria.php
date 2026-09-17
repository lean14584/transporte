<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaBancaria extends Model
{
    /** @use HasFactory<\Database\Factories\CuentaBancariaFactory> */
    use HasFactory;

    protected $fillable = [
        'banco',
        'numero_cuenta',
        'alias_cbu',
        'saldo',
    ];

    protected function casts(): array
    {
        return [
            'saldo' => 'decimal:2',
        ];
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }
}

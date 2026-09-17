<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cheque extends Model
{
    /** @use HasFactory<\Database\Factories\ChequeFactory> */
    use HasFactory;

    protected $fillable = [
        'cuenta_bancaria_id',
        'numero',
        'banco',
        'beneficiario',
        'fecha_emision',
        'fecha_pago',
        'monto',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_pago' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function cuentaBancaria()
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    public function ordenPago()
    {
        return $this->hasOne(OrdenPago::class);
    }

    /**
     * Emite el cheque contra la cuenta bancaria, descontando el monto del saldo disponible.
     */
    public static function emitir(CuentaBancaria $cuentaBancaria, array $datos): self
    {
        return DB::transaction(function () use ($cuentaBancaria, $datos) {
            $cuentaBloqueada = CuentaBancaria::whereKey($cuentaBancaria->id)->lockForUpdate()->first();

            $cheque = self::create([
                'cuenta_bancaria_id' => $cuentaBloqueada->id,
                'numero' => $datos['numero'],
                'banco' => $datos['banco'],
                'beneficiario' => $datos['beneficiario'],
                'fecha_emision' => $datos['fecha_emision'] ?? now()->toDateString(),
                'fecha_pago' => $datos['fecha_pago'],
                'monto' => $datos['monto'],
                'estado' => 'emitido',
            ]);

            $cuentaBloqueada->update([
                'saldo' => $cuentaBloqueada->saldo - $datos['monto'],
            ]);

            return $cheque;
        });
    }
}

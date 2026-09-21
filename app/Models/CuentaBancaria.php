<?php

namespace App\Models;

use App\Models\Concerns\RegistraAutoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaBancaria extends Model
{
    /** @use HasFactory<\Database\Factories\CuentaBancariaFactory> */
    use HasFactory;
    use RegistraAutoria;

    protected $fillable = [
        'banco',
        'numero_cuenta',
        'alias_cbu',
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

    /**
     * Crea una cuenta bancaria con su saldo inicial. El saldo queda fuera de
     * $fillable a propósito: después de abierta, solo Cheque::emitir()/
     * rechazar() pueden moverlo, nunca un update() directo desde un form.
     */
    public static function abrir(array $datos): self
    {
        $cuenta = new self($datos);
        $cuenta->saldo = $datos['saldo'] ?? 0;
        $cuenta->save();

        return $cuenta;
    }

    /**
     * Actualiza los datos de la cuenta sin tocar el saldo (no es editable a mano).
     */
    public function actualizarDatos(array $datos): void
    {
        $this->update([
            'banco' => $datos['banco'],
            'numero_cuenta' => $datos['numero_cuenta'] ?? null,
            'alias_cbu' => $datos['alias_cbu'] ?? null,
        ]);
    }
}

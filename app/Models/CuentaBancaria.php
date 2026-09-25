<?php

namespace App\Models;

use App\Models\Concerns\RegistraAutoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

    public function movimientos()
    {
        return $this->hasMany(CuentaBancariaMovimiento::class);
    }

    /**
     * Crea una cuenta bancaria con su saldo inicial. El saldo queda fuera de
     * $fillable a propósito: después de abierta, solo Cheque::emitir()/
     * rechazar() o un ajuste manual (vía CuentaBancariaMovimiento::registrar())
     * pueden moverlo, nunca un update() directo desde un form.
     *
     * El saldo inicial se asigna directo (no vía registrar()) para permitir
     * abrir con un saldo negativo sin chocar contra el resguardo de "egreso
     * deja saldo negativo" que sí aplica a los movimientos posteriores.
     */
    public static function abrir(array $datos): self
    {
        $cuenta = new self($datos);
        $cuenta->saldo = $datos['saldo'] ?? 0;
        $cuenta->save();

        $saldoInicial = (float) ($datos['saldo'] ?? 0);

        if ($saldoInicial !== 0.0) {
            CuentaBancariaMovimiento::create([
                'cuenta_bancaria_id' => $cuenta->id,
                'fecha' => now()->toDateString(),
                'tipo' => $saldoInicial > 0 ? 'ingreso' : 'egreso',
                'concepto' => 'Apertura de cuenta',
                'monto' => abs($saldoInicial),
                'saldo' => $saldoInicial,
                'usuario_id' => Auth::id(),
            ]);
        }

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

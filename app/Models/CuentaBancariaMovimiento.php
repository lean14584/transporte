<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CuentaBancariaMovimiento extends Model
{
    /** @use HasFactory<\Database\Factories\CuentaBancariaMovimientoFactory> */
    use HasFactory;

    protected $fillable = [
        'cuenta_bancaria_id',
        'fecha',
        'tipo',
        'concepto',
        'monto',
        'saldo',
        'cheque_id',
        'revierte_movimiento_id',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
            'saldo' => 'decimal:2',
        ];
    }

    public function cuentaBancaria()
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    /**
     * Si este movimiento es la reversión de otro (ej. un cheque rechazado),
     * el movimiento original que revierte.
     */
    public function revierteMovimiento()
    {
        return $this->belongsTo(self::class, 'revierte_movimiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Registra un movimiento de la cuenta bancaria y actualiza su saldo,
     * bloqueando la fila de la cuenta para evitar carreras entre movimientos concurrentes.
     */
    public static function registrar(CuentaBancaria $cuentaBancaria, string $tipo, string $concepto, float $monto, array $extra = []): self
    {
        return DB::transaction(function () use ($cuentaBancaria, $tipo, $concepto, $monto, $extra) {
            $cuentaBloqueada = CuentaBancaria::whereKey($cuentaBancaria->id)->lockForUpdate()->first();

            $signo = $tipo === 'ingreso' ? 1 : -1;
            $nuevoSaldo = (float) $cuentaBloqueada->saldo + ($signo * $monto);

            if ($tipo === 'egreso' && $nuevoSaldo < 0) {
                throw new \RuntimeException('Saldo insuficiente en la cuenta bancaria.');
            }

            $movimiento = self::create([
                'cuenta_bancaria_id' => $cuentaBloqueada->id,
                'fecha' => $extra['fecha'] ?? now()->toDateString(),
                'tipo' => $tipo,
                'concepto' => $concepto,
                'monto' => $monto,
                'saldo' => $nuevoSaldo,
                'cheque_id' => $extra['cheque_id'] ?? null,
                'revierte_movimiento_id' => $extra['revierte_movimiento_id'] ?? null,
                'usuario_id' => Auth::id(),
            ]);

            $cuentaBloqueada->forceFill(['saldo' => $nuevoSaldo])->save();

            return $movimiento;
        });
    }
}

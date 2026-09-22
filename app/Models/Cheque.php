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
        if ($datos['monto'] <= 0) {
            throw new \RuntimeException('El monto del cheque debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($cuentaBancaria, $datos) {
            $cuentaBloqueada = CuentaBancaria::whereKey($cuentaBancaria->id)->lockForUpdate()->first();

            $nuevoSaldo = $cuentaBloqueada->saldo - $datos['monto'];

            if ($nuevoSaldo < 0) {
                throw new \RuntimeException('Saldo insuficiente en la cuenta bancaria para emitir este cheque.');
            }

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

            $cuentaBloqueada->forceFill(['saldo' => $nuevoSaldo])->save();

            return $cheque;
        });
    }

    /**
     * Marca el cheque como cobrado. Solo válido desde el estado "emitido"
     * (evita transiciones repetidas por doble clic o llamadas directas).
     */
    public static function cobrar(self $cheque): self
    {
        return DB::transaction(function () use ($cheque) {
            $chequeBloqueado = self::whereKey($cheque->id)->lockForUpdate()->first();

            if ($chequeBloqueado->estado !== 'emitido') {
                throw new \RuntimeException('Solo se puede marcar como cobrado un cheque emitido.');
            }

            $chequeBloqueado->update(['estado' => 'cobrado']);

            return $chequeBloqueado;
        });
    }

    /**
     * Marca el cheque como rechazado y devuelve el monto a la cuenta bancaria
     * (el débito de emitir() nunca se hizo efectivo en el banco). Si el cheque
     * corresponde a una orden de pago a un fletero, revierte también el pago
     * en su cta cte: la empresa vuelve a deberle ese monto, porque el fletero
     * nunca cobró de verdad.
     */
    public static function rechazar(self $cheque): self
    {
        return DB::transaction(function () use ($cheque) {
            $chequeBloqueado = self::whereKey($cheque->id)->lockForUpdate()->first();

            if ($chequeBloqueado->estado !== 'emitido') {
                throw new \RuntimeException('Solo se puede rechazar un cheque emitido.');
            }

            $chequeBloqueado->update(['estado' => 'rechazado']);

            $cuentaBloqueada = CuentaBancaria::whereKey($chequeBloqueado->cuenta_bancaria_id)->lockForUpdate()->first();
            $cuentaBloqueada->forceFill(['saldo' => $cuentaBloqueada->saldo + $chequeBloqueado->monto])->save();

            $ordenPago = $chequeBloqueado->ordenPago;

            if ($ordenPago !== null) {
                // Sin 'orden_pago_id': esa columna es unique (protege contra
                // doble-submit del pago original) y esta reversión es un
                // segundo movimiento sobre la misma orden. El enlace
                // estructurado al movimiento original va por
                // 'revierte_movimiento_id' en su lugar.
                $movimientoOriginal = FleteroCuentaCorrienteMovimiento::where('orden_pago_id', $ordenPago->id)->first();

                FleteroCuentaCorrienteMovimiento::registrar(
                    $ordenPago->fletero,
                    'cargo',
                    "Cheque {$chequeBloqueado->numero} rechazado - revierte {$ordenPago->folio()}",
                    (float) $chequeBloqueado->monto,
                    ['revierte_movimiento_id' => $movimientoOriginal?->id],
                );
            }

            return $chequeBloqueado;
        });
    }
}

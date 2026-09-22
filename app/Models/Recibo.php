<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Recibo extends Model
{
    /** @use HasFactory<\Database\Factories\ReciboFactory> */
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'fecha',
        'monto',
        'concepto',
        'medio_pago',
        'estado',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function movimientoCtaCte()
    {
        return $this->hasOne(CuentaCorrienteMovimiento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function folio(): string
    {
        return 'R-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Registra el cobro: crea el recibo y descuenta el pago de la cta cte del cliente.
     */
    public static function cobrar(Cliente $cliente, float $monto, string $medioPago, ?string $concepto = null): self
    {
        return DB::transaction(function () use ($cliente, $monto, $medioPago, $concepto) {
            $recibo = self::create([
                'cliente_id' => $cliente->id,
                'fecha' => now()->toDateString(),
                'monto' => $monto,
                'concepto' => $concepto,
                'medio_pago' => $medioPago,
                'usuario_id' => Auth::id(),
            ]);

            CuentaCorrienteMovimiento::registrar(
                $cliente,
                'pago',
                $concepto ?: "Recibo {$recibo->folio()}",
                $monto,
                ['recibo_id' => $recibo->id],
            );

            return $recibo;
        });
    }

    /**
     * Marca un cobro hecho con cheque como rechazado y revierte el pago: la
     * deuda vuelve a la cta cte del cliente, porque el cheque nunca se hizo
     * efectivo. Solo válido para recibos con medio_pago 'cheque' que todavía
     * estén confirmados (evita revertir dos veces por doble clic).
     */
    public static function rechazarCheque(self $recibo): self
    {
        return DB::transaction(function () use ($recibo) {
            $reciboBloqueado = self::whereKey($recibo->id)->lockForUpdate()->first();

            if ($reciboBloqueado->medio_pago !== 'cheque') {
                throw new \RuntimeException('Solo se puede rechazar un cobro hecho con cheque.');
            }

            if ($reciboBloqueado->estado !== 'confirmado') {
                throw new \RuntimeException('Este recibo ya fue marcado como rechazado.');
            }

            $reciboBloqueado->update(['estado' => 'rechazado']);

            // Sin 'recibo_id': esa columna es unique (protege contra
            // doble-submit del cobro original) y esta reversión es un
            // segundo movimiento sobre el mismo recibo. La referencia
            // queda en el concepto.
            CuentaCorrienteMovimiento::registrar(
                $reciboBloqueado->cliente,
                'cargo',
                "Cheque rechazado - Recibo {$reciboBloqueado->folio()}",
                (float) $reciboBloqueado->monto,
            );

            return $reciboBloqueado;
        });
    }
}

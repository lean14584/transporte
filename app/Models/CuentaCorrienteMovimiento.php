<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CuentaCorrienteMovimiento extends Model
{
    /** @use HasFactory<\Database\Factories\CuentaCorrienteMovimientoFactory> */
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'fecha',
        'tipo',
        'concepto',
        'monto',
        'saldo',
        'guia_id',
        'recibo_id',
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

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function guia()
    {
        return $this->belongsTo(Guia::class);
    }

    public function recibo()
    {
        return $this->belongsTo(Recibo::class);
    }

    /**
     * Registra un movimiento de cta cte y actualiza el saldo del cliente,
     * bloqueando la fila del cliente para evitar carreras entre movimientos concurrentes.
     */
    public static function registrar(Cliente $cliente, string $tipo, string $concepto, float $monto, array $extra = []): self
    {
        return DB::transaction(function () use ($cliente, $tipo, $concepto, $monto, $extra) {
            $clienteBloqueado = Cliente::whereKey($cliente->id)->lockForUpdate()->first();

            $signo = $tipo === 'cargo' ? 1 : -1;
            $nuevoSaldo = (float) $clienteBloqueado->saldo_cta_cte + ($signo * $monto);

            $movimiento = self::create([
                'cliente_id' => $clienteBloqueado->id,
                'fecha' => $extra['fecha'] ?? now()->toDateString(),
                'tipo' => $tipo,
                'concepto' => $concepto,
                'monto' => $monto,
                'saldo' => $nuevoSaldo,
                'guia_id' => $extra['guia_id'] ?? null,
                'recibo_id' => $extra['recibo_id'] ?? null,
                'usuario_id' => Auth::id(),
            ]);

            $clienteBloqueado->update(['saldo_cta_cte' => $nuevoSaldo]);

            return $movimiento;
        });
    }
}

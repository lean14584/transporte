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
                $concepto ?: "Recibo Nº{$recibo->id}",
                $monto,
                ['recibo_id' => $recibo->id],
            );

            return $recibo;
        });
    }
}

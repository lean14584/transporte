<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PagoFletero extends Model
{
    /** @use HasFactory<\Database\Factories\PagoFleteroFactory> */
    use HasFactory;

    protected $fillable = [
        'fletero_id',
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

    public function fletero()
    {
        return $this->belongsTo(Fletero::class);
    }

    public function movimientoCtaCte()
    {
        return $this->hasOne(FleteroCuentaCorrienteMovimiento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function folio(): string
    {
        return 'PF-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Registra el pago: crea el pago y descuenta el monto de la cta cte del fletero.
     */
    public static function pagar(Fletero $fletero, float $monto, string $medioPago, ?string $concepto = null): self
    {
        return DB::transaction(function () use ($fletero, $monto, $medioPago, $concepto) {
            $pago = self::create([
                'fletero_id' => $fletero->id,
                'fecha' => now()->toDateString(),
                'monto' => $monto,
                'concepto' => $concepto,
                'medio_pago' => $medioPago,
                'usuario_id' => Auth::id(),
            ]);

            FleteroCuentaCorrienteMovimiento::registrar(
                $fletero,
                'pago',
                $concepto ?: "Pago {$pago->folio()}",
                $monto,
                ['pago_fletero_id' => $pago->id],
            );

            return $pago;
        });
    }
}

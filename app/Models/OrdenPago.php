<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrdenPago extends Model
{
    /** @use HasFactory<\Database\Factories\OrdenPagoFactory> */
    use HasFactory;

    protected $fillable = [
        'fletero_id',
        'fecha',
        'monto',
        'concepto',
        'medio_pago',
        'cheque_id',
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

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
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
        return 'OP-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Emite la orden de pago y descuenta el monto de la cta cte del fletero
     * (lo que la empresa le debe baja en la misma medida que se le paga).
     */
    public static function emitir(Fletero $fletero, float $monto, string $medioPago, ?string $concepto = null, ?int $chequeId = null): self
    {
        return DB::transaction(function () use ($fletero, $monto, $medioPago, $concepto, $chequeId) {
            $orden = self::create([
                'fletero_id' => $fletero->id,
                'fecha' => now()->toDateString(),
                'monto' => $monto,
                'concepto' => $concepto,
                'medio_pago' => $medioPago,
                'cheque_id' => $chequeId,
            ]);

            FleteroCuentaCorrienteMovimiento::registrar(
                $fletero,
                'pago',
                $concepto ?: "Orden de pago {$orden->folio()}",
                $monto,
                ['orden_pago_id' => $orden->id],
            );

            return $orden;
        });
    }

    protected static function booted(): void
    {
        static::creating(function (self $ordenPago) {
            $ordenPago->usuario_id ??= Auth::id();
        });
    }
}

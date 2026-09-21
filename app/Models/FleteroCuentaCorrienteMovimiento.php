<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FleteroCuentaCorrienteMovimiento extends Model
{
    /** @use HasFactory<\Database\Factories\FleteroCuentaCorrienteMovimientoFactory> */
    use HasFactory;

    protected $fillable = [
        'fletero_id',
        'fecha',
        'tipo',
        'concepto',
        'monto',
        'saldo',
        'guia_id',
        'orden_pago_id',
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

    public function fletero()
    {
        return $this->belongsTo(Fletero::class);
    }

    public function guia()
    {
        return $this->belongsTo(Guia::class);
    }

    public function ordenPago()
    {
        return $this->belongsTo(OrdenPago::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Registra un movimiento de cta cte del fletero y actualiza su saldo,
     * bloqueando la fila del fletero para evitar carreras entre movimientos
     * concurrentes (mismo patrón que CuentaCorrienteMovimiento::registrar).
     */
    public static function registrar(Fletero $fletero, string $tipo, string $concepto, float $monto, array $extra = []): self
    {
        return DB::transaction(function () use ($fletero, $tipo, $concepto, $monto, $extra) {
            $fleteroBloqueado = Fletero::whereKey($fletero->id)->lockForUpdate()->first();

            $signo = $tipo === 'cargo' ? 1 : -1;
            $nuevoSaldo = (float) $fleteroBloqueado->saldo_cta_cte + ($signo * $monto);

            $movimiento = self::create([
                'fletero_id' => $fleteroBloqueado->id,
                'fecha' => $extra['fecha'] ?? now()->toDateString(),
                'tipo' => $tipo,
                'concepto' => $concepto,
                'monto' => $monto,
                'saldo' => $nuevoSaldo,
                'guia_id' => $extra['guia_id'] ?? null,
                'orden_pago_id' => $extra['orden_pago_id'] ?? null,
                'usuario_id' => Auth::id(),
            ]);

            $fleteroBloqueado->forceFill(['saldo_cta_cte' => $nuevoSaldo])->save();

            return $movimiento;
        });
    }
}

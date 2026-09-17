<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Guia extends Model
{
    /** @use HasFactory<\Database\Factories\GuiaFactory> */
    use HasFactory;

    protected $fillable = [
        'transporte_id',
        'cliente_id',
        'transportista',
        'camion_marca',
        'camion_modelo',
        'camion_patente',
        'camion_color',
        'mercaderia',
        'cantidad',
        'unidad',
        'fecha_carga',
        'estado',
        'pesaje_bruto',
        'tara',
        'neto',
        'porcentaje_ganancia',
        'monto_ganancia',
        'fecha_recepcion',
        'usuario_carga_id',
        'usuario_recepcion_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_carga' => 'date',
            'fecha_recepcion' => 'date',
            'cantidad' => 'decimal:2',
            'pesaje_bruto' => 'decimal:2',
            'tara' => 'decimal:2',
            'neto' => 'decimal:2',
            'porcentaje_ganancia' => 'decimal:2',
            'monto_ganancia' => 'decimal:2',
        ];
    }

    public function transporte()
    {
        return $this->belongsTo(Transporte::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function movimientoCtaCte()
    {
        return $this->hasOne(CuentaCorrienteMovimiento::class);
    }

    public function folio(): string
    {
        return 'G-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Cierra la recepción: calcula neto (bruto - tara) y la ganancia sobre el neto,
     * y carga esa ganancia como saldo en la cta cte del cliente.
     */
    public function recepcionar(float $pesajeBruto, float $tara, float $porcentajeGanancia): void
    {
        DB::transaction(function () use ($pesajeBruto, $tara, $porcentajeGanancia) {
            $neto = $pesajeBruto - $tara;
            $montoGanancia = round($neto * $porcentajeGanancia / 100, 2);

            $this->update([
                'pesaje_bruto' => $pesajeBruto,
                'tara' => $tara,
                'neto' => $neto,
                'porcentaje_ganancia' => $porcentajeGanancia,
                'monto_ganancia' => $montoGanancia,
                'estado' => 'recibida',
                'fecha_recepcion' => now()->toDateString(),
                'usuario_recepcion_id' => Auth::id(),
            ]);

            CuentaCorrienteMovimiento::registrar(
                $this->cliente,
                'cargo',
                "Guía {$this->folio()} - flete {$this->mercaderia}",
                $montoGanancia,
                ['guia_id' => $this->id],
            );
        });
    }
}

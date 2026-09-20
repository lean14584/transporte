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
        'fletero_id',
        'producto_id',
        'transportista',
        'transportista_documento',
        'camion_marca',
        'camion_modelo',
        'camion_patente',
        'camion_color',
        'mercaderia',
        'cantidad',
        'tn_cargadas',
        'tn_cliente',
        'tn_fletero',
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
            'tn_cargadas' => 'decimal:2',
            'tn_cliente' => 'decimal:2',
            'tn_fletero' => 'decimal:2',
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

    public function fletero()
    {
        return $this->belongsTo(Fletero::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function movimientoCtaCte()
    {
        return $this->hasOne(CuentaCorrienteMovimiento::class);
    }

    public function usuarioCarga()
    {
        return $this->belongsTo(User::class, 'usuario_carga_id');
    }

    public function usuarioRecepcion()
    {
        return $this->belongsTo(User::class, 'usuario_recepcion_id');
    }

    public function folio(): string
    {
        return 'G-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Cierra la recepción de una guía por kilos: calcula neto (bruto - tara)
     * y la ganancia sobre el neto, y la carga como saldo en la cta cte del cliente.
     */
    public function recepcionar(float $pesajeBruto, float $tara, float $porcentajeGanancia): void
    {
        if ($pesajeBruto <= $tara) {
            throw new \InvalidArgumentException('El peso bruto debe ser mayor que la tara.');
        }

        $this->finalizarRecepcion(
            neto: $pesajeBruto - $tara,
            porcentajeGanancia: $porcentajeGanancia,
            pesajeBruto: $pesajeBruto,
            tara: $tara,
        );
    }

    /**
     * Cierra la recepción de una guía por unidades: la cantidad recibida
     * (puede diferir de la declarada al cargar) es directamente el neto.
     */
    public function recepcionarPorCantidad(float $cantidadRecibida, float $porcentajeGanancia): void
    {
        $this->finalizarRecepcion(
            neto: $cantidadRecibida,
            porcentajeGanancia: $porcentajeGanancia,
        );
    }

    private function finalizarRecepcion(float $neto, float $porcentajeGanancia, ?float $pesajeBruto = null, ?float $tara = null): void
    {
        DB::transaction(function () use ($neto, $porcentajeGanancia, $pesajeBruto, $tara) {
            $guiaBloqueada = self::whereKey($this->id)->lockForUpdate()->first();

            if ($guiaBloqueada->estado === 'recibida') {
                throw new \RuntimeException('Esta guía ya fue recepcionada.');
            }

            $montoGanancia = round($neto * $porcentajeGanancia / 100, 2);

            $guiaBloqueada->update([
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
                $guiaBloqueada->cliente,
                'cargo',
                "Guía {$guiaBloqueada->folio()} - flete {$guiaBloqueada->mercaderia}",
                $montoGanancia,
                ['guia_id' => $guiaBloqueada->id],
            );
        });

        $this->refresh();
    }
}

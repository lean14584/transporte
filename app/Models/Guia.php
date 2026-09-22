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
        'precio_cliente',
        'precio_fletero',
        'unidad',
        'fecha_carga',
        'estado',
        'pesaje_bruto',
        'tara',
        'neto',
        'porcentaje_ganancia',
        'monto_ganancia',
        'total_cliente',
        'total_fletero',
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
            'precio_cliente' => 'decimal:2',
            'precio_fletero' => 'decimal:2',
            'pesaje_bruto' => 'decimal:2',
            'tara' => 'decimal:2',
            'neto' => 'decimal:2',
            'porcentaje_ganancia' => 'decimal:2',
            'monto_ganancia' => 'decimal:2',
            'total_cliente' => 'decimal:2',
            'total_fletero' => 'decimal:2',
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

    public function movimientoCtaCteFletero()
    {
        return $this->hasOne(FleteroCuentaCorrienteMovimiento::class);
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
     * La carga "por kilos" en realidad se pesa y se factura en toneladas (TN Cargadas,
     * TN Descargadas), no en kilogramos — 'kilos' es solo el valor interno histórico
     * de la columna `unidad`. Esta etiqueta evita mostrarle "kilos" al usuario.
     */
    public function unidadLabel(): string
    {
        return $this->unidad === 'kilos' ? 'TN' : 'unidades';
    }

    /**
     * Cierra la recepción de una guía por kilos: TN descargadas, total cliente y
     * total fletero se cargan a mano (números reales de esa guía). El total cliente
     * se carga como "cargo" en la cta cte del cliente, y el total fletero como
     * "cargo" en la cta cte del fletero (lo que la empresa le debe por este flete).
     * El pesaje bruto/tara son opcionales, solo quedan como referencia si se informan.
     */
    public function recepcionar(float $totalDescargado, float $totalCliente, float $totalFletero, ?float $pesajeBruto = null, ?float $tara = null): void
    {
        if ($pesajeBruto !== null && $tara !== null && $pesajeBruto <= $tara) {
            throw new \InvalidArgumentException('El peso bruto debe ser mayor que la tara.');
        }

        DB::transaction(function () use ($totalDescargado, $totalCliente, $totalFletero, $pesajeBruto, $tara) {
            $guiaBloqueada = $this->lockParaRecepcion();

            $guiaBloqueada->update([
                'pesaje_bruto' => $pesajeBruto,
                'tara' => $tara,
                'neto' => $totalDescargado,
                'total_cliente' => $totalCliente,
                'total_fletero' => $totalFletero,
                'estado' => 'recibida',
                'fecha_recepcion' => now()->toDateString(),
                'usuario_recepcion_id' => Auth::id(),
            ]);

            CuentaCorrienteMovimiento::registrar(
                $guiaBloqueada->cliente,
                'cargo',
                "Guía {$guiaBloqueada->folio()} - flete {$guiaBloqueada->mercaderia}",
                $totalCliente,
                ['guia_id' => $guiaBloqueada->id],
            );

            if ($guiaBloqueada->fletero_id !== null) {
                FleteroCuentaCorrienteMovimiento::registrar(
                    $guiaBloqueada->fletero,
                    'cargo',
                    "Guía {$guiaBloqueada->folio()} - flete {$guiaBloqueada->mercaderia}",
                    $totalFletero,
                    ['guia_id' => $guiaBloqueada->id],
                );
            }
        });

        $this->refresh();
    }

    /**
     * Cierra la recepción de una guía por unidades: la cantidad recibida
     * (puede diferir de la declarada al cargar) es directamente el neto,
     * y la ganancia se calcula sobre un porcentaje configurado y se carga
     * a la cta cte del cliente. El monto a pagar al fletero se carga a mano
     * (no hay un valor por unidad configurado, a diferencia del flujo por
     * kilos) y se carga a su cta cte, igual que en recepcionar().
     */
    public function recepcionarPorCantidad(float $cantidadRecibida, float $porcentajeGanancia, float $montoFletero): void
    {
        $montoGanancia = round($cantidadRecibida * $porcentajeGanancia / 100, 2);

        DB::transaction(function () use ($cantidadRecibida, $porcentajeGanancia, $montoGanancia, $montoFletero) {
            $guiaBloqueada = $this->lockParaRecepcion();

            $guiaBloqueada->update([
                'neto' => $cantidadRecibida,
                'porcentaje_ganancia' => $porcentajeGanancia,
                'monto_ganancia' => $montoGanancia,
                'total_fletero' => $montoFletero,
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

            if ($guiaBloqueada->fletero_id !== null) {
                FleteroCuentaCorrienteMovimiento::registrar(
                    $guiaBloqueada->fletero,
                    'cargo',
                    "Guía {$guiaBloqueada->folio()} - flete {$guiaBloqueada->mercaderia}",
                    $montoFletero,
                    ['guia_id' => $guiaBloqueada->id],
                );
            }
        });

        $this->refresh();
    }

    /**
     * Bloquea la fila de la guía dentro de la transacción activa y verifica que
     * todavía no haya sido recepcionada. Debe llamarse dentro de un DB::transaction.
     */
    private function lockParaRecepcion(): self
    {
        $guiaBloqueada = self::whereKey($this->id)->lockForUpdate()->first();

        if ($guiaBloqueada->estado === 'recibida') {
            throw new \RuntimeException('Esta guía ya fue recepcionada.');
        }

        return $guiaBloqueada;
    }
}

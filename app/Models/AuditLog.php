<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'changes',
    ];

    /**
     * Modelos auditados y su etiqueta legible para el filtro de la pantalla
     * de Auditoría. Lista cerrada a propósito (no todos los modelos del
     * sistema son auditables, ver App\Models\Concerns\Auditable).
     */
    private const ETIQUETAS = [
        Cliente::class => 'Cliente',
        Fletero::class => 'Fletero',
        Producto::class => 'Producto',
        Transporte::class => 'Transporte',
        CuentaBancaria::class => 'Cuenta bancaria',
        Cheque::class => 'Cheque',
        OrdenPago::class => 'Orden de pago',
        Guia::class => 'Guía',
        Recibo::class => 'Recibo',
        Configuracion::class => 'Configuración',
        User::class => 'Usuario',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function etiquetaModelo(string $tipo): string
    {
        return self::ETIQUETAS[$tipo] ?? class_basename($tipo);
    }

    /** @return array<class-string, string> */
    public static function tiposAuditados(): array
    {
        return self::ETIQUETAS;
    }
}

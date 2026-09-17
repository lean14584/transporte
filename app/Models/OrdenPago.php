<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrdenPago extends Model
{
    /** @use HasFactory<\Database\Factories\OrdenPagoFactory> */
    use HasFactory;

    protected $fillable = [
        'transporte_id',
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

    public function transporte()
    {
        return $this->belongsTo(Transporte::class);
    }

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $ordenPago) {
            $ordenPago->usuario_id ??= Auth::id();
        });
    }
}

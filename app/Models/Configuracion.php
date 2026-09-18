<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = [
        'porcentaje_ganancia_kilo',
        'porcentaje_ganancia_unidad',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje_ganancia_kilo' => 'decimal:2',
            'porcentaje_ganancia_unidad' => 'decimal:2',
        ];
    }

    /**
     * Fila única de configuración global, creada con los defaults de la migración
     * si todavía no existe.
     */
    public static function actual(): self
    {
        return static::query()->firstOrCreate([]);
    }
}

<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Registra automáticamente quién creó y quién hizo la última edición de un
 * modelo, usando el usuario autenticado en el momento de guardar.
 */
trait RegistraAutoria
{
    protected static function bootRegistraAutoria(): void
    {
        static::creating(function ($model) {
            $model->creado_por_id ??= Auth::id();
        });

        static::updating(function ($model) {
            $model->actualizado_por_id = Auth::id();
        });
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por_id');
    }
}

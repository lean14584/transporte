<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Registra en audit_logs cada alta/edición/baja de este modelo, apoyado en
 * los eventos nativos de Eloquent. Por diseño NO captura cambios hechos por
 * el query builder (Model::where(...)->update(...)/increment()) — si algún
 * día se agrega una operación masiva así sobre un modelo auditado, hay que
 * llamar a writeAuditLog() a mano ahí, porque bootAuditable() no la ve.
 */
trait Auditable
{
    protected array $auditPendingDiff = [];

    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAuditLog('created', $model->auditCreatedDiff());
        });

        static::updating(function ($model) {
            $model->auditPendingDiff = $model->auditUpdatedDiff();
        });

        static::updated(function ($model) {
            $diff = $model->auditPendingDiff;
            $model->auditPendingDiff = [];

            if ($diff !== []) {
                $model->writeAuditLog('updated', $diff);
            }
        });

        static::deleted(function ($model) {
            $model->writeAuditLog('deleted', $model->auditDeletedDiff());
        });
    }

    /** @return string[] */
    protected function auditExcludedAttributes(): array
    {
        // creado_por_id/actualizado_por_id (RegistraAutoria) ya están
        // representados por el user_id del propio log, mostrarlos también
        // en el diff sería ruido.
        return array_merge(
            ['id', 'created_at', 'updated_at', 'creado_por_id', 'actualizado_por_id'],
            $this->auditExclude ?? []
        );
    }

    protected function auditCreatedDiff(): array
    {
        $excluded = $this->auditExcludedAttributes();
        $diff = [];

        foreach ($this->getAttributes() as $campo => $valor) {
            if (in_array($campo, $excluded, true)) {
                continue;
            }

            $diff[$campo] = ['old' => null, 'new' => $valor];
        }

        return $diff;
    }

    protected function auditUpdatedDiff(): array
    {
        $excluded = $this->auditExcludedAttributes();
        $diff = [];

        foreach ($this->getDirty() as $campo => $nuevo) {
            if (in_array($campo, $excluded, true)) {
                continue;
            }

            $diff[$campo] = ['old' => $this->getOriginal($campo), 'new' => $nuevo];
        }

        return $diff;
    }

    protected function auditDeletedDiff(): array
    {
        $excluded = $this->auditExcludedAttributes();
        $diff = [];

        foreach ($this->getAttributes() as $campo => $valor) {
            if (in_array($campo, $excluded, true)) {
                continue;
            }

            $diff[$campo] = ['old' => $valor, 'new' => null];
        }

        return $diff;
    }

    private function writeAuditLog(string $event, array $changes): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'changes' => $changes,
        ]);
    }
}

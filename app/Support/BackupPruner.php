<?php

namespace App\Support;

class BackupPruner
{
    /**
     * Borra los respaldos (*.sql.gz) más viejos que $diasDeRetencion dentro
     * de $directorio. Devuelve la cantidad de archivos borrados.
     */
    public function podar(string $directorio, int $diasDeRetencion): int
    {
        if (! is_dir($directorio)) {
            return 0;
        }

        $limite = now()->subDays($diasDeRetencion)->getTimestamp();
        $borrados = 0;

        foreach (glob($directorio.'/*.sql.gz') ?: [] as $archivo) {
            if (filemtime($archivo) < $limite) {
                unlink($archivo);
                $borrados++;
            }
        }

        return $borrados;
    }
}

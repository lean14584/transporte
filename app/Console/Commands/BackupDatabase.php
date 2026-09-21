<?php

namespace App\Console\Commands;

use App\Support\BackupPruner;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Genera un respaldo comprimido de la base de datos y elimina los respaldos vencidos.';

    public function handle(BackupPruner $pruner): int
    {
        $conexionActual = config('database.default');
        $conexion = config("database.connections.{$conexionActual}");

        if (($conexion['driver'] ?? null) !== 'mysql') {
            $this->error("El respaldo automático solo soporta MySQL (conexión actual: \"{$conexionActual}\").");

            return self::FAILURE;
        }

        $directorio = config('backup.directory');

        if (! is_dir($directorio) && ! mkdir($directorio, 0755, true) && ! is_dir($directorio)) {
            $this->error("No se pudo crear el directorio de respaldos: {$directorio}");

            return self::FAILURE;
        }

        $archivoSql = $directorio.'/transporte_'.now()->format('Ymd_His').'.sql';
        $archivoComprimido = $archivoSql.'.gz';

        $comando = [
            config('backup.mysqldump_path'),
            '--host='.$conexion['host'],
            '--port='.$conexion['port'],
            '--user='.$conexion['username'],
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--result-file='.$archivoSql,
            $conexion['database'],
        ];

        // La contraseña va por variable de entorno (MYSQL_PWD), no como
        // argumento de línea de comandos, para que no quede visible en la
        // lista de procesos del servidor.
        $proceso = new Process($comando, null, ['MYSQL_PWD' => $conexion['password']]);
        $proceso->setTimeout(300);
        $proceso->run();

        if (! $proceso->isSuccessful() || ! file_exists($archivoSql)) {
            $this->error('mysqldump falló: '.$proceso->getErrorOutput());

            if (file_exists($archivoSql)) {
                unlink($archivoSql);
            }

            return self::FAILURE;
        }

        $this->comprimir($archivoSql, $archivoComprimido);
        unlink($archivoSql);

        $borrados = $pruner->podar($directorio, (int) config('backup.retention_days'));

        $this->info('Respaldo generado: '.basename($archivoComprimido).' ('.$this->tamanioLegible(filesize($archivoComprimido)).')');

        if ($borrados > 0) {
            $this->info("Respaldos vencidos eliminados: {$borrados}");
        }

        return self::SUCCESS;
    }

    private function comprimir(string $origen, string $destino): void
    {
        $entrada = fopen($origen, 'rb');
        $salida = gzopen($destino, 'wb9');

        while (! feof($entrada)) {
            gzwrite($salida, fread($entrada, 1024 * 512));
        }

        fclose($entrada);
        gzclose($salida);
    }

    private function tamanioLegible(int $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($unidades) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$unidades[$i];
    }
}

<?php

use App\Support\BackupPruner;

function crearArchivoDeRespaldo(string $directorio, string $nombre, int $antiguedadEnDias): string
{
    $ruta = $directorio.'/'.$nombre;
    file_put_contents($ruta, 'contenido de prueba');
    touch($ruta, now()->subDays($antiguedadEnDias)->getTimestamp());

    return $ruta;
}

test('podar borra solo los respaldos más viejos que la retención configurada', function () {
    $directorio = sys_get_temp_dir().'/backup_pruner_test_'.uniqid();
    mkdir($directorio);

    $viejo = crearArchivoDeRespaldo($directorio, 'transporte_viejo.sql.gz', 20);
    $reciente = crearArchivoDeRespaldo($directorio, 'transporte_reciente.sql.gz', 2);
    crearArchivoDeRespaldo($directorio, 'otro_archivo.txt', 20); // no es un .sql.gz, no se toca

    $borrados = (new BackupPruner)->podar($directorio, diasDeRetencion: 14);

    expect($borrados)->toBe(1)
        ->and(file_exists($viejo))->toBeFalse()
        ->and(file_exists($reciente))->toBeTrue()
        ->and(file_exists($directorio.'/otro_archivo.txt'))->toBeTrue();

    array_map('unlink', glob($directorio.'/*'));
    rmdir($directorio);
});

test('podar no falla si el directorio no existe todavía', function () {
    $inexistente = sys_get_temp_dir().'/backup_pruner_no_existe_'.uniqid();

    expect((new BackupPruner)->podar($inexistente, diasDeRetencion: 14))->toBe(0);
});

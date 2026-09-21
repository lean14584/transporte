<?php

test('el comando de respaldo falla con un mensaje claro si la conexión activa no es mysql', function () {
    // El entorno de test usa sqlite (ver phpunit.xml); el comando debe
    // negarse a intentar un mysqldump contra una conexión que no es mysql.
    $this->artisan('db:backup')
        ->assertFailed()
        ->expectsOutputToContain('solo soporta MySQL');
});

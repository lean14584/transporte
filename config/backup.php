<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Respaldo de la base de datos
    |--------------------------------------------------------------------------
    |
    | Configuración del comando `db:backup`, que vuelca la base con
    | mysqldump y comprime el resultado. Pensado para correr diario vía el
    | scheduler de Laravel (routes/console.php) tanto en local (Laragon)
    | como en el hosting real.
    |
    */

    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),

    'directory' => env('BACKUP_DIRECTORY', storage_path('app/private/backups')),

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Copia off-site
    |--------------------------------------------------------------------------
    |
    | Si se configura, cada respaldo se manda también por email (además de
    | quedar en el disco local) — así sobrevive aunque el servidor entero
    | falle. Vacío/null desactiva el envío (default: no manda nada).
    |
    */

    'email_to' => env('BACKUP_EMAIL_TO'),

];

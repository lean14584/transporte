<?php

use App\Mail\RespaldoGenerado;
use Illuminate\Support\Facades\Mail;

test('RespaldoGenerado se manda al destinatario con el archivo adjunto y asunto correcto', function () {
    Mail::fake();

    $archivo = sys_get_temp_dir().'/respaldo_test_'.uniqid().'.sql.gz';
    file_put_contents($archivo, 'contenido de prueba');

    Mail::to('owner@example.com')->send(new RespaldoGenerado($archivo));

    Mail::assertSent(RespaldoGenerado::class, function (RespaldoGenerado $mail) use ($archivo) {
        return $mail->hasTo('owner@example.com')
            && $mail->archivoComprimido === $archivo
            && count($mail->attachments()) === 1;
    });

    unlink($archivo);
});

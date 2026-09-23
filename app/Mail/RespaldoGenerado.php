<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RespaldoGenerado extends Mailable
{
    public function __construct(public readonly string $archivoComprimido)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[transporte] Respaldo de base de datos '.now()->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.respaldo-generado',
        );
    }

    public function attachments(): array
    {
        return [Attachment::fromPath($this->archivoComprimido)];
    }
}

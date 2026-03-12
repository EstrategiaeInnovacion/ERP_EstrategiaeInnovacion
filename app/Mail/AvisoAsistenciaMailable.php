<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Models\AvisoAsistencia;

class AvisoAsistenciaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $aviso;

    /**
     * Create a new message instance.
     */
    public function __construct(AvisoAsistencia $aviso)
    {
        $this->aviso = $aviso;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $tipo = match($this->aviso->tipo) {
            'retardos' => 'Retardos',
            'faltas' => 'Faltas',
            default => 'Asistencia'
        };

        return new Envelope(
            subject: 'Aviso Oficial de ' . $tipo . ' - Recursos Humanos',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.recursos_humanos.aviso_asistencia',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

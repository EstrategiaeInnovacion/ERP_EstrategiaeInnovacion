<?php

namespace App\Mail;

use App\Models\Sistemas_IT\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoTicketNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public Ticket $ticket;

    /**
     * Create a new message instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $tipoLabel = match($this->ticket->tipo_problema) {
            'software' => 'Software',
            'hardware' => 'Hardware',
            'mantenimiento' => 'Mantenimiento',
            default => 'Ticket'
        };

        return new Envelope(
            subject: "[{$this->ticket->folio}] Nuevo Ticket de {$tipoLabel}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.nuevo_ticket',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

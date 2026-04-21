<?php

namespace App\Mail;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProyectoAsignado extends Mailable
{
    use Queueable, SerializesModels;

    public Proyecto $proyecto;
    public User $usuario;
    public string $tipo;
    public ?User $enviadoPor;

    public function __construct(Proyecto $proyecto, User $usuario, string $tipo = 'usuario', ?User $enviadoPor = null)
    {
        $this->proyecto = $proyecto;
        $this->usuario = $usuario;
        $this->tipo = $tipo;
        $this->enviadoPor = $enviadoPor;
    }

    public function envelope(): Envelope
    {
        $from = $this->enviadoPor?->email
            ? new Address($this->enviadoPor->email, $this->enviadoPor->name ?? $this->enviadoPor->email)
            : new Address(config('mail.from.address'), config('mail.from.name'));

        return new Envelope(
            from: $from,
            subject: "Te han asignado al proyecto: {$this->proyecto->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.proyectos.asignado',
        );
    }
}
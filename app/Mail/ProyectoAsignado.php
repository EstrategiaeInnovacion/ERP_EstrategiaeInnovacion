<?php

namespace App\Mail;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProyectoAsignado extends Mailable
{
    use Queueable, SerializesModels;

    public Proyecto $proyecto;
    public User $usuario;
    public string $tipo; // 'usuario' o 'responsable_ti'

    public function __construct(Proyecto $proyecto, User $usuario, string $tipo = 'usuario')
    {
        $this->proyecto = $proyecto;
        $this->usuario = $usuario;
        $this->tipo = $tipo;
    }

    public function envelope(): Envelope
    {
        $label = $this->tipo === 'responsable_ti' ? 'Responsable de TI' : 'Usuario';

        return new Envelope(
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
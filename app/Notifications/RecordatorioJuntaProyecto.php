<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecordatorioJuntaProyecto extends Notification
{
    public $proyecto;

    public $fechaJunta;

    public function __construct($proyecto, $fechaJunta)
    {
        $this->proyecto = $proyecto;
        $this->fechaJunta = $fechaJunta;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $esHoy = $this->fechaJunta->isToday();
        $titulo = $esHoy ? 'Junta de seguimiento HOY' : 'Recordatorio de junta de seguimiento';

        return (new MailMessage)
            ->subject("{$titulo} - {$this->proyecto->nombre}")
            ->greeting("Hola {$notifiable->name}!")
            ->line('Tienes una junta de seguimiento programada:')
            ->line("**📋 Proyecto:** {$this->proyecto->nombre}")
            ->line("**📅 Fecha:** {$this->fechaJunta->format('d/m/Y')}")
            ->line('**🔄 Recurrencia:** '.ucfirst($this->proyecto->recurrencia))
            ->action('Ver Proyecto', url('/proyectos/'.$this->proyecto->id))
            ->line('Por favor prepara tus avances y pendientes para la junta.')
            ->line('Si tienes actividades asignadas en este proyecto, recuerda revisar tu panel de actividades.')
            ->line('---')
            ->line('Este es un recordatorio automático. Si ya no participas en este proyecto, contacta al administrador.');
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\SolicitudVacacion;

class SolicitudVacacionNotification extends Notification
{
    use Queueable;

    public $solicitud;
    public $tipo; // 'para_supervisor', 'para_rh', 'para_analista'

    /**
     * Create a new notification instance.
     */
    public function __construct(SolicitudVacacion $solicitud, $tipo)
    {
        $this->solicitud = $solicitud;
        $this->tipo = $tipo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $mensaje = '';
        $titulo = '';
        $url = route('vacaciones.aprobaciones'); // Nueva vista de aprobaciones
        
        switch ($this->tipo) {
            case 'para_supervisor':
                $titulo = 'Nueva solicitud de vacaciones';
                $mensaje = "{$this->solicitud->empleado->nombre} ha solicitado {$this->solicitud->dias_solicitados} día(s) de vacaciones.";
                break;
            case 'para_rh':
                $titulo = 'Solicitud de vacaciones autorizada por supervisor';
                $mensaje = "El supervisor ha autorizado las vacaciones de {$this->solicitud->empleado->nombre}. Requiere visto bueno de RH.";
                break;
            case 'para_analista':
                $titulo = 'Actualización en tu solicitud de vacaciones';
                if ($this->solicitud->estado == 'aprobado') {
                    $mensaje = 'Tus vacaciones han sido APROBADAS.';
                } elseif ($this->solicitud->estado == 'rechazado') {
                    $mensaje = 'Tus vacaciones han sido RECHAZADAS.';
                } else {
                    $mensaje = 'Tu solicitud de vacaciones ha cambiado de estado.';
                }
                $url = route('profile.edit');
                break;
        }

        return [
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'url' => $url,
            'tipo' => 'vacaciones',
            'icon' => 'calendar'
        ];
    }
}

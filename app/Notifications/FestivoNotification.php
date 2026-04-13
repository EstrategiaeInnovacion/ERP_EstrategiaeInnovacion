<?php

namespace App\Notifications;

use App\Models\DiaFestivo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FestivoNotification extends Notification
{
    use Queueable;

    protected $diaFestivo;

    protected $esManana;

    public function __construct(DiaFestivo $diaFestivo, bool $esManana = false)
    {
        $this->diaFestivo = $diaFestivo;
        $this->esManana = $esManana;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $titulo = $this->esManana
            ? '📅 Recordatorio: Mañana es día '.($this->diaFestivo->tipo === 'festivo' ? 'festivo' : 'inhábil')
            : '📅 Recordatorio: Hoy es día '.($this->diaFestivo->tipo === 'festivo' ? 'festivo' : 'inhábil');

        $mail = (new MailMessage)
            ->subject($titulo)
            ->greeting('Hola '.$notifiable->name.',');

        if ($this->esManana) {
            $mail->line("Te informamos que **mañana** es día {$this->diaFestivo->tipo}: **{$this->diaFestivo->nombre}**.");
        } else {
            $mail->line("Te informamos que **hoy** es día {$this->diaFestivo->tipo}: **{$this->diaFestivo->nombre}**.");
        }

        if ($this->diaFestivo->descripcion) {
            $mail->line("\n{$this->diaFestivo->descripcion}");
        }

        $mail->line("\n¡Que tengas un buen descanso! 🏖️");

        return $mail->salutation('Saludos, Área de Recursos Humanos');
    }

    public function toArray($notifiable)
    {
        $cuando = $this->esManana ? 'mañana' : 'hoy';

        return [
            'titulo' => "📅 Día {$this->diaFestivo->tipo}: {$this->diaFestivo->nombre}",
            'mensaje' => "Te informamos que {$cuando} es día {$this->diaFestivo->tipo} ({$this->diaFestivo->nombre}).",
            'descripcion' => $this->diaFestivo->descripcion,
            'fecha' => $this->diaFestivo->fecha->format('Y-m-d'),
            'tipo' => $this->diaFestivo->tipo,
            'dia_festivo_id' => $this->diaFestivo->id,
        ];
    }
}

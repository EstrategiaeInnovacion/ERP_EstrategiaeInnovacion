<?php

namespace App\Notifications;

use App\Models\Sistemas_IT\ComputerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProximoMantenimientoNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected ComputerProfile $profile,
        protected bool $esAdminFallback = false
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $nextAt   = $this->profile->next_maintenance_at?->timezone('America/Mexico_City');
        $label    = trim(implode(' ', array_filter([
            $this->profile->brand,
            $this->profile->model,
            $this->profile->identifier ? "({$this->profile->identifier})" : null,
        ]))) ?: 'Equipo sin identificar';

        $diasRestantes = (int) now('America/Mexico_City')->startOfDay()
            ->diffInDays($nextAt?->copy()->startOfDay(), false);

        if ($diasRestantes < 0) {
            $urgencia = 'VENCIDO hace ' . abs($diasRestantes) . ' día(s)';
        } elseif ($diasRestantes === 0) {
            $urgencia = 'HOY';
        } else {
            $urgencia = 'en ' . $diasRestantes . ' día(s)';
        }

        $mailMessage = (new MailMessage)
            ->subject("[IT] Mantenimiento próximo: {$label}")
            ->greeting('Hola, ' . $notifiable->name)
            ->line("Tu equipo **{$label}** tiene programado su mantenimiento **{$urgencia}**.")
            ->line('**Fecha programada:** ' . ($nextAt?->format('d/m/Y') ?? 'Sin fecha'));

        if ($this->esAdminFallback) {
            $mailMessage->line('> Este aviso se envía al equipo de TI porque el equipo no tiene usuario asignado actualmente.');
        }

        return $mailMessage
            ->action('Ver ficha técnica', route('admin.maintenance.computers.show', $this->profile))
            ->line('Por favor coordina el mantenimiento a tiempo para evitar problemas.');
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type'                 => 'proximo_mantenimiento',
            'profile_id'          => $this->profile->id,
            'identifier'          => $this->profile->identifier,
            'label'               => trim(implode(' ', array_filter([
                $this->profile->brand,
                $this->profile->model,
                $this->profile->identifier ? "({$this->profile->identifier})" : null,
            ]))) ?: 'Equipo sin identificar',
            'next_maintenance_at' => $this->profile->next_maintenance_at?->toDateString(),
            'es_admin_fallback'   => $this->esAdminFallback,
            'url'                 => route('admin.maintenance.computers.show', $this->profile),
        ];
    }
}

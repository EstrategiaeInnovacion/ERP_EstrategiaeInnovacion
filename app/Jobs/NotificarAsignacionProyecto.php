<?php

namespace App\Jobs;

use App\Mail\ProyectoAsignado;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotificarAsignacionProyecto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Proyecto $proyecto;
    public User $usuario;
    public string $tipo;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(Proyecto $proyecto, User $usuario, string $tipo = 'usuario')
    {
        $this->proyecto = $proyecto;
        $this->usuario = $usuario;
        $this->tipo = $tipo;
    }

    public function handle(): void
    {
        if (!$this->usuario->email) {
            return;
        }

        $correo = new ProyectoAsignado($this->proyecto, $this->usuario, $this->tipo);

        Mail::to($this->usuario->email)->send($correo);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("Error al notificar asignación de proyecto {$this->proyecto->id} al usuario {$this->usuario->id}: " . $exception->getMessage());
    }
}
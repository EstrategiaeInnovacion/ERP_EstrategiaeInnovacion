<?php

namespace App\Jobs;

use App\Models\DiaFestivo;
use App\Models\Empleado;
use App\Notifications\FestivoNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarDiaFestivoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?Carbon $fechaEspecifica;

    protected bool $esManana;

    public function __construct(?Carbon $fecha = null, bool $esManana = true)
    {
        $this->fechaEspecifica = $fecha;
        $this->esManana = $esManana;
    }

    public function handle(): void
    {
        $fecha = $this->fechaEspecifica ?? Carbon::tomorrow();

        Log::info('Iniciando notificaciones de días festivos para: '.$fecha->format('Y-m-d'));

        $diasFestivos = DiaFestivo::paraFecha($fecha)->get();

        if ($diasFestivos->isEmpty()) {
            Log::info("No hay días festivos configurados para el {$fecha->format('Y-m-d')}");

            return;
        }

        $empleados = Empleado::where('es_activo', true)
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        $notificacionesEnviadas = 0;

        foreach ($diasFestivos as $diaFestivo) {
            foreach ($empleados as $empleado) {
                if (! $empleado->user) {
                    continue;
                }

                try {
                    $empleado->user->notify(new FestivoNotification($diaFestivo, $this->esManana));
                    $notificacionesEnviadas++;
                } catch (\Exception $e) {
                    Log::error("Error al enviar notificación a {$empleado->nombre}: ".$e->getMessage());
                }
            }

            Log::info("Notificaciones enviadas para día festivo: {$diaFestivo->nombre}");
        }

        Log::info("Proceso completado. Total notificaciones enviadas: {$notificacionesEnviadas}");
    }

    public static function ejecutarParaManana(): void
    {
        dispatch(new self(Carbon::tomorrow(), true));
    }

    public static function ejecutarParaHoy(): void
    {
        dispatch(new self(Carbon::today(), false));
    }
}

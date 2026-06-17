<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

class LimpiarTareasPendientesAprobacion extends Command
{
    protected $signature = 'tareas:limpiar-pendientes-aprobacion {--dias=30 : Días sin aprobarse/validarse antes de eliminar la tarea}';
    protected $description = 'Elimina automáticamente (a papelera) las tareas "Por Aprobar" o "Por Validar" que excedieron el plazo sin que el coordinador o dirección las valide';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');

        // "Por Aprobar": se cuenta desde la creación de la tarea (nunca se vuelve a
        // tocar hasta que se aprueba/rechaza).
        $porAprobar = Activity::where('estatus', 'Por Aprobar')
            ->where('created_at', '<=', now()->subDays($dias))
            ->get();

        // "Por Validar": se cuenta desde que se marcó el cierre (updated_at), ya que
        // la tarea puede haberse creado mucho antes de terminarse.
        $porValidar = Activity::where('estatus', 'Por Validar')
            ->where('updated_at', '<=', now()->subDays($dias))
            ->get();

        $tareas = $porAprobar->merge($porValidar);

        foreach ($tareas as $tarea) {
            $tarea->motivo_rechazo = 'Excedió el tiempo para validar por parte del coordinador o dirección.';
            $tarea->deleted_by = null;
            $tarea->save();

            // No se crea registro en activity_histories: user_id es obligatorio (NOT NULL)
            // y esta eliminación no la realiza ningún usuario, sino el propio sistema.
            $tarea->delete();
        }

        $this->info("Se eliminaron automáticamente {$tareas->count()} tarea(s) por exceder el plazo de validación.");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Logistica\OperacionLogistica;
use Illuminate\Support\Facades\Log;

class ActualizarStatusLogistica extends Command
{
    /**
     * El nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'logistica:actualizar-status';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Recalcula días transcurridos y estatus de todas las operaciones activas';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        $this->info('Iniciando actualización de estatus logísticos...');
        Log::info('CronJob: Iniciando actualización de status logística.');

        $count = 0;

        // Buscamos todas las operaciones que NO estén terminadas ("Done")
        // No filtramos por fecha de último cálculo para asegurar que "días transcurridos"
        // se actualice diariamente aunque el estatus no cambie.
        OperacionLogistica::where('status_manual', '!=', 'Done')
            ->chunk(100, function ($operaciones) use (&$count) {
                foreach ($operaciones as $operacion) {
                    try {
                        // true = forzar guardar historial si hay cambios significativos
                        $operacion->actualizarStatusAutomaticamente(true);
                        $count++;
                    } catch (\Exception $e) {
                        Log::error("Error actualizando operación ID {$operacion->id}: " . $e->getMessage());
                    }
                }
                
                $this->info("Procesadas {$count} operaciones...");
            });

        $this->info("¡Listo! Se actualizaron {$count} operaciones.");
        Log::info("CronJob: Finalizado. {$count} operaciones procesadas.");
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Proyecto;
use App\Notifications\RecordatorioJuntaProyecto;
use Illuminate\Console\Command;

class EnviarRecordatoriosProyectos extends Command
{
    protected $signature = 'proyectos:recordatorios';

    protected $description = 'Envía recordatorios de juntas de seguimiento según la recurrencia de cada proyecto';

    public function handle()
    {
        $this->info('Iniciando envío de recordatorios de proyectos...');

        $proyectos = Proyecto::where('archivado', false)->get();
        $enviados = 0;
        $omitidos = 0;

        foreach ($proyectos as $proyecto) {
            $usuarios = $proyecto->usuarios()->get();

            if ($usuarios->isEmpty()) {
                $omitidos++;

                continue;
            }

            $siguienteFecha = $proyecto->siguienteFechaJunta();

            if ($siguienteFecha->isToday() || $siguienteFecha->isYesterday()) {
                foreach ($usuarios as $usuario) {
                    try {
                        $usuario->notify(new RecordatorioJuntaProyecto($proyecto, $siguienteFecha));
                        $enviados++;
                    } catch (\Exception $e) {
                        $this->warn("Error notificando a {$usuario->name}: ".$e->getMessage());
                    }
                }
                $this->line("✓ Proyecto '{$proyecto->nombre}': {$usuarios->count()} usuario(s) notificado(s) - Fecha: {$siguienteFecha->format('d/m/Y')}");
            } else {
                $omitidos++;
            }
        }

        $this->info("Proceso completado. Enviados: {$enviados}, Omitidos: {$omitidos}");

        return 0;
    }
}

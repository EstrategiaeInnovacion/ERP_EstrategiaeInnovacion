<?php

namespace App\Console\Commands;

use App\Jobs\NotificarDiaFestivoJob;
use Illuminate\Console\Command;

class NotificarDiasFestivos extends Command
{
    protected $signature = 'rh:notificar-dias-festivos {--hoy : Notificar para hoy en lugar de mañana}';

    protected $description = 'Envía notificaciones a todos los empleados sobre días festivos próximos';

    public function handle(): int
    {
        $esHoy = $this->option('hoy');

        if ($esHoy) {
            $this->info('Enviando notificaciones para el día de HOY...');
            NotificarDiaFestivoJob::ejecutarParaHoy();
        } else {
            $this->info('Enviando notificaciones para el día de MAÑANA...');
            NotificarDiaFestivoJob::ejecutarParaManana();
        }

        $this->info('Notificaciones programadas correctamente.');

        return Command::SUCCESS;
    }
}

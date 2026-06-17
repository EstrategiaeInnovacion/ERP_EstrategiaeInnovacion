<?php

namespace App\Console\Commands;

use App\Models\Sistemas_IT\MaintenanceSlot;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarHorariosMantenimiento extends Command
{
    protected $signature = 'mantenimiento:generar-horarios {--dias=90 : Días hacia adelante a generar}';
    protected $description = 'Genera (rellena) los horarios disponibles de mantenimiento (maintenance_slots) para los próximos días, en días hábiles';

    private const TIME_SLOTS = [
        ['start' => '09:00', 'end' => '10:00'],
        ['start' => '10:00', 'end' => '11:00'],
        ['start' => '11:00', 'end' => '12:00'],
        ['start' => '12:00', 'end' => '13:00'],
        ['start' => '13:00', 'end' => '14:00'],
        ['start' => '15:00', 'end' => '16:00'],
    ];

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $creados = 0;

        $fecha = Carbon::today('America/Mexico_City');
        $limite = $fecha->copy()->addDays($dias);

        while ($fecha->lte($limite)) {
            if (! $fecha->isWeekend()) {
                foreach (self::TIME_SLOTS as $slot) {
                    $nuevo = MaintenanceSlot::firstOrCreate(
                        [
                            'date' => $fecha->toDateString(),
                            'start_time' => $slot['start'],
                            'end_time' => $slot['end'],
                        ],
                        [
                            'capacity' => 1,
                            'booked_count' => 0,
                            'is_active' => true,
                        ]
                    );

                    if ($nuevo->wasRecentlyCreated) {
                        $creados++;
                    }
                }
            }

            $fecha->addDay();
        }

        $this->info("Se generaron {$creados} horario(s) de mantenimiento nuevo(s) para los próximos {$dias} días.");

        return Command::SUCCESS;
    }
}

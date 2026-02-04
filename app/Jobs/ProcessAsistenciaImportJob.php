<?php

namespace App\Jobs;

use App\Services\ProcesarAsistenciaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProcessAsistenciaImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $path;
    public string $progressKey;

    public function __construct(string $path, string $progressKey)
    {
        $this->path = $path;
        $this->progressKey = $progressKey;
    }

    public function handle(ProcesarAsistenciaService $service): void
    {
        Cache::put($this->progressKey, [
            'status' => 'procesando',
            'mensaje' => 'Iniciando procesamiento',
            'sheet_actual' => 0,
            'sheet_total' => 0,
            'registros' => 0,
            'finalizado' => false,
        ], now()->addMinutes(30));

        $resultado = $service->process($this->path, true, function (array $evento) {
            $data = Cache::get($this->progressKey);
            if (!$data) { $data = []; }
            if ($evento['evento'] === 'preparando') {
                $data['sheet_total'] = $evento['total'];
                $data['sheet_actual'] = 0;
                $data['mensaje'] = 'Preparando hojas (' . $evento['total'] . ')';
                Cache::put($this->progressKey, $data, now()->addMinutes(30));
                return; // evitamos sobreescritura adicional en este ciclo
            }
            if ($evento['evento'] === 'inicio_hoja') {
                $data['sheet_actual'] = $evento['indice'];
                $data['sheet_total'] = $evento['total'];
                $data['mensaje'] = 'Procesando hoja: ' . $evento['titulo'];
            } elseif ($evento['evento'] === 'fin_hoja') {
                $data['registros'] = $evento['registros_acumulados'];
                $data['mensaje'] = 'Completada hoja: ' . $evento['titulo'];
            }
            Cache::put($this->progressKey, $data, now()->addMinutes(30));
        });

        Cache::put($this->progressKey, [
            'status' => 'completado',
            'mensaje' => 'Importación finalizada',
            'sheet_actual' => $resultado['hojas_procesadas'],
            'sheet_total' => $resultado['hojas_procesadas'],
            'registros' => $resultado['total_registros'],
            'periodo' => $resultado['periodo'],
            'finalizado' => true,
        ], now()->addMinutes(30));
    }
}

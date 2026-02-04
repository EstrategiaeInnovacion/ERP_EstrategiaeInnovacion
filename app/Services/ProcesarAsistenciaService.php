<?php

namespace App\Services;

use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Importante
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProcesarAsistenciaService
{
    protected string $horaRegex = '/\\b(2[0-3]|[01]?\\d):([0-5]\\d)\\b/u';
    protected string $horaEntradaLimite = '08:45:00'; 
    protected bool $persistRegistros = true;
    protected array $collectedRegistros = [];

    public function process(string $path, bool $persist = true, callable $onSheetProgress = null): array
    {
        $this->persistRegistros = $persist;
        $this->collectedRegistros = [];

        Log::info("Iniciando procesamiento de archivo: $path");

        // PASO 1: Carga del archivo (Pesado, NO transaccional)
        // Hacemos esto fuera de la transacción para no bloquear la tabla durante la lectura del disco
        try {
            $inputFileType = IOFactory::identify($path);
            $reader = IOFactory::createReader($inputFileType);
            
            if ($inputFileType === 'Csv') {
                /** @var Csv $reader */
                $reader->setDelimiter(',');
                $reader->setEnclosure('"');
                $reader->setSheetIndex(0);
            }

            $spreadsheet = $reader->load($path);
        } catch (\Exception $e) {
            Log::error("Error crítico cargando archivo: " . $e->getMessage());
            throw new \Exception("Error leyendo el archivo: " . $e->getMessage());
        }

        // PASO 2: Procesamiento de Datos (Transaccional)
        // Aquí empieza la escritura en BD. Si algo falla aquí, revertimos TODO.
        DB::beginTransaction();

        try {
            $sheets = $spreadsheet->getAllSheets();
            $totalRegistros = 0;
            $empleadosProcesados = 0;
            $periodoGlobal = null;

            foreach ($sheets as $index => $sheet) {
                $sheetTitle = $sheet->getTitle();
                Log::info("Analizando hoja: {$sheetTitle}");

                // Lógica de lectura (sin cambios en lógica de negocio, solo envuelto)
                $periodo = $this->parsePeriodo($sheet) ?? $periodoGlobal;
                if ($periodo && !$periodoGlobal) {
                    $periodoGlobal = $periodo;
                }

                $dayColumns = $this->mapDayColumns($sheet);
                
                if (!$periodo || empty($dayColumns)) {
                    continue;
                }

                $highestRow = $sheet->getHighestDataRow();
                $empleadoActual = null;

                for ($row = 1; $row <= $highestRow; $row++) {
                    $rowValues = $this->getRowValues($sheet, $row);

                    // A. Buscar cabecera
                    if ($this->isEmpleadoHeader($rowValues)) {
                        $nuevoId = $this->extraerValorPosterior($rowValues, ['No', 'Num', 'ID', 'Empleado']);
                        $nuevoNombre = $this->extraerValorPosterior($rowValues, ['Nombre', 'Name']);

                        if (!empty($nuevoId)) {
                            if ($empleadoActual) $empleadosProcesados++;
                            $empleadoActual = ['no' => $nuevoId, 'nombre' => $nuevoNombre];
                        }
                        continue;
                    }

                    // B. Buscar nombre
                    if ($empleadoActual && empty($empleadoActual['nombre']) && $this->isNombreHeader($rowValues)) {
                        $empleadoActual['nombre'] = $this->extraerValorPosterior($rowValues, ['Nombre', 'Name']);
                        continue;
                    }

                    // C. Procesar Checadas (Escritura en BD)
                    if ($empleadoActual && $this->filaTieneChecadas($rowValues)) {
                        $totalRegistros += $this->procesarFilaDeChecadas($rowValues, $dayColumns, $periodo, $empleadoActual);
                    }
                }
                if ($empleadoActual) $empleadosProcesados++;

                if ($onSheetProgress) {
                    $onSheetProgress(['total' => count($sheets), 'indice' => $index + 1]);
                }
            }

            // Si llegamos aquí sin errores, confirmamos todos los cambios
            DB::commit();

            return [
                'total_registros' => $totalRegistros,
                'empleados_procesados' => $empleadosProcesados,
                'periodo' => $periodoGlobal,
                'registros' => $this->collectedRegistros
            ];

        } catch (\Throwable $e) {
            // Si ocurre CUALQUIER error durante el bucle, revertimos todo
            DB::rollBack();
            Log::error("Error durante transacción de importación: " . $e->getMessage());
            throw $e; // Re-lanzamos para que el controlador lo note
        }
    }

    // ... (El resto de métodos auxiliares se mantiene idéntico) ...
    
    // Solo mostramos procesarFilaDeChecadas para confirmar que no necesita cambios internos
    // ya que la transacción lo envuelve desde arriba.
    protected function procesarFilaDeChecadas(array $rowValues, array $dayColumns, array $periodo, array $empleado): int
    {
        $count = 0;
        foreach ($dayColumns as $day => $colIdx) {
            $raw = $rowValues[$colIdx] ?? '';
            if (empty($raw) || !preg_match($this->horaRegex, $raw)) continue;

            $horas = $this->extraerHoras($raw);
            if (count($horas) < 1) continue;

            $fecha = $this->construirFecha($periodo, $day);
            
            $empleadoId = $this->persistRegistros ? $this->buscarEmpleadoId($empleado['no'], $empleado['nombre']) : null;
            
            for ($i = 0; $i < count($horas); $i += 2) {
                $entrada = $horas[$i];
                $salida = $horas[$i+1] ?? null;

                $esRetardo = false;
                if ($entrada) {
                    try {
                        $esRetardo = Carbon::parse($entrada)->format('H:i:s') > $this->horaEntradaLimite;
                    } catch (\Exception $e) {}
                }

                if ($this->persistRegistros) {
                    // Esta operación ahora ocurre dentro de la transacción iniciada en process()
                    $existing = DB::table('asistencias')
                        ->where('empleado_no', $empleado['no'])
                        ->where('fecha', $fecha->toDateString())
                        ->where('entrada', $entrada) 
                        ->first();

                    if ($existing && $existing->tipo_registro !== 'asistencia') {
                        continue; 
                    }

                    DB::table('asistencias')->updateOrInsert(
                        [
                            'empleado_no' => $empleado['no'],
                            'fecha' => $fecha->toDateString(),
                            'entrada' => $entrada, 
                        ],
                        [
                            'nombre' => $empleado['nombre'] ?? 'Desconocido',
                            'salida' => $salida,
                            'checadas' => json_encode($horas),
                            'empleado_id' => $empleadoId,
                            'tipo_registro' => 'asistencia',
                            'es_retardo' => $esRetardo,
                            'updated_at' => now(),
                        ]
                    );
                } else {
                    $this->collectedRegistros[] = compact('empleado', 'fecha', 'entrada', 'salida');
                }
                $count++;
            }
        }
        return $count;
    }

    // (El resto de métodos helpers siguen igual: parsePeriodo, mapDayColumns, etc...)
    protected function parsePeriodo(Worksheet $sheet): ?array
    {
        for ($row = 1; $row <= 50; $row++) {
            $rowVals = $this->getRowValues($sheet, $row);
            $joined = implode(' ', $rowVals);
            if (preg_match('/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})\s*[~-]\s*(?:(\d{4})[\/\-])?(\d{1,2})[\/\-](\d{1,2})/', $joined, $m)) {
                try {
                    $year = (int)$m[1];
                    $start = Carbon::create($year, (int)$m[2], (int)$m[3]);
                    $endYear = !empty($m[4]) ? (int)$m[4] : $year;
                    $endMonth = (int)$m[5];
                    $endDay = (int)$m[6];
                    try {
                        $end = Carbon::create($endYear, $endMonth, $endDay);
                    } catch (\Exception $ex) {
                        $end = $start->copy()->endOfMonth();
                    }
                    return ['inicio' => $start, 'fin' => $end];
                } catch (\Throwable $e) { continue; }
            }
        }
        return null;
    }

    protected function mapDayColumns(Worksheet $sheet): array
    {
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        for ($row = 1; $row <= 30; $row++) { 
            $map = [];
            for ($col = 1; $col <= $highestCol; $col++) {
                $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);
                $val = $cell->getValue(); 
                if (is_numeric($val)) {
                    $num = (int)$val;
                    if ($num >= 1 && $num <= 31) {
                        $map[$num] = $col - 1;
                    }
                }
            }
            if (count($map) >= 5) return $map;
        }
        return [];
    }

    protected function extraerHoras(string $cellValue): array
    {
        $normalized = preg_replace('/\r\n?|\n/u', ' ', $cellValue);
        preg_match_all($this->horaRegex, $normalized, $matches);
        $horas = [];
        foreach ($matches[0] ?? [] as $h) {
            try {
                $horas[] = Carbon::createFromFormat('H:i', $h)->format('H:i:s');
            } catch (\Exception $e) {}
        }
        return $horas;
    }

    protected function construirFecha(array $periodo, int $day): Carbon
    {
        $date = $periodo['inicio']->copy()->day($day);
        if ($day < $periodo['inicio']->day) $date->addMonth();
        if ($periodo['inicio']->month == 12 && $date->month == 1) $date->addYear();
        return $date;
    }

    protected function buscarEmpleadoId(string $no, ?string $nombre): ?int
    {
        $emp = Empleado::where('id_empleado', $no)->orWhere('id_empleado', (int)$no)->first();
        if ($emp) return $emp->id;
        if ($nombre) {
            $cleanName = str_replace(' ', '', Str::lower($nombre));
            $emp = Empleado::whereRaw("LOWER(REPLACE(nombre, ' ', '')) LIKE ?", ["%{$cleanName}%"])->first();
            if ($emp) return $emp->id;
        }
        return null;
    }

    protected function getRowValues(Worksheet $sheet, int $row): array
    {
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $values = [];
        for ($col = 1; $col <= $highestCol; $col++) {
            $val = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getValue(); 
            $values[] = trim((string)$val);
        }
        return $values;
    }

    protected function isEmpleadoHeader(array $vals): bool {
        $s = implode(' ', $vals);
        return (bool)preg_match('/\b(No|Num|ID)\s*[:\.]/i', $s);
    }

    protected function isNombreHeader(array $vals): bool {
        $s = implode(' ', $vals);
        return (bool)preg_match('/\b(Nombre|Name)\s*[:\.]/i', $s);
    }

    protected function filaTieneChecadas(array $vals): bool {
        foreach ($vals as $v) { if (preg_match($this->horaRegex, $v)) return true; }
        return false;
    }

    protected function extraerValorPosterior(array $vals, array $keys): ?string {
        foreach ($vals as $i => $val) {
            foreach ($keys as $key) {
                if (stripos($val, $key) !== false) {
                    for ($j = $i + 1; $j < min(count($vals), $i + 4); $j++) {
                        $c = trim($vals[$j]);
                        if (!empty($c) && $c !== ':' && $c !== '.') return $c;
                    }
                }
            }
        }
        return null;
    }
}
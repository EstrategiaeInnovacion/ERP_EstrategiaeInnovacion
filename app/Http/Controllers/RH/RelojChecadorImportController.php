<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Services\ProcesarAsistenciaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; // Importante para las transacciones
use Carbon\Carbon;

class RelojChecadorImportController extends Controller
{
    public function index(Request $request)
    {
        // ... (El código del index se mantiene igual) ...
        // 1. Definir Periodo
        $inicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fin = $request->input('fecha_fin', now()->endOfMonth()->toDateString());
        
        $start = Carbon::parse($inicio);
        $end = Carbon::parse($fin);

        // 2. Generar Array de Fechas
        $fechas = [];
        $loopDate = $end->copy(); 
        
        while ($loopDate->gte($start)) {
            if (!$loopDate->isWeekend()) {
                $fechas[] = $loopDate->copy();
            }
            $loopDate->subDay();
        }

        // 3. Preparar Límites BD
        $dbFechaFin = Carbon::parse($fin)->addDay()->format('Y-m-d');

        // 4. Obtener Empleados
        $search = $request->input('search');

        $empleados = Empleado::query()
            ->when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellido_paterno', 'like', "%{$search}%")
                      ->orWhere('id_empleado', 'like', "%{$search}%")
                      ->orWhere('no_empleado', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->with(['asistencias' => function($q) use ($inicio, $dbFechaFin) {
                $q->where('fecha', '>=', $inicio)
                  ->where('fecha', '<', $dbFechaFin);
            }])
            ->paginate(15)
            ->withQueryString();

        // 5. KPIs
        $baseQuery = Asistencia::query()
            ->where('fecha', '>=', $inicio)
            ->where('fecha', '<', $dbFechaFin);
        
        $kpis = [
            'total' => $baseQuery->count(),
            'ok' => (clone $baseQuery)->where('es_retardo', false)->count(),
            'retardos' => (clone $baseQuery)->where('es_retardo', true)->where('es_justificado', false)->count(),
            'faltas' => (clone $baseQuery)->where('tipo_registro', 'falta')->count(),
        ];

        // Cálculo de Horas
        $registrosTiempos = (clone $baseQuery)
            ->whereNotNull('entrada')
            ->whereNotNull('salida')
            ->get(['entrada', 'salida']);
            
        $minutosTotales = 0;
        foreach ($registrosTiempos as $registro) {
            $entrada = Carbon::parse($registro->entrada);
            $salida = Carbon::parse($registro->salida);
            if ($salida->gt($entrada)) {
                $minutosTotales += $entrada->diffInMinutes($salida);
            }
        }
        $horas = floor($minutosTotales / 60);
        $minutos = $minutosTotales % 60;
        $horasTotales = sprintf('%d:%02d', $horas, $minutos);

        $porcentajeAsistencia = $kpis['total'] > 0 ? round(($kpis['ok'] / $kpis['total']) * 100, 1) : 0;

        $topRetardos = (clone $baseQuery)
            ->where('es_retardo', true)
            ->where('es_justificado', false)
            ->select('nombre', DB::raw('count(*) as total'))
            ->groupBy('nombre')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        return view('Recursos_Humanos.reloj_checador', compact(
            'empleados', 
            'fechas',
            'porcentajeAsistencia',
            'topRetardos',
            'horasTotales'
        ) + [
            'totalRegistros' => $kpis['total'],
            'asistenciasOk' => $kpis['ok'],
            'retardos' => $kpis['retardos'],
            'faltas' => $kpis['faltas']
        ]);
    }

    /**
     * Actualiza un registro individual (Edición rápida).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tipo_registro' => 'required|string',
            'comentarios' => 'nullable|string|max:255',
        ]);

        // ATOMICIDAD: Aunque es un solo registro, buena práctica envolverlo.
        DB::transaction(function () use ($request, $id) {
            $asistencia = Asistencia::findOrFail($id);
            
            $asistencia->update([
                'tipo_registro' => $request->tipo_registro,
                'comentarios' => $request->comentarios,
                'es_justificado' => $request->has('es_justificado'),
            ]);
        });

        return back()->with('success', 'Registro actualizado correctamente.');
    }

    /**
     * Registra incidencias individuales o MASIVAS
     * ATOMICIDAD APLICADA: Si falla el registro 99, se revierten los 98 anteriores.
     */
    public function store(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required', 
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo_registro' => 'required'
        ]);

        return DB::transaction(function () use ($request) {
            $inicio = Carbon::parse($request->fecha_inicio);
            $fin = $request->fecha_fin ? Carbon::parse($request->fecha_fin) : $inicio->copy();
            
            $targetEmpleados = collect();

            if ($request->empleado_id === 'all') {
                $targetEmpleados = Empleado::all(); 
            } else {
                $emp = Empleado::find($request->empleado_id);
                if ($emp) {
                    $targetEmpleados->push($emp);
                }
            }

            if ($targetEmpleados->isEmpty()) {
                return back()->with('error', 'No se encontraron empleados.');
            }

            $contador = 0;

            foreach ($targetEmpleados as $empleado) {
                $loopDate = $inicio->copy();

                while ($loopDate->lte($fin)) {
                    
                    $registroExistente = Asistencia::where('empleado_id', $empleado->id)
                        ->whereDate('fecha', $loopDate->toDateString())
                        ->lockForUpdate() // Bloqueamos la fila para evitar condiciones de carrera
                        ->first();

                    // Datos base
                    $datosGuardar = [
                        'empleado_id' => $empleado->id,
                        'fecha' => $registroExistente ? $registroExistente->fecha : $loopDate->toDateString(),
                        'empleado_no' => $empleado->id_empleado ?? 'S/N',
                        'nombre' => $empleado->nombre . ' ' . $empleado->apellido_paterno,
                        'tipo_registro' => $request->tipo_registro,
                        'comentarios' => $request->comentarios,
                        'es_justificado' => true,
                        'es_retardo' => false,
                        'updated_at' => now(),
                    ];

                    if ($registroExistente) {
                        $registroExistente->update($datosGuardar);
                    } else {
                        $datosGuardar['created_at'] = now();
                        $datosGuardar['checadas'] = '[]';
                        $datosGuardar['entrada'] = null;
                        $datosGuardar['salida'] = null;

                        Asistencia::create($datosGuardar);
                    }
                    
                    $contador++;
                    $loopDate->addDay();
                }
            }

            return back()->with('success', "Proceso terminado. Se registraron {$contador} incidencias correctamente.");
        });
    }

    /**
     * Proceso de Importación (Async con Cache)
     */
    public function start(Request $request)
    {
        set_time_limit(300); 

        $request->validate([
            'archivo' => ['required', 'file', 'max:10240', 'mimes:xls,xlsx'],
            'progress_key' => ['required', 'string'], 
        ]);

        $file = $request->file('archivo');
        $path = $file->storeAs('imports/reloj', Str::uuid() . '_' . $file->getClientOriginalName());
        $fullPath = Storage::path($path);
        
        $key = $request->progress_key;
        $this->updateProgress($key, 'procesando', 5, 'Iniciando lectura...');

        try {
            if (!class_exists(ProcesarAsistenciaService::class)) {
                throw new \Exception("Servicio de procesamiento no encontrado.");
            }

            $service = new ProcesarAsistenciaService();
            
            // La transacción está implementada DENTRO del servicio para no bloquear 
            // la base de datos mientras se lee el archivo Excel (que es lento).
            $resultado = $service->process($fullPath, true, function ($estado) use ($key) {
                $percent = ($estado['total'] > 0) ? round(($estado['indice'] / $estado['total']) * 100) : 0;
                $this->updateProgress($key, 'procesando', max(5, $percent), "Procesando registros...");
            });

            $this->updateProgress($key, 'completado', 100, "Completado. " . ($resultado['total_registros'] ?? 0) . " registros.", true);

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            Log::error("Error Importación Reloj: " . $e->getMessage());
            $this->updateProgress($key, 'error', 0, "Error: " . $e->getMessage(), true);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Helper privado
    private function updateProgress($key, $status, $percent, $msg, $finalizado = false) {
        Cache::put($key, [
            'status' => $status,
            'percent' => $percent,
            'mensaje' => $msg,
            'finalizado' => $finalizado
        ], now()->addMinutes(10));
    }

    public function progress(string $key)
    {
        return response()->json(Cache::get($key) ?? ['percent' => 0, 'finalizado' => false]);
    }

    public function clear()
    {
        Asistencia::truncate();
        return redirect()->route('rh.reloj.index')->with('success', 'Base de datos de asistencia vaciada correctamente.');
    }
}
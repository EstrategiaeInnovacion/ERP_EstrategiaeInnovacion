<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Mail\AvisoAsistenciaMailable;
use App\Models\Asistencia;
use App\Models\AvisoAsistencia;
use App\Models\Empleado;
use App\Services\ProcesarAsistenciaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RelojChecadorImportController extends Controller
{
    private function determinarEsRetardo($entrada)
    {
        if (!$entrada) {
            return false;
        }
        $limite = Carbon::createFromFormat('H:i', '08:40');
        try {
            return Carbon::createFromFormat('H:i:s', $entrada)->gt($limite);
        } catch (\Exception $e) {
            try {
                return Carbon::createFromFormat('H:i', $entrada)->gt($limite);
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    private function generarFechasPeriodo($start, $end)
    {
        $fechas = [];
        $loopDate = $start->copy();
        while ($loopDate->lte($end)) {
            if (!$loopDate->isWeekend()) {
                $fechas[] = $loopDate->copy();
            }
            $loopDate->addDay();
        }
        return $fechas;
    }

    private function calcularKpisAsistencia($baseQuery)
    {
        return [
            'total' => (clone $baseQuery)->laborales()->count(),
            'ok' => (clone $baseQuery)->asistenciasOk()->count(),
            'retardos' => (clone $baseQuery)->retardosInjustificados()->count(),
            'faltas' => (clone $baseQuery)->soloFaltas()->count(),
        ];
    }

    private function calcularHorasTrabajadas($baseQuery)
    {
        $registrosTiempos = (clone $baseQuery)
            ->whereNotNull('entrada')
            ->whereNotNull('salida')
            ->get();

        $minutosTotales = 0;
        foreach ($registrosTiempos as $registro) {
            $fechaStr = $registro->fecha ? $registro->fecha->format('Y-m-d') : now()->format('Y-m-d');
            $entrada = Carbon::parse($fechaStr . ' ' . $registro->entrada);
            $salida = Carbon::parse($fechaStr . ' ' . $registro->salida);
            if ($salida->gt($entrada)) {
                $minutosTotales += $entrada->diffInMinutes($salida);
            }
        }
        $horas = floor($minutosTotales / 60);
        $minutos = $minutosTotales % 60;
        return sprintf('%d:%02d', $horas, $minutos);
    }

    public function index(Request $request)
    {
        Carbon::setLocale('es');

        $inicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fin = $request->input('fecha_fin', now()->endOfMonth()->toDateString());

        $start = Carbon::parse($inicio);
        $end = Carbon::parse($fin);

        $fechas = $this->generarFechasPeriodo($start, $end);

        $dbFechaFin = Carbon::parse($fin)->addDay()->format('Y-m-d');

        $search = $request->input('search');

        $empleados = Empleado::query()
            ->where('es_activo', true)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('id_empleado', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->with([
                'asistencias' => function ($q) use ($inicio, $dbFechaFin) {
                    $q->where('fecha', '>=', $inicio)
                        ->where('fecha', '<', $dbFechaFin);
                },
                'avisosAsistencia' => function ($q) {
                    $q->orderBy('created_at', 'desc')->with('enviadoPor');
                },
            ])
            ->paginate(15)
            ->withQueryString();

        $baseQuery = Asistencia::query()
            ->where('fecha', '>=', $inicio)
            ->where('fecha', '<', $dbFechaFin);

        $kpis = $this->calcularKpisAsistencia($baseQuery);
        $horasTotales = $this->calcularHorasTrabajadas($baseQuery);

        $porcentajeAsistencia = $kpis['total'] > 0 ? round(($kpis['ok'] / $kpis['total']) * 100, 1) : 0;

        $topRetardos = (clone $baseQuery)
            ->where('tipo_registro', 'asistencia')
            ->where('es_retardo', true)
            ->where('es_justificado', false)
            ->select('nombre', DB::raw('count(*) as total'))
            ->groupBy('nombre')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        $fechaInicioFormato = Carbon::parse($inicio)->isoFormat('D [de] MMMM [de] YYYY');
        $fechaFinFormato = Carbon::parse($fin)->isoFormat('D [de] MMMM [de] YYYY');

        $sinResultados = $search && $empleados->isEmpty();

        $todosEmpleados = Empleado::orderBy('nombre')->get(['id', 'nombre']);
        $empleadosActivos = Empleado::where('es_activo', true)->orderBy('nombre')->get(['id', 'nombre', 'id_empleado']);

        return view('Recursos_Humanos.reloj_checador', compact(
            'empleados',
            'todosEmpleados',
            'empleadosActivos',
            'fechas',
            'porcentajeAsistencia',
            'topRetardos',
            'horasTotales',
            'fechaInicioFormato',
            'fechaFinFormato',
            'sinResultados'
        ) + [
            'retardos' => $kpis['retardos'],
            'faltas' => $kpis['faltas'],
            'busqueda' => $search,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tipo_registro' => 'required|string',
            'comentarios' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $id) {
            $asistencia = Asistencia::findOrFail($id);

            $asistencia->update([
                'tipo_registro' => $request->tipo_registro,
                'comentarios' => $request->comentarios,
                'es_justificado' => $request->has('es_justificado'),
            ]);
        });

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Registro actualizado correctamente.']);
        }

        return back()->with('success', 'Registro actualizado correctamente.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'tipo_registro' => 'required',
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
                        ->lockForUpdate()
                        ->first();

                    $datosGuardar = [
                        'empleado_id' => $empleado->id,
                        'fecha' => $registroExistente ? $registroExistente->fecha : $loopDate->toDateString(),
                        'empleado_no' => $empleado->id_empleado ?? 'S/N',
                        'nombre' => $empleado->nombre.' '.$empleado->apellido_paterno,
                        'tipo_registro' => $request->tipo_registro,
                        'comentarios' => $request->comentarios,
                        'es_justificado' => $request->input('es_justificado', false) ? true : false,
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

    public function storeManual(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'entrada' => 'nullable|date_format:H:i',
            'salida' => 'nullable|date_format:H:i',
        ]);

        $empleado = Empleado::findOrFail($request->empleado_id);

        $entrada = $request->entrada;
        $salida = $request->salida;

        $esRetardo = $this->determinarEsRetardo($entrada);

        $inicio = Carbon::parse($request->fecha_inicio);
        $fin = $request->fecha_fin ? Carbon::parse($request->fecha_fin) : $inicio->copy();
        $contador = 0;

        $loopDate = $inicio->copy();
        while ($loopDate->lte($fin)) {
            if (! $loopDate->isWeekend()) {
                Asistencia::updateOrCreate(
                    [
                        'empleado_id' => $empleado->id,
                        'fecha' => $loopDate->toDateString(),
                    ],
                    [
                        'empleado_no' => $empleado->id_empleado ?? 'S/N',
                        'nombre' => $empleado->nombre.' '.($empleado->apellido_paterno ?? ''),
                        'entrada' => $entrada,
                        'salida' => $salida,
                        'tipo_registro' => 'asistencia',
                        'es_retardo' => $esRetardo,
                        'es_justificado' => false,
                        'checadas' => json_encode(array_filter([$entrada, $salida])),
                        'comentarios' => 'Registro manual',
                    ]
                );
                $contador++;
            }
            $loopDate->addDay();
        }

        return back()->with('success', "Se registraron {$contador} día(s) de asistencia para {$empleado->nombre}.");
    }

    public function start(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'archivo' => ['required', 'file', 'max:10240', 'mimes:xls,xlsx'],
            'progress_key' => ['required', 'string'],
        ]);

        $file = $request->file('archivo');
        $path = $file->storeAs('imports/reloj', Str::uuid().'_'.$file->getClientOriginalName());
        $fullPath = Storage::path($path);

        $key = $request->progress_key;
        $this->updateProgress($key, 'procesando', 5, 'Iniciando lectura...');

        try {
            if (! class_exists(ProcesarAsistenciaService::class)) {
                throw new \Exception('Servicio de procesamiento no encontrado.');
            }

            $service = new ProcesarAsistenciaService;

            $filtroEmpleados = $request->input('empleados_filtro', []);
            if (is_string($filtroEmpleados)) {
                $filtroEmpleados = array_filter(explode(',', $filtroEmpleados));
            }

            $resultado = $service->process($fullPath, true, function ($estado) use ($key) {
                $percent = ($estado['total'] > 0) ? round(($estado['indice'] / $estado['total']) * 100) : 0;
                $this->updateProgress($key, 'procesando', max(5, $percent), 'Procesando registros...');
            }, $filtroEmpleados);

            $this->updateProgress($key, 'completado', 100, 'Completado. '.($resultado['total_registros'] ?? 0).' registros.', true);

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            Log::error('Error Importación Reloj: '.$e->getMessage());
            $this->updateProgress($key, 'error', 0, 'Error: '.$e->getMessage(), true);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function updateProgress($key, $status, $percent, $msg, $finalizado = false)
    {
        Cache::put($key, [
            'status' => $status,
            'percent' => $percent,
            'mensaje' => $msg,
            'finalizado' => $finalizado,
        ], now()->addMinutes(10));
    }

    public function progress(string $key)
    {
        return response()->json(Cache::get($key) ?? ['percent' => 0, 'finalizado' => false]);
    }

    public function clear(Request $request)
    {
        if ($request->query('confirm') !== 'yes') {
            return redirect()->route('rh.reloj.index')->with('error', 'El vaciado de base de datos debe ser confirmado.');
        }

        Asistencia::truncate();

        return redirect()->route('rh.reloj.index')->with('success', 'Base de datos de asistencia vaciada correctamente.');
    }

    public function clearRango(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $eliminados = Asistencia::whereBetween('fecha', [
            $request->fecha_inicio,
            $request->fecha_fin,
        ])->delete();

        return redirect()->route('rh.reloj.index')->with(
            'success',
            "Se eliminaron {$eliminados} registros del período {$request->fecha_inicio} al {$request->fecha_fin}."
        );
    }

    public function revertirRango(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $asistencias = Asistencia::where('empleado_id', $request->empleado_id)
            ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
            ->get();

        $toDeleteIds = [];
        $toUpdateAsistenciaIds = [];
        $toUpdateAsistenciaRetardoIds = [];
        $toUpdateIncompletoIds = [];
        $toUpdateIncompletoRetardoIds = [];

        foreach ($asistencias as $as) {
            if (!$as->entrada && !$as->salida) {
                $toDeleteIds[] = $as->id;
            } else {
                $esRetardo = $this->determinarEsRetardo($as->entrada);
                $isIncompleto = ($as->entrada && !$as->salida);
                
                if ($isIncompleto) {
                    if ($esRetardo) {
                        $toUpdateIncompletoRetardoIds[] = $as->id;
                    } else {
                        $toUpdateIncompletoIds[] = $as->id;
                    }
                } else {
                    if ($esRetardo) {
                        $toUpdateAsistenciaRetardoIds[] = $as->id;
                    } else {
                        $toUpdateAsistenciaIds[] = $as->id;
                    }
                }
            }
        }

        $revertidos = 0;
        $eliminados = 0;

        if (!empty($toDeleteIds)) {
            $eliminados = Asistencia::whereIn('id', $toDeleteIds)->delete();
        }
        if (!empty($toUpdateAsistenciaIds)) {
            $revertidos += Asistencia::whereIn('id', $toUpdateAsistenciaIds)->update([
                'tipo_registro' => 'asistencia',
                'es_justificado' => false,
                'es_retardo' => false,
                'comentarios' => null,
            ]);
        }
        if (!empty($toUpdateAsistenciaRetardoIds)) {
            $revertidos += Asistencia::whereIn('id', $toUpdateAsistenciaRetardoIds)->update([
                'tipo_registro' => 'asistencia',
                'es_justificado' => false,
                'es_retardo' => true,
                'comentarios' => null,
            ]);
        }
        if (!empty($toUpdateIncompletoIds)) {
            $revertidos += Asistencia::whereIn('id', $toUpdateIncompletoIds)->update([
                'tipo_registro' => 'incompleto',
                'es_justificado' => false,
                'es_retardo' => false,
                'comentarios' => null,
            ]);
        }
        if (!empty($toUpdateIncompletoRetardoIds)) {
            $revertidos += Asistencia::whereIn('id', $toUpdateIncompletoRetardoIds)->update([
                'tipo_registro' => 'incompleto',
                'es_justificado' => false,
                'es_retardo' => true,
                'comentarios' => null,
            ]);
        }

        $empleado = Empleado::findOrFail($request->empleado_id);
        $partes = [];
        if ($revertidos > 0) {
            $partes[] = "{$revertidos} revertido(s) al estado original";
        }
        if ($eliminados > 0) {
            $partes[] = "{$eliminados} manual(es) eliminado(s)";
        }
        $resumen = empty($partes) ? 'Sin registros en ese rango' : implode(', ', $partes);

        return redirect()->route('rh.reloj.index')->with(
            'success',
            "Rango revertido para {$empleado->nombre}: {$resumen} ({$request->fecha_inicio} al {$request->fecha_fin})."
        );
    }

    public function revertir(Request $request, $id)
    {
        $asistencia = Asistencia::findOrFail($id);

        if ($asistencia->entrada || $asistencia->salida) {
            $esRetardo = $this->determinarEsRetardo($asistencia->entrada);
            $tipo = ($asistencia->entrada && ! $asistencia->salida) ? 'incompleto' : 'asistencia';

            $asistencia->update([
                'tipo_registro' => $tipo,
                'es_justificado' => false,
                'es_retardo' => $esRetardo,
                'comentarios' => null,
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Registro revertido a su estado original.']);
            }

            return back()->with('success', 'Registro revertido a su estado original.');
        }

        $asistencia->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Registro eliminado correctamente.']);
        }

        return back()->with('success', 'Registro eliminado correctamente. El día queda disponible para un nuevo registro.');
    }

    public function enviarAviso(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'tipo' => 'required|in:retardos,faltas,general,vestimenta',
            'cantidad_incidencias' => 'required|integer|min:0',
            'periodo' => 'required|string|max:255',
            'mensaje' => 'required|string',
        ]);

        $aviso = AvisoAsistencia::create([
            'empleado_id' => $request->empleado_id,
            'enviado_por' => auth()->id(),
            'tipo' => $request->tipo,
            'periodo' => $request->periodo,
            'cantidad_incidencias' => $request->cantidad_incidencias,
            'mensaje' => $request->mensaje,
            'leido' => false,
        ]);

        try {
            $empleado = $aviso->empleado;
            $usuarioEmpleado = $empleado->user;

            if ($usuarioEmpleado && $usuarioEmpleado->email) {
                $emailEmpleado = $usuarioEmpleado->email;
                $emailRRHH = config('rh.asistencia.user_rrhh_email');
                $esRRHH = mb_strtolower($emailEmpleado) === mb_strtolower($emailRRHH);
                $esSupervisor = $empleado->subordinados()->where('es_activo', true)->exists();

                $ccList = [config('rh.asistencia.cc_default')];

                if ($esRRHH) {
                    //
                } elseif ($esSupervisor) {
                    $ccList[] = config('rh.asistencia.cc_rrhh');
                } else {
                    $ccList[] = config('rh.asistencia.cc_rrhh');
                    if ($empleado->supervisor?->user?->email) {
                        $ccList[] = $empleado->supervisor->user->email;
                    }
                }

                Mail::to($emailEmpleado)
                    ->cc($ccList)
                    ->send(new AvisoAsistenciaMailable($aviso));
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar correo de aviso de asistencia: '.$e->getMessage());
        }

        return back()->with('success', 'Aviso enviado exitosamente al dashboard y por correo electrónico (si aplica).');
    }

    public function equipo(Request $request)
    {
        $user = auth()->user();
        $miEmpleado = $user->empleado;

        if (!$miEmpleado) {
            abort(403, 'No tienes acceso a este módulo.');
        }

        $subordinadosQuery = $miEmpleado->subordinados()->where('es_activo', true);
        
        $posicion = mb_strtolower($miEmpleado->posicion ?? '', 'UTF-8');
        $tieneSubordinados = $subordinadosQuery->exists();

        $esSupervisor = Str::contains($posicion, 'coordinador') || 
                        Str::contains($posicion, 'coordinadora') || 
                        Str::contains($posicion, 'direcc') || 
                        $tieneSubordinados;

        if (!$esSupervisor) {
            abort(403, 'No tienes acceso a este módulo.');
        }
        
        if (Str::contains($posicion, 'direcc')) {
            $subordinados = Empleado::where('es_activo', true)->pluck('id')->toArray();
        } else {
            $subordinados = $subordinadosQuery->pluck('id')->toArray();
        }

        if (empty($subordinados)) {
            return view('Recursos_Humanos.reloj_checador', [
                'empleados' => collect([]),
                'sinResultados' => false,
                'busqueda' => null,
                'horasTotales' => 0,
                'esSoloLectura' => true,
                'fechas' => collect([]),
                'porcentajeAsistencia' => 0,
                'topRetardos' => collect([]),
                'fechaInicioFormato' => now()->translatedFormat('d M'),
                'fechaFinFormato' => now()->translatedFormat('d M'),
                'retardos' => 0,
                'faltas' => 0,
                'todosEmpleados' => collect([]),
                'empleadosActivos' => collect([]),
            ]);
        }

        Carbon::setLocale('es');

        $inicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fin = $request->input('fecha_fin', now()->endOfMonth()->toDateString());
        $start = Carbon::parse($inicio);
        $end = Carbon::parse($fin);

        $fechas = $this->generarFechasPeriodo($start, $end);

        $dbFechaFin = Carbon::parse($fin)->addDay()->format('Y-m-d');

        $search = $request->input('search');

        $empleados = Empleado::query()
            ->where('es_activo', true)
            ->whereIn('id', $subordinados)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('id_empleado', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->with([
                'asistencias' => function ($q) use ($inicio, $dbFechaFin) {
                    $q->where('fecha', '>=', $inicio)
                        ->where('fecha', '<', $dbFechaFin);
                },
                'avisosAsistencia' => function ($q) {
                    $q->orderBy('created_at', 'desc')->with('enviadoPor');
                },
            ])
            ->paginate(15)
            ->withQueryString();

        $baseQuery = Asistencia::query()
            ->whereIn('empleado_id', $subordinados)
            ->where('fecha', '>=', $inicio)
            ->where('fecha', '<', $dbFechaFin);

        $kpis = $this->calcularKpisAsistencia($baseQuery);
        $horasTotales = $this->calcularHorasTrabajadas($baseQuery);

        $porcentajeAsistencia = $kpis['total'] > 0 ? round(($kpis['ok'] / $kpis['total']) * 100, 1) : 0;

        return view('Recursos_Humanos.reloj_checador', [
            'empleados' => $empleados,
            'sinResultados' => false,
            'busqueda' => $search,
            'horasTotales' => $horasTotales,
            'esSoloLectura' => true,
            'fechas' => $fechas,
            'porcentajeAsistencia' => $porcentajeAsistencia,
            'topRetardos' => collect([]),
            'fechaInicioFormato' => $start->translatedFormat('d M'),
            'fechaFinFormato' => $end->translatedFormat('d M'),
            'retardos' => $kpis['retardos'],
            'faltas' => $kpis['faltas'],
            'todosEmpleados' => Empleado::orderBy('nombre')->get(['id', 'nombre']),
            'empleadosActivos' => Empleado::where('es_activo', true)->orderBy('nombre')->get(['id', 'nombre', 'id_empleado']),
        ]);
    }
}

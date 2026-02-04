<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\CriterioEvaluacion;
use App\Models\Evaluacion;
use App\Models\EvaluacionDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EvaluacionController extends Controller
{
    private function isEvaluationWindowOpen()
    {
        // --- MODO PRUEBAS: SIEMPRE ABIERTO ---
        return true; 
        
        /*
        $now = Carbon::now();
        return ($now->month == 6 && $now->day >= 21 && $now->day <= 30) || 
                ($now->month == 12 && $now->day >= 1 && $now->day <= 31);
        */
    }

    // --- DETECCIÓN DE PUESTO (POSICIÓN) ---
    private function isAdminRH($empleado)
    {
        if (!$empleado) return false;
        $pos = mb_strtolower($empleado->posicion, 'UTF-8');
        
        return str_contains($pos, 'administración rh') || 
               str_contains($pos, 'administracion rh') ||
               str_contains($pos, 'administracion de rh') ||
               str_contains($pos, 'administración de rh');
    }

    // --- NUEVO MÉTODO INTELIGENTE PARA DETECTAR ÁREA TÉCNICA ---
    private function getTechnicalArea($posicion)
    {
        $pos = mb_strtolower($posicion, 'UTF-8');

        // Mapeo: Palabra clave en el puesto => Área en CriteriosEvaluacion
        $mapa = [
            'logistica'      => 'Logistica',
            'logística'      => 'Logistica',
            'legal'          => 'Legal',
            'abogado'        => 'Legal',
            'anexo 24'       => 'Anexo 24',
            'anexo 31'       => 'Anexo 24',
            'ti'             => 'TI',
            'sistemas'       => 'TI',
            'programador'    => 'TI',
            'soporte'        => 'TI',
            'pedimentos'     => 'Pedimentos',
            'glosa'          => 'Pedimentos',
            'auditoria'      => 'Auditoria',
            'auditor'        => 'Auditoria',
            'post-operacion' => 'Post-Operacion',
            'post operacion' => 'Post-Operacion',
            'postoperacion'  => 'Post-Operacion',
            'administracion rh' => 'Gestion RH', // Parte técnica de RH
            'recursos humanos'  => 'Gestion RH',
        ];

        foreach ($mapa as $keyword => $area) {
            if (str_contains($pos, $keyword)) {
                return $area;
            }
        }

        return 'General'; // Default si no coincide con nada
    }

    private function hasFullVisibility($user)
    {
        $empleado = Empleado::where('correo', $user->email)->first();
        if (!$empleado) return false;

        $pos = mb_strtolower($empleado->posicion, 'UTF-8');
        $area = mb_strtolower($empleado->area, 'UTF-8');

        return str_contains($pos, 'dirección') || 
               str_contains($pos, 'direccion') || 
               $this->isAdminRH($empleado) ||
               str_contains($area, 'recursos humanos');
    }

    public function index(Request $request)
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $defaultPeriod = ($currentMonth <= 6) ? "$currentYear | Enero - Junio" : "$currentYear | Julio - Diciembre";
        $selectedPeriod = $request->input('periodo', $defaultPeriod);
        
        $periodos = [
            ($currentYear + 1) . " | Enero - Junio",
            "$currentYear | Julio - Diciembre",
            "$currentYear | Enero - Junio",
            ($currentYear - 1) . " | Julio - Diciembre",
            ($currentYear - 1) . " | Enero - Junio",
        ];

        $user = Auth::user();
        $me = Empleado::where('correo', $user->email)->first();
        $hasFullVisibility = $this->hasFullVisibility($user);
        $isWindowOpen = $this->isEvaluationWindowOpen();

        $query = Empleado::query();

        if ($hasFullVisibility) {
            if ($request->has('area') && $request->area !== 'Todos') {
                $query->where('posicion', 'LIKE', '%' . $request->area . '%');
            }
        } elseif ($me) {
            $query->where(function($q) use ($me) {
                $q->where('supervisor_id', $me->id)
                  ->orWhere('id', $me->supervisor_id);
            });
        } else {
            $query->where('id', 0);
        }

        // Nota: Idealmente usar eager loading (with) aquí en el futuro
        $empleados = $query->get()->map(function($target) use ($selectedPeriod, $user) {
            $target->mi_evaluacion = Evaluacion::where('empleado_id', $target->id)
                ->where('evaluador_id', $user->id)
                ->where('periodo', $selectedPeriod)
                ->first();
            return $target;
        });

        $areas = Empleado::select('posicion')->distinct()->pluck('posicion');

        return view('Recursos_Humanos.evaluacion.index', compact('areas', 'empleados', 'periodos', 'selectedPeriod', 'isWindowOpen', 'hasFullVisibility'));
    }

    public function show(Request $request, $id)
    {
        $target = Empleado::findOrFail($id);
        $user = Auth::user();
        $me = Empleado::where('correo', $user->email)->first();
        $periodo = $request->query('periodo');

        if (!$periodo) return back()->with('error', 'Periodo requerido');

        $isAdminRH = $this->isAdminRH($me);
        $hasFullVisibility = $this->hasFullVisibility($user);

        // 1. Validar auto-evaluación
        if ($me && $me->id == $target->id && !$hasFullVisibility) {
            return redirect()->route('rh.evaluacion.index')->with('error', 'No puedes evaluarte a ti mismo.');
        }

        // 2. Permisos
        $canEvaluate = false;
        $isDirectSupervisor = false;
        $isBoss = false;

        if ($me) {
            $isDirectSupervisor = ($target->supervisor_id == $me->id);
            $isBoss = ($me->supervisor_id == $target->id);
            if ($isDirectSupervisor || $isBoss) $canEvaluate = true;
        }

        if ($isAdminRH) $canEvaluate = true;

        if (!$canEvaluate && !$hasFullVisibility) {
            return redirect()->route('rh.evaluacion.index')->with('error', 'No autorizado.');
        }

        // Cargar evaluación existente
        $evaluacion = Evaluacion::with('detalles')
            ->where('empleado_id', $id)
            ->where('evaluador_id', $user->id)
            ->where('periodo', $periodo)
            ->first();

        $respuestas = [];
        $observaciones = [];
        if ($evaluacion) {
            foreach ($evaluacion->detalles as $detalle) {
                $respuestas[$detalle->criterio_id] = $detalle->calificacion;
                $observaciones[$detalle->criterio_id] = $detalle->observaciones;
            }
        }

        // --- SELECCIÓN DE CRITERIOS (LOGICA MEJORADA) ---
        $queryCriterios = CriterioEvaluacion::query();
        $areaDisplay = '';

        // CASO A: Evaluación Hacia Arriba (Analista -> Jefe)
        if ($isBoss) {
             $queryCriterios->where('area', 'Evaluación Supervisor'); 
             $areaDisplay = 'Evaluación de Liderazgo (A tu Supervisor)';
        }
        // CASO B: Evaluación Hacia Abajo (Jefe -> Subordinado)
        elseif ($isDirectSupervisor) {
            // Detectamos el área técnica basada en el PUESTO del evaluado
            $areaTecnica = $this->getTechnicalArea($target->posicion);
            
            $queryCriterios->where(function($q) use ($areaTecnica) {
                $q->where('area', $areaTecnica)          // Preguntas técnicas
                  ->orWhere('area', 'Recursos Humanos'); // Preguntas Soft Skills
            });
            $areaDisplay = "Evaluación de Desempeño ($areaTecnica + RH)";
        }
        // CASO C: Admin RH que no es jefe directo (Solo ve Soft Skills)
        elseif ($isAdminRH) {
             $queryCriterios->where('area', 'Recursos Humanos');
             $areaDisplay = 'Evaluación de Habilidades Blandas (RH)';
        }
        // CASO D: Default
        else {
             $queryCriterios->where('area', 'Recursos Humanos');
             $areaDisplay = 'Evaluación General';
        }

        $criterios = $queryCriterios->get();
        
        $isWindowOpen = $this->isEvaluationWindowOpen();
        $isFinalized = ($evaluacion && $evaluacion->edit_count >= 1);
        $canEdit = $isWindowOpen && !$isFinalized;

        return view('Recursos_Humanos.evaluacion.show', [
            'empleado' => $target,
            'area' => $areaDisplay,
            'criterios' => $criterios,
            'periodo' => $periodo,
            'evaluacion' => $evaluacion,
            'respuestas' => $respuestas,
            'observaciones' => $observaciones,
            'is_locked' => !$canEdit,
            'isWindowOpen' => $isWindowOpen,
            'isMe' => ($me && $me->id == $target->id)
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->isEvaluationWindowOpen()) return back()->with('error', 'Periodo cerrado.');
        
        $existe = Evaluacion::where('empleado_id', $request->empleado_id)
            ->where('evaluador_id', Auth::id())
            ->where('periodo', $request->periodo)
            ->exists();
        if ($existe) return back()->with('error', 'Ya evaluaste a esta persona.');

        $target = Empleado::find($request->empleado_id);
        $me = Empleado::where('correo', Auth::user()->email)->first();
        if ($me && $me->id == $target->id) return abort(403);

        try {
            DB::beginTransaction();
            // Calcular promedio ponderado
            $criteriosDb = CriterioEvaluacion::whereIn('id', array_keys($request->calificaciones))->get();
            $totalPuntos = 0;
            $totalPeso = 0;
            foreach ($criteriosDb as $criterio) {
                $calificacion = $request->calificaciones[$criterio->id] ?? 0;
                $peso = $criterio->peso ?? 0;
                $totalPuntos += ($calificacion * $peso);
                $totalPeso += $peso;
            }
            $promedio = ($totalPeso > 0) ? ($totalPuntos / $totalPeso) : 0;

            $evaluacion = Evaluacion::create([
                'empleado_id' => $request->empleado_id,
                'evaluador_id' => Auth::id(),
                'periodo' => $request->periodo,
                'promedio_final' => $promedio,
                'comentarios_generales' => $request->comentarios_generales,
                'edit_count' => 1
            ]);

            foreach ($request->calificaciones as $criterioId => $valor) {
                EvaluacionDetalle::create([
                    'evaluacion_id' => $evaluacion->id,
                    'criterio_id' => $criterioId,
                    'calificacion' => $valor,
                    'observaciones' => $request->observaciones[$criterioId] ?? null
                ]);
            }
            DB::commit();
            return redirect()->route('rh.evaluacion.index', ['periodo' => $request->periodo])->with('success', 'Enviado.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $evaluacion = Evaluacion::findOrFail($id);
        if ($evaluacion->evaluador_id != Auth::id()) return abort(403);
        
        try {
            DB::beginTransaction();
            $criteriosDb = CriterioEvaluacion::whereIn('id', array_keys($request->calificaciones))->get();
            $totalPuntos = 0;
            $totalPeso = 0;
            foreach ($criteriosDb as $criterio) {
                $calificacion = $request->calificaciones[$criterio->id] ?? 0;
                $peso = $criterio->peso ?? 0;
                $totalPuntos += ($calificacion * $peso);
                $totalPeso += $peso;
            }
            $promedio = ($totalPeso > 0) ? ($totalPuntos / $totalPeso) : 0;

            $evaluacion->update([
                'promedio_final' => $promedio,
                'comentarios_generales' => $request->comentarios_generales,
                'edit_count' => $evaluacion->edit_count + 1
            ]);
            
            $evaluacion->detalles()->delete();
            foreach ($request->calificaciones as $criterioId => $valor) {
                EvaluacionDetalle::create([
                    'evaluacion_id' => $evaluacion->id,
                    'criterio_id' => $criterioId,
                    'calificacion' => $valor,
                    'observaciones' => $request->observaciones[$criterioId] ?? null
                ]);
            }
            DB::commit();
            return redirect()->route('rh.evaluacion.index', ['periodo' => $evaluacion->periodo])->with('success', 'Actualizado.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function resultados(Request $request, $id)
    {
        $user = Auth::user();
        if (!$this->hasFullVisibility($user)) return redirect()->route('rh.evaluacion.index');

        $empleado = Empleado::findOrFail($id);
        $periodo = $request->query('periodo');
        
        $evaluaciones = Evaluacion::with(['evaluador.empleado'])
            ->where('empleado_id', $id)
            ->where('periodo', $periodo)
            ->get();

        if ($evaluaciones->isEmpty()) return back()->with('error', 'Sin datos.');

        $promedioGeneral = $evaluaciones->avg('promedio_final');

        $desglose = $evaluaciones->map(function($eval) use ($empleado) {
            $evaluador = $eval->evaluador->empleado;
            $rol = 'Colaborador';
            if ($evaluador) {
                $pos = mb_strtolower($evaluador->posicion ?? '', 'UTF-8');
                $esAdminRH = str_contains($pos, 'administración rh') || str_contains($pos, 'administracion rh');

                if ($empleado->supervisor_id == $evaluador->id) $rol = 'Supervisor Directo';
                elseif ($evaluador->supervisor_id == $empleado->id) $rol = 'Subordinado';
                elseif ($esAdminRH) $rol = 'Administración RH';
            }
            $eval->rol_evaluador = $rol;
            $eval->nombre_evaluador = $evaluador ? ($evaluador->nombre . ' ' . $evaluador->apellido_paterno) : $eval->evaluador->name;
            return $eval;
        });

        return view('Recursos_Humanos.evaluacion.resultados', compact('empleado', 'periodo', 'promedioGeneral', 'desglose'));
    }
}
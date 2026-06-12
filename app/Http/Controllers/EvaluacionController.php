<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\CriterioEvaluacion;
use App\Models\Evaluacion;
use App\Models\EvaluacionDetalle;
use App\Models\EvaluacionVentana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EvaluacionController extends Controller
{
    private function isEvaluationWindowOpen(): bool
    {
        // Primero consulta si hay una ventana configurada en BD
        if (EvaluacionVentana::where('activo', true)->exists()) {
            return EvaluacionVentana::estaAbierta();
        }

        // Fallback: lógica semestral original
        $now = Carbon::now();
        return ($now->month == 6 && $now->day >= 21 && $now->day <= 30) ||
               ($now->month == 12 && $now->day >= 1  && $now->day <= 31);
    }

    // -------------------------------------------------------
    // Gestión de Ventanas de Evaluación (solo Admin RH)
    // -------------------------------------------------------

    public function getVentanas()
    {
        $user = Auth::user();
        $me   = Empleado::where('correo', $user->email)->first();

        if (!$this->isAdminRH($me) && !$user->isAdmin()) {
            abort(403);
        }

        $ventanas = EvaluacionVentana::orderByDesc('fecha_apertura')->get();
        $ventanaActiva = EvaluacionVentana::ventanaActual();

        return response()->json([
            'ventanas'       => $ventanas,
            'ventana_activa' => $ventanaActiva,
        ]);
    }

    public function saveVentana(Request $request)
    {
        $user = Auth::user();
        $me   = Empleado::where('correo', $user->email)->first();

        if (!$this->isAdminRH($me) && !$user->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'nombre'         => 'required|string|max:100',
            'fecha_apertura' => 'required|date',
            'fecha_cierre'   => 'required|date|after_or_equal:fecha_apertura',
        ]);

        // Desactivar ventanas previas si se activa la nueva
        if ($request->boolean('activo', true)) {
            EvaluacionVentana::where('activo', true)->update(['activo' => false]);
        }

        $ventana = EvaluacionVentana::create([
            'nombre'         => $request->nombre,
            'fecha_apertura' => $request->fecha_apertura,
            'fecha_cierre'   => $request->fecha_cierre,
            'activo'         => $request->boolean('activo', true),
            'creado_por'     => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'ventana' => $ventana,
            'message' => 'Ventana de evaluación guardada correctamente.',
        ]);
    }

    public function toggleVentana(Request $request, $id)
    {
        $user = Auth::user();
        $me   = Empleado::where('correo', $user->email)->first();

        if (!$this->isAdminRH($me) && !$user->isAdmin()) {
            abort(403);
        }

        $ventana = EvaluacionVentana::findOrFail($id);

        // Si se va a activar, desactivar las demás
        if (!$ventana->activo) {
            EvaluacionVentana::where('activo', true)->update(['activo' => false]);
        }

        $ventana->activo = !$ventana->activo;
        $ventana->save();

        return response()->json(['success' => true, 'activo' => $ventana->activo]);
    }

    // --- DETECCIÓN DE PUESTO (POSICIÓN) ---
    private function isAdminRH($empleado)
    {
        if (!$empleado)
            return false;
        $pos  = mb_strtolower($empleado->posicion ?? '', 'UTF-8');
        $area = mb_strtolower($empleado->area ?? '', 'UTF-8');

        return str_contains($pos, 'administración rh') ||
            str_contains($pos, 'administracion rh') ||
            str_contains($pos, 'administracion de rh') ||
            str_contains($pos, 'administración de rh') ||
            str_contains($area, 'recursos humanos') ||
            str_contains($area, 'administracion rh') ||
            str_contains($area, 'administración rh');
    }

    // --- NUEVO MÉTODO INTELIGENTE PARA DETECTAR ÁREA TÉCNICA ---
    private function getTechnicalArea($posicion)
    {
        $pos = mb_strtolower($posicion, 'UTF-8');

        // Mapeo: Palabra clave en el puesto => Área en CriteriosEvaluacion
        $mapa = [
            'logistica' => 'Logistica',
            'logística' => 'Logistica',
            'legal' => 'Legal',
            'abogado' => 'Legal',
            'anexo 24' => 'Anexo 24',
            'anexo 31' => 'Anexo 24',
            'ti' => 'TI',
            'sistemas' => 'TI',
            'programador' => 'TI',
            'soporte' => 'TI',
            'pedimentos' => 'Pedimentos',
            'glosa' => 'Pedimentos',
            'auditoria' => 'Auditoria',
            'auditor' => 'Auditoria',
            'post-operacion' => 'Post-Operacion',
            'post operacion' => 'Post-Operacion',
            'postoperacion' => 'Post-Operacion',
            'administracion rh' => 'Gestion RH', // Parte técnica de RH
            'recursos humanos' => 'Gestion RH',
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
        if (!$empleado)
            return false;

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
        $hasFullVisibility       = $this->hasFullVisibility($user);
        $isWindowOpen            = $this->isEvaluationWindowOpen();
        $isAdminRH               = $this->isAdminRH($me);
        $puedeGestionarVentanas  = $isAdminRH || $user->isAdmin();
        $ventanaActiva           = EvaluacionVentana::ventanaActual();

        $query = Empleado::query();

        if ($hasFullVisibility) {
            if ($request->has('area') && $request->area !== 'Todos') {
                $query->where('posicion', 'LIKE', '%' . $request->area . '%');
            }
        }
        elseif ($me) {
            $query->where(function ($q) use ($me) {
                $q->where('supervisor_id', $me->id)
                    ->orWhere('id', $me->supervisor_id);
            });
        }
        else {
            $query->where('id', 0);
        }

        $empleados = $query->get()->map(function ($target) use ($selectedPeriod, $user, $ventanaActiva, $me, $isAdminRH) {
            $baseEvalQuery = function ($tipo) use ($target, $user, $ventanaActiva, $selectedPeriod) {
                $q = Evaluacion::where('empleado_id', $target->id)
                    ->where('evaluador_id', $user->id)
                    ->where('tipo', $tipo);
                if ($ventanaActiva) {
                    $q->where('ventana_id', $ventanaActiva->id);
                } else {
                    $q->where('periodo', $selectedPeriod);
                }
                return $q;
            };

            $isDirectSupervisor = $me && $target->supervisor_id == $me->id;
            $target->dual_role = $isAdminRH && $isDirectSupervisor;

            $target->is_my_boss = $me && $me->supervisor_id == $target->id;

            $target->evaluacion_actual = $baseEvalQuery('supervisor')->first();

            if ($target->dual_role) {
                $target->evaluacion_adminrh = $baseEvalQuery('admin_rh')->first();
            }

            if ($target->is_my_boss) {
                $target->evaluacion_subordinado = $baseEvalQuery('subordinado')->first();
            }

            return $target;
        });

        $areas = Empleado::select('posicion')->distinct()->pluck('posicion');

        return view('Recursos_Humanos.evaluacion.index', compact('areas', 'empleados', 'periodos', 'selectedPeriod', 'isWindowOpen', 'hasFullVisibility', 'isAdminRH', 'puedeGestionarVentanas', 'ventanaActiva'));
    }

    public function show(Request $request, $id)
    {
        $target = Empleado::findOrFail($id);
        $user = Auth::user();
        $me = Empleado::where('correo', $user->email)->first();
        $periodo = $request->query('periodo');
        $tipo = $request->query('tipo', 'supervisor');

        if (!$periodo)
            return back()->with('error', 'Periodo requerido');

        $isAdminRH = $this->isAdminRH($me);
        $hasFullVisibility = $this->hasFullVisibility($user);

        $isDirectSupervisor = $me && $target->supervisor_id == $me->id;
        $isBoss = $me && $me->supervisor_id == $target->id;

        $tiposPermitidos = [];
        if ($isAdminRH || $hasFullVisibility) $tiposPermitidos[] = 'admin_rh';
        if ($isDirectSupervisor || $hasFullVisibility) $tiposPermitidos[] = 'supervisor';
        if ($isBoss || $hasFullVisibility) $tiposPermitidos[] = 'subordinado';
        $tiposPermitidos = array_unique($tiposPermitidos);

        if (!in_array($tipo, $tiposPermitidos)) {
            return redirect()->route('rh.evaluacion.index')->with('error', 'No autorizado para este tipo de evaluación.');
        }

        $ventanaActualShow = EvaluacionVentana::ventanaActual();
        $qEval = Evaluacion::with('detalles')
            ->where('empleado_id', $id)
            ->where('evaluador_id', $user->id)
            ->where('tipo', $tipo);
        if ($ventanaActualShow) {
            $qEval->where('ventana_id', $ventanaActualShow->id);
        } else {
            $qEval->where('periodo', $periodo);
        }
        $evaluacion = $qEval->first();

        $respuestas = [];
        $observaciones = [];
        if ($evaluacion) {
            foreach ($evaluacion->detalles as $detalle) {
                $respuestas[$detalle->criterio_id] = $detalle->calificacion;
                $observaciones[$detalle->criterio_id] = $detalle->observaciones;
            }
        }

        switch ($tipo) {
            case 'admin_rh':
                $criterios = CriterioEvaluacion::where('area', 'Administracion RH')->get();
                $areaDisplay = 'Evaluación de Habilidades Blandas y Valores (RH)';
                break;
            case 'supervisor':
                $areaTecnica = $this->getTechnicalArea($target->posicion);
                $criterios = CriterioEvaluacion::where(function ($q) use ($areaTecnica) {
                    $q->where('area', $areaTecnica)->orWhere('area', 'Recursos Humanos');
                })->where('criterio', '!=', 'Puntualidad y Asistencia')->get();
                $areaDisplay = "Evaluación de Desempeño ($areaTecnica + Soft Skills)";
                break;
            case 'subordinado':
                $criterios = CriterioEvaluacion::where('area', 'Evaluacion Supervisor')->get();
                $areaDisplay = 'Evaluación de Liderazgo (A tu Supervisor)';
                break;
            default:
                return redirect()->route('rh.evaluacion.index')->with('error', 'Tipo de evaluación inválido.');
        }

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
            'tipo' => $tipo,
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->isEvaluationWindowOpen())
            return back()->with('error', 'Periodo cerrado.');

        $ventanaActiva = EvaluacionVentana::ventanaActual();
        if (!$ventanaActiva)
            return back()->with('error', 'No hay una ventana de evaluación activa.');

        $request->validate([
            'tipo' => 'required|string|in:supervisor,admin_rh,subordinado',
            'observaciones' => 'required|array',
            'observaciones.*' => 'required|string|min:1',
            'comentarios_generales' => 'required|string|min:1',
        ], [
            'observaciones.*.required' => 'El comentario para cada criterio es obligatorio.',
            'comentarios_generales.required' => 'Los comentarios generales son obligatorios.',
        ]);

        $existe = Evaluacion::where('empleado_id', $request->empleado_id)
            ->where('evaluador_id', Auth::id())
            ->where('ventana_id', $ventanaActiva->id)
            ->where('tipo', $request->tipo)
            ->exists();
        if ($existe)
            return back()->with('error', 'Ya evaluaste a esta persona como ' . $request->tipo . ' en este periodo.');

        $target = Empleado::find($request->empleado_id);
        $me = Empleado::where('correo', Auth::user()->email)->first();

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
                'ventana_id' => $ventanaActiva->id,
                'tipo' => $request->tipo,
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
        }
        catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $evaluacion = Evaluacion::findOrFail($id);
        if ($evaluacion->evaluador_id != Auth::id())
            return abort(403);

        $request->validate([
            'tipo' => 'required|string|in:supervisor,admin_rh,subordinado',
            'observaciones' => 'required|array',
            'observaciones.*' => 'required|string|min:1',
            'comentarios_generales' => 'required|string|min:1',
        ], [
            'observaciones.*.required' => 'El comentario para cada criterio es obligatorio.',
            'comentarios_generales.required' => 'Los comentarios generales son obligatorios.',
        ]);

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
        }
        catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $me   = Empleado::where('correo', $user->email)->first();

        if (!$this->isAdminRH($me) && !$user->isAdmin()) {
            abort(403);
        }

        $evaluacion = Evaluacion::findOrFail($id);
        $evaluacion->delete(); // cascade borra los detalles

        return back()->with('success', 'Evaluación eliminada correctamente.');
    }

    public function resultados(Request $request, $id)
    {
        $user = Auth::user();
        if (!$this->hasFullVisibility($user))
            return redirect()->route('rh.evaluacion.index');

        $currentYear = Carbon::now()->year;
        $periodos = [
            ($currentYear + 1) . " | Enero - Junio",
            "$currentYear | Julio - Diciembre",
            "$currentYear | Enero - Junio",
            ($currentYear - 1) . " | Julio - Diciembre",
            ($currentYear - 1) . " | Enero - Junio",
        ];

        $empleado = Empleado::findOrFail($id);
        $periodo = $request->query('periodo', $periodos[2] ?? "$currentYear | Enero - Junio");

        $evaluaciones = Evaluacion::with(['evaluador.empleado', 'detalles.criterio'])
            ->where('empleado_id', $id)
            ->where('periodo', $periodo)
            ->get();

        if ($evaluaciones->isEmpty())
            return back()->with('error', 'Sin datos.');

        $promedioGeneral = $evaluaciones->avg('promedio_final');

        $desglose = $evaluaciones->map(function ($eval) use ($empleado) {
            $evaluador = $eval->evaluador->empleado;
            $rol = 'Colaborador';
            if ($evaluador) {
                $pos = mb_strtolower($evaluador->posicion ?? '', 'UTF-8');
                $esAdminRH = str_contains($pos, 'administración rh') || str_contains($pos, 'administracion rh');

                if ($empleado->supervisor_id == $evaluador->id)
                    $rol = 'Supervisor Directo';
                elseif ($evaluador->supervisor_id == $empleado->id)
                    $rol = 'Subordinado';
                elseif ($esAdminRH)
                    $rol = 'Administración RH';
            }
            $eval->rol_evaluador = $rol;
            $eval->nombre_evaluador = $evaluador ? ($evaluador->nombre . ' ' . $evaluador->apellido_paterno) : $eval->evaluador->name;
            return $eval;
        });

        return view('Recursos_Humanos.evaluacion.resultados', compact('empleado', 'periodo', 'promedioGeneral', 'desglose', 'periodos'));
    }

    public function resultadosExcel(Request $request, $id)
    {
        $user = Auth::user();
        if (!$this->hasFullVisibility($user))
            return redirect()->route('rh.evaluacion.index');

        $empleado = Empleado::findOrFail($id);

        $currentYear = Carbon::now()->year;
        $periodos = [
            ($currentYear + 1) . " | Enero - Junio",
            "$currentYear | Julio - Diciembre",
            "$currentYear | Enero - Junio",
            ($currentYear - 1) . " | Julio - Diciembre",
            ($currentYear - 1) . " | Enero - Junio",
        ];
        $periodo = $request->query('periodo', $periodos[2] ?? "$currentYear | Enero - Junio");

        $evaluaciones = Evaluacion::with(['evaluador.empleado', 'detalles.criterio'])
            ->where('empleado_id', $id)
            ->where('periodo', $periodo)
            ->get();

        if ($evaluaciones->isEmpty())
            return back()->with('error', 'Sin datos.');

        $promedioGeneral = $evaluaciones->avg('promedio_final');

        $desglose = $evaluaciones->map(function ($eval) use ($empleado) {
            $evaluador = $eval->evaluador->empleado;
            $rol = 'Colaborador';
            if ($evaluador) {
                $pos = mb_strtolower($evaluador->posicion ?? '', 'UTF-8');
                $esAdminRH = str_contains($pos, 'administración rh') || str_contains($pos, 'administracion rh');

                if ($empleado->supervisor_id == $evaluador->id)
                    $rol = 'Supervisor Directo';
                elseif ($evaluador->supervisor_id == $empleado->id)
                    $rol = 'Subordinado';
                elseif ($esAdminRH)
                    $rol = 'Administración RH';
            }
            $eval->rol_evaluador = $rol;
            $eval->nombre_evaluador = $evaluador ? ($evaluador->nombre . ' ' . $evaluador->apellido_paterno) : $eval->evaluador->name;
            return $eval;
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Sheet 1: Resumen
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');

        $sheet->setCellValue('A1', 'RESULTADOS DE EVALUACIÓN');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet->setCellValue('A3', 'Empleado:');
        $sheet->setCellValue('B3', $empleado->nombre . ' ' . $empleado->apellido_paterno);
        $sheet->setCellValue('A4', 'Puesto:');
        $sheet->setCellValue('B4', $empleado->posicion ?? '-');
        $sheet->setCellValue('A5', 'Periodo:');
        $sheet->setCellValue('B5', $periodo);
        $sheet->setCellValue('A6', 'Calificación Final:');
        $sheet->setCellValue('B6', number_format($promedioGeneral, 1) . ' / 100');

        $sheet->setCellValue('A8', 'Evaluador');
        $sheet->setCellValue('B8', 'Relación');
        $sheet->setCellValue('C8', 'Tipo');
        $sheet->setCellValue('D8', 'Nota');
        $sheet->setCellValue('E8', 'Comentarios');

        $headerStyle = $sheet->getStyle('A8:E8');
        $headerStyle->getFont()->setBold(true)->setSize(10);
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setARGB('FF334155');
        $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');

        $cols = ['A', 'B', 'C', 'D', 'E'];
        foreach ($cols as $c) $sheet->getColumnDimension($c)->setAutoSize(true);

        $row = 9;
        foreach ($desglose as $eval) {
            $sheet->setCellValue("A$row", $eval->nombre_evaluador);
            $sheet->setCellValue("B$row", $eval->rol_evaluador);
            $sheet->setCellValue("C$row", match ($eval->tipo ?? 'supervisor') {
                'admin_rh' => 'Admin RH',
                'subordinado' => 'Subordinado',
                default => 'Supervisor',
            });
            $sheet->setCellValue("D$row", number_format($eval->promedio_final, 1));
            $sheet->setCellValue("E$row", $eval->comentarios_generales ?? '');
            $row++;
        }

        // Sheet 2: Detalle
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Detalle');

        $sheet2->setCellValue('A1', 'DETALLE DE EVALUACIÓN');
        $sheet2->mergeCells('A1:F1');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet2->setCellValue('A3', 'Empleado:');
        $sheet2->setCellValue('B3', $empleado->nombre . ' ' . $empleado->apellido_paterno);
        $sheet2->setCellValue('A4', 'Periodo:');
        $sheet2->setCellValue('B4', $periodo);

        $sheet2->setCellValue('A6', 'Evaluador');
        $sheet2->setCellValue('B6', 'Pregunta');
        $sheet2->setCellValue('C6', 'Peso');
        $sheet2->setCellValue('D6', 'Nota');
        $sheet2->setCellValue('E6', 'Comentario');

        $hdr2 = $sheet2->getStyle('A6:E6');
        $hdr2->getFont()->setBold(true)->setSize(10);
        $hdr2->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $hdr2->getFill()->getStartColor()->setARGB('FF334155');
        $hdr2->getFont()->getColor()->setARGB('FFFFFFFF');

        foreach (['A', 'B', 'C', 'D', 'E'] as $c) $sheet2->getColumnDimension($c)->setAutoSize(true);

        $row = 7;
        foreach ($desglose as $eval) {
            $evaluatorName = $eval->nombre_evaluador;
            foreach ($eval->detalles as $detalle) {
                $sheet2->setCellValue("A$row", $evaluatorName);
                $sheet2->setCellValue("B$row", $detalle->criterio->descripcion ?? 'Criterio #' . $detalle->criterio_id);
                $sheet2->setCellValue("C$row", ($detalle->criterio->peso ?? '-') . '%');
                $sheet2->setCellValue("D$row", number_format($detalle->calificacion, 0));
                $sheet2->setCellValue("E$row", $detalle->observaciones ?? '');
                $row++;
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = "evaluacion_{$empleado->id}_{$periodo}.xlsx";

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}
<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Logistica\Aduana;
use App\Models\Logistica\Cliente;
use App\Models\Logistica\MatrizSeguimiento;
use App\Models\Logistica\MatrizSeguimientoComentario;
use App\Models\Logistica\Pedimento;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MatrizSeguimientoController extends Controller
{
    public function index(Request $request)
    {
        $user           = auth()->user();
        $empleadoActual = $user ? Empleado::where('correo', $user->email)->first() : null;

        // ── Detectar si es coordinador de logística ──────────────────────────
        $esCoordinador = false;
        if ($empleadoActual && $empleadoActual->es_coordinador) {
            $area     = mb_strtolower($empleadoActual->area     ?? '', 'UTF-8');
            $posicion = mb_strtolower($empleadoActual->posicion ?? '', 'UTF-8');
            foreach (['logística', 'logistica', 'sistemas', 'dirección', 'direccion'] as $p) {
                if (str_contains($area, $p) || str_contains($posicion, $p)) {
                    $esCoordinador = true;
                    break;
                }
            }
        }

        // ── Status que significan "completado" ────────────────────────────────
        $statusCompletados = ['Entregado', 'Cancelado'];

        // ── Parámetros de filtro (solo coordinador) ───────────────────────────
        $filtroCliente   = $request->input('filtro_cliente');
        $filtroEjecutivo = $request->input('filtro_ejecutivo'); // user_id

        // ── Queries base ──────────────────────────────────────────────────────
        $queryActivos     = MatrizSeguimiento::with(['historial', 'user'])
                                ->whereNotIn('status', $statusCompletados);
        $queryCompletados = MatrizSeguimiento::with(['historial', 'user'])
                                ->whereIn('status', $statusCompletados);

        if ($esCoordinador) {
            if ($filtroCliente) {
                $queryActivos->where('proveedor_cliente', 'like', '%' . $filtroCliente . '%');
                $queryCompletados->where('proveedor_cliente', 'like', '%' . $filtroCliente . '%');
            }
            if ($filtroEjecutivo) {
                $queryActivos->where('user_id', $filtroEjecutivo);
                $queryCompletados->where('user_id', $filtroEjecutivo);
            }
            // Sin filtros: solo muestra las operaciones propias del coordinador
            if (!$filtroCliente && !$filtroEjecutivo && $user) {
                $queryActivos->where('user_id', $user->id);
                $queryCompletados->where('user_id', $user->id);
            }
        } else {
            // Ejecutivo: solo ve sus propias operaciones
            if ($user) {
                $queryActivos->where('user_id', $user->id);
                $queryCompletados->where('user_id', $user->id);
            }
        }

        $registros   = $queryActivos->orderByDesc('created_at')->get();
        $completados = $queryCompletados->orderByDesc('created_at')->get();

        // ── Clientes del catálogo asignados al ejecutivo (para el SELECT del formulario) ──
        $misClientes = $empleadoActual
            ? Cliente::where('ejecutivo_asignado_id', $empleadoActual->id)
                ->orderBy('cliente')
                ->pluck('cliente')
            : collect();

        // ── Valores únicos de proveedor_cliente para el filtro ────────────────
        $todosClientes = $esCoordinador
            ? MatrizSeguimiento::whereNotNull('proveedor_cliente')
                ->distinct()
                ->orderBy('proveedor_cliente')
                ->pluck('proveedor_cliente')
            : collect();

        // ── Valores únicos de proveedor_cliente del ejecutivo (para su filtro) ─
        $misClientesFiltro = $user
            ? MatrizSeguimiento::where('user_id', $user->id)
                ->whereNotNull('proveedor_cliente')
                ->distinct()
                ->orderBy('proveedor_cliente')
                ->pluck('proveedor_cliente')
            : collect();

        // ── Ejecutivos para el filtro del coordinador ─────────────────────────
        $ejecutivos = collect();
        if ($esCoordinador) {
            $ejecutivos = Empleado::where(function ($q) {
                $q->where('posicion', 'like', '%logistic%')
                  ->orWhere('area', 'like', '%logistic%');
            })->where('es_activo', true)->whereNotNull('user_id')->orderBy('nombre')->get(['id', 'nombre', 'user_id']);
        }

        $tiposOperacion  = MatrizSeguimiento::TIPOS_OPERACION;
        $aduanas         = Aduana::orderBy('aduana')->orderBy('seccion')->get(['aduana', 'seccion', 'denominacion']);
        $claves          = Pedimento::orderBy('clave')->get(['clave', 'descripcion']);
        $cargaTipos      = MatrizSeguimiento::CARGA_TIPOS;
        $tiposContenedor = MatrizSeguimiento::TIPOS_CONTENEDOR;
        $miUserId        = $user?->id;

        return view('Logistica.matriz-seguimiento', compact(
            'registros', 'completados', 'tiposOperacion',
            'aduanas', 'claves', 'cargaTipos', 'tiposContenedor',
            'misClientes', 'misClientesFiltro', 'esCoordinador', 'ejecutivos', 'todosClientes',
            'filtroCliente', 'filtroEjecutivo', 'miUserId'
        ));
    }

    public function reportes(Request $request)
    {
        $user           = auth()->user();
        $empleadoActual = $user ? Empleado::where('correo', $user->email)->first() : null;

        $esCoordinador = false;
        if ($empleadoActual && $empleadoActual->es_coordinador) {
            $area     = mb_strtolower($empleadoActual->area     ?? '', 'UTF-8');
            $posicion = mb_strtolower($empleadoActual->posicion ?? '', 'UTF-8');
            foreach (['logística', 'logistica', 'sistemas', 'dirección', 'direccion'] as $p) {
                if (str_contains($area, $p) || str_contains($posicion, $p)) {
                    $esCoordinador = true;
                    break;
                }
            }
        }

        if (!$esCoordinador) {
            abort(403, 'Acceso solo para coordinadores.');
        }

        $filtroAplicado = $request->has('aplicar');
        $periodo        = $request->input('periodo', 'semana');
        $filtroDesde    = $request->input('desde');
        $filtroHasta    = $request->input('hasta');

        // ── Semana actual (siempre visible) ───────────────────────────────────
        $semanaActual = MatrizSeguimiento::with('user')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->orderByDesc('created_at')
            ->get();

        // ── Ranking últimos 7 días ─────────────────────────────────────────────
        $ultimosSieteDias = MatrizSeguimiento::with('user')
            ->where('updated_at', '>=', now()->subDays(7))
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get();

        // ── Todas las ops para resumen y gráficas ─────────────────────────────
        $allOps = MatrizSeguimiento::with('user')->get();

        // ── Operaciones filtradas (solo cuando se aplica filtro) ──────────────
        $operacionesFiltradas = collect();
        if ($filtroAplicado) {
            $q = MatrizSeguimiento::with('user');
            if ($periodo === 'semana') {
                $q->where('created_at', '>=', now()->subWeek());
            } elseif ($periodo === 'mes') {
                $q->where('created_at', '>=', now()->subMonth());
            } elseif ($periodo === 'año') {
                $q->where('created_at', '>=', now()->subYear());
            } elseif ($filtroDesde && $filtroHasta) {
                $q->whereBetween('created_at', [$filtroDesde, $filtroHasta . ' 23:59:59']);
            }
            $operacionesFiltradas = $q->orderByDesc('created_at')->get();
        }

        // ── Distribución por status ────────────────────────────────────────────
        $statusList    = ['Pendiente', 'En Tránsito', 'En Aduana', 'Previo Programado', 'Cita Programada', 'Despachado', 'Entregado', 'Cancelado'];
        $statsByStatus = [];
        foreach ($statusList as $s) {
            $cnt = $allOps->where('status', $s)->count();
            if ($cnt > 0) $statsByStatus[$s] = $cnt;
        }

        // ── Por tipo de operación ──────────────────────────────────────────────
        $statsByTipo = $allOps->whereNotNull('tipo_operacion')
            ->groupBy('tipo_operacion')
            ->map->count()
            ->sortDesc()
            ->toArray();

        // ── Línea: últimos 30 días ─────────────────────────────────────────────
        $rawByDay = MatrizSeguimiento::selectRaw('DATE(created_at) as dia, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia')
            ->toArray();

        $lineLabels = [];
        $lineData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $day          = now()->subDays($i)->format('Y-m-d');
            $lineLabels[] = now()->subDays($i)->format('d/m');
            $lineData[]   = $rawByDay[$day] ?? 0;
        }

        // ── Top clientes (bar) ────────────────────────────────────────────────
        $topClientes = $allOps->whereNotNull('proveedor_cliente')
            ->groupBy('proveedor_cliente')
            ->map->count()
            ->sortDesc()
            ->take(8)
            ->toArray();

        // ── Eficiencia ────────────────────────────────────────────────────────
        $totalOps    = $allOps->count();
        $exitosas    = $allOps->where('resultado', 'Exitoso')->count();
        $demoradas   = $allOps->where('resultado', 'Demorado')->count();
        $enProceso   = $allOps->where('resultado', 'En Proceso')->count();
        $canceladas  = $allOps->where('resultado', 'Cancelado')->count();
        $completadas = $exitosas + $demoradas + $canceladas;
        $eficiencia  = $completadas > 0 ? round(($exitosas / $completadas) * 100, 1) : 0;

        return view('Logistica.reportes', compact(
            'semanaActual', 'ultimosSieteDias', 'operacionesFiltradas',
            'statsByStatus', 'statsByTipo', 'lineLabels', 'lineData',
            'totalOps', 'exitosas', 'demoradas', 'enProceso', 'canceladas',
            'eficiencia', 'completadas',
            'filtroAplicado', 'periodo', 'filtroDesde', 'filtroHasta',
            'topClientes'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $data['user_id']   = auth()->id();
        $data['status']    = $this->calcularStatus($data);
        $data['resultado'] = $this->calcularResultado($data);

        $registro = MatrizSeguimiento::create($data);

        return response()->json(['success' => true, 'registro' => $registro]);
    }

    public function update(Request $request, MatrizSeguimiento $seguimiento)
    {
        $data = $this->validar($request);
        $data['status']    = $this->calcularStatus($data);
        $data['resultado'] = $this->calcularResultado($data);

        $seguimiento->update($data);

        return response()->json(['success' => true, 'registro' => $seguimiento]);
    }

    public function completar(MatrizSeguimiento $seguimiento)
    {
        $data = $seguimiento->toArray();
        $data['arribo_planta'] = today()->format('Y-m-d');
        $data['status']        = 'Entregado';
        $data['resultado']     = $this->calcularResultado($data);

        $seguimiento->update([
            'arribo_planta' => $data['arribo_planta'],
            'status'        => $data['status'],
            'resultado'     => $data['resultado'],
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(MatrizSeguimiento $seguimiento)
    {
        $seguimiento->historial()->delete();
        $seguimiento->delete();

        return response()->json(['success' => true]);
    }

    public function getComentarios(MatrizSeguimiento $seguimiento)
    {
        $comentarios = $seguimiento->historial()
            ->with('user:id,name')
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'comentario'  => $c->comentario,
                'usuario'     => $c->user?->name ?? 'Sistema',
                'fecha'       => $c->created_at->format('d/m/Y H:i'),
            ]);

        return response()->json(['comentarios' => $comentarios]);
    }

    public function storeComentario(Request $request, MatrizSeguimiento $seguimiento)
    {
        $request->validate(['comentario' => 'required|string|max:2000']);

        $comentario = MatrizSeguimientoComentario::create([
            'matriz_seguimiento_id' => $seguimiento->id,
            'user_id'               => auth()->id(),
            'comentario'            => $request->comentario,
        ]);

        return response()->json([
            'success'    => true,
            'comentario' => [
                'id'         => $comentario->id,
                'comentario' => $comentario->comentario,
                'usuario'    => auth()->user()?->name ?? 'Sistema',
                'fecha'      => $comentario->created_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function validar(Request $request): array
    {
        return $request->validate([
            'ref_interna'        => 'nullable|string|max:100',
            'proveedor_cliente'  => 'nullable|string|max:255',
            'cliente_operacion'  => 'nullable|string|max:255',
            'factura'            => 'nullable|string|max:100',
            'impo_ex'          => 'nullable|in:IMP,EX',
            'tipo_operacion'   => 'nullable|string|max:100',
            'transporte'       => 'nullable|string|max:255',
            'naviera'          => 'nullable|string|max:255',
            'buque'            => 'nullable|string|max:255',
            'carga_tipo'       => 'nullable|string|max:10',
            'no_contenedor'    => 'nullable|string|max:100',
            'tipo_contenedor'  => 'nullable|string|max:50',
            'aduana'           => 'nullable|string|max:255',
            'clave'            => 'nullable|string|max:100',
            'pedimento'        => 'nullable|string|max:100',
            'bl_guia'          => 'nullable|string|max:100',
            'etd'              => 'nullable|date',
            'eta'              => 'nullable|date',
            'dias_libres'      => 'nullable|integer|min:0|max:99',
            'previo'           => 'nullable|date',
            'cita_despacho'    => 'nullable|date',
            'arribo_planta'    => 'nullable|date',
            'target'           => 'nullable|string|max:100',
            'comentarios'      => 'nullable|string',
        ]);
    }

    private function calcularStatus(array $data): string
    {
        if (!empty($data['arribo_planta']))  return 'Entregado';
        if (!empty($data['cita_despacho'])) return 'Cita Programada';
        if (!empty($data['previo']))         return 'Previo Programado';
        if (!empty($data['eta']))            return 'En Aduana';
        if (!empty($data['etd']))            return 'En Tránsito';
        return 'Pendiente';
    }

    private function calcularResultado(array $data): string
    {
        if (empty($data['eta'])) return 'En Proceso';

        $targetDias = ($data['tipo_operacion'] ?? '') === 'Marítimo' ? 7 : 3;
        $eta = Carbon::parse($data['eta']);

        if (!empty($data['arribo_planta'])) {
            $dias = $eta->diffInDays(Carbon::parse($data['arribo_planta']));
            return $dias <= $targetDias ? 'Exitoso' : 'Demorado';
        }

        $diasTranscurridos = $eta->diffInDays(now());
        return $diasTranscurridos > $targetDias ? 'Demorado' : 'En Proceso';
    }
}

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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

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

        $registros = $queryActivos
            ->orderByRaw("CASE WHEN eta IS NULL THEN 9999 ELSE DATEDIFF(DATE_ADD(eta, INTERVAL COALESCE(dias_libres, 20) DAY), CURDATE()) END ASC")
            ->orderByDesc('created_at')
            ->get();
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

    // ── Exportar Excel ───────────────────────────────────────────────
    public function exportar(Request $request)
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

        $cliente = $request->input('cliente');

        $query = MatrizSeguimiento::with(['historial', 'user']);
        if (!$esCoordinador && $user) {
            $query->where('user_id', $user->id);
        }
        if ($cliente) {
            $query->where('proveedor_cliente', $cliente);
        }

        $registros = $query
            ->orderByRaw("CASE WHEN status IN ('Entregado','Cancelado') THEN 1 ELSE 0 END ASC")
            ->orderByRaw("CASE WHEN eta IS NULL THEN 9999 ELSE DATEDIFF(DATE_ADD(eta, INTERVAL COALESCE(dias_libres,20) DAY), CURDATE()) END ASC")
            ->orderByDesc('created_at')
            ->get();

        // Convierte columna numérica (1-based) a letra(s) Excel
        $cl = fn(int $n): string => Coordinate::stringFromColumnIndex($n);

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF064E3B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF10B981']]],
        ];
        $rowBorder = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFE2E8F0']]],
        ];

        // Convierte Carbon/date a serial Excel; usa dateTimeToExcel (API v2+)
        $exDate = fn($d) => $d ? ExcelDate::dateTimeToExcel($d->toDateTime()) : null;

        $spreadsheet = new Spreadsheet();

        // ════════════════════════════════════════════════════════════
        // HOJA 1 — Operaciones (todos los campos)
        // ════════════════════════════════════════════════════════════
        $sh1 = $spreadsheet->getActiveSheet();
        $sh1->setTitle('Operaciones');

        // col# 1-28
        $cols1 = [
            'Ref. Interna',           // 1
            'Cliente (Filtro)',        // 2
            'Cliente / Proveedor',     // 3
            'Factura',                 // 4
            'IMP / EXP',              // 5
            'T. Operación',           // 6
            'Transporte',             // 7
            'Naviera / Aerolínea',    // 8
            'Buque',                  // 9
            'FCL / LCL',             // 10
            'No. Contenedor / Caja',  // 11
            'Tipo Contenedor / Caja', // 12
            'Aduana',                 // 13
            'Clave',                  // 14
            'Pedimento',              // 15
            'BL / Guía',             // 16
            'ETD',                    // 17 ← fecha
            'ETA',                    // 18 ← fecha
            'Días Libres',            // 19
            'Cita de Previo',         // 20 ← fecha
            'Cita de Despacho',       // 21 ← fecha
            'Fecha Arribo a Planta',  // 22 ← fecha
            'Status',                 // 23
            'Resultado',              // 24
            'Target',                 // 25
            'Último Comentario',      // 26
            'Ejecutivo',              // 27
            'Fecha Creación',         // 28 ← fecha
        ];
        $last1 = $cl(count($cols1));

        foreach ($cols1 as $ci => $hdr) {
            $sh1->setCellValue($cl($ci + 1) . '1', $hdr);
        }
        $sh1->getStyle("A1:{$last1}1")->applyFromArray($headerStyle);
        $sh1->getRowDimension(1)->setRowHeight(28);
        $sh1->freezePane('A2');
        $sh1->setAutoFilter("A1:{$last1}1");

        $widths1 = [14, 22, 26, 14, 10, 14, 24, 24, 20, 12, 24, 22, 16, 10, 16, 16, 12, 12, 11, 16, 17, 20, 15, 12, 15, 40, 22, 16];
        foreach ($widths1 as $ci => $w) {
            $sh1->getColumnDimension($cl($ci + 1))->setWidth($w);
        }

        // Columnas de fecha (1-indexed): ETD=17, ETA=18, Previo=20, Despacho=21, Arribo=22, Creación=28
        $dateCols1 = [17, 18, 20, 21, 22, 28];

        foreach ($registros as $i => $reg) {
            $row = $i + 2;
            $bg  = $i % 2 === 0 ? 'FFFFFFFF' : 'FFF8FAFC';

            $ultimoComentario = $reg->historial->first()?->comentario ?? $reg->comentarios ?? '';

            $values = [
                $reg->ref_interna,
                $reg->proveedor_cliente,
                $reg->cliente_operacion,
                $reg->factura,
                $reg->impo_ex,
                $reg->tipo_operacion,
                $reg->transporte,
                $reg->naviera,
                $reg->buque,
                $reg->carga_tipo,
                $reg->no_contenedor,
                $reg->tipo_contenedor,
                $reg->aduana,
                $reg->clave,
                $reg->pedimento,
                $reg->bl_guia,
                $exDate($reg->etd),
                $exDate($reg->eta),
                $reg->dias_libres ?? 20,
                $exDate($reg->previo),
                $exDate($reg->cita_despacho),
                $exDate($reg->arribo_planta),
                $reg->status,
                $reg->resultado,
                $reg->target,
                $ultimoComentario,
                $reg->user?->name,
                $exDate($reg->created_at),
            ];

            foreach ($values as $ci => $val) {
                $sh1->setCellValue($cl($ci + 1) . $row, $val ?? '');
            }

            foreach ($dateCols1 as $dc) {
                $sh1->getStyle($cl($dc) . $row)->getNumberFormat()->setFormatCode('DD/MM/YYYY');
            }

            $sh1->getStyle("A{$row}:{$last1}{$row}")
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            $sh1->getStyle("A{$row}:{$last1}{$row}")->applyFromArray($rowBorder);
            $sh1->getStyle('Z' . $row)->getAlignment()->setWrapText(true); // col 26 = Z
        }

        // ════════════════════════════════════════════════════════════
        // HOJA 2 — Transporte (referencia cruzada por Ref. Interna)
        // ════════════════════════════════════════════════════════════
        $sh2 = $spreadsheet->createSheet();
        $sh2->setTitle('Transporte');

        $cols2 = [
            'Ref. Interna', 'T. Operación', 'Transportista',
            'Naviera / Aerolínea', 'Buque',
            'FCL / LCL', 'No. Contenedor / Caja', 'Tipo Contenedor / Caja',
        ];
        $last2 = $cl(count($cols2));

        foreach ($cols2 as $ci => $hdr) {
            $sh2->setCellValue($cl($ci + 1) . '1', $hdr);
        }
        $sh2->getStyle("A1:{$last2}1")->applyFromArray($headerStyle);
        $sh2->getRowDimension(1)->setRowHeight(28);
        $sh2->freezePane('A2');
        $sh2->setAutoFilter("A1:{$last2}1");

        $sh2->setCellValue('J1', 'Ref. Interna corresponde a columna A de la hoja "Operaciones"');
        $sh2->getStyle('J1')->getFont()->setItalic(true)->getColor()->setARGB('FF6B7280');
        $sh2->getColumnDimension('J')->setWidth(58);

        $widths2 = [14, 15, 26, 26, 22, 12, 24, 24];
        foreach ($widths2 as $ci => $w) {
            $sh2->getColumnDimension($cl($ci + 1))->setWidth($w);
        }

        $row2 = 2;
        foreach ($registros as $reg) {
            if (!$reg->transporte && !$reg->naviera && !$reg->buque
                && !$reg->no_contenedor && !$reg->tipo_contenedor && !$reg->carga_tipo) {
                continue;
            }
            $bg2 = ($row2 - 2) % 2 === 0 ? 'FFFFFFFF' : 'FFF8FAFC';

            $v2 = [
                $reg->ref_interna, $reg->tipo_operacion, $reg->transporte,
                $reg->naviera,     $reg->buque,          $reg->carga_tipo,
                $reg->no_contenedor, $reg->tipo_contenedor,
            ];
            foreach ($v2 as $ci => $val) {
                $sh2->setCellValue($cl($ci + 1) . $row2, $val ?? '');
            }

            $sh2->getStyle("A{$row2}:{$last2}{$row2}")
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg2);
            $sh2->getStyle("A{$row2}:{$last2}{$row2}")->applyFromArray($rowBorder);
            $row2++;
        }

        // ── Descarga ──────────────────────────────────────────────────
        $spreadsheet->setActiveSheetIndex(0);
        $clienteLabel = $cliente
            ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cliente)
            : 'Todos';
        $filename = 'MatrizSeguimiento_' . $clienteLabel . '_' . now()->format('Ymd_His') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
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
            'impo_ex'          => 'nullable|in:IMP,EXP',
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

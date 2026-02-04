<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Logistica\Pedimento;
use App\Models\Logistica\OperacionLogistica;
use App\Models\Logistica\PedimentoOperacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedimentoController extends Controller
{
    /**
     * Mostrar la lista de pedimentos agrupados por clave.
     * Detecta automáticamente si es SQLite o MySQL para evitar errores de sintaxis.
     */
    public function index(Request $request)
    {
        // 1. Query Base: Operaciones con Pedimento y Clave
        $operacionesQuery = OperacionLogistica::whereNotNull('no_pedimento')
            ->where('no_pedimento', '!=', '')
            ->whereNotNull('clave')
            ->where('clave', '!=', '');

        // 2. Filtros de Búsqueda
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $operacionesQuery->where(function($query) use ($buscar) {
                $query->where('clave', 'like', "%{$buscar}%")
                      ->orWhere('cliente', 'like', "%{$buscar}%")
                      ->orWhere('no_pedimento', 'like', "%{$buscar}%");
            });
        }

        // 3. --- CORRECCIÓN DE COMPATIBILIDAD SQLITE/MYSQL ---
        $driver = DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        // SQLite no soporta DISTINCT ni SEPARATOR dentro de GROUP_CONCAT de la misma forma que MySQL
        $sqlClientes = $isSqlite 
            ? 'GROUP_CONCAT(cliente)' 
            : 'GROUP_CONCAT(DISTINCT cliente ORDER BY cliente SEPARATOR ", ")';

        $sqlEjecutivos = $isSqlite
            ? 'GROUP_CONCAT(ejecutivo)' 
            : 'GROUP_CONCAT(DISTINCT ejecutivo ORDER BY ejecutivo SEPARATOR ", ")';
        // -----------------------------------------------------

        // 4. Ejecutar la agrupación
        $clavesPedimentos = $operacionesQuery
            ->select('clave', 
                     DB::raw('COUNT(*) as total_pedimentos'),
                     DB::raw("$sqlClientes as clientes"),
                     DB::raw("$sqlEjecutivos as ejecutivos"),
                     DB::raw('MIN(fecha_embarque) as primera_fecha'),
                     DB::raw('MAX(fecha_embarque) as ultima_fecha'))
            ->groupBy('clave')
            ->orderBy('clave')
            ->paginate(15);

        // 5. Procesar el estado de pagos (Lógica de Negocio)
        $pedimentosConEstado = collect();
        
        foreach ($clavesPedimentos as $claveData) {
            // Limpieza de cadenas para SQLite (eliminar duplicados manualmente si es necesario)
            if ($isSqlite) {
                $claveData->clientes = implode(', ', array_unique(explode(',', $claveData->clientes)));
                $claveData->ejecutivos = implode(', ', array_unique(explode(',', $claveData->ejecutivos)));
            }

            // Obtener operaciones individuales
            $operacionesIndividuales = OperacionLogistica::where('clave', $claveData->clave)
                ->whereNotNull('no_pedimento')
                ->where('no_pedimento', '!=', '')
                ->select('id', 'no_pedimento', 'cliente', 'ejecutivo', 'fecha_embarque')
                ->get();
            
            $pedimentosPorPagar = 0;
            $pedimentosPagados = 0;
            
            foreach ($operacionesIndividuales as $operacion) {
                // Verificar o crear registro de control de pago
                $registroPago = PedimentoOperacion::firstOrCreate(
                    [
                        'no_pedimento' => $operacion->no_pedimento,
                        'operacion_logistica_id' => $operacion->id
                    ],
                    [
                        'clave' => $claveData->clave,
                        'estado_pago' => 'pendiente'
                    ]
                );
                
                if ($registroPago->estado_pago === 'pendiente') {
                    $pedimentosPorPagar++;
                } elseif ($registroPago->estado_pago === 'pagado') {
                    $pedimentosPagados++;
                }
            }
            
            $catalogoPedimento = Pedimento::where('clave', $claveData->clave)->first();
            
            $resumenClave = (object) [
                'id' => $catalogoPedimento ? $catalogoPedimento->id : null,
                'clave' => $claveData->clave,
                'descripcion' => "Tipo de operación {$claveData->clave}",
                'total_pedimentos' => $claveData->total_pedimentos,
                'clientes' => $claveData->clientes,
                'ejecutivos' => $claveData->ejecutivos,
                'primera_fecha' => $claveData->primera_fecha,
                'ultima_fecha' => $claveData->ultima_fecha,
                'pedimentos_por_pagar' => $pedimentosPorPagar,
                'pedimentos_pagados' => $pedimentosPagados,
                // Lógica del semáforo: Si hay uno pendiente, el grupo está pendiente
                'estado_pago' => $pedimentosPorPagar > 0 ? 'pendiente' : ($pedimentosPagados > 0 ? 'pagado' : 'pendiente'),
                'fecha_pago' => null,
                'monto' => null
            ];
            
            $pedimentosConEstado->push($resumenClave);
        }

        // Filtro post-procesamiento por estado de pago
        if ($request->filled('estado_pago')) {
            $pedimentosConEstado = $pedimentosConEstado->filter(function ($item) use ($request) {
                return $item->estado_pago === $request->estado_pago;
            });
        }

        // Re-paginar la colección filtrada manualmente
        // (Nota: Esto es necesario porque el filtro de estado se hace después de la consulta SQL)
        $paginatedPedimentos = new \Illuminate\Pagination\LengthAwarePaginator(
            $pedimentosConEstado,
            $clavesPedimentos->total(),
            $clavesPedimentos->perPage(),
            $clavesPedimentos->currentPage(),
            ['path' => $request->url(), 'pageName' => 'page']
        );
        $paginatedPedimentos->appends($request->query());

        // Estadísticas generales
        $stats = [
            'total_claves' => $pedimentosConEstado->count(),
            'total_pedimentos' => $pedimentosConEstado->sum('total_pedimentos'),
            'pagados' => $pedimentosConEstado->sum('pedimentos_pagados'),
            'pendientes' => $pedimentosConEstado->sum('pedimentos_por_pagar')
        ];
        
        return view('Logistica.pedimentos.index', [
            'paginatedPedimentos' => $paginatedPedimentos,
            'stats' => $stats
        ]);
    }

    /**
     * Guardar un nuevo tipo de pedimento en el catálogo (si aplica).
     */
    public function store(Request $request)
    {
        $request->validate(['clave' => 'required|unique:pedimentos,clave']);
        Pedimento::create($request->all());
        return back()->with('success', 'Pedimento registrado correctamente');
    }

    /**
     * Ver detalles de un pedimento (Operaciones asociadas).
     */
    public function show($id)
    {
        // En este contexto, $id puede ser la CLAVE del pedimento (ej: A1)
        // Buscamos las operaciones asociadas a esa clave
        $clave = $id; 
        
        $operaciones = OperacionLogistica::where('clave', $clave)
            ->with('pedimentoStatus') // Asumiendo relación hasOne con PedimentoOperacion
            ->paginate(20);

        return view('Logistica.pedimentos.show', compact('operaciones', 'clave'));
    }

    /**
     * Eliminar un registro del catálogo.
     */
    public function destroy($id)
    {
        $pedimento = Pedimento::find($id);
        if ($pedimento) $pedimento->delete();
        return back()->with('success', 'Registro eliminado');
    }

    /**
     * Actualizar estado de pago de un pedimento específico (Operación individual).
     */
    public function updateEstadoPago(Request $request, $id)
    {
        // $id aquí refiere a PedimentoOperacion ID o OperacionLogistica ID según tu lógica
        // Asumiremos que buscamos por no_pedimento y operacion_id
        
        $registro = PedimentoOperacion::where('operacion_logistica_id', $id)->first();
        
        if ($registro) {
            $registro->estado_pago = $request->estado; // 'pagado' o 'pendiente'
            $registro->fecha_pago = $request->estado === 'pagado' ? now() : null;
            $registro->save();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Registro no encontrado'], 404);
    }

    /**
     * Marcar múltiples pedimentos como pagados (Bulk Action).
     */
    public function marcarPagados(Request $request)
    {
        $ids = $request->ids; // Array de IDs de OperacionLogistica
        
        PedimentoOperacion::whereIn('operacion_logistica_id', $ids)
            ->update([
                'estado_pago' => 'pagado',
                'fecha_pago' => now()
            ]);

        return response()->json(['success' => true, 'message' => 'Pedimentos actualizados']);
    }

    /**
     * API: Obtener pedimentos por clave para modales.
     */
    public function getPedimentosPorClave($clave)
    {
        $pedimentos = OperacionLogistica::where('clave', $clave)
            ->select('id', 'no_pedimento', 'cliente', 'monto_pedimento')
            ->get();
            
        // Mapear con su estado actual
        $data = $pedimentos->map(function($p) {
            $estado = PedimentoOperacion::where('no_pedimento', $p->no_pedimento)
                        ->where('operacion_logistica_id', $p->id)
                        ->value('estado_pago') ?? 'pendiente';
            $p->estado_pago = $estado;
            return $p;
        });

        return response()->json($data);
    }

    /**
     * Actualizar datos individuales de un pedimento.
     */
    public function actualizarPedimento(Request $request)
    {
        $operacion = OperacionLogistica::find($request->id);
        if ($operacion) {
            // Actualizar lógica específica si es necesario
            $operacion->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }

    /**
     * Obtener lista de monedas disponibles.
     */
    public function getMonedas()
    {
        return response()->json(['MXN', 'USD', 'EUR']);
    }

    /**
     * Exportar reporte de pedimentos a CSV
     */
    public function exportCSV(Request $request)
    {
        try {
            $filename = 'Reporte_Pedimentos_' . date('Y-m-d_H-i') . '.csv';
            
            $headers = [
                "Content-Type" => "text/csv",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ];

            $callback = function() {
                $file = fopen('php://output', 'w');
                
                // Encabezados del CSV
                fputcsv($file, [
                    'Clave', 
                    'No. Pedimento', 
                    'Cliente', 
                    'Ejecutivo', 
                    'Fecha Embarque', 
                    'Fecha Arribo',
                    'Estado Pago', 
                    'Monto'
                ]);

                // Query optimizado (Chunks para memoria baja)
                // Obtenemos solo operaciones con pedimento
                $query = \App\Models\Logistica\OperacionLogistica::whereNotNull('no_pedimento')
                    ->where('no_pedimento', '!=', '')
                    ->orderBy('created_at', 'desc');

                $query->chunk(200, function($operaciones) use ($file) {
                    foreach ($operaciones as $op) {
                        // Buscar estado de pago (optimización: idealmente cargar con 'with' si hay relación)
                        $registroPago = \App\Models\Logistica\PedimentoOperacion::where('no_pedimento', $op->no_pedimento)
                            ->where('operacion_logistica_id', $op->id)
                            ->first();
                        
                        $estado = $registroPago ? $registroPago->estado_pago : 'pendiente';

                        fputcsv($file, [
                            $op->clave ?? '-',
                            $op->no_pedimento,
                            $op->cliente,
                            $op->ejecutivo,
                            $op->fecha_embarque ? $op->fecha_embarque->format('d/m/Y') : '-',
                            $op->fecha_arribo_aduana ? $op->fecha_arribo_aduana->format('d/m/Y') : '-',
                            strtoupper($estado),
                            $op->monto_pedimento ?? '0.00'
                        ]);
                    }
                });

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Error exportando pedimentos: ' . $e->getMessage());
            return back()->with('error', 'Error al exportar: ' . $e->getMessage());
        }
    }
}
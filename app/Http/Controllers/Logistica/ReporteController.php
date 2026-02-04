<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Logistica\OperacionLogistica;
use App\Models\Logistica\Cliente;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

// --- LIBRERÍAS NUEVAS PARA FILTROS ---
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

// --- LIBRERÍAS PARA EXCEL Y GRÁFICAS ---
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\Layout;

class ReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Helper privado para reutilizar la lógica de filtros en todos los métodos
     */
    private function obtenerQueryBase(Request $request)
    {
        $usuarioActual = auth()->user();
        $empleadoActual = null;
        $esAdmin = false;

        if ($usuarioActual) {
            $empleadoActual = Empleado::where('correo', $usuarioActual->email)
                ->orWhere('nombre', 'like', '%' . $usuarioActual->name . '%')
                ->first();
            $esAdmin = $usuarioActual->hasRole('admin');
        }

        // 1. Iniciamos el QueryBuilder
        $query = QueryBuilder::for(OperacionLogistica::class)
            ->allowedFilters([
                AllowedFilter::exact('cliente', 'cliente_id')->ignore('todos'), // Ajusta si usas 'cliente' (texto) o 'cliente_id'
                AllowedFilter::partial('ejecutivo')->ignore('todos'),
                
                // Filtro de Estatus Inteligente
                AllowedFilter::callback('status', function (Builder $query, $value) {
                    if ($value === 'todos') return;
                    $query->where(function($q) use ($value) {
                        $q->where('status_manual', $value)
                          ->orWhere('status_calculado', $value);
                    });
                }),

                // Filtros de fecha estándar
                AllowedFilter::callback('fecha_creacion_desde', fn ($q, $v) => $q->whereDate('created_at', '>=', $v)),
                AllowedFilter::callback('fecha_creacion_hasta', fn ($q, $v) => $q->whereDate('created_at', '<=', $v)),

                // Búsqueda General
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where(function($q) use ($value) {
                        $q->where('operacion', 'like', "%{$value}%")
                          ->orWhere('referencia_cliente', 'like', "%{$value}%")
                          ->orWhere('no_pedimento', 'like', "%{$value}%");
                    });
                }),
            ]);

        // 2. Aplicar restricciones de seguridad (Si no es Admin, solo ve lo suyo)
        if (!$esAdmin && $empleadoActual) {
            $query->where('ejecutivo', 'LIKE', '%' . $empleadoActual->nombre . '%');
        }

        // 3. Filtros Manuales Extra (Periodos predefinidos)
        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            if ($periodo === 'semanal') $query->where('created_at', '>=', now()->subWeek());
            elseif ($periodo === 'mensual') $query->where('created_at', '>=', now()->subMonth());
            elseif ($periodo === 'anual') $query->where('created_at', '>=', now()->subYear());
        }

        if ($request->filled('mes') && $request->filled('anio')) {
            $query->whereMonth('created_at', $request->mes)->whereYear('created_at', $request->anio);
        }

        return $query;
    }

    /**
     * Vista principal de reportes (Dashboard y Gráficos Web)
     */
    public function index(Request $request)
    {
        // Usamos el helper para obtener datos filtrados
        $operaciones = $this->obtenerQueryBase($request)
            ->orderBy('created_at', 'desc')
            ->get();

        // Datos para la vista (Usuario y Admin)
        $usuarioActual = auth()->user();
        $empleadoActual = null;
        $esAdmin = false;
        if ($usuarioActual) {
            $empleadoActual = Empleado::where('correo', $usuarioActual->email)->first();
            $esAdmin = $usuarioActual->hasRole('admin');
        }

        // Procesar Estadísticas
        $comportamientoTemporal = [];
        $clientes_unicos = [];
        
        $statsTemporales = [
            'en_tiempo' => 0,
            'en_riesgo' => 0,
            'con_retraso' => 0,
            'completado_tiempo' => 0,
            'completado_retraso' => 0,
            'total_dias' => 0,
            'total_target' => 0,
            'total_operaciones' => 0
        ];

        foreach ($operaciones as $op) {
            $diasTranscurridos = $op->dias_transcurridos_calculados ?? 0;
            $target = $op->target ?? 30;
            $statusFinal = ($op->status_manual === 'Done') ? 'Done' : $op->status_calculado;
            $retraso = max(0, $diasTranscurridos - $target);

            $categoria = 'En Tiempo';
            if ($statusFinal === 'Done') {
                if ($diasTranscurridos <= $target) {
                    $categoria = 'Completado a Tiempo';
                    $statsTemporales['completado_tiempo']++;
                } else {
                    $categoria = 'Completado con Retraso';
                    $statsTemporales['completado_retraso']++;
                }
            } else {
                if ($diasTranscurridos > $target) {
                    $categoria = 'Con Retraso';
                    $statsTemporales['con_retraso']++;
                } elseif ($diasTranscurridos >= ($target * 0.8)) {
                    $categoria = 'En Riesgo';
                    $statsTemporales['en_riesgo']++;
                } else {
                    $categoria = 'En Tiempo';
                    $statsTemporales['en_tiempo']++;
                }
            }

            $statsTemporales['total_dias'] += $diasTranscurridos;
            $statsTemporales['total_target'] += $target;
            
            $comportamientoTemporal[] = [
                'id' => $op->id,
                'cliente' => $op->clienteRelacion->razon_social ?? $op->cliente, // Usar relación si existe
                'ejecutivo' => $op->ejecutivo,
                'dias_transcurridos' => (int)$diasTranscurridos,
                'target' => $target,
                'retraso' => $retraso,
                'status' => $statusFinal,
                'categoria' => $categoria,
                'porcentaje_progreso' => min(100, ($diasTranscurridos / max($target, 1)) * 100)
            ];

            if (!in_array($op->cliente, $clientes_unicos)) {
                $clientes_unicos[] = $op->cliente; // Guardamos ID o nombre para el filtro
            }
        }

        $totalOps = count($comportamientoTemporal);
        $statsTemporales['total_operaciones'] = $totalOps;
        $statsTemporales['promedio_dias'] = $totalOps > 0 ? $statsTemporales['total_dias'] / $totalOps : 0;
        $statsTemporales['promedio_target'] = $totalOps > 0 ? $statsTemporales['total_target'] / $totalOps : 0;

        $stats = [
            'en_proceso' => $statsTemporales['en_tiempo'] + $statsTemporales['en_riesgo'],
            'fuera_metrica' => $statsTemporales['con_retraso'],
            'done' => $statsTemporales['completado_tiempo'] + $statsTemporales['completado_retraso'],
        ];

        // Obtener lista de clientes para el dropdown (si tienes el modelo Cliente)
        $clientes = Cliente::orderBy('razon_social')->get(); 

        return view('Logistica.reportes', compact(
            'statsTemporales', 
            'stats',
            'comportamientoTemporal', 
            'clientes', 
            'esAdmin',
            'empleadoActual',
            'operaciones'
        ));
    }

    /**
     * Exportar Excel con GRÁFICA NATIVA en la primera hoja.
     */
    public function exportExcelProfesional(Request $request)
    {
        try {
            // Usamos el helper para obtener datos
            $operaciones = $this->obtenerQueryBase($request)->get();

            // Calcular Estadísticas para la Gráfica
            $stats = [
                'En Tiempo' => 0,
                'En Riesgo' => 0,
                'Fuera de Metrica' => 0,
                'Completado OK' => 0,
                'Completado Tarde' => 0
            ];

            foreach($operaciones as $op) {
                $dias = $op->dias_transcurridos_calculados ?? 0;
                $target = $op->target ?? 30;
                $status = $op->status_manual ?: $op->status_calculado;

                if($status === 'Done') {
                    if($dias <= $target) $stats['Completado OK']++;
                    else $stats['Completado Tarde']++;
                } else {
                    if($dias > $target) $stats['Fuera de Metrica']++;
                    elseif($dias >= ($target * 0.8)) $stats['En Riesgo']++;
                    else $stats['En Tiempo']++;
                }
            }

            // Iniciar Excel
            $spreadsheet = new Spreadsheet();

            // --- HOJA 1: DASHBOARD (Gráfica) ---
            $sheetChart = $spreadsheet->getActiveSheet();
            $sheetChart->setTitle('Dashboard Ejecutivo');

            // Datos Fuente para la Gráfica
            $sheetChart->setCellValue('A1', 'Estatus');
            $sheetChart->setCellValue('B1', 'Cantidad');
            $sheetChart->getStyle('A1:B1')->getFont()->setBold(true);

            $row = 2;
            foreach($stats as $key => $val) {
                $sheetChart->setCellValue('A' . $row, $key);
                $sheetChart->setCellValue('B' . $row, $val);
                $row++;
            }

            // Crear Gráfica de Pastel
            $xAxisTickValues = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Dashboard Ejecutivo'!\$A\$2:\$A\$6", null, 5)];
            $dataSeriesValues = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Dashboard Ejecutivo'!\$B\$2:\$B\$6", null, 5)];

            $series = new DataSeries(
                DataSeries::TYPE_PIECHART,
                null, range(0, count($dataSeriesValues) - 1), 
                [], $xAxisTickValues, $dataSeriesValues
            );

            $layout = new Layout();
            $layout->setShowVal(true);
            $layout->setShowPercent(true);

            $plotArea = new PlotArea($layout, [$series]);
            $legend = new Legend(Legend::POSITION_RIGHT, null, false);
            $title = new Title('Distribución de Estatus - ' . date('d/m/Y'));

            $chart = new Chart('chart_status', $title, $legend, $plotArea, true, 0, null, null);
            $chart->setTopLeftPosition('D2');
            $chart->setBottomRightPosition('M20');
            $sheetChart->addChart($chart);

            $sheetChart->getColumnDimension('A')->setAutoSize(true);
            $sheetChart->setShowGridlines(false);

            // --- HOJA 2: DATOS DETALLADOS ---
            $sheetData = new Worksheet($spreadsheet, 'Detalle Operaciones');
            $spreadsheet->addSheet($sheetData);
            
            $headers = ['Folio', 'Cliente', 'Operación', 'Referencia', 'Pedimento', 'Fecha Embarque', 'Fecha Arribo', 'Status', 'Días', 'Target'];
            $sheetData->fromArray($headers, NULL, 'A1');
            $sheetData->getStyle('A1:J1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheetData->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');

            $rowsData = [];
            foreach($operaciones as $op) {
                $rowsData[] = [
                    $op->id,
                    $op->clienteRelacion->razon_social ?? $op->cliente, // Usar relación
                    $op->operacion,
                    $op->referencia_cliente,
                    $op->no_pedimento,
                    $op->fecha_embarque ? $op->fecha_embarque->format('d/m/Y') : '-',
                    $op->fecha_arribo_aduana ? $op->fecha_arribo_aduana->format('d/m/Y') : '-',
                    $op->status_manual ?: $op->status_calculado,
                    $op->dias_transcurridos_calculados,
                    $op->target
                ];
            }
            $sheetData->fromArray($rowsData, NULL, 'A2');
            foreach(range('A','J') as $col) $sheetData->getColumnDimension($col)->setAutoSize(true);

            $spreadsheet->setActiveSheetIndex(0);

            return response()->streamDownload(function() use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->setIncludeCharts(true);
                $writer->save('php://output');
            }, 'Reporte_Grafico_Logistica_' . date('Ymd_His') . '.xlsx');

        } catch (\Exception $e) {
            Log::error('Error exportando Excel gráfico: ' . $e->getMessage());
            return back()->with('error', 'Error generando reporte: ' . $e->getMessage());
        }
    }

    /**
     * Exportar Matriz de Seguimiento
     */
    public function exportMatrizSeguimiento(Request $request)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            $usuarioActual = auth()->user();
            $esAdmin = $usuarioActual->hasRole('admin');

            if ($esAdmin) {
                $ejecutivos = Empleado::where('area', 'Logistica')
                    ->orWhere('posicion', 'like', '%Logistica%')->get();

                foreach ($ejecutivos as $index => $ejecutivo) {
                    // Aquí construimos el query manualmente porque necesitamos filtrar por ejecutivo específico
                    // pero aprovechando los filtros globales
                    $query = $this->obtenerQueryBase($request);
                    $query->where('ejecutivo', 'LIKE', '%' . $ejecutivo->nombre . '%');
                    
                    $operaciones = $query->get();

                    if ($operaciones->count() > 0) {
                        $sheet = new Worksheet($spreadsheet, substr($ejecutivo->nombre, 0, 30));
                        $spreadsheet->addSheet($sheet, $index);
                        $this->llenarHojaMatriz($sheet, $operaciones);
                    }
                }
            } else {
                $sheet = new Worksheet($spreadsheet, 'Mis Operaciones');
                $spreadsheet->addSheet($sheet, 0);
                
                $operaciones = $this->obtenerQueryBase($request)->get();
                $this->llenarHojaMatriz($sheet, $operaciones);
            }

            if ($spreadsheet->getSheetCount() == 0) {
                $sheet = new Worksheet($spreadsheet, 'Sin Datos');
                $spreadsheet->addSheet($sheet);
                $sheet->setCellValue('A1', 'No hay datos disponibles.');
            }

            $spreadsheet->setActiveSheetIndex(0);
            
            return response()->streamDownload(function() use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 'Matriz_Seguimiento_' . date('Ymd') . '.xlsx');

        } catch (\Exception $e) {
            return back()->with('error', 'Error exportando matriz: ' . $e->getMessage());
        }
    }

    private function llenarHojaMatriz($sheet, $operaciones)
    {
        $headers = ['Folio', 'Ejecutivo', 'Cliente', 'Operación', 'Referencia', 'Pedimento', 'Fechas', 'Status', 'Días'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        $row = 2;
        foreach ($operaciones as $op) {
            $sheet->setCellValue('A' . $row, $op->id);
            $sheet->setCellValue('B' . $row, $op->ejecutivo);
            $sheet->setCellValue('C' . $row, $op->clienteRelacion->razon_social ?? $op->cliente);
            $sheet->setCellValue('D' . $row, $op->operacion);
            $sheet->setCellValue('E' . $row, $op->referencia_cliente);
            $sheet->setCellValue('F' . $row, $op->no_pedimento);
            $sheet->setCellValue('G' . $row, ($op->fecha_embarque ? $op->fecha_embarque->format('d/m/Y') : '-'));
            $sheet->setCellValue('H' . $row, $op->status_manual ?: $op->status_calculado);
            $sheet->setCellValue('I' . $row, $op->dias_transcurridos_calculados);
            $row++;
        }
        foreach(range('A','I') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    public function exportCSV(Request $request)
    {
        $operaciones = $this->obtenerQueryBase($request)->get();

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=reporte.csv"
        ];

        return response()->stream(function() use ($operaciones) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Cliente', 'Status']);
            foreach($operaciones as $op) {
                fputcsv($file, [$op->id, $op->clienteRelacion->razon_social ?? $op->cliente, $op->status_manual ?: $op->status_calculado]);
            }
            fclose($file);
        }, 200, $headers);
    }
}
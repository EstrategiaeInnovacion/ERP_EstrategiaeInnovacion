<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class ExcelReportService
{
    private $spreadsheet;
    private $worksheet;
    private $currentRow = 1;
    private $columnasOrdenadas = [];

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->worksheet = $this->spreadsheet->getActiveSheet();
    }

    /**
     * Establecer el orden de columnas personalizado
     */
    public function setColumnasOrdenadas($columnas)
    {
        $this->columnasOrdenadas = $columnas;
    }

    /**
     * Generar reporte completo de operaciones logísticas con gráficos y diseño moderno
     */
    public function generateLogisticsReport($operaciones, $filtros = [], $estadisticas = [])
    {
        // 1. Configurar propiedades del documento
        $this->setDocumentProperties();

        // 2. Crear portada profesional
        $this->createCoverPage($filtros, $estadisticas);

        // 3. Crear hoja de resumen ejecutivo con gráficos
        $this->createExecutiveSummary($estadisticas);

        // 4. Crear hoja de datos detallados
        $this->createDataSheet($operaciones);

        // 5. Crear hoja de análisis temporal
        $this->createTemporalAnalysis($operaciones);

        // 6. Crear hoja de performance por ejecutivo
        $this->createExecutivePerformance($operaciones);

        return $this->spreadsheet;
    }

    /**
     * Configurar propiedades del documento
     */
    private function setDocumentProperties()
    {
        $this->spreadsheet->getProperties()
            ->setCreator('Estrategia e Innovación - Sistema de Logística')
            ->setLastModifiedBy('Sistema ERP')
            ->setTitle('Reporte de Operaciones Logísticas')
            ->setSubject('Análisis de Performance Logística')
            ->setDescription('Reporte detallado de operaciones con análisis de performance y métricas')
            ->setKeywords('logística, operaciones, performance, análisis')
            ->setCategory('Reportes Ejecutivos');
    }

    /**
     * Crear portada profesional
     */
    private function createCoverPage($filtros, $estadisticas)
    {
        $sheet = $this->worksheet;
        $sheet->setTitle('Portada');

        // Logo y header principal
        $sheet->mergeCells('A1:H5');
        $sheet->setCellValue('A1', 'REPORTE DE OPERACIONES LOGÍSTICAS');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'size' => 28,
                'bold' => true,
                'color' => ['rgb' => '2E4BC6']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'fill' => [
                'fillType' => Fill::FILL_GRADIENT_LINEAR,
                'startColor' => ['rgb' => 'F8F9FA'],
                'endColor' => ['rgb' => 'E3F2FD']
            ]
        ]);

        // Información del reporte
        $sheet->setCellValue('A7', 'ESTRATEGIA E INNOVACIÓN');
        $sheet->getStyle('A7')->applyFromArray([
            'font' => ['size' => 16, 'bold' => true, 'color' => ['rgb' => '495057']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $sheet->mergeCells('A7:H7');

        // Fecha y período
        $sheet->setCellValue('A10', 'Fecha de Generación:');
        $sheet->setCellValue('C10', now()->format('d/m/Y H:i'));
        $sheet->setCellValue('A11', 'Período del Reporte:');
        $fechaInicio = $filtros['fecha_desde'] ?? 'Inicio';
        $fechaFin = $filtros['fecha_hasta'] ?? 'Actual';
        $sheet->setCellValue('C11', "{$fechaInicio} - {$fechaFin}");

        // Estadísticas resumen en la portada
        $this->addCoverStatistics($sheet, $estadisticas);

        // Estilo para la información
        $sheet->getStyle('A10:C11')->applyFromArray([
            'font' => ['size' => 12, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
        ]);
    }

    /**
     * Agregar estadísticas a la portada
     */
    private function addCoverStatistics($sheet, $estadisticas)
    {
        $row = 15;

        // Header de estadísticas
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'RESUMEN EJECUTIVO');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['size' => 18, 'bold' => true, 'color' => ['rgb' => '2E4BC6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $row += 2;

        $stats = [
            'Total de Operaciones' => $estadisticas['total'] ?? 0,
            'En Proceso' => $estadisticas['en_proceso'] ?? 0,
            'Completadas' => $estadisticas['completadas'] ?? 0,
            'Fuera de Métrica' => $estadisticas['fuera_metrica'] ?? 0
        ];

        foreach ($stats as $label => $value) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("C{$row}", $value);

            // Estilos para las métricas
            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                'font' => ['size' => 12, 'bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            $row++;
        }
    }

    /**
     * Crear hoja de resumen ejecutivo con gráficos
     */
    private function createExecutiveSummary($estadisticas)
    {
        $summarySheet = $this->spreadsheet->createSheet();
        $summarySheet->setTitle('Resumen Ejecutivo');

        // Header del resumen
        $summarySheet->mergeCells('A1:H1');
        $summarySheet->setCellValue('A1', 'ANÁLISIS DE PERFORMANCE - DASHBOARD EJECUTIVO');
        $summarySheet->getStyle('A1')->applyFromArray([
            'font' => ['size' => 20, 'bold' => true, 'color' => ['rgb' => '2E4BC6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
        ]);

        // KPIs principales
        $this->createKPICards($summarySheet, $estadisticas);

        // Gráfico de distribución por status
        $this->createStatusChart($summarySheet, $estadisticas);

        // Gráfico de tendencias temporales
        $this->createTrendsChart($summarySheet, $estadisticas);
    }

    /**
     * Crear gráfico de tendencias temporales
     */
    private function createTrendsChart($sheet, $estadisticas)
    {
        // Datos de ejemplo para tendencias (últimos 6 meses)
        $meses = ['Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $tendencias = [
            'Completadas' => [45, 52, 48, 61, 58, ($estadisticas['completadas'] ?? 0)],
            'En Proceso' => [12, 15, 18, 14, 16, ($estadisticas['en_proceso'] ?? 0)],
            'Fuera Métrica' => [8, 6, 9, 7, 5, ($estadisticas['fuera_metrica'] ?? 0)]
        ];

        $row = 28; // Posición debajo del gráfico de pie

        // Título del gráfico
        $sheet->mergeCells('A' . ($row - 1) . ':F' . ($row - 1));
        $sheet->setCellValue('A' . ($row - 1), 'TENDENCIAS ÚLTIMOS 6 MESES');
        $sheet->getStyle('A' . ($row - 1))->applyFromArray([
            'font' => ['size' => 14, 'bold' => true, 'color' => ['rgb' => '2E4BC6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Encabezados
        $sheet->setCellValue('A' . $row, 'Mes');
        $col = 'B';
        foreach (array_keys($tendencias) as $serie) {
            $sheet->setCellValue($col . $row, $serie);
            $col++;
        }

        // Datos
        foreach ($meses as $index => $mes) {
            $currentRow = $row + 1 + $index;
            $sheet->setCellValue('A' . $currentRow, $mes);

            $col = 'B';
            foreach ($tendencias as $serie => $datos) {
                $sheet->setCellValue($col . $currentRow, $datos[$index]);
                $col++;
            }
        }

        // Aplicar formato a los headers
        $headerRange = 'A' . $row . ':D' . $row;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E4BC6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Aplicar formato alternado a las filas de datos
        for ($i = 1; $i <= count($meses); $i++) {
            $dataRow = $row + $i;
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $dataRow . ':D' . $dataRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8F9FA');
            }
        }

        // Crear gráfico de líneas para tendencias
        $this->createLineChart($sheet, 'A' . $row . ':D' . ($row + count($meses)), 'F' . ($row - 1) . ':L' . ($row + 15), 'Tendencias de Operaciones');
    }

    /**
     * Crear gráfico de líneas
     */
    private function createLineChart($sheet, $dataRange, $chartPosition, $title)
    {
        // Configurar categorías (meses)
        $categories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheet->getTitle() . '!' . 'A29:A34', null, 6)
        ];

        // Configurar series de datos
        $dataSeriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheet->getTitle() . '!' . 'B28:D28', null, 3)
        ];

        $dataSeriesValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $sheet->getTitle() . '!' . 'B29:B34', null, 6),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $sheet->getTitle() . '!' . 'C29:C34', null, 6),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $sheet->getTitle() . '!' . 'D29:D34', null, 6)
        ];

        // Crear serie de datos
        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            $categories,
            $dataSeriesValues
        );

        // Configurar propiedades de la serie
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        // Configurar área de ploteo
        $plotArea = new PlotArea(null, [$series]);

        // Configurar leyenda
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);

        // Crear título del gráfico
        $chartTitle = new Title($title);

        // Crear el gráfico
        $chart = new Chart('trendChart', $chartTitle, $legend, $plotArea);
        $chart->setTopLeftPosition($chartPosition);
        $chart->setBottomRightPosition(chr(ord(substr($chartPosition, 0, 1)) + 6) . (intval(substr($chartPosition, 1)) + 16));

        // Agregar gráfico a la hoja
        $sheet->addChart($chart);
    }

    /**
     * Crear tarjetas KPI en el resumen
     */
    private function createKPICards($sheet, $estadisticas)
    {
        $row = 4;

        $kpis = [
            ['titulo' => 'TOTAL OPERACIONES', 'valor' => $estadisticas['total'] ?? 0, 'color' => '2196F3'],
            ['titulo' => 'EFICIENCIA (%)', 'valor' => number_format(($estadisticas['completadas'] ?? 0) / max(($estadisticas['total'] ?? 1), 1) * 100, 1) . '%', 'color' => '4CAF50'],
            ['titulo' => 'EN PROCESO', 'valor' => $estadisticas['en_proceso'] ?? 0, 'color' => 'FF9800'],
            ['titulo' => 'FUERA DE MÉTRICA', 'valor' => $estadisticas['fuera_metrica'] ?? 0, 'color' => 'F44336']
        ];

        $col = 'A';
        foreach ($kpis as $kpi) {
            // Crear tarjeta KPI
            $range = $col . $row . ':' . chr(ord($col) + 1) . ($row + 2);
            $sheet->mergeCells($range);

            $sheet->setCellValue($col . $row, $kpi['titulo']);
            $sheet->setCellValue($col . ($row + 1), $kpi['valor']);

            // Estilos para la tarjeta
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $kpi['color']]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ]);

            $sheet->getStyle($col . $row)->getFont()->setSize(10);
            $sheet->getStyle($col . ($row + 1))->getFont()->setSize(18);

            $col = chr(ord($col) + 3); // Saltar 3 columnas para el siguiente KPI
        }
    }

    /**
     * Crear gráfico de distribución por status
     */
    private function createStatusChart($sheet, $estadisticas)
    {
        // Datos para el gráfico
        $row = 10;
        $sheet->setCellValue('A' . $row, 'Status');
        $sheet->setCellValue('B' . $row, 'Cantidad');

        $data = [
            ['En Proceso', $estadisticas['en_proceso'] ?? 0],
            ['Completadas', $estadisticas['completadas'] ?? 0],
            ['Fuera de Métrica', $estadisticas['fuera_metrica'] ?? 0]
        ];

        $currentRow = $row + 1;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $currentRow, $item[0]);
            $sheet->setCellValue('B' . $currentRow, $item[1]);
            $currentRow++;
        }

        // Crear gráfico de dona/pie
        $this->createPieChart($sheet, 'A' . $row . ':B' . ($currentRow - 1), 'D10:I25', 'Distribución por Status');
    }

    /**
     * Crear gráfico circular (pie/dona)
     */
    private function createPieChart($sheet, $dataRange, $chartPosition, $title)
    {
        // Configurar series de datos
        $dataSeriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheet->getTitle() . '!' . 'A11:A13', null, 3)
        ];

        $dataSeriesValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $sheet->getTitle() . '!' . 'B11:B13', null, 3)
        ];

        // Crear categorías para el gráfico
        $plotCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheet->getTitle() . '!' . 'A11:A13', null, 3)
        ];

        // Crear serie de datos
        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            $plotCategories,
            $dataSeriesValues
        );

        // Configurar área de ploteo
        $plotArea = new PlotArea(null, [$series]);

        // Configurar leyenda
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);

        // Crear título del gráfico
        $chartTitle = new Title($title);

        // Crear el gráfico
        $chart = new Chart('chart1', $chartTitle, $legend, $plotArea);
        $chart->setTopLeftPosition($chartPosition);
        $chart->setBottomRightPosition(chr(ord(substr($chartPosition, 0, 1)) + 5) . (intval(substr($chartPosition, 1)) + 15));

        // Agregar gráfico a la hoja
        $sheet->addChart($chart);
    }

    /**
     * Mapeo de columnas a propiedades del modelo
     */
    private function getColumnMapping()
    {
        return [
            'id' => ['nombre' => 'ID', 'campo' => 'id'],
            'ejecutivo' => ['nombre' => 'Ejecutivo', 'campo' => 'ejecutivo'],
            'operacion' => ['nombre' => 'Operación', 'campo' => 'operacion'],
            'cliente' => ['nombre' => 'Cliente', 'campo' => 'cliente'],
            'proveedor_o_cliente' => ['nombre' => 'Proveedor/Cliente', 'campo' => 'proveedor_o_cliente'],
            'fecha_embarque' => ['nombre' => 'Fecha Embarque', 'campo' => 'fecha_embarque', 'tipo' => 'fecha'],
            'no_factura' => ['nombre' => 'No. Factura', 'campo' => 'no_factura'],
            'tipo_carga' => ['nombre' => 'Tipo Carga', 'campo' => 'tipo_carga'],
            'tipo_incoterm' => ['nombre' => 'Incoterm', 'campo' => 'tipo_incoterm'],
            'tipo_operacion_enum' => ['nombre' => 'T. Operación', 'campo' => 'tipo_operacion_enum'],
            'clave' => ['nombre' => 'Clave', 'campo' => 'clave'],
            'referencia_interna' => ['nombre' => 'Ref. Interna', 'campo' => 'referencia_interna'],
            'aduana' => ['nombre' => 'Aduana', 'campo' => 'aduana'],
            'agente_aduanal' => ['nombre' => 'A.A', 'campo' => 'agente_aduanal'],
            'referencia_aa' => ['nombre' => 'Ref. A.A', 'campo' => 'referencia_aa'],
            'no_pedimento' => ['nombre' => 'No. Pedimento', 'campo' => 'no_pedimento'],
            'transporte' => ['nombre' => 'Transporte', 'campo' => 'transporte'],
            'fecha_arribo_aduana' => ['nombre' => 'Arribo Aduana', 'campo' => 'fecha_arribo_aduana', 'tipo' => 'fecha'],
            'guia_bl' => ['nombre' => 'Guía/BL', 'campo' => 'guia_bl'],
            'puerto_salida' => ['nombre' => 'Puerto Salida', 'campo' => 'puerto_salida'],
            'in_charge' => ['nombre' => 'Responsable', 'campo' => 'in_charge'],
            'proveedor' => ['nombre' => 'Proveedor', 'campo' => 'proveedor'],
            'tipo_previo' => ['nombre' => 'Modalidad/Previo', 'campo' => 'tipo_previo'],
            'fecha_etd' => ['nombre' => 'Fecha ETD', 'campo' => 'fecha_etd', 'tipo' => 'fecha'],
            'fecha_zarpe' => ['nombre' => 'Fecha Zarpe', 'campo' => 'fecha_zarpe', 'tipo' => 'fecha'],
            'pedimento_en_carpeta' => ['nombre' => 'Ped. en Carpeta', 'campo' => 'pedimento_en_carpeta'],
            'referencia_cliente' => ['nombre' => 'Ref. Cliente', 'campo' => 'referencia_cliente'],
            'mail_subject' => ['nombre' => 'Asunto Correo', 'campo' => 'mail_subject'],
            'status' => ['nombre' => 'Status', 'campo' => 'status_manual'],
            'fecha_modulacion' => ['nombre' => 'Salida Aduana', 'campo' => 'fecha_modulacion', 'tipo' => 'fecha'],
            'fecha_arribo_planta' => ['nombre' => 'Arribo Planta', 'campo' => 'fecha_arribo_planta', 'tipo' => 'fecha'],
            'resultado' => ['nombre' => 'Resultado', 'campo' => 'resultado', 'tipo' => 'calculado'],
            'target' => ['nombre' => 'Target', 'campo' => 'target'],
            'dias_transito' => ['nombre' => 'Días Tránsito', 'campo' => 'dias_transito', 'tipo' => 'calculado'],
            'post_operaciones' => ['nombre' => 'Post-Operaciones', 'campo' => 'post_operaciones', 'tipo' => 'relacion'],
            'comentarios' => ['nombre' => 'Comentarios', 'campo' => 'comentarios'],
            'folio' => ['nombre' => 'Folio', 'campo' => 'folio'],
        ];
    }

    /**
     * Crear hoja de datos detallados
     */
    private function createDataSheet($operaciones)
    {
        $dataSheet = $this->spreadsheet->createSheet();
        $dataSheet->setTitle('Datos Detallados');

        // Usar columnas ordenadas si están definidas
        if (!empty($this->columnasOrdenadas)) {
            $this->createDataSheetConOrden($dataSheet, $operaciones);
        } else {
            $this->createDataSheetDefault($dataSheet, $operaciones);
        }
    }

    /**
     * Crear hoja de datos con columnas ordenadas personalizadas
     */
    private function createDataSheetConOrden($dataSheet, $operaciones)
    {
        $columnMapping = $this->getColumnMapping();
        $headers = [];
        $columnas = [];
        
        // Filtrar solo columnas visibles y ordenadas
        foreach ($this->columnasOrdenadas as $col) {
            if ($col['visible'] && isset($columnMapping[$col['columna']])) {
                $headers[] = $col['nombre'] ?? $columnMapping[$col['columna']]['nombre'];
                $columnas[] = $col['columna'];
            }
        }

        // Escribir encabezados
        foreach ($headers as $col => $header) {
            $cellRef = $this->getColumnLetter($col) . '1';
            $dataSheet->setCellValue($cellRef, $header);
        }

        // Estilo para encabezados
        $lastCol = $this->getColumnLetter(count($headers) - 1);
        $headerRange = 'A1:' . $lastCol . '1';
        $dataSheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E4BC6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Escribir datos
        $row = 2;
        foreach ($operaciones as $operacion) {
            foreach ($columnas as $colIndex => $columna) {
                $cellRef = $this->getColumnLetter($colIndex) . $row;
                $valor = $this->getValorColumna($operacion, $columna, $columnMapping);
                $dataSheet->setCellValue($cellRef, $valor);
            }

            // Aplicar bordes alternados
            if ($row % 2 == 0) {
                $rowRange = 'A' . $row . ':' . $lastCol . $row;
                $dataSheet->getStyle($rowRange)->getFill()->setFillType(Fill::FILL_SOLID);
                $dataSheet->getStyle($rowRange)->getFill()->getStartColor()->setRGB('F8F9FA');
            }

            $row++;
        }

        // Autoajustar columnas
        for ($i = 0; $i < count($headers); $i++) {
            $dataSheet->getColumnDimension($this->getColumnLetter($i))->setAutoSize(true);
        }
    }

    /**
     * Obtener el valor de una columna de la operación
     */
    private function getValorColumna($operacion, $columna, $columnMapping)
    {
        $mapping = $columnMapping[$columna] ?? null;
        if (!$mapping) return '';

        $campo = $mapping['campo'];
        $tipo = $mapping['tipo'] ?? 'texto';

        switch ($tipo) {
            case 'fecha':
                $valor = $operacion->$campo;
                return $valor ? (is_string($valor) ? $valor : $valor->format('d/m/Y')) : '';
            
            case 'calculado':
                if ($columna === 'resultado') {
                    $resultado = $this->calculateResult($operacion);
                    return $resultado['texto'];
                } elseif ($columna === 'dias_transito') {
                    return method_exists($operacion, 'calcularDiasTranscurridos') 
                        ? $operacion->calcularDiasTranscurridos() 
                        : '';
                }
                return '';
            
            case 'relacion':
                if ($columna === 'post_operaciones') {
                    $count = $operacion->postOperacionesCompletas ?? 0;
                    $pending = $operacion->postOperacionesPendientes ?? 0;
                    return "$count completadas / $pending pendientes";
                }
                return '';
            
            default:
                return $operacion->$campo ?? '';
        }
    }

    /**
     * Convertir índice de columna a letra (0=A, 1=B, 26=AA, etc.)
     */
    private function getColumnLetter($index)
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = floor($index / 26) - 1;
        }
        return $letter;
    }

    /**
     * Crear hoja de datos con orden por defecto
     */
    private function createDataSheetDefault($dataSheet, $operaciones)
    {
        // Configurar encabezados por defecto
        $headers = [
            'ID', 'Cliente', 'Ejecutivo', 'Fecha Creación', 'ETA', 'Agente Aduanal',
            'Pedimento', 'Guía/BL', 'Transporte', 'Status Calculado', 'Status Manual',
            'Días Transcurridos', 'Target Días', 'Resultado', 'Comentarios',
            'Post-Operaciones Completas', 'Post-Operaciones Pendientes'
        ];

        // Escribir encabezados con estilo
        foreach ($headers as $col => $header) {
            $cellRef = chr(65 + $col) . '1';
            $dataSheet->setCellValue($cellRef, $header);
        }

        // Estilo para encabezados
        $headerRange = 'A1:' . chr(65 + count($headers) - 1) . '1';
        $dataSheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E4BC6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Escribir datos
        $row = 2;
        foreach ($operaciones as $operacion) {
            $dataSheet->setCellValue('A' . $row, $operacion->id);
            $dataSheet->setCellValue('B' . $row, $operacion->cliente);
            $dataSheet->setCellValue('C' . $row, $operacion->ejecutivo);
            $dataSheet->setCellValue('D' . $row, $operacion->created_at->format('d/m/Y'));
            $dataSheet->setCellValue('E' . $row, $operacion->eta ? $operacion->eta->format('d/m/Y') : 'N/A');
            $dataSheet->setCellValue('F' . $row, $operacion->agente_aduanal);
            $dataSheet->setCellValue('G' . $row, $operacion->pedimento);
            $dataSheet->setCellValue('H' . $row, $operacion->guia_bl ?? 'N/A');
            $dataSheet->setCellValue('I' . $row, $operacion->transporte);
            $dataSheet->setCellValue('J' . $row, $operacion->status_calculado);
            $dataSheet->setCellValue('K' . $row, $operacion->status_manual ?? $operacion->status_calculado);
            $dataSheet->setCellValue('L' . $row, method_exists($operacion, 'calcularDiasTranscurridos') ? $operacion->calcularDiasTranscurridos() : '');
            $dataSheet->setCellValue('M' . $row, $operacion->dias_objetivo ?? 5);

            // Colorear resultado basado en performance
            $resultado = $this->calculateResult($operacion);
            $dataSheet->setCellValue('N' . $row, $resultado['texto']);
            $dataSheet->getStyle('N' . $row)->getFill()->setFillType(Fill::FILL_SOLID);
            $dataSheet->getStyle('N' . $row)->getFill()->getStartColor()->setRGB($resultado['color']);

            $dataSheet->setCellValue('O' . $row, $operacion->comentarios ?? '');
            $dataSheet->setCellValue('P' . $row, $operacion->postOperacionesCompletas ?? 0);
            $dataSheet->setCellValue('Q' . $row, $operacion->postOperacionesPendientes ?? 0);

            // Aplicar bordes alternados
            if ($row % 2 == 0) {
                $rowRange = 'A' . $row . ':Q' . $row;
                $dataSheet->getStyle($rowRange)->getFill()->setFillType(Fill::FILL_SOLID);
                $dataSheet->getStyle($rowRange)->getFill()->getStartColor()->setRGB('F8F9FA');
            }

            $row++;
        }

       // Autoajustar columnas
        foreach (range('A', 'Q') as $col) {
            $dataSheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Crear análisis temporal
     */
    private function createTemporalAnalysis($operaciones)
    {
        $temporalSheet = $this->spreadsheet->createSheet();
        $temporalSheet->setTitle('Análisis Temporal');

        $temporalSheet->mergeCells('A1:F1');
        $temporalSheet->setCellValue('A1', 'ANÁLISIS DE TENDENCIAS Y PERFORMANCE TEMPORAL');
        $temporalSheet->getStyle('A1')->applyFromArray([
            'font' => ['size' => 16, 'bold' => true, 'color' => ['rgb' => '2E4BC6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
        ]);

        // Análisis por mes
        $monthlyData = $this->processMonthlyData($operaciones);
        $this->createMonthlyChart($temporalSheet, $monthlyData);
    }

    /**
     * Procesar datos mensuales
     */
    private function processMonthlyData($operaciones)
    {
        $monthlyData = [];

        foreach ($operaciones as $operacion) {
            $month = $operacion->created_at->format('Y-m');
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = ['total' => 0, 'completadas' => 0, 'en_proceso' => 0];
            }

            $monthlyData[$month]['total']++;

            if ($operacion->status_manual === 'Done' || $operacion->status_calculado === 'Done') {
                $monthlyData[$month]['completadas']++;
            } else {
                $monthlyData[$month]['en_proceso']++;
            }
        }

        return $monthlyData;
    }

    /**
     * Crear gráfico mensual
     */
    private function createMonthlyChart($sheet, $monthlyData)
    {
        $row = 4;

        // Headers
        $sheet->setCellValue('A' . $row, 'Mes');
        $sheet->setCellValue('B' . $row, 'Total');
        $sheet->setCellValue('C' . $row, 'Completadas');
        $sheet->setCellValue('D' . $row, 'En Proceso');

        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E4BC6']],
            'font' => ['color' => ['rgb' => 'FFFFFF']]
        ]);

        $currentRow = $row + 1;
        foreach ($monthlyData as $month => $data) {
            $sheet->setCellValue('A' . $currentRow, $month);
            $sheet->setCellValue('B' . $currentRow, $data['total']);
            $sheet->setCellValue('C' . $currentRow, $data['completadas']);
            $sheet->setCellValue('D' . $currentRow, $data['en_proceso']);
            $currentRow++;
        }
    }

    /**
     * Crear análisis de performance por ejecutivo
     */
    private function createExecutivePerformance($operaciones)
    {
        $execSheet = $this->spreadsheet->createSheet();
        $execSheet->setTitle('Performance Ejecutivos');

        $execSheet->mergeCells('A1:F1');
        $execSheet->setCellValue('A1', 'ANÁLISIS DE PERFORMANCE POR EJECUTIVO');
        $execSheet->getStyle('A1')->applyFromArray([
            'font' => ['size' => 16, 'bold' => true, 'color' => ['rgb' => '2E4BC6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
        ]);

        // Procesar datos por ejecutivo
        $execData = $this->processExecutiveData($operaciones);
        $this->renderExecutiveTable($execSheet, $execData);
    }

    /**
     * Procesar datos por ejecutivo
     */
    private function processExecutiveData($operaciones)
    {
        $execData = [];

        foreach ($operaciones as $operacion) {
            $ejecutivo = $operacion->ejecutivo ?? 'Sin Asignar';

            if (!isset($execData[$ejecutivo])) {
                $execData[$ejecutivo] = [
                    'total' => 0,
                    'completadas' => 0,
                    'en_proceso' => 0,
                    'fuera_metrica' => 0,
                    'promedio_dias' => 0,
                    'total_dias' => 0
                ];
            }

            $execData[$ejecutivo]['total']++;
            $execData[$ejecutivo]['total_dias'] += $operacion->calcularDiasTranscurridos();

            if ($operacion->status_manual === 'Done') {
                $execData[$ejecutivo]['completadas']++;
            } elseif ($operacion->status_manual === 'Out of Metric') {
                $execData[$ejecutivo]['fuera_metrica']++;
            } else {
                $execData[$ejecutivo]['en_proceso']++;
            }
        }

        // Calcular promedios
        foreach ($execData as $ejecutivo => $data) {
            $execData[$ejecutivo]['promedio_dias'] = $data['total'] > 0 ?
                round($data['total_dias'] / $data['total'], 1) : 0;
            $execData[$ejecutivo]['eficiencia'] = $data['total'] > 0 ?
                round(($data['completadas'] / $data['total']) * 100, 1) : 0;
        }

        return $execData;
    }

    /**
     * Renderizar tabla de ejecutivos
     */
    private function renderExecutiveTable($sheet, $execData)
    {
        $row = 4;
        $headers = ['Ejecutivo', 'Total Ops', 'Completadas', 'En Proceso', 'Fuera Métrica', 'Promedio Días', 'Eficiencia %'];

        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . $row, $header);
        }

        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E4BC6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $currentRow = $row + 1;
        foreach ($execData as $ejecutivo => $data) {
            $sheet->setCellValue('A' . $currentRow, $ejecutivo);
            $sheet->setCellValue('B' . $currentRow, $data['total']);
            $sheet->setCellValue('C' . $currentRow, $data['completadas']);
            $sheet->setCellValue('D' . $currentRow, $data['en_proceso']);
            $sheet->setCellValue('E' . $currentRow, $data['fuera_metrica']);
            $sheet->setCellValue('F' . $currentRow, $data['promedio_dias']);
            $sheet->setCellValue('G' . $currentRow, $data['eficiencia'] . '%');

            // Colorear eficiencia
            $eficienciaColor = $data['eficiencia'] >= 80 ? '4CAF50' : ($data['eficiencia'] >= 60 ? 'FF9800' : 'F44336');
            $sheet->getStyle('G' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID);
            $sheet->getStyle('G' . $currentRow)->getFill()->getStartColor()->setRGB($eficienciaColor);

            $currentRow++;
        }

        // Autoajustar columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Calcular resultado de operación
     */
    private function calculateResult($operacion)
    {
        $dias = $operacion->calcularDiasTranscurridos();
        $target = $operacion->dias_objetivo ?? 5;

        if ($operacion->status_manual === 'Done') {
            if ($dias <= $target) {
                return ['texto' => 'Completado a Tiempo', 'color' => '4CAF50'];
            } else {
                return ['texto' => 'Completado con Retraso', 'color' => 'FF9800'];
            }
        } elseif ($operacion->status_manual === 'Out of Metric') {
            return ['texto' => 'Fuera de Métrica', 'color' => 'F44336'];
        } else {
            if ($dias <= $target) {
                return ['texto' => 'En Tiempo', 'color' => '2196F3'];
            } elseif ($dias <= $target + 2) {
                return ['texto' => 'En Riesgo', 'color' => 'FF9800'];
            } else {
                return ['texto' => 'Con Retraso', 'color' => 'F44336'];
            }
        }
    }

    /**
     * Guardar archivo Excel
     */
    public function save($filename)
    {
        $writer = new Xlsx($this->spreadsheet);

        // Asegurar que el directorio existe
        $directory = dirname($filename);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $writer->save($filename);
        return $filename;
    }

    /**
     * Generar y retornar el contenido del archivo
     */
    public function output()
    {
        $writer = new Xlsx($this->spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    private function generarExcelTSV($operaciones, $rutaArchivo, $camposPersonalizados = null, $configColumnas = [])
    {
        // Usar PhpSpreadsheet para generar Excel nativo con diseño
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Operaciones Logísticas');
        
        // Obtener configuración de columnas
        $columnasVisibles = $configColumnas['columnas_visibles'] ?? [];
        $columnasOcultas = $configColumnas['columnas_ocultas'] ?? [];
        $idioma = $configColumnas['idioma'] ?? 'es';
        
        // Nombres de columnas según idioma
        $nombresPredeterminados = ColumnaVisibleEjecutivo::$columnasPredeterminadas;
        $nombresOpcionales = ColumnaVisibleEjecutivo::$columnasOpcionales;
        
        // Mapeo de columnas base (predeterminadas) con sus claves para posicionamiento
        $columnasBaseFull = [
            'id' => $nombresPredeterminados['id'][$idioma] ?? 'No.',
            'ejecutivo' => $nombresPredeterminados['ejecutivo'][$idioma] ?? 'Ejecutivo',
            'operacion' => $nombresPredeterminados['operacion'][$idioma] ?? 'Operación',
            'cliente' => $nombresPredeterminados['cliente'][$idioma] ?? 'Cliente',
            'proveedor_o_cliente' => $nombresPredeterminados['proveedor_o_cliente'][$idioma] ?? 'Proveedor/Cliente',
            'fecha_embarque' => $nombresPredeterminados['fecha_embarque'][$idioma] ?? 'Fecha de Embarque',
            'no_factura' => $nombresPredeterminados['no_factura'][$idioma] ?? 'No. Factura',
            'tipo_operacion_enum' => $nombresPredeterminados['tipo_operacion_enum'][$idioma] ?? 'T. Operación',
            'clave' => $nombresPredeterminados['clave'][$idioma] ?? 'Clave',
            'referencia_interna' => $nombresPredeterminados['referencia_interna'][$idioma] ?? 'Referencia Interna',
            'aduana' => $nombresPredeterminados['aduana'][$idioma] ?? 'Aduana',
            'agente_aduanal' => $nombresPredeterminados['agente_aduanal'][$idioma] ?? 'A.A',
            'referencia_aa' => $nombresPredeterminados['referencia_aa'][$idioma] ?? 'Referencia A.A',
            'no_pedimento' => $nombresPredeterminados['no_pedimento'][$idioma] ?? 'No Ped',
            'transporte' => $nombresPredeterminados['transporte'][$idioma] ?? 'Transporte',
            'fecha_arribo_aduana' => $nombresPredeterminados['fecha_arribo_aduana'][$idioma] ?? 'Fecha Arribo Aduana',
            'guia_bl' => $nombresPredeterminados['guia_bl'][$idioma] ?? 'Guía/BL',
            'status' => $nombresPredeterminados['status'][$idioma] ?? 'Status',
            'fecha_modulacion' => $nombresPredeterminados['fecha_modulacion'][$idioma] ?? 'Fecha Modulación',
            'fecha_arribo_planta' => $nombresPredeterminados['fecha_arribo_planta'][$idioma] ?? 'Fecha Arribo Planta',
            'resultado' => $nombresPredeterminados['resultado'][$idioma] ?? 'Resultado',
            'target' => $nombresPredeterminados['target'][$idioma] ?? 'Target',
            'dias_transito' => $nombresPredeterminados['dias_transito'][$idioma] ?? 'Días en Tránsito',
            'post_operaciones' => $nombresPredeterminados['post_operaciones'][$idioma] ?? 'Post-Operaciones',
            'comentarios' => $nombresPredeterminados['comentarios'][$idioma] ?? 'Comentarios'
        ];
        
        // Columnas opcionales con sus nombres
        $columnasOpcionalesFull = [
            'tipo_carga' => $nombresOpcionales['tipo_carga'][$idioma] ?? 'Tipo de Carga',
            'tipo_incoterm' => $nombresOpcionales['tipo_incoterm'][$idioma] ?? 'Incoterm',
            'puerto_salida' => $nombresOpcionales['puerto_salida'][$idioma] ?? 'Puerto de Salida',
            'in_charge' => $nombresOpcionales['in_charge'][$idioma] ?? 'Responsable',
            'proveedor' => $nombresOpcionales['proveedor'][$idioma] ?? 'Proveedor',
            'tipo_previo' => $nombresOpcionales['tipo_previo'][$idioma] ?? 'Modalidad/Previo',
            'fecha_etd' => $nombresOpcionales['fecha_etd'][$idioma] ?? 'Fecha ETD',
            'fecha_zarpe' => $nombresOpcionales['fecha_zarpe'][$idioma] ?? 'Fecha Zarpe',
            'pedimento_en_carpeta' => $nombresOpcionales['pedimento_en_carpeta'][$idioma] ?? 'Pedimento en Carpeta',
            'referencia_cliente' => $nombresOpcionales['referencia_cliente'][$idioma] ?? 'Referencia Cliente',
            'mail_subject' => $nombresOpcionales['mail_subject'][$idioma] ?? 'Asunto de Correo'
        ];
        
        // Filtrar columnas base (quitar las ocultas)
        $columnasBase = [];
        foreach ($columnasBaseFull as $clave => $nombre) {
            if (!in_array($clave, $columnasOcultas)) {
                $columnasBase[$clave] = $nombre;
            }
        }
        
        // Agregar columnas opcionales visibles
        foreach ($columnasVisibles as $colVisible) {
            if (isset($columnasOpcionalesFull[$colVisible])) {
                $columnasBase[$colVisible] = $columnasOpcionalesFull[$colVisible];
            }
        }

        // Construir cabeceras con campos personalizados insertados en posición correcta
        $cabeceras = [];
        $camposEnPosicion = []; // Para rastrear qué campos personalizados van después de qué columna
        
        // Agrupar campos personalizados por su posición (mostrar_despues_de)
        if ($camposPersonalizados && $camposPersonalizados->isNotEmpty()) {
            foreach ($camposPersonalizados as $campo) {
                $posicion = $campo->mostrar_despues_de ?? 'comentarios'; // Por defecto al final
                if (!isset($camposEnPosicion[$posicion])) {
                    $camposEnPosicion[$posicion] = [];
                }
                $camposEnPosicion[$posicion][] = $campo;
            }
        }

        // Construir array de cabeceras insertando campos personalizados en su posición
        foreach ($columnasBase as $clave => $nombreColumna) {
            $cabeceras[] = ['tipo' => 'base', 'clave' => $clave, 'nombre' => $nombreColumna];
            
            // Si hay campos personalizados que van después de esta columna, insertarlos
            if (isset($camposEnPosicion[$clave])) {
                foreach ($camposEnPosicion[$clave] as $campoPersonalizado) {
                    $cabeceras[] = ['tipo' => 'personalizado', 'campo' => $campoPersonalizado, 'nombre' => $campoPersonalizado->nombre];
                }
            }
        }
        
        // Agregar campos personalizados sin posición definida o con posición inválida al final
        $clavesValidas = array_keys($columnasBase);
        if ($camposPersonalizados && $camposPersonalizados->isNotEmpty()) {
            foreach ($camposPersonalizados as $campo) {
                $posicion = $campo->mostrar_despues_de;
                if (!$posicion || !in_array($posicion, $clavesValidas)) {
                    // Solo agregar si no fue agregado antes (posición inválida o vacía)
                    if (!$posicion) {
                        $cabeceras[] = ['tipo' => 'personalizado', 'campo' => $campo, 'nombre' => $campo->nombre];
                    }
                }
            }
        }

        // Configurar estilo profesional para cabeceras
        $estilosCabecera = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 
                      'startColor' => ['rgb' => '2E86AB']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];

        // Estilo especial para campos personalizados (fondo diferente)
        $estilosCabeceraPersonalizado = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 
                      'startColor' => ['rgb' => '6366F1']], // Color indigo para campos personalizados
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];

        // Escribir cabeceras con estilo Excel
        $columna = 1;
        foreach ($cabeceras as $cabecera) {
            $coordenada = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columna) . '1';
            $sheet->setCellValue($coordenada, $cabecera['nombre']);
            // Aplicar estilo diferente para campos personalizados
            if ($cabecera['tipo'] === 'personalizado') {
                $sheet->getStyle($coordenada)->applyFromArray($estilosCabeceraPersonalizado);
            } else {
                $sheet->getStyle($coordenada)->applyFromArray($estilosCabecera);
            }
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columna))->setAutoSize(true);
            $columna++;
        }

        // Preparar mapeo de valores de campos personalizados por operación
        $valoresPorOperacion = [];
        if ($camposPersonalizados && $camposPersonalizados->isNotEmpty()) {
            foreach ($operaciones as $op) {
                $valoresPorOperacion[$op->id] = $op->valoresCamposPersonalizados->keyBy('campo_personalizado_id');
            }
        }

        // DATOS COMPLETOS DE LA MATRIZ DE SEGUIMIENTO
        $filaExcel = 2; // Comenzar despus de las cabeceras
        foreach ($operaciones as $operacion) {
            // Calcular status actual (prioriza Done manual, sino usa calculado)
            $statusFinal = ($operacion->status_manual === 'Done') ? 'Done' : $operacion->status_calculado;
            $statusDisplay = match($statusFinal) {
                'In Process' => 'En Proceso',
                'Out of Metric' => 'Fuera de METRICA',
                'Done' => 'Completado',
                default => $statusFinal ?? 'En Proceso'
            };

            // Mapeo de valores base (columnas predeterminadas y opcionales)
            $valoresBase = [
                // Columnas predeterminadas
                'id' => $operacion->id,
                'ejecutivo' => $operacion->ejecutivo ?? 'Sin asignar',
                'operacion' => $operacion->operacion ?? '-',
                'cliente' => $operacion->cliente ?? 'Sin cliente',
                'proveedor_o_cliente' => $operacion->proveedor_o_cliente ?? '-',
                'fecha_embarque' => optional($operacion->fecha_embarque)->format('d/m/Y') ?? '-',
                'no_factura' => $operacion->no_factura ?? '-',
                'tipo_operacion_enum' => $operacion->tipo_operacion_enum ?? '-',
                'clave' => $operacion->clave ?? '-',
                'referencia_interna' => $operacion->referencia_interna ?? '-',
                'aduana' => $operacion->aduana ?? '-',
                'agente_aduanal' => $operacion->agente_aduanal ?? '-',
                'referencia_aa' => $operacion->referencia_aa ?? '-',
                'no_pedimento' => $operacion->no_pedimento ?? '-',
                'transporte' => $operacion->transporte ?? '-',
                'fecha_arribo_aduana' => optional($operacion->fecha_arribo_aduana)->format('d/m/Y') ?? '-',
                'guia_bl' => $operacion->guia_bl ?? '-',
                'status' => $statusDisplay,
                'fecha_modulacion' => optional($operacion->fecha_modulacion)->format('d/m/Y') ?? '-',
                'fecha_arribo_planta' => optional($operacion->fecha_arribo_planta)->format('d/m/Y') ?? '-',
                'resultado' => $operacion->resultado ?? '-',
                'target' => $operacion->target ?? '-',
                'dias_transito' => $operacion->dias_transito ?? '-',
                'post_operaciones' => $this->formatearPostOperaciones($operacion),
                'comentarios' => $this->limpiarTexto($operacion->comentarios ?? '-'),
                
                // Columnas opcionales
                'tipo_carga' => $operacion->tipo_carga ?? '-',
                'tipo_incoterm' => $operacion->tipo_incoterm ?? '-',
                'puerto_salida' => $operacion->puerto_salida ?? '-',
                'in_charge' => $operacion->in_charge ?? '-',
                'proveedor' => $operacion->proveedor ?? '-',
                'tipo_previo' => $operacion->tipo_previo ?? '-',
                'fecha_etd' => optional($operacion->fecha_etd)->format('d/m/Y') ?? '-',
                'fecha_zarpe' => optional($operacion->fecha_zarpe)->format('d/m/Y') ?? '-',
                'pedimento_en_carpeta' => $operacion->pedimento_en_carpeta ? 'Sí' : 'No',
                'referencia_cliente' => $operacion->referencia_cliente ?? '-',
                'mail_subject' => $operacion->mail_subject ?? '-'
            ];

            // Construir fila siguiendo el orden de cabeceras
            $fila = [];
            $valoresOperacion = $valoresPorOperacion[$operacion->id] ?? collect();
            
            foreach ($cabeceras as $cabecera) {
                if ($cabecera['tipo'] === 'base') {
                    $fila[] = $valoresBase[$cabecera['clave']] ?? '-';
                } else {
                    // Campo personalizado
                    $campo = $cabecera['campo'];
                    $valorCampo = $valoresOperacion->get($campo->id);
                    $valorMostrar = '-';
                    if ($valorCampo && $valorCampo->valor) {
                        $valorMostrar = $valorCampo->valor;
                        // Formatear fecha si es campo de tipo fecha
                        if ($campo->tipo === 'fecha') {
                            try {
                                $valorMostrar = \Carbon\Carbon::parse($valorCampo->valor)->format('d/m/Y');
                            } catch (\Exception $e) {
                                $valorMostrar = $valorCampo->valor;
                            }
                        }
                    }
                    $fila[] = $valorMostrar;
                }
            }

            // Escribir fila de datos en Excel con estilo
            $columna = 1;
            foreach ($fila as $valor) {
                $coordenada = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columna) . $filaExcel;
                $sheet->setCellValue($coordenada, $valor);
                
                // Aplicar estilo a datos
                $sheet->getStyle($coordenada)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
                ]);
                
                $columna++;
            }
            
            $filaExcel++; // Incrementar fila para la siguiente iteracin
        }

        // Guardar archivo Excel
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($rutaArchivo);
    }
}

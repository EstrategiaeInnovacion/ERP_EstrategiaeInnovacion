<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\Axis;
use PhpOffice\PhpSpreadsheet\Chart\GridLines;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelChartService
{
    /**
     * Crear gráfico de barras avanzado
     */
    public static function createAdvancedBarChart(
        Worksheet $worksheet, 
        string $dataRange, 
        string $position, 
        string $title,
        array $options = []
    ) {
        // Extraer rangos de datos
        $ranges = self::parseDataRange($dataRange);
        
        // Configurar etiquetas
        $dataSeriesLabels = [];
        for ($i = 1; $i < count($ranges['columns']); $i++) {
            $dataSeriesLabels[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $worksheet->getTitle() . '!' . $ranges['columns'][$i] . $ranges['headerRow'],
                null,
                1
            );
        }
        
        // Configurar valores
        $dataSeriesValues = [];
        for ($i = 1; $i < count($ranges['columns']); $i++) {
            $dataSeriesValues[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $worksheet->getTitle() . '!' . $ranges['columns'][$i] . $ranges['firstRow'] . ':' . $ranges['columns'][$i] . $ranges['lastRow'],
                null,
                $ranges['dataRows']
            );
        }
        
        // Configurar categorías (etiquetas del eje X)
        $xAxisLabels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $worksheet->getTitle() . '!' . $ranges['columns'][0] . $ranges['firstRow'] . ':' . $ranges['columns'][0] . $ranges['lastRow'],
                null,
                $ranges['dataRows']
            )
        ];
        
        // Crear serie de datos
        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            $xAxisLabels,
            $dataSeriesValues
        );
        
        // Configurar dirección de las barras
        $series->setPlotDirection(DataSeries::DIRECTION_COL);
        
        // Crear área de ploteo
        $plotArea = new PlotArea(null, [$series]);
        
        // Configurar leyenda
        $legend = new Legend(
            $options['legendPosition'] ?? Legend::POSITION_TOP, 
            null, 
            false
        );
        
        // Crear título
        $chartTitle = new Title($title);
        
        // Crear gráfico
        $chart = new Chart(
            'chart_' . uniqid(),
            $chartTitle,
            $legend,
            $plotArea
        );
        
        // Configurar posición
        $positionData = self::parsePosition($position);
        $chart->setTopLeftPosition($positionData['start']);
        $chart->setBottomRightPosition($positionData['end']);
        
        // Aplicar estilos personalizados
        self::applyChartStyles($chart, $options);
        
        return $chart;
    }
    
    /**
     * Crear gráfico de líneas con múltiples series
     */
    public static function createLineChart(
        Worksheet $worksheet,
        string $dataRange,
        string $position,
        string $title,
        array $options = []
    ) {
        $ranges = self::parseDataRange($dataRange);
        
        // Configurar etiquetas de series
        $dataSeriesLabels = [];
        for ($i = 1; $i < count($ranges['columns']); $i++) {
            $dataSeriesLabels[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $worksheet->getTitle() . '!' . $ranges['columns'][$i] . $ranges['headerRow'],
                null,
                1
            );
        }
        
        // Configurar valores de series
        $dataSeriesValues = [];
        for ($i = 1; $i < count($ranges['columns']); $i++) {
            $dataSeriesValues[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $worksheet->getTitle() . '!' . $ranges['columns'][$i] . $ranges['firstRow'] . ':' . $ranges['columns'][$i] . $ranges['lastRow'],
                null,
                $ranges['dataRows']
            );
        }
        
        // Configurar categorías (eje X)
        $xAxisLabels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $worksheet->getTitle() . '!' . $ranges['columns'][0] . $ranges['firstRow'] . ':' . $ranges['columns'][0] . $ranges['lastRow'],
                null,
                $ranges['dataRows']
            )
        ];
        
        // Crear serie de datos
        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            $xAxisLabels,
            $dataSeriesValues
        );
        
        // Configurar área de ploteo
        $plotArea = new PlotArea(null, [$series]);
        
        // Configurar leyenda
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        
        // Crear título
        $chartTitle = new Title($title);
        
        // Crear gráfico
        $chart = new Chart(
            'lineChart_' . uniqid(),
            $chartTitle,
            $legend,
            $plotArea
        );
        
        // Configurar posición
        $positionData = self::parsePosition($position);
        $chart->setTopLeftPosition($positionData['start']);
        $chart->setBottomRightPosition($positionData['end']);
        
        return $chart;
    }
    
    /**
     * Crear gráfico de área apilada
     */
    public static function createAreaChart(
        Worksheet $worksheet,
        string $dataRange,
        string $position,
        string $title,
        array $options = []
    ) {
        $ranges = self::parseDataRange($dataRange);
        
        // Similar configuración que el gráfico de líneas
        $dataSeriesLabels = [];
        $dataSeriesValues = [];
        
        for ($i = 1; $i < count($ranges['columns']); $i++) {
            $dataSeriesLabels[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $worksheet->getTitle() . '!' . $ranges['columns'][$i] . $ranges['headerRow'],
                null,
                1
            );
            
            $dataSeriesValues[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $worksheet->getTitle() . '!' . $ranges['columns'][$i] . $ranges['firstRow'] . ':' . $ranges['columns'][$i] . $ranges['lastRow'],
                null,
                $ranges['dataRows']
            );
        }
        
        $xAxisLabels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $worksheet->getTitle() . '!' . $ranges['columns'][0] . $ranges['firstRow'] . ':' . $ranges['columns'][0] . $ranges['lastRow'],
                null,
                $ranges['dataRows']
            )
        ];
        
        // Crear serie de datos para área apilada
        $series = new DataSeries(
            DataSeries::TYPE_AREACHART,
            DataSeries::GROUPING_STACKED,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            $xAxisLabels,
            $dataSeriesValues
        );
        
        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $chartTitle = new Title($title);
        
        $chart = new Chart(
            'areaChart_' . uniqid(),
            $chartTitle,
            $legend,
            $plotArea
        );
        
        $positionData = self::parsePosition($position);
        $chart->setTopLeftPosition($positionData['start']);
        $chart->setBottomRightPosition($positionData['end']);
        
        return $chart;
    }
    
    /**
     * Crear gráfico de dona/pie mejorado
     */
    public static function createEnhancedPieChart(
        Worksheet $worksheet,
        string $dataRange,
        string $position,
        string $title,
        array $options = []
    ) {
        $ranges = self::parseDataRange($dataRange);
        
        // Para gráfico pie, solo necesitamos una serie
        $dataSeriesLabels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $worksheet->getTitle() . '!' . $ranges['columns'][0] . $ranges['firstRow'] . ':' . $ranges['columns'][0] . $ranges['lastRow'],
                null,
                $ranges['dataRows']
            )
        ];
        
        $dataSeriesValues = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $worksheet->getTitle() . '!' . $ranges['columns'][1] . $ranges['firstRow'] . ':' . $ranges['columns'][1] . $ranges['lastRow'],
                null,
                $ranges['dataRows']
            )
        ];
        
        // Crear serie
        $chartType = $options['chartType'] ?? DataSeries::TYPE_PIECHART;
        $series = new DataSeries(
            $chartType,
            DataSeries::GROUPING_STANDARD,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            null,
            $dataSeriesValues
        );
        
        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $chartTitle = new Title($title);
        
        $chart = new Chart(
            'pieChart_' . uniqid(),
            $chartTitle,
            $legend,
            $plotArea
        );
        
        $positionData = self::parsePosition($position);
        $chart->setTopLeftPosition($positionData['start']);
        $chart->setBottomRightPosition($positionData['end']);
        
        return $chart;
    }
    
    /**
     * Parsear rango de datos
     */
    private static function parseDataRange(string $range): array
    {
        // Ejemplo: "A1:D10"
        [$start, $end] = explode(':', $range);
        
        $startCol = preg_replace('/[0-9]/', '', $start);
        $startRow = (int) preg_replace('/[A-Z]/', '', $start);
        $endCol = preg_replace('/[0-9]/', '', $end);
        $endRow = (int) preg_replace('/[A-Z]/', '', $end);
        
        // Generar columnas
        $columns = [];
        for ($col = $startCol; $col <= $endCol; $col++) {
            $columns[] = $col;
        }
        
        return [
            'columns' => $columns,
            'headerRow' => $startRow,
            'firstRow' => $startRow + 1,
            'lastRow' => $endRow,
            'dataRows' => $endRow - $startRow
        ];
    }
    
    /**
     * Parsear posición del gráfico
     */
    private static function parsePosition(string $position): array
    {
        // Ejemplo: "E1:J15"
        [$start, $end] = explode(':', $position);
        
        return [
            'start' => $start,
            'end' => $end
        ];
    }
    
    /**
     * Aplicar estilos personalizados al gráfico
     */
    private static function applyChartStyles(Chart $chart, array $options): void
    {
        // Aquí se pueden aplicar estilos adicionales
        // Por ejemplo, colores personalizados, fuentes, etc.
        
        if (isset($options['colors'])) {
            // Aplicar colores personalizados
            // Nota: PhpSpreadsheet tiene limitaciones para colores personalizados
        }
        
        if (isset($options['fontSize'])) {
            // Aplicar tamaño de fuente
        }
    }
    
    /**
     * Crear dashboard con múltiples gráficos
     */
    public static function createDashboard(
        Worksheet $worksheet,
        array $chartConfigs
    ): array {
        $charts = [];
        
        foreach ($chartConfigs as $config) {
            switch ($config['type']) {
                case 'bar':
                    $chart = self::createAdvancedBarChart(
                        $worksheet,
                        $config['dataRange'],
                        $config['position'],
                        $config['title'],
                        $config['options'] ?? []
                    );
                    break;
                    
                case 'line':
                    $chart = self::createLineChart(
                        $worksheet,
                        $config['dataRange'],
                        $config['position'],
                        $config['title'],
                        $config['options'] ?? []
                    );
                    break;
                    
                case 'area':
                    $chart = self::createAreaChart(
                        $worksheet,
                        $config['dataRange'],
                        $config['position'],
                        $config['title'],
                        $config['options'] ?? []
                    );
                    break;
                    
                case 'pie':
                    $chart = self::createEnhancedPieChart(
                        $worksheet,
                        $config['dataRange'],
                        $config['position'],
                        $config['title'],
                        $config['options'] ?? []
                    );
                    break;
                    
                default:
                    continue 2;
            }
            
            $worksheet->addChart($chart);
            $charts[] = $chart;
        }
        
        return $charts;
    }
}
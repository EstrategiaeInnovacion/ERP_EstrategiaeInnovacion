<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use App\Models\Logistica\Aduana;

class AduanaImportService
{
    /**
     * Importar aduanas desde un archivo (Word, Excel o CSV)
     *
     * @param string $filePath Ruta del archivo a importar
     * @return array Resultado de la importación
     */
    public function import(string $filePath): array
    {
        try {
            // Verificar que el archivo existe
            if (!file_exists($filePath)) {
                throw new \Exception("El archivo no existe: {$filePath}");
            }

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            // Procesar según el tipo de archivo
            if (in_array($extension, ['docx', 'doc'])) {
                return $this->importFromWord($filePath);
            } elseif (in_array($extension, ['csv'])) {
                return $this->importFromCsv($filePath);
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                return $this->importFromExcel($filePath);
            } else {
                throw new \Exception("Tipo de archivo no soportado: {$extension}");
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
                'total_processed' => 0,
                'total_imported' => 0,
                'total_skipped' => 0,
                'rows' => []
            ];
        }
    }

    /**
     * Importar desde archivo Word
     */
    private function importFromWord(string $filePath): array
    {
        // Cargar el documento Word
        $doc = IOFactory::load($filePath);
        $rows = [];

        // Expresión regular para extraer información de aduanas
        // Formato esperado: "99 9 Denominación de la aduana" o "99 Denominación"
        $regex = '/^(\d{2})\s+(\d{1})?\s*(.+)$/u';

        // Procesar todas las secciones del documento
        foreach ($doc->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $this->processElement($element, $regex, $rows);
            }
        }

        // Filtrar y validar los datos extraídos
        $rows = $this->validateAndCleanRows($rows);

        return $this->saveAduanas($rows);
    }

    /**
     * Importar desde archivo CSV
     */
    private function importFromCsv(string $filePath): array
    {
        $rows = [];
        $file = fopen($filePath, 'r');
        
        // Leer encabezados (primera línea)
        $headers = fgetcsv($file);
        
        // Leer datos
        while (($data = fgetcsv($file)) !== FALSE) {
            if (count($data) >= 2 && !empty($data[0]) && is_numeric($data[0])) {
                $rows[] = [
                    'aduana' => str_pad($data[0], 2, '0', STR_PAD_LEFT),
                    'seccion' => isset($data[1]) ? $this->cleanExcelValue($data[1]) : '0',
                    'denominacion' => isset($data[2]) ? $this->cleanExcelValue($data[2]) : $this->cleanExcelValue($data[1])
                ];
            }
        }
        
        fclose($file);
        
        $rows = $this->validateAndCleanRows($rows);
        return $this->saveAduanas($rows);
    }

    /**
     * Importar desde archivo Excel
     */
    private function importFromExcel(string $filePath): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($worksheet->getRowIterator(2) as $row) { // Empezar desde fila 2 (omitir encabezados)
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $value = $cell->getValue();
                // Limpiar valores de error de Excel
                $rowData[] = $this->cleanExcelValue($value);
            }
            
            if (count($rowData) >= 2 && !empty($rowData[0]) && is_numeric($rowData[0])) {
                $rows[] = [
                    'aduana' => str_pad($rowData[0], 2, '0', STR_PAD_LEFT),
                    'seccion' => isset($rowData[1]) ? $rowData[1] : '0',
                    'denominacion' => isset($rowData[2]) ? $rowData[2] : $rowData[1]
                ];
            }
        }

        $rows = $this->validateAndCleanRows($rows);
        return $this->saveAduanas($rows);
    }

    /**
     * Guardar aduanas en la base de datos
     */
    private function saveAduanas($rows): array
    {
        $totalImported = 0;

        DB::transaction(function () use ($rows, &$totalImported) {
            foreach ($rows as $row) {
                // Verificar si ya existe la combinación aduana + sección
                $existing = Aduana::where('aduana', $row['aduana'])
                                 ->where('seccion', $row['seccion'])
                                 ->first();

                if (!$existing) {
                    Aduana::create([
                        'aduana' => $row['aduana'],
                        'seccion' => $row['seccion'],
                        'denominacion' => $row['denominacion'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $totalImported++;
                }
            }
        });

        return [
            'success' => true,
            'total_processed' => count($rows),
            'total_imported' => $totalImported,
            'total_skipped' => count($rows) - $totalImported,
            'rows' => $rows,
            'message' => "Importación completada. {$totalImported} aduanas importadas, " . (count($rows) - $totalImported) . " omitidas por duplicados."
        ];
    }

    /**
     * Procesar elemento del documento Word recursivamente
     */
    private function processElement($element, $regex, &$rows)
    {
        // Si el elemento tiene método getText
        if (method_exists($element, 'getText')) {
            $text = $element->getText();
            $this->extractDataFromText($text, $regex, $rows);
        }
        
        // Si el elemento tiene elementos hijos
        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $this->processElement($childElement, $regex, $rows);
            }
        }
        
        // Procesar tablas si existen
        if (method_exists($element, 'getRows')) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    $this->processElement($cell, $regex, $rows);
                }
            }
        }
    }

    /**
     * Extraer datos del texto usando regex
     */
    private function extractDataFromText($text, $regex, &$rows)
    {
        // Procesar cada línea del texto
        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            
            // Saltar líneas vacías o muy cortas
            if (empty($line) || strlen($line) < 3) {
                continue;
            }

            // Aplicar regex para extraer datos
            if (preg_match($regex, $line, $matches)) {
                $rows[] = [
                    'aduana' => $matches[1],
                    'seccion' => $matches[2] ?? '0',
                    'denominacion' => trim($matches[3])
                ];
            }
        }
    }

    /**
     * Validar y limpiar los datos extraídos
     */
    private function validateAndCleanRows($rows)
    {
        $cleanRows = [];
        
        foreach ($rows as $row) {
            // Validar que los campos requeridos existan
            if (empty($row['aduana']) || empty($row['denominacion'])) {
                continue;
            }
            
            // Limpiar y normalizar datos
            $cleanRow = [
                'aduana' => str_pad($row['aduana'], 2, '0', STR_PAD_LEFT),
                'seccion' => $this->validateSeccion($row['seccion']),
                'denominacion' => $this->cleanDenominacion($row['denominacion']),
                'patente' => null, // Se puede completar manualmente después
                'pais' => null     // Se puede completar manualmente después
            ];
            
            // Validar que la sección sea válida después de la limpieza
            if (strlen($cleanRow['seccion']) > 1) {
                continue; // Saltar filas con sección inválida
            }
            
            // Evitar duplicados
            $key = $cleanRow['aduana'] . '_' . $cleanRow['seccion'];
            if (!isset($cleanRows[$key])) {
                $cleanRows[$key] = $cleanRow;
            }
        }
        
        return array_values($cleanRows);
    }

    /**
     * Obtener estadísticas de aduanas
     */
    public function getStats(): array
    {
        return [
            'total_aduanas' => Aduana::count(),
            'por_pais' => Aduana::select('pais', DB::raw('count(*) as total'))
                                ->groupBy('pais')
                                ->get()
                                ->pluck('total', 'pais')
                                ->toArray(),
            'ultimas_importadas' => Aduana::latest()
                                          ->limit(5)
                                          ->get()
                                          ->map(function ($aduana) {
                                              return [
                                                  'id' => $aduana->id,
                                                  'codigo' => $aduana->aduana . $aduana->seccion,
                                                  'denominacion' => $aduana->denominacion,
                                                  'fecha' => $aduana->created_at->format('d/m/Y H:i')
                                              ];
                                          })
                                          ->toArray()
        ];
    }

    /**
     * Limpiar valores de Excel que pueden contener errores
     */
    private function cleanExcelValue($value)
    {
        // Si el valor es null o está vacío
        if ($value === null || $value === '') {
            return null;
        }

        // Convertir a string para procesar
        $stringValue = (string) $value;

        // Manejar errores comunes de Excel
        $excelErrors = ['#VALUE!', '#N/A', '#REF!', '#DIV/0!', '#NUM!', '#NAME?', '#NULL!'];
        
        if (in_array($stringValue, $excelErrors)) {
            return null;
        }

        // Limpiar espacios extra
        return trim($stringValue);
    }

    /**
     * Validar y limpiar el campo sección
     */
    private function validateSeccion($seccion)
    {
        // Si es null o vacío, usar '0' por defecto
        if (empty($seccion)) {
            return '0';
        }

        // Convertir a string y limpiar
        $seccion = trim((string) $seccion);

        // Si contiene errores de Excel, usar '0'
        $excelErrors = ['#VALUE!', '#N/A', '#REF!', '#DIV/0!', '#NUM!', '#NAME?', '#NULL!'];
        if (in_array($seccion, $excelErrors)) {
            return '0';
        }

        // Solo permitir números de un dígito
        if (preg_match('/^\d{1}$/', $seccion)) {
            return $seccion;
        }

        // Si no es válido, usar '0' por defecto
        return '0';
    }

    /**
     * Limpiar y validar el campo denominación
     */
    private function cleanDenominacion($denominacion)
    {
        // Si es null o vacío
        if (empty($denominacion)) {
            return '';
        }

        // Convertir a string
        $denominacion = (string) $denominacion;

        // Manejar errores de Excel
        $excelErrors = ['#VALUE!', '#N/A', '#REF!', '#DIV/0!', '#NUM!', '#NAME?', '#NULL!'];
        if (in_array($denominacion, $excelErrors)) {
            return '';
        }

        // Limpiar espacios múltiples y normalizar
        return trim(preg_replace('/\s+/', ' ', $denominacion));
    }
}

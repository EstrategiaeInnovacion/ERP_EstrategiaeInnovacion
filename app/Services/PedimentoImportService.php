<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use App\Models\Logistica\Pedimento;

class PedimentoImportService
{
    /**
     * Importar pedimentos desde un archivo Excel
     *
     * @param string $filePath Ruta del archivo Excel a importar (.xlsx, .xls)
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
            if (in_array($extension, ['xlsx', 'xls'])) {
                return $this->importFromExcel($filePath);
            } else {
                throw new \Exception("Tipo de archivo no soportado. Solo se aceptan archivos Excel (.xlsx, .xls): {$extension}");
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
        \Log::info("Iniciando importación de pedimentos desde Word: {$filePath}");
        
        // Cargar el documento Word
        $doc = IOFactory::load($filePath);
        $rows = [];
        $currentCategoria = null;

        // Expresiones regulares para extraer información (más flexibles)
        $pedimentoRegex = '/([A-Z]\d+)\s*[-–—]\s*(.+)/';
        $categoriaRegex = '/^[A-ZÁÉÍÓÚÑ\s\(\)]{8,}$/';

        \Log::info("Procesando " . count($doc->getSections()) . " secciones del documento");

        // Procesar todas las secciones del documento
        foreach ($doc->getSections() as $sectionIndex => $section) {
            \Log::info("Procesando sección {$sectionIndex} con " . count($section->getElements()) . " elementos");
            
            foreach ($section->getElements() as $elementIndex => $element) {
                $this->processElementWithCategories($element, $pedimentoRegex, $categoriaRegex, $rows, $currentCategoria);
            }
        }
        
        \Log::info("Total de filas extraídas: " . count($rows));
        
        // Si no se encontraron filas, intentar método alternativo
        if (empty($rows)) {
            \Log::info("No se encontraron filas, intentando método alternativo");
            $rows = $this->extractAllTextAlternative($doc, $pedimentoRegex, $categoriaRegex);
            \Log::info("Método alternativo extrajo: " . count($rows) . " filas");
        }

        try {
            $result = $this->savePedimentos($rows);
            
            return [
                'success' => true,
                'total_imported' => $result['total_imported'],
                'errors' => []
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'total_imported' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
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
            if (count($data) >= 2 && !empty($data[0])) {
                $rows[] = [
                    'clave' => $this->cleanExcelValue($data[0]),
                    'descripcion' => $this->cleanExcelValue($data[1])
                ];
            }
        }
        
        fclose($file);
        
        $rows = $this->validateAndCleanRows($rows);
        return $this->savePedimentos($rows);
    }

    /**
     * Importar desde archivo Excel
     */
    private function importFromExcel(string $filePath): array
    {
        \Log::info("Iniciando importación de pedimentos desde Excel: {$filePath}");
        
        $spreadsheet = SpreadsheetIOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        // Verificar encabezados para asegurar el formato correcto
        $headerRow = $worksheet->rangeToArray('A1:C1')[0];
        \Log::info("Encabezados encontrados: " . json_encode($headerRow));

        foreach ($worksheet->getRowIterator(2) as $rowNumber => $row) { // Empezar desde fila 2 (omitir encabezados)
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $value = $cell->getValue();
                // Limpiar valores de error de Excel
                $rowData[] = $this->cleanExcelValue($value);
            }
            
            // Verificar que tengamos al menos 3 columnas y que la clave no esté vacía
            if (count($rowData) >= 3 && !empty($rowData[0])) {
                $clave = trim($rowData[0]);
                $denominacion = trim($rowData[1] ?? '');
                $tipo = trim($rowData[2] ?? '');
                
                \Log::info("Fila {$rowNumber}: Clave='{$clave}', Denominación='{$denominacion}', Tipo='{$tipo}'");
                
                // Determinar categoría basado en el tipo
                $categoria = null;
                
                if (!empty($tipo) && $tipo !== '=') {
                    $categoria = $tipo;
                } else {
                    // Si no hay tipo específico, usar lógica basada en la clave
                    if (preg_match('/^[A-Z]\d+/', $clave)) {
                        $categoria = 'OPERACIONES GENERALES';
                    }
                }
                
                $rows[] = [
                    'clave' => $clave,
                    'descripcion' => $denominacion,
                    'categoria' => $categoria
                ];
            } else if (!empty($rowData[0])) {
                \Log::warning("Fila {$rowNumber} omitida - datos insuficientes: " . json_encode($rowData));
            }
        }

        \Log::info("Total de filas procesadas: " . count($rows));
        $rows = $this->validateAndCleanRows($rows);
        return $this->savePedimentos($rows);
    }

    /**
     * Procesar elemento del documento con detección de categorías
     */
    private function processElementWithCategories($element, $pedimentoRegex, $categoriaRegex, &$rows, &$currentCategoria)
    {
        if (method_exists($element, 'getText')) {
            $text = trim($element->getText());
            
            if (!empty($text)) {
                \Log::info("Procesando texto: '{$text}'");
                
                // Verificar si es una categoría principal (texto en mayúsculas sin códigos)
                if (preg_match($categoriaRegex, $text) && !preg_match('/^[A-Z]\d+/', $text)) {
                    \Log::info("Detectada categoría: '{$text}'");
                    
                    // Es una categoría
                    if (strlen($text) > 20 && strpos($text, '(') !== false) {
                        // Categoría principal con subcategoría en paréntesis - solo tomar la parte principal
                        $parts = explode('(', $text);
                        $currentCategoria = trim($parts[0]);
                        \Log::info("Categoría detectada: '{$currentCategoria}'");
                    } else if (strlen($text) > 10 && ctype_upper(str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], '', $text))) {
                        // Categoría principal
                        $currentCategoria = $text;
                        \Log::info("Categoría principal: '{$currentCategoria}'");
                    }
                }
                // Verificar si es un código de pedimento
                else if (preg_match($pedimentoRegex, $text, $matches)) {
                    $clave = trim($matches[1]);
                    $descripcion = trim($matches[2]);
                    \Log::info("Detectado pedimento: '{$clave}' - '{$descripcion}'");
                    
                    if (!empty($clave) && !empty($descripcion)) {
                        $rows[] = [
                            'categoria' => $currentCategoria,
                            'clave' => $clave,
                            'descripcion' => $descripcion
                        ];
                        \Log::info("Pedimento agregado a filas");
                    }
                } else {
                    \Log::info("Texto no coincide con ningún patrón");
                }
            }
        }

        // Procesar elementos anidados
        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $this->processElementWithCategories($childElement, $pedimentoRegex, $categoriaRegex, $rows, $currentCategoria);
            }
        }
    }

    /**
     * Guardar pedimentos en la base de datos
     */
    private function savePedimentos($rows): array
    {
        $totalImported = 0;
        $totalSkipped = 0;

        \Log::info("Intentando guardar " . count($rows) . " pedimentos");

        DB::transaction(function () use ($rows, &$totalImported, &$totalSkipped) {
            foreach ($rows as $row) {
                // Verificar si ya existe
                $existing = Pedimento::where('clave', $row['clave'])->first();

                if (!$existing) {
                    $pedimento = Pedimento::create([
                        'categoria' => $row['categoria'] ?? null,
                        'clave' => $row['clave'],
                        'descripcion' => $row['descripcion'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $totalImported++;
                    \Log::info("Pedimento guardado: {$pedimento->clave} - {$pedimento->descripcion}");
                } else {
                    $totalSkipped++;
                    \Log::info("Pedimento omitido (ya existe): {$row['clave']}");
                }
            }
        });

        \Log::info("Importación completada - Procesados: " . count($rows) . ", Importados: {$totalImported}, Omitidos: {$totalSkipped}");

        return [
            'success' => true,
            'total_processed' => count($rows),
            'total_imported' => $totalImported,
            'total_skipped' => $totalSkipped,
            'rows' => $rows,
            'message' => "Importación completada. {$totalImported} pedimentos importados, {$totalSkipped} omitidos por duplicados."
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
                    'clave' => strtoupper(trim($matches[1])),
                    'descripcion' => trim($matches[2])
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
            if (empty($row['clave']) || empty($row['descripcion'])) {
                \Log::warning("Fila omitida - clave o descripción vacía: " . json_encode($row));
                continue;
            }
            
            // Limpiar y normalizar datos
            $cleanRow = [
                'clave' => strtoupper(trim($row['clave'])),
                'descripcion' => $this->cleanDescripcion($row['descripcion']),
                'categoria' => !empty($row['categoria']) ? trim($row['categoria']) : null
            ];
            
            \Log::info("Fila limpiada: " . json_encode($cleanRow));
            
            // Evitar duplicados en el mismo archivo
            $key = $cleanRow['clave'];
            if (!isset($cleanRows[$key])) {
                $cleanRows[$key] = $cleanRow;
            } else {
                \Log::warning("Duplicado omitido en el archivo: {$key}");
            }
        }
        
        \Log::info("Filas limpias generadas: " . count($cleanRows));
        return array_values($cleanRows);
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
     * Limpiar y validar el campo descripción
     */
    private function cleanDescripcion($descripcion)
    {
        // Si es null o vacío
        if (empty($descripcion)) {
            return '';
        }

        // Convertir a string
        $descripcion = (string) $descripcion;

        // Manejar errores de Excel
        $excelErrors = ['#VALUE!', '#N/A', '#REF!', '#DIV/0!', '#NUM!', '#NAME?', '#NULL!'];
        if (in_array($descripcion, $excelErrors)) {
            return '';
        }

        // Limpiar espacios múltiples y normalizar
        return trim(preg_replace('/\s+/', ' ', $descripcion));
    }

    /**
     * Obtener estadísticas de pedimentos
     */
    public function getStats(): array
    {
        return [
            'total_pedimentos' => Pedimento::count(),
            'por_tipo' => Pedimento::select(DB::raw('SUBSTRING(clave, 1, 1) as tipo'), DB::raw('count(*) as total'))
                                  ->groupBy(DB::raw('SUBSTRING(clave, 1, 1)'))
                                  ->get()
                                  ->pluck('total', 'tipo')
                                  ->toArray(),
            'ultimos_importados' => Pedimento::latest()
                                            ->limit(5)
                                            ->get()
                                            ->map(function ($pedimento) {
                                                return [
                                                    'id' => $pedimento->id,
                                                    'clave' => $pedimento->clave,
                                                    'descripcion' => $pedimento->descripcion,
                                                    'fecha' => $pedimento->created_at->format('d/m/Y H:i')
                                                ];
                                            })
                                            ->toArray()
        ];
    }

    /**
     * Método alternativo para extraer todo el texto del documento
     */
    private function extractAllTextAlternative($doc, $pedimentoRegex, $categoriaRegex)
    {
        $rows = [];
        $currentCategoria = null;
        
        try {
            // Obtener todo el texto del documento de una vez
            $allText = '';
            foreach ($doc->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $allText .= $this->getAllTextFromElement($element) . "\n";
                }
            }
            
            \Log::info("Texto completo extraído del documento (primeros 500 caracteres): " . substr($allText, 0, 500));
            
            // Procesar línea por línea
            $lines = explode("\n", $allText);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Verificar si es categoría
                if (preg_match($categoriaRegex, $line) && !preg_match('/[A-Z]\d+/', $line)) {
                    if (strlen($line) > 20 && strpos($line, '(') !== false) {
                        $parts = explode('(', $line);
                        $currentCategoria = trim($parts[0]);
                    } else if (strlen($line) > 8) {
                        $currentCategoria = $line;
                    }
                }
                // Verificar si es pedimento
                else if (preg_match($pedimentoRegex, $line, $matches)) {
                    $clave = trim($matches[1]);
                    $descripcion = trim($matches[2]);
                    
                    if (!empty($clave) && !empty($descripcion)) {
                        $rows[] = [
                            'categoria' => $currentCategoria,
                            'clave' => $clave,
                            'descripcion' => $descripcion
                        ];
                    }
                }
            }
            
        } catch (\Exception $e) {
            \Log::error("Error en método alternativo: " . $e->getMessage());
        }
        
        return $rows;
    }

    /**
     * Extraer todo el texto de un elemento recursivamente
     */
    private function getAllTextFromElement($element)
    {
        $text = '';
        
        if (method_exists($element, 'getText')) {
            $text .= $element->getText() . ' ';
        }
        
        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $text .= $this->getAllTextFromElement($childElement);
            }
        }
        
        return $text;
    }
}
<?php

namespace App\Services;

use App\Models\Logistica\Cliente;
use App\Models\Empleado;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClienteImportService
{
    public function importFromExcel($filePath)
    {
        try {
            Log::info("Iniciando importación de clientes desde: {$filePath}");
            
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remover la fila de encabezados
            array_shift($rows);
            
            $resultados = [
                'procesados' => 0,
                'creados' => 0,
                'actualizados' => 0,
                'ejecutivos_no_encontrados' => [],
                'errores' => []
            ];
            
            foreach ($rows as $index => $row) {
                try {
                    $rowNumber = $index + 2; // +2 porque removimos encabezados y index empieza en 0
                    
                    // Verificar que la fila tenga los datos mínimos necesarios
                    if (empty($row[0]) && empty($row[2])) {
                        continue; // Saltar filas vacías
                    }
                    
                    $nombreEjecutivo = trim($row[0] ?? '');
                    $correoEjecutivo = trim($row[1] ?? '');
                    $nombreCliente = trim($row[2] ?? '');
                    $correoCliente = trim($row[3] ?? '');
                    $periodicidad = trim($row[4] ?? 'Diario');
                    
                    Log::info("Procesando fila {$rowNumber}: Cliente={$nombreCliente}, Ejecutivo={$nombreEjecutivo}");
                    
                    if (empty($nombreCliente)) {
                        continue; // Saltar si no hay nombre de cliente
                    }
                    
                    // Buscar o crear cliente
                    $cliente = Cliente::where('cliente', $nombreCliente)->first();
                    $esNuevo = false;
                    
                    if (!$cliente) {
                        $cliente = new Cliente([
                            'cliente' => $nombreCliente,
                            'periodicidad_reporte' => $periodicidad,
                            'fecha_carga_excel' => now()
                        ]);
                        $esNuevo = true;
                        Log::info("Creando nuevo cliente: {$nombreCliente}");
                    } else {
                        $cliente->periodicidad_reporte = $periodicidad;
                        if (!$cliente->fecha_carga_excel) {
                            $cliente->fecha_carga_excel = now();
                        }
                        Log::info("Actualizando cliente existente: {$nombreCliente}");
                    }
                    
                    // Agregar correo del cliente si existe
                    if (!empty($correoCliente)) {
                        $cliente->addCorreo($correoCliente);
                    }
                    
                    // Buscar ejecutivo si se proporcionó
                    $ejecutivo = null;
                    if (!empty($nombreEjecutivo) || !empty($correoEjecutivo)) {
                        $ejecutivo = $this->findEjecutivo($nombreEjecutivo, $correoEjecutivo);
                        
                        if ($ejecutivo) {
                            $cliente->ejecutivo_asignado_id = $ejecutivo->id;
                            Log::info("Ejecutivo encontrado y asignado: {$ejecutivo->nombre} (ID: {$ejecutivo->id})");
                        } else {
                            $resultados['ejecutivos_no_encontrados'][] = [
                                'fila' => $rowNumber,
                                'nombre' => $nombreEjecutivo,
                                'correo' => $correoEjecutivo,
                                'cliente' => $nombreCliente
                            ];
                            Log::warning("Ejecutivo no encontrado para fila {$rowNumber}: {$nombreEjecutivo} / {$correoEjecutivo}");
                        }
                    }
                    
                    $cliente->save();
                    
                    if ($esNuevo) {
                        $resultados['creados']++;
                    } else {
                        $resultados['actualizados']++;
                    }
                    
                    $resultados['procesados']++;
                    
                } catch (\Exception $e) {
                    $error = "Error en fila {$rowNumber}: " . $e->getMessage();
                    Log::error($error);
                    $resultados['errores'][] = $error;
                }
            }
            
            Log::info("Importación completada", $resultados);
            return $resultados;
            
        } catch (\Exception $e) {
            Log::error("Error en importación de clientes: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Busca un ejecutivo por nombre y/o correo con matching inteligente
     */
    private function findEjecutivo($nombre, $correo)
    {
        // Primero buscar por correo exacto (más confiable)
        if (!empty($correo)) {
            $ejecutivo = Empleado::where('correo', $correo)
                ->where('area', 'Logística')
                ->first();
            
            if ($ejecutivo) {
                Log::info("Ejecutivo encontrado por correo: {$correo} -> {$ejecutivo->nombre}");
                return $ejecutivo;
            }
        }
        
        // Si no se encontró por correo, buscar por nombre
        if (!empty($nombre)) {
            // Normalizar el nombre para la búsqueda
            $nombreNormalizado = $this->normalizeString($nombre);
            
            // Obtener todos los ejecutivos de logística
            $ejecutivos = Empleado::where('area', 'Logística')->get();
            
            foreach ($ejecutivos as $ejecutivo) {
                $nombreEjecutivoNormalizado = $this->normalizeString($ejecutivo->nombre);
                
                // Búsqueda exacta normalizada
                if ($nombreNormalizado === $nombreEjecutivoNormalizado) {
                    Log::info("Ejecutivo encontrado por nombre exacto: {$nombre} -> {$ejecutivo->nombre}");
                    return $ejecutivo;
                }
                
                // Búsqueda parcial (nombre contiene o está contenido)
                if (Str::contains($nombreEjecutivoNormalizado, $nombreNormalizado) || 
                    Str::contains($nombreNormalizado, $nombreEjecutivoNormalizado)) {
                    Log::info("Ejecutivo encontrado por nombre parcial: {$nombre} -> {$ejecutivo->nombre}");
                    return $ejecutivo;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Normaliza un string para comparación (minúsculas, sin acentos, sin espacios extra)
     */
    private function normalizeString($str)
    {
        // Convertir a minúsculas
        $str = strtolower($str);
        
        // Remover acentos
        $unwanted_array = [
            'á'=>'a', 'à'=>'a', 'ä'=>'a', 'â'=>'a', 'ā'=>'a', 'ã'=>'a', 'å'=>'a', 'ą'=>'a',
            'é'=>'e', 'è'=>'e', 'ë'=>'e', 'ê'=>'e', 'ē'=>'e', 'ę'=>'e',
            'í'=>'i', 'ì'=>'i', 'ï'=>'i', 'î'=>'i', 'ī'=>'i', 'į'=>'i',
            'ó'=>'o', 'ò'=>'o', 'ö'=>'o', 'ô'=>'o', 'ō'=>'o', 'õ'=>'o', 'ø'=>'o', 'ő'=>'o', 'ð'=>'o',
            'ú'=>'u', 'ù'=>'u', 'ü'=>'u', 'û'=>'u', 'ū'=>'u', 'ų'=>'u', 'ű'=>'u', 'ů'=>'u',
            'ñ'=>'n', 'ń'=>'n', 'ň'=>'n', 'ņ'=>'n'
        ];
        
        $str = strtr($str, $unwanted_array);
        
        // Limpiar espacios múltiples
        $str = preg_replace('/\s+/', ' ', trim($str));
        
        return $str;
    }
}
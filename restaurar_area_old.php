<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESTAURAR AREA en BD OLD desde BD LOCAL (estrategias_innovacion)      ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// Obtener empleados de la BD LOCAL con area no NULL
$empleadosLocal = DB::table('empleados')
    ->whereNotNull('area')
    ->get();

// Obtener empleados de la BD OLD
$empleadosOld = DB::connection('mysql_old')->table('empleados')->get();

echo "Empleados en BD LOCAL con area: " . $empleadosLocal->count() . "\n";
echo "Empleados en BD OLD: " . $empleadosOld->count() . "\n\n";

$actualizados = 0;
$noEncontrados = [];

foreach ($empleadosLocal as $empLocal) {
    if (empty($empLocal->area)) continue;
    
    // Buscar coincidencia en BD OLD por nombre
    $empOld = buscarEnOld($empLocal->nombre, $empleadosOld);
    
    if ($empOld) {
        // Solo actualizar area
        DB::connection('mysql_old')
            ->table('empleados')
            ->where('id', $empOld->id)
            ->update(['area' => $empLocal->area]);
        
        $actualizados++;
        echo "✓ {$empLocal->nombre}\n";
        echo "  → area: {$empLocal->area}\n";
    } else {
        $noEncontrados[] = $empLocal->nombre;
    }
}

echo "\n╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN                                                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "✓ Areas actualizadas en BD OLD: {$actualizados}\n";
echo "✗ No encontrados: " . count($noEncontrados) . "\n";

if (count($noEncontrados) > 0 && count($noEncontrados) <= 10) {
    echo "\nNo encontrados:\n";
    foreach ($noEncontrados as $nombre) {
        echo "  - {$nombre}\n";
    }
}

echo "\n✅ RESTAURACIÓN DE AREA COMPLETADA\n";

// ============================================================================
// FUNCIONES
// ============================================================================

function buscarEnOld(string $nombreLocal, $empleadosOld) {
    $nombreNorm = normalizar($nombreLocal);
    $palabras = obtenerPalabras($nombreNorm);
    
    // Estrategia 1: Coincidencia exacta
    foreach ($empleadosOld as $emp) {
        if (normalizar($emp->nombre) === $nombreNorm) {
            return $emp;
        }
    }
    
    // Estrategia 2: Todas las palabras coinciden
    foreach ($empleadosOld as $emp) {
        $nombreOldNorm = normalizar($emp->nombre);
        $todasCoinciden = true;
        
        foreach ($palabras as $palabra) {
            if (strlen($palabra) >= 3 && strpos($nombreOldNorm, $palabra) === false) {
                $todasCoinciden = false;
                break;
            }
        }
        
        if ($todasCoinciden && count($palabras) >= 2) {
            return $emp;
        }
    }
    
    // Estrategia 3: Primer nombre + último apellido
    if (count($palabras) >= 2) {
        $primerNombre = $palabras[0];
        $ultimoApellido = end($palabras);
        
        foreach ($empleadosOld as $emp) {
            $nombreOldNorm = normalizar($emp->nombre);
            
            if (strpos($nombreOldNorm, $primerNombre) === 0 && 
                strpos($nombreOldNorm, $ultimoApellido) !== false) {
                return $emp;
            }
        }
    }
    
    // Estrategia 4: Primer nombre + cualquier apellido (>= 4 chars)
    if (count($palabras) >= 2) {
        $primerNombre = $palabras[0];
        
        foreach ($empleadosOld as $emp) {
            $nombreOldNorm = normalizar($emp->nombre);
            $palabrasOld = obtenerPalabras($nombreOldNorm);
            
            if (strpos($nombreOldNorm, $primerNombre) !== 0) {
                continue;
            }
            
            foreach ($palabras as $i => $palabra) {
                if ($i === 0) continue;
                if (strlen($palabra) >= 4) {
                    foreach ($palabrasOld as $j => $palabraOld) {
                        if ($j === 0) continue;
                        if (strlen($palabraOld) >= 4 && $palabra === $palabraOld) {
                            return $emp;
                        }
                    }
                }
            }
        }
    }
    
    return null;
}

function normalizar(string $nombre): string {
    $nombre = mb_strtolower(trim($nombre), 'UTF-8');
    
    $acentos = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ];
    
    $nombre = strtr($nombre, $acentos);
    $nombre = preg_replace('/[^a-z\s]/', '', $nombre);
    $nombre = preg_replace('/\s+/', ' ', $nombre);
    
    return trim($nombre);
}

function obtenerPalabras(string $nombreNorm): array {
    return array_filter(explode(' ', $nombreNorm), fn($p) => strlen($p) >= 2);
}

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Empleado;
use App\Models\User;

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  COMPARACIÓN Y SINCRONIZACIÓN DE EMPLEADOS: SEEDER vs BD              ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// ===============================
// DATOS DEL SEEDER (fuente de verdad para estructura)
// ===============================
$empleadosSeeder = [
    ['id_empleado' => '0', 'nombre' => 'Amos Guillermo Aguilera Gonzalez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Direccion', 'supervisor' => null],
    ['id_empleado' => '36', 'nombre' => 'Liliana Hernandez Castilla', 'area' => 'Recursos Humanos', 'posicion' => 'Administracion RH', 'supervisor' => 'Guillermo Aguilera'],
    ['id_empleado' => '23', 'nombre' => 'Silvestre Reyes Castillo', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Auditoria', 'supervisor' => 'Guillermo Aguilera'],
    ['id_empleado' => '30', 'nombre' => 'Nancy Beatriz Gomez Hernandez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica', 'supervisor' => 'Guillermo Aguilera'],
    ['id_empleado' => '56', 'nombre' => 'Jazzman Jerssain Aguilar Cisneros', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal', 'supervisor' => 'Guillermo Aguilera'],
    ['id_empleado' => '57', 'nombre' => 'Mario Mojica Morales', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Post-Operacion', 'supervisor' => 'Guillermo Aguilera'],
    ['id_empleado' => '74', 'nombre' => 'Aneth Alejandra Herrera Hernandez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Post-Operacion', 'supervisor' => 'Mario Mojica Morales'],
    ['id_empleado' => '22', 'nombre' => 'Zaira Isabel Martinez Urbina', 'area' => 'Chronos Fullfillment', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '60', 'nombre' => 'Luis Eduardo Inclan Soriano', 'area' => 'Siegwerk', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '68', 'nombre' => 'Guadalupe Jacqueline Mendoza Rodriguez', 'area' => 'AGC', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '73', 'nombre' => 'Mariana Rodriguez Rueda', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '78', 'nombre' => 'Oscar Eduardo Morin Carrizales', 'area' => 'PPM Industries', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '53', 'nombre' => 'Alisson Cassiel Pineda Martinez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '86', 'nombre' => 'Ivan Rodriguez Juarez', 'area' => 'Sarrel', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '87', 'nombre' => 'Karen Cristina Bonal Mata', 'area' => 'EB-Tecnica', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '96', 'nombre' => 'Jacob de Jesus Medina Ramirez', 'area' => 'AsiaWay', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '99', 'nombre' => 'Fatima Esther Torres Arriaga', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica', 'supervisor' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '84', 'nombre' => 'Mariana Calderón Ojeda', 'area' => 'Recursos Humanos', 'posicion' => 'Administracion RH', 'supervisor' => 'Liliana Hernandez Castilla'],
    ['id_empleado' => '95', 'nombre' => 'Jonathan Loredo Palacios', 'area' => 'Estrategia e Innovacion', 'posicion' => 'TI', 'supervisor' => 'Liliana Hernandez Castilla'],
    ['id_empleado' => '103', 'nombre' => 'Isaac Covarrubias Quintero', 'area' => 'Estrategia e Innovacion', 'posicion' => 'TI', 'supervisor' => 'Liliana Hernandez Castilla'],
    ['id_empleado' => '90', 'nombre' => 'Jessica Anahi Esparza Gonzalez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Anexo 24', 'supervisor' => 'Mario Mojica Morales'],
    ['id_empleado' => '98', 'nombre' => 'Felipe de Jesus Rodriguez Ledesma', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Anexo 24', 'supervisor' => 'Mario Mojica Morales'],
    ['id_empleado' => '100', 'nombre' => 'Mayra Susana Coreño Arriaga', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Post-Operacion', 'supervisor' => 'Mario Mojica Morales'],
    ['id_empleado' => '101', 'nombre' => 'Erika Liliana Mireles Sanchez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Anexo 24', 'supervisor' => 'Mario Mojica Morales'],
    ['id_empleado' => '80', 'nombre' => 'Ana Sofia Cuello Aguilar', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal', 'supervisor' => 'Jazzman Jerssain Aguilar Cisneros'],
    ['id_empleado' => '97', 'nombre' => 'Jesus David Rivera Romero', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal', 'supervisor' => 'Jazzman Jerssain Aguilar Cisneros'],
    ['id_empleado' => '102', 'nombre' => 'Carlos Alfonso Rivera Moran', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal', 'supervisor' => 'Jazzman Jerssain Aguilar Cisneros'],
];

// Función para normalizar nombre (quitar acentos, minúsculas, espacios extra)
function normalizarNombre($nombre) {
    $nombre = mb_strtolower(trim($nombre));
    $nombre = preg_replace('/\s+/', ' ', $nombre);
    // Quitar acentos
    $acentos = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u'];
    $nombre = strtr($nombre, $acentos);
    return $nombre;
}

// Función para extraer palabras clave del nombre (primer nombre y primer apellido)
function getPalabrasClave($nombre) {
    $partes = explode(' ', normalizarNombre($nombre));
    // Filtrar palabras cortas como "de", "la", etc.
    $partes = array_filter($partes, fn($p) => strlen($p) > 2);
    return array_values($partes);
}

// Función para verificar coincidencia parcial de nombres
function nombresCoinciden($nombreBD, $nombreSeeder) {
    $palabrasBD = getPalabrasClave($nombreBD);
    $palabrasSeeder = getPalabrasClave($nombreSeeder);
    
    // Contar cuántas palabras coinciden
    $coincidencias = 0;
    foreach ($palabrasBD as $palabra) {
        foreach ($palabrasSeeder as $palabraS) {
            if ($palabra === $palabraS || 
                strpos($palabraS, $palabra) !== false || 
                strpos($palabra, $palabraS) !== false) {
                $coincidencias++;
                break;
            }
        }
    }
    
    // Si coinciden al menos 2 palabras (nombre y apellido), es match
    return $coincidencias >= 2;
}

// ===============================
// OBTENER EMPLEADOS DE LA BD
// ===============================
$empleadosBD = Empleado::all();
$usersBD = User::all();

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  EMPLEADOS EN BD: " . $empleadosBD->count() . "\n";
echo "  EMPLEADOS EN SEEDER: " . count($empleadosSeeder) . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ===============================
// ANÁLISIS
// ===============================
$encontrados = [];
$noEnSeeder = [];
$noEnBD = [];

// 1. Buscar cada empleado de BD en el seeder
echo "╔═══════════════════════════════════════════════════════════════════════╗\n";
echo "║  EMPLEADOS EN BD vs SEEDER                                            ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════╝\n\n";

foreach ($empleadosBD as $empBD) {
    $encontrado = false;
    $matchSeeder = null;
    
    foreach ($empleadosSeeder as $empSeeder) {
        // Primero intentar por id_empleado
        if ($empBD->id_empleado && $empBD->id_empleado == $empSeeder['id_empleado']) {
            $encontrado = true;
            $matchSeeder = $empSeeder;
            break;
        }
        
        // Luego por coincidencia de nombre
        if (nombresCoinciden($empBD->nombre, $empSeeder['nombre'])) {
            $encontrado = true;
            $matchSeeder = $empSeeder;
            break;
        }
    }
    
    if ($encontrado) {
        $encontrados[] = [
            'bd' => $empBD,
            'seeder' => $matchSeeder
        ];
        echo "✓ ENCONTRADO: {$empBD->nombre}\n";
        echo "  → Match con: {$matchSeeder['nombre']}\n";
        echo "  → Correo BD: {$empBD->correo} (SE MANTIENE)\n";
        echo "  → Area BD: {$empBD->area} → Seeder: {$matchSeeder['area']}\n";
        echo "  → Posicion BD: {$empBD->posicion} → Seeder: {$matchSeeder['posicion']}\n\n";
    } else {
        $noEnSeeder[] = $empBD;
        echo "⚠ NO EN SEEDER (posible baja): {$empBD->nombre}\n";
        echo "  → Correo: {$empBD->correo}\n";
        echo "  → Area: {$empBD->area}\n";
        echo "  → Posicion: {$empBD->posicion}\n\n";
    }
}

// 2. Verificar empleados del seeder que no están en BD
echo "\n╔═══════════════════════════════════════════════════════════════════════╗\n";
echo "║  EMPLEADOS EN SEEDER QUE NO ESTÁN EN BD (NUEVOS)                      ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════╝\n\n";

foreach ($empleadosSeeder as $empSeeder) {
    $encontradoEnBD = false;
    
    foreach ($empleadosBD as $empBD) {
        if ($empBD->id_empleado && $empBD->id_empleado == $empSeeder['id_empleado']) {
            $encontradoEnBD = true;
            break;
        }
        if (nombresCoinciden($empBD->nombre, $empSeeder['nombre'])) {
            $encontradoEnBD = true;
            break;
        }
    }
    
    if (!$encontradoEnBD) {
        $noEnBD[] = $empSeeder;
        echo "➕ NUEVO (no en BD): {$empSeeder['nombre']}\n";
        echo "  → ID Empleado: {$empSeeder['id_empleado']}\n";
        echo "  → Area: {$empSeeder['area']}\n";
        echo "  → Posicion: {$empSeeder['posicion']}\n\n";
    }
}

if (empty($noEnBD)) {
    echo "  Todos los empleados del seeder ya están en la BD.\n\n";
}

// ===============================
// RESUMEN
// ===============================
echo "\n╔═══════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN                                                              ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════╝\n\n";

echo "  ✓ Empleados coincidentes (BD ↔ Seeder): " . count($encontrados) . "\n";
echo "  ⚠ Empleados en BD pero NO en seeder (posibles bajas): " . count($noEnSeeder) . "\n";
echo "  ➕ Empleados en seeder pero NO en BD (nuevos): " . count($noEnBD) . "\n\n";

// ===============================
// PREGUNTAR SI ACTUALIZAR
// ===============================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "¿Deseas actualizar los empleados coincidentes con los datos del seeder?\n";
echo "(Se actualizará: nombre, id_empleado, area, posicion - NO el correo)\n";
echo "Escribe 'SI' para confirmar: ";

$handle = fopen("php://stdin", "r");
$respuesta = trim(fgets($handle));

if (strtoupper($respuesta) === 'SI') {
    echo "\n\n━━━ ACTUALIZANDO EMPLEADOS ━━━\n\n";
    
    foreach ($encontrados as $match) {
        $empBD = $match['bd'];
        $empSeeder = $match['seeder'];
        
        // Actualizar empleado (mantener correo)
        $empBD->update([
            'id_empleado' => $empSeeder['id_empleado'],
            'nombre' => $empSeeder['nombre'],
            'area' => $empSeeder['area'],
            'posicion' => $empSeeder['posicion'],
        ]);
        
        // Actualizar nombre del usuario asociado
        if ($empBD->user_id) {
            $user = User::find($empBD->user_id);
            if ($user) {
                $user->update(['name' => $empSeeder['nombre']]);
            }
        }
        
        echo "✓ Actualizado: {$empSeeder['nombre']}\n";
    }
    
    // Asignar supervisores
    echo "\n━━━ ASIGNANDO SUPERVISORES ━━━\n\n";
    
    // Crear mapa de nombres a IDs
    $mapaEmpleados = [];
    foreach (Empleado::all() as $emp) {
        $mapaEmpleados[normalizarNombre($emp->nombre)] = $emp->id;
    }
    
    foreach ($empleadosSeeder as $empSeeder) {
        if ($empSeeder['supervisor']) {
            $empleado = Empleado::where('id_empleado', $empSeeder['id_empleado'])->first();
            if ($empleado) {
                // Buscar supervisor por nombre
                $supervisorNorm = normalizarNombre($empSeeder['supervisor']);
                $supervisorId = null;
                
                foreach ($mapaEmpleados as $nombre => $id) {
                    if (strpos($nombre, $supervisorNorm) !== false || 
                        strpos($supervisorNorm, $nombre) !== false ||
                        nombresCoinciden($nombre, $empSeeder['supervisor'])) {
                        $supervisorId = $id;
                        break;
                    }
                }
                
                if ($supervisorId) {
                    $empleado->update(['supervisor_id' => $supervisorId]);
                    echo "✓ {$empleado->nombre} → Supervisor asignado\n";
                }
            }
        }
    }
    
    echo "\n✅ ACTUALIZACIÓN COMPLETADA\n";
} else {
    echo "\nActualización cancelada.\n";
}

fclose($handle);

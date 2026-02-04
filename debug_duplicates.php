<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS DE DUPLICADOS ===\n\n";

// 1. Buscar usuarios duplicados por similitud de nombre
echo "1. USUARIOS DUPLICADOS:\n";
echo str_repeat("-", 80) . "\n";

$users = User::orderBy('name')->get();
$usersAgrupados = [];

foreach ($users as $user) {
    $nombreNormalizado = strtolower(trim($user->name));
    $palabras = explode(' ', $nombreNormalizado);
    
    // Agrupar por similitud
    $encontrado = false;
    foreach ($usersAgrupados as $key => &$grupo) {
        $keyNormalizado = strtolower($key);
        $palabrasKey = explode(' ', $keyNormalizado);
        
        // Verificar si comparten al menos 2 palabras significativas
        $coincidencias = array_intersect($palabras, $palabrasKey);
        if (count($coincidencias) >= 2) {
            $grupo[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
            $encontrado = true;
            break;
        }
    }
    
    if (!$encontrado) {
        $usersAgrupados[$user->name] = [[
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]];
    }
}

foreach ($usersAgrupados as $nombre => $grupo) {
    if (count($grupo) > 1) {
        echo "\nGrupo: $nombre\n";
        foreach ($grupo as $u) {
            echo "  ID: {$u['id']} | Name: {$u['name']} | Email: {$u['email']}\n";
        }
    }
}

// 2. Buscar empleados duplicados por similitud de nombre
echo "\n\n2. EMPLEADOS DUPLICADOS:\n";
echo str_repeat("-", 80) . "\n";

$empleados = Empleado::orderBy('nombre')->get();
$empleadosAgrupados = [];

foreach ($empleados as $emp) {
    $nombreNormalizado = strtolower(trim($emp->nombre));
    $palabras = explode(' ', $nombreNormalizado);
    
    $encontrado = false;
    foreach ($empleadosAgrupados as $key => &$grupo) {
        $keyNormalizado = strtolower($key);
        $palabrasKey = explode(' ', $keyNormalizado);
        
        $coincidencias = array_intersect($palabras, $palabrasKey);
        if (count($coincidencias) >= 2) {
            $grupo[] = [
                'id' => $emp->id,
                'id_empleado' => $emp->id_empleado,
                'nombre' => $emp->nombre,
                'correo' => $emp->correo,
                'user_id' => $emp->user_id,
            ];
            $encontrado = true;
            break;
        }
    }
    
    if (!$encontrado) {
        $empleadosAgrupados[$emp->nombre] = [[
            'id' => $emp->id,
            'id_empleado' => $emp->id_empleado,
            'nombre' => $emp->nombre,
            'correo' => $emp->correo,
            'user_id' => $emp->user_id,
        ]];
    }
}

foreach ($empleadosAgrupados as $nombre => $grupo) {
    if (count($grupo) > 1) {
        echo "\nGrupo: $nombre\n";
        foreach ($grupo as $e) {
            echo "  ID: {$e['id']} | ID_Empleado: {$e['id_empleado']} | Nombre: {$e['nombre']} | Correo: {$e['correo']} | User_ID: {$e['user_id']}\n";
        }
    }
}

// 3. Resumen
echo "\n\n3. RESUMEN:\n";
echo str_repeat("-", 80) . "\n";
echo "Total Usuarios: " . User::count() . "\n";
echo "Total Empleados: " . Empleado::count() . "\n";

// Contar grupos duplicados
$gruposDuplicadosUsers = 0;
$totalDuplicadosUsers = 0;
foreach ($usersAgrupados as $grupo) {
    if (count($grupo) > 1) {
        $gruposDuplicadosUsers++;
        $totalDuplicadosUsers += count($grupo);
    }
}

$gruposDuplicadosEmpleados = 0;
$totalDuplicadosEmpleados = 0;
foreach ($empleadosAgrupados as $grupo) {
    if (count($grupo) > 1) {
        $gruposDuplicadosEmpleados++;
        $totalDuplicadosEmpleados += count($grupo);
    }
}

echo "Grupos de usuarios duplicados: $gruposDuplicadosUsers (Total registros: $totalDuplicadosUsers)\n";
echo "Grupos de empleados duplicados: $gruposDuplicadosEmpleados (Total registros: $totalDuplicadosEmpleados)\n";

echo "\n=== FIN DEL ANÁLISIS ===\n";

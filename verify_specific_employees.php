<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DE EMPLEADOS ESPECÍFICOS ===\n\n";

// Los 3 empleados que el usuario dice que existen
$buscar = [
    ['id_empleado' => '95', 'nombre' => 'Jonathan Loredo Palacios', 'palabras_clave' => ['jonathan', 'loredo']],
    ['id_empleado' => '70', 'nombre' => 'Karen Michelle Echevarria Garcia', 'palabras_clave' => ['michelle', 'garcia', 'echevarria']],
    ['id_empleado' => '80', 'nombre' => 'Ana Sofia Cuello Aguilar', 'palabras_clave' => ['ana', 'sofia', 'cuello']],
];

echo "BÚSQUEDA EN EMPLEADOS:\n";
echo str_repeat("-", 120) . "\n";

foreach ($buscar as $persona) {
    echo "\nBuscando: {$persona['nombre']} (ID: {$persona['id_empleado']})\n";
    echo "Palabras clave: " . implode(', ', $persona['palabras_clave']) . "\n\n";
    
    // Buscar por palabras clave
    $empleados = Empleado::where(function($query) use ($persona) {
        foreach ($persona['palabras_clave'] as $palabra) {
            $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower($palabra) . '%']);
        }
    })->get();
    
    if ($empleados->count() > 0) {
        echo "✓ ENCONTRADO(S) EN EMPLEADOS (" . $empleados->count() . "):\n";
        foreach ($empleados as $emp) {
            echo "  - ID: {$emp->id} | ID_Empleado: {$emp->id_empleado} | Nombre: {$emp->nombre}\n";
            echo "    Correo: {$emp->correo}\n";
            echo "    User_ID: {$emp->user_id}\n";
        }
    } else {
        echo "✗ NO ENCONTRADO EN EMPLEADOS\n";
    }
    
    // Buscar en users
    $users = User::where(function($query) use ($persona) {
        foreach ($persona['palabras_clave'] as $palabra) {
            $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($palabra) . '%']);
        }
    })->get();
    
    if ($users->count() > 0) {
        echo "\n✓ ENCONTRADO(S) EN USERS (" . $users->count() . "):\n";
        foreach ($users as $user) {
            echo "  - ID: {$user->id} | Name: {$user->name}\n";
            echo "    Email: {$user->email}\n";
        }
    } else {
        echo "\n✗ NO ENCONTRADO EN USERS\n";
    }
    
    echo "\n" . str_repeat("-", 120) . "\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";

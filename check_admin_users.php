<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;

echo "=== VERIFICACIÓN DE USUARIOS IT ===\n\n";

// Buscar usuario de sistemas
$userSistemas = User::where('email', 'sistemas@estrategiaeinnovacion.com.mx')->first();

if ($userSistemas) {
    echo "USUARIO ENCONTRADO:\n";
    echo str_repeat("-", 80) . "\n";
    echo "ID: {$userSistemas->id}\n";
    echo "Name: {$userSistemas->name}\n";
    echo "Email: {$userSistemas->email}\n";
    echo "Role: " . ($userSistemas->role ?? 'NULL') . "\n";
    echo "Is Admin: " . ($userSistemas->isAdmin() ? 'SÍ' : 'NO') . "\n";
    
    if ($userSistemas->empleado) {
        echo "\nEMPLEADO ASOCIADO:\n";
        echo "ID Empleado: {$userSistemas->empleado->id_empleado}\n";
        echo "Nombre: {$userSistemas->empleado->nombre}\n";
        echo "Área: {$userSistemas->empleado->area}\n";
        echo "Posición: {$userSistemas->empleado->posicion}\n";
    } else {
        echo "\n⚠ NO TIENE EMPLEADO ASOCIADO\n";
    }
} else {
    echo "✗ Usuario NO encontrado\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "OTROS USUARIOS TI:\n";
echo str_repeat("-", 80) . "\n";

$usersTI = User::whereHas('empleado', function($query) {
    $query->where('posicion', 'LIKE', '%TI%')
          ->orWhere('posicion', 'LIKE', '%IT%')
          ->orWhere('area', 'LIKE', '%Sistemas%');
})->get();

foreach ($usersTI as $user) {
    echo "\nID: {$user->id} | Name: {$user->name}\n";
    echo "  Email: {$user->email}\n";
    echo "  Role: " . ($user->role ?? 'NULL') . "\n";
    echo "  Is Admin: " . ($user->isAdmin() ? 'SÍ' : 'NO') . "\n";
    if ($user->empleado) {
        echo "  Área: {$user->empleado->area} | Posición: {$user->empleado->posicion}\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "USUARIOS CON ROLE ADMIN:\n";
echo str_repeat("-", 80) . "\n";

$admins = User::where('role', 'admin')->get();
echo "Total: " . $admins->count() . "\n\n";

foreach ($admins as $admin) {
    echo "ID: {$admin->id} | Name: {$admin->name} | Email: {$admin->email}\n";
    if ($admin->empleado) {
        echo "  Empleado: {$admin->empleado->nombre} | Área: {$admin->empleado->area}\n";
    }
    echo "\n";
}

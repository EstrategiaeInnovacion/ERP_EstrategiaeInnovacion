<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;

echo "=== ESTADO FINAL DE LA BASE DE DATOS ===\n\n";

echo "Total Empleados: " . Empleado::count() . "\n";
echo "Total Usuarios: " . User::count() . "\n\n";

echo "MUESTRA DE EMPLEADOS Y SUS USUARIOS:\n";
echo str_repeat("-", 120) . "\n";

Empleado::with('user')->orderBy('id_empleado')->take(15)->get()->each(function($e) {
    $userEmail = $e->user ? $e->user->email : 'N/A';
    $userName = $e->user ? $e->user->name : 'N/A';
    printf("ID: %-3s | Nombre: %-40s | Correo: %-45s\n", 
        $e->id_empleado, 
        substr($e->nombre, 0, 40), 
        substr($e->correo, 0, 45)
    );
    printf("          User: %-40s | Email: %s\n", 
        substr($userName, 0, 40), 
        $userEmail
    );
    echo str_repeat("-", 120) . "\n";
});

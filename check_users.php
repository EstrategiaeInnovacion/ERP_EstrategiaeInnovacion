<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;

echo "=== USUARIOS DEL SISTEMA ===\n";
$users = User::all(['id', 'name', 'email']);
foreach ($users as $u) {
    $empleado = Empleado::where('correo', $u->email)
        ->orWhere('nombre', 'like', '%' . $u->name . '%')->first();
    
    echo "User ID: {$u->id} | {$u->name} | {$u->email} => Empleado: " . ($empleado ? "ID {$empleado->id} ({$empleado->nombre})" : "NO ENCONTRADO") . "\n";
}

echo "\n=== EMPLEADOS SIN USUARIO ===\n";
$empleados = Empleado::whereNotNull('correo')->get(['id', 'nombre', 'correo']);
foreach ($empleados as $e) {
    $user = User::where('email', $e->correo)->first();
    if (!$user) {
        echo "Empleado ID: {$e->id} | {$e->nombre} | {$e->correo} => SIN USUARIO\n";
    }
}

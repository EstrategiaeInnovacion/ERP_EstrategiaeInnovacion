<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;
use App\Models\EmpleadoBaja;

echo "=== ESTADO FINAL DEL SISTEMA ===\n\n";

echo "📊 ESTADÍSTICAS:\n";
echo str_repeat("-", 80) . "\n";
echo "Empleados activos: " . Empleado::count() . "\n";
echo "Usuarios activos: " . User::count() . "\n";
echo "Empleados de baja: " . EmpleadoBaja::count() . "\n\n";

echo "👥 EMPLEADOS ACTIVOS:\n";
echo str_repeat("-", 80) . "\n";
Empleado::orderBy('id_empleado')->get()->each(function($e) {
    printf("ID: %-3s | %-40s | %s\n", 
        $e->id_empleado, 
        substr($e->nombre, 0, 40), 
        $e->area
    );
});

echo "\n❌ EMPLEADOS DE BAJA:\n";
echo str_repeat("-", 80) . "\n";
EmpleadoBaja::orderBy('fecha_baja', 'desc')->get()->each(function($b) {
    printf("%-40s | Fecha: %s | Motivo: %s\n", 
        substr($b->nombre, 0, 40), 
        $b->fecha_baja->format('Y-m-d'),
        $b->motivo_baja
    );
});

echo "\n=== FIN ===\n";

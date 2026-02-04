<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Empleado;
use App\Models\EmpleadoBaja;
use App\Models\User;

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  CORRECCIÓN FINAL DE BAJAS                                             ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// ===============================
// 1. ELIMINAR MARIA FERNANDA DE EMPLEADOS ACTIVOS (ya está en bajas)
// ===============================
echo "━━━ Eliminando duplicado de Maria Fernanda de empleados activos ━━━\n";
$mariaFernanda = Empleado::where('nombre', 'like', '%MARIA FERNANDA%')->first();
if ($mariaFernanda) {
    $mariaFernanda->delete();
    echo "  ✓ Maria Fernanda eliminada de empleados activos (ya está en bajas)\n";
} else {
    echo "  → No se encontró en empleados activos\n";
}

echo "\n";

// ===============================
// 2. RESTAURAR A MARIANA CALDERÓN (buscar en bajas por correo administracion)
// ===============================
echo "━━━ Restaurando a Mariana Calderón Ojeda ━━━\n";

// Buscar usuario con correo administracion
$userMariana = User::where('email', 'administracion@estrategiaeinnovacion.com.mx')->first();

if ($userMariana) {
    echo "  ✓ Usuario encontrado: {$userMariana->name} (ID: {$userMariana->id})\n";
    
    // Verificar si ya existe como empleado
    $empleadoExistente = Empleado::where('user_id', $userMariana->id)
        ->orWhere('correo', 'administracion@estrategiaeinnovacion.com.mx')
        ->first();
    
    if (!$empleadoExistente) {
        // Crear empleado
        $empleado = Empleado::create([
            'id_empleado' => '84',
            'nombre' => 'Mariana Calderón Ojeda',
            'correo' => 'administracion@estrategiaeinnovacion.com.mx',
            'area' => 'Recursos Humanos',
            'posicion' => 'Administracion RH',
            'user_id' => $userMariana->id,
        ]);
        echo "  ✓ Empleado creado (ID: {$empleado->id})\n";
    } else {
        echo "  → Ya existe como empleado (ID: {$empleadoExistente->id})\n";
    }
    
    // Activar usuario
    $userMariana->update(['status' => 'approved']);
    echo "  ✓ Usuario activado\n";
    
    // Eliminar de bajas si está
    EmpleadoBaja::where('correo', 'administracion@estrategiaeinnovacion.com.mx')
        ->orWhere('nombre', 'like', '%Mariana%Calder%')
        ->delete();
    echo "  ✓ Eliminada de bajas (si estaba)\n";
} else {
    echo "  ⚠ No se encontró usuario con correo administracion@...\n";
    // Crear desde cero
    $user = User::create([
        'name' => 'Mariana Calderón Ojeda',
        'email' => 'administracion@estrategiaeinnovacion.com.mx',
        'password' => bcrypt('password'),
        'status' => 'approved',
        'role' => 'admin',
    ]);
    echo "  ✓ Usuario creado (ID: {$user->id})\n";
    
    $empleado = Empleado::create([
        'id_empleado' => '84',
        'nombre' => 'Mariana Calderón Ojeda',
        'correo' => 'administracion@estrategiaeinnovacion.com.mx',
        'area' => 'Recursos Humanos',
        'posicion' => 'Administracion RH',
        'user_id' => $user->id,
    ]);
    echo "  ✓ Empleado creado (ID: {$empleado->id})\n";
}

echo "\n";

// ===============================
// RESUMEN FINAL
// ===============================
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  ESTADO ACTUAL                                                         ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

echo "━━━ EMPLEADOS ACTIVOS (" . Empleado::count() . ") ━━━\n";
$empleados = Empleado::orderBy('nombre')->get();
foreach ($empleados as $emp) {
    echo sprintf("  • %-40s %s\n", $emp->nombre, $emp->correo);
}

echo "\n━━━ EMPLEADOS EN BAJA (" . EmpleadoBaja::count() . ") ━━━\n";
$bajas = EmpleadoBaja::orderBy('nombre')->get();
foreach ($bajas as $baja) {
    echo sprintf("  • %-40s %s\n", $baja->nombre, $baja->correo);
}

echo "\n✅ CORRECCIÓN COMPLETADA\n";

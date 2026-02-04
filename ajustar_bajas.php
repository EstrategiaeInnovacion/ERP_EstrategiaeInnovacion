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
echo "║  AJUSTE DE BAJAS: Restaurar Mariana, Dar de baja Jesus David          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// ===============================
// 1. RESTAURAR A MARIANA CALDERÓN
// ===============================
echo "━━━ RESTAURANDO: Mariana Calderón Ojeda ━━━\n";

$bajaMaria = EmpleadoBaja::where('correo', 'like', '%administracion%')
    ->orWhere('nombre', 'like', '%Mariana%Calder%')
    ->first();

if ($bajaMaria) {
    // Reactivar usuario si existe
    if ($bajaMaria->user_id) {
        $user = User::find($bajaMaria->user_id);
        if ($user) {
            $user->update(['status' => 'approved']);
            echo "  ✓ Usuario reactivado (ID: {$user->id})\n";
        }
    }
    
    // Recrear empleado
    $empleado = Empleado::create([
        'id_empleado' => '84',
        'nombre' => 'Mariana Calderón Ojeda',
        'correo' => $bajaMaria->correo,
        'area' => 'Recursos Humanos',
        'posicion' => 'Administracion RH',
        'user_id' => $bajaMaria->user_id,
    ]);
    echo "  ✓ Empleado recreado (ID: {$empleado->id})\n";
    
    // Eliminar de bajas
    $bajaMaria->delete();
    echo "  ✓ Eliminado de tabla empleados_baja\n";
} else {
    echo "  ⚠ No se encontró en bajas, verificando si ya está activa...\n";
    $empleadoActivo = Empleado::where('nombre', 'like', '%Mariana%Calder%')->first();
    if ($empleadoActivo) {
        echo "  ✓ Ya está activa como empleado (ID: {$empleadoActivo->id})\n";
    }
}

echo "\n";

// ===============================
// 2. DAR DE BAJA A JESUS DAVID RIVERA
// ===============================
echo "━━━ DANDO DE BAJA: Jesus David Rivera Romero ━━━\n";

$empleadoJesus = Empleado::where('nombre', 'like', '%Jesus%David%Rivera%')
    ->orWhere('correo', 'like', '%Legal@%')
    ->first();

if ($empleadoJesus) {
    // Verificar si ya está en bajas
    $yaEnBaja = EmpleadoBaja::where('correo', $empleadoJesus->correo)->exists();
    
    if ($yaEnBaja) {
        echo "  → Ya está registrado en bajas\n";
    } else {
        // Crear registro de baja
        $baja = EmpleadoBaja::create([
            'empleado_id' => $empleadoJesus->id,
            'user_id' => $empleadoJesus->user_id,
            'nombre' => 'Jesus David Rivera Romero',
            'correo' => $empleadoJesus->correo,
            'motivo_baja' => 'Baja de personal',
            'fecha_baja' => now(),
            'observaciones' => 'Confirmado por usuario',
        ]);
        echo "  ✓ Registrado en empleados_baja (ID: {$baja->id})\n";
        
        // Desactivar usuario
        if ($empleadoJesus->user_id) {
            $user = User::find($empleadoJesus->user_id);
            if ($user) {
                $user->update(['status' => 'rejected']);
                echo "  ✓ Usuario desactivado\n";
            }
        }
        
        // Eliminar empleado
        $empleadoJesus->delete();
        echo "  ✓ Eliminado de tabla empleados\n";
    }
} else {
    echo "  ⚠ No se encontró el empleado Jesus David Rivera\n";
}

echo "\n";

// ===============================
// RESUMEN FINAL
// ===============================
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  ESTADO ACTUAL                                                         ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

echo "━━━ EMPLEADOS ACTIVOS ━━━\n";
$empleados = Empleado::orderBy('nombre')->get();
foreach ($empleados as $emp) {
    echo sprintf("  • %-40s %s\n", $emp->nombre, $emp->correo);
}
echo "\nTotal empleados activos: " . $empleados->count() . "\n\n";

echo "━━━ EMPLEADOS EN BAJA ━━━\n";
$bajas = EmpleadoBaja::orderBy('nombre')->get();
foreach ($bajas as $baja) {
    echo sprintf("  • %-40s %s\n", $baja->nombre, $baja->correo);
}
echo "\nTotal bajas: " . $bajas->count() . "\n";

echo "\n✅ PROCESO COMPLETADO\n";

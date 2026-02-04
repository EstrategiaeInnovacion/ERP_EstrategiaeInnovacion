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
echo "║  RESTAURAR EMPLEADOS DE BAJAS                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// Mostrar lista actual de bajas
echo "━━━ LISTA ACTUAL DE EMPLEADOS EN BAJA ━━━\n\n";
$bajas = EmpleadoBaja::all();
foreach ($bajas as $baja) {
    echo sprintf("  ID: %d | %-40s | %s\n", $baja->id, $baja->nombre, $baja->correo);
}

echo "\n";

// Empleados a restaurar (NO son bajas realmente)
$empleadosRestaurar = [
    'administracion@estrategiaeinnovacion.com.mx', // Mariana Calderón Ojeda
    'Legal@estrategiaeinnovacion.com.mx', // Jesus David Rivera Romero
];

foreach ($empleadosRestaurar as $correo) {
    echo "━━━ Buscando: {$correo} ━━━\n";
    
    // Buscar en bajas
    $baja = EmpleadoBaja::where('correo', $correo)->first();
    
    if (!$baja) {
        // Buscar de forma más flexible (case insensitive)
        $baja = EmpleadoBaja::whereRaw('LOWER(correo) = ?', [strtolower($correo)])->first();
    }
    
    if ($baja) {
        echo "  ✓ Encontrado en bajas: {$baja->nombre}\n";
        
        // Verificar si ya existe en empleados
        $empleadoExiste = Empleado::where('correo', $correo)->exists();
        
        if (!$empleadoExiste) {
            // Restaurar empleado
            $empleado = Empleado::create([
                'nombre' => $baja->nombre,
                'correo' => $baja->correo,
                'user_id' => $baja->user_id,
            ]);
            echo "  ✓ Empleado restaurado (ID: {$empleado->id})\n";
        } else {
            echo "  → El empleado ya existe en la tabla empleados\n";
        }
        
        // Reactivar usuario
        if ($baja->user_id) {
            $user = User::find($baja->user_id);
            if ($user) {
                $user->update(['status' => 'approved']);
                echo "  ✓ Usuario reactivado (status: approved)\n";
            }
        }
        
        // Eliminar de bajas
        $baja->delete();
        echo "  ✓ Eliminado de tabla empleados_baja\n";
        
    } else {
        echo "  ⚠ No encontrado en bajas con correo: {$correo}\n";
        
        // Verificar si está en empleados
        $empleado = Empleado::where('correo', $correo)->first();
        if ($empleado) {
            echo "  → Ya está activo en empleados: {$empleado->nombre}\n";
        }
    }
    echo "\n";
}

// Mostrar lista actualizada de bajas
echo "━━━ LISTA ACTUALIZADA DE EMPLEADOS EN BAJA ━━━\n\n";
$bajasActualizadas = EmpleadoBaja::all();
if ($bajasActualizadas->count() > 0) {
    foreach ($bajasActualizadas as $baja) {
        echo sprintf("  • %-40s %s\n", $baja->nombre, $baja->correo);
    }
} else {
    echo "  (No hay empleados en baja)\n";
}

// Mostrar empleados activos
echo "\n━━━ EMPLEADOS ACTIVOS ━━━\n\n";
$empleadosActivos = Empleado::orderBy('nombre')->get();
foreach ($empleadosActivos as $emp) {
    echo sprintf("  • %-40s %s\n", $emp->nombre, $emp->correo);
}

echo "\n✅ PROCESO COMPLETADO\n";

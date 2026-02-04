<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;
use App\Models\EmpleadoBaja;
use Illuminate\Support\Facades\DB;

echo "=== REGISTRO DE EMPLEADOS DE BAJA ===\n\n";

// Empleados a dar de baja
$empleadosBaja = [
    [
        'correo' => 'tradecompliance4@estrategiaeinnovacion.com.mx',
        'nombre' => 'MARIA FERNANDA SANCHEZ MIRANDA',
        'motivo' => 'No está en seeder actual',
    ],
    [
        'correo' => 'aduanas@plastinver.com.mx',
        'nombre' => 'GABRIELA ROMERO',
        'motivo' => 'No está en seeder actual',
    ],
    [
        'correo' => 'logistocs@dekosys.mx',
        'nombre' => 'Karen Michelle Echevarria Garcia',
        'motivo' => 'Baja solicitada',
    ],
];

DB::beginTransaction();

try {
    foreach ($empleadosBaja as $baja) {
        echo "Procesando: {$baja['nombre']}\n";
        
        // Buscar empleado
        $empleado = Empleado::where('correo', $baja['correo'])->first();
        $user = User::where('email', $baja['correo'])->first();
        
        if (!$empleado && !$user) {
            echo "  ⚠ No encontrado en la BD\n\n";
            continue;
        }
        
        // Verificar si ya está en la tabla de bajas
        $yaEnBaja = EmpleadoBaja::where('correo', $baja['correo'])->first();
        
        if ($yaEnBaja) {
            echo "  ⚠ Ya está registrado como baja\n\n";
            continue;
        }
        
        // Registrar en tabla de bajas
        $empleadoBaja = EmpleadoBaja::create([
            'empleado_id' => $empleado ? $empleado->id : null,
            'user_id' => $user ? $user->id : null,
            'nombre' => $baja['nombre'],
            'correo' => $baja['correo'],
            'motivo_baja' => $baja['motivo'],
            'fecha_baja' => now(),
            'observaciones' => 'Baja registrada automáticamente',
        ]);
        
        echo "  ✓ Registrado en empleados_baja (ID: {$empleadoBaja->id})\n";
        
        // Eliminar de empleados
        if ($empleado) {
            // Primero eliminar referencias de supervisor
            Empleado::where('supervisor_id', $empleado->id)->update(['supervisor_id' => null]);
            
            $empleado->delete();
            echo "  ✓ Eliminado de tabla empleados\n";
        }
        
        // Eliminar de users
        if ($user) {
            $user->delete();
            echo "  ✓ Eliminado de tabla users\n";
        }
        
        echo "\n";
    }
    
    DB::commit();
    
    echo str_repeat("=", 80) . "\n";
    echo "✅ BAJAS PROCESADAS EXITOSAMENTE\n";
    echo "Total de bajas registradas: " . count($empleadosBaja) . "\n\n";
    
    // Mostrar resumen de bajas
    echo "RESUMEN DE BAJAS:\n";
    echo str_repeat("-", 80) . "\n";
    $bajas = EmpleadoBaja::orderBy('fecha_baja', 'desc')->get();
    foreach ($bajas as $b) {
        echo sprintf("ID: %-3s | %-40s | %s\n", 
            $b->id, 
            substr($b->nombre, 0, 40), 
            $b->correo
        );
        echo "  Fecha: {$b->fecha_baja->format('Y-m-d')} | Motivo: {$b->motivo_baja}\n";
        echo str_repeat("-", 80) . "\n";
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

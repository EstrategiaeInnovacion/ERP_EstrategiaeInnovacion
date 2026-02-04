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
echo "║  REGISTRO DE BAJAS DE EMPLEADOS                                        ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// Lista de empleados a dar de baja
$empleadosBaja = [
    [
        'nombre' => 'MARIA FERNANDA SANCHEZ MIRANDA',
        'correo' => 'tradecompliance4@estrategiaeinnovacion.com.mx',
        'motivo_baja' => 'Baja de personal',
        'observaciones' => 'No está en el seeder actualizado de empleados',
    ],
    [
        'nombre' => 'GABRIELA ROMERO',
        'correo' => 'aduanas@plastinver.com.mx',
        'motivo_baja' => 'Baja de personal',
        'observaciones' => 'No está en el seeder actualizado de empleados',
    ],
    [
        'nombre' => 'Michelle Garcia',
        'correo' => 'logistocs@dekosys.mx',
        'motivo_baja' => 'Baja de personal',
        'observaciones' => 'No está en el seeder actualizado de empleados',
    ],
    [
        'nombre' => 'Aneth Alejandra Herrera Hernandez',
        'correo' => 'virtuales@hernandezbolanos.com.mx',
        'motivo_baja' => 'Baja de personal',
        'observaciones' => 'Confirmado por usuario - baja de empleado',
    ],
    [
        'nombre' => 'Jacob de Jesus Medina Ramirez',
        'correo' => 'consultora.ei@asiaway.com',
        'motivo_baja' => 'Baja de personal',
        'observaciones' => 'Confirmado por usuario - baja de empleado',
    ],
];

$procesados = 0;
$errores = 0;

foreach ($empleadosBaja as $empBaja) {
    echo "━━━ Procesando: {$empBaja['nombre']} ━━━\n";
    
    // Buscar empleado por correo o nombre
    $empleado = Empleado::where('correo', $empBaja['correo'])
        ->orWhereRaw('LOWER(TRIM(nombre)) LIKE ?', ['%' . strtolower(explode(' ', $empBaja['nombre'])[0]) . '%'])
        ->first();
    
    if (!$empleado) {
        // Buscar de forma más flexible
        $palabras = explode(' ', $empBaja['nombre']);
        foreach ($palabras as $palabra) {
            if (strlen($palabra) > 3) {
                $empleado = Empleado::whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower($palabra) . '%'])->first();
                if ($empleado) break;
            }
        }
    }
    
    $userId = null;
    $empleadoId = null;
    
    if ($empleado) {
        $empleadoId = $empleado->id;
        $userId = $empleado->user_id;
        echo "  ✓ Empleado encontrado en BD (ID: {$empleado->id})\n";
    } else {
        // Buscar usuario por correo
        $user = User::where('email', $empBaja['correo'])->first();
        if ($user) {
            $userId = $user->id;
            echo "  ✓ Usuario encontrado en BD (ID: {$user->id})\n";
        } else {
            echo "  ⚠ No se encontró empleado ni usuario en BD\n";
        }
    }
    
    // Verificar si ya está en bajas
    $yaEnBaja = EmpleadoBaja::where('correo', $empBaja['correo'])->exists();
    
    if ($yaEnBaja) {
        echo "  → Ya está registrado en bajas, saltando...\n\n";
        continue;
    }
    
    try {
        // Crear registro de baja
        $baja = EmpleadoBaja::create([
            'empleado_id' => $empleadoId,
            'user_id' => $userId,
            'nombre' => $empBaja['nombre'],
            'correo' => $empBaja['correo'],
            'motivo_baja' => $empBaja['motivo_baja'],
            'fecha_baja' => now(),
            'observaciones' => $empBaja['observaciones'],
        ]);
        
        echo "  ✓ Registrado en empleados_baja (ID: {$baja->id})\n";
        
        // Eliminar de la tabla empleados
        if ($empleado) {
            $empleado->delete();
            echo "  ✓ Eliminado de tabla empleados\n";
        }
        
        // Desactivar usuario (no eliminar, solo cambiar status)
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $user->update(['status' => 'rejected']);
                echo "  ✓ Usuario desactivado (status: rejected)\n";
            }
        }
        
        $procesados++;
        echo "\n";
        
    } catch (\Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n\n";
        $errores++;
    }
}

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN                                                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "  ✓ Procesados correctamente: {$procesados}\n";
echo "  ✗ Errores: {$errores}\n\n";

// Mostrar lista actual de bajas
echo "━━━ LISTA ACTUAL DE EMPLEADOS EN BAJA ━━━\n\n";
$bajas = EmpleadoBaja::all();
foreach ($bajas as $baja) {
    echo sprintf("  • %-40s %s\n", $baja->nombre, $baja->correo);
    echo sprintf("    Fecha: %s | Motivo: %s\n\n", $baja->fecha_baja->format('Y-m-d'), $baja->motivo_baja);
}

echo "\n✅ PROCESO COMPLETADO\n";

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

echo "=== ACTUALIZACIÓN DE EMPLEADOS EXISTENTES ===\n\n";

$actualizaciones = [
    [
        'empleado_id_bd' => 1,  // Jonathan Israel Loredo Palacios
        'user_id_bd' => 1,
        'id_empleado' => '95',
        'nombre' => 'Jonathan Loredo Palacios',
        'correo_mantener' => 'sistemas@estrategiaeinnovacion.com.mx',
        'area' => 'Estrategia e Innovacion',
        'posicion' => 'TI',
    ],
    [
        'empleado_id_bd' => 16,  // Michelle Garcia
        'user_id_bd' => 20,
        'id_empleado' => '70',
        'nombre' => 'Karen Michelle Echevarria Garcia',
        'correo_mantener' => 'logistocs@dekosys.mx',
        'area' => 'Estrategia e Innovacion',
        'posicion' => 'Logistica',
    ],
    [
        'empleado_id_bd' => 3,  // Ana Sofía Cuello
        'user_id_bd' => 6,
        'id_empleado' => '80',
        'nombre' => 'Ana Sofia Cuello Aguilar',
        'correo_mantener' => 'legal3@estrategiaeinnovacion.com.mx',
        'area' => 'Estrategia e Innovacion',
        'posicion' => 'Legal',
    ],
];

DB::beginTransaction();

try {
    foreach ($actualizaciones as $act) {
        echo "Actualizando: {$act['nombre']} (ID Empleado: {$act['id_empleado']})\n";
        
        // Actualizar empleado
        $empleado = Empleado::find($act['empleado_id_bd']);
        if ($empleado) {
            $empleado->update([
                'id_empleado' => $act['id_empleado'],
                'nombre' => $act['nombre'],
                'area' => $act['area'],
                'posicion' => $act['posicion'],
                // Mantener correo real
            ]);
            echo "  ✓ Empleado actualizado (BD ID: {$act['empleado_id_bd']})\n";
            echo "    Nombre anterior -> Nuevo: {$act['nombre']}\n";
            echo "    Correo mantenido: {$act['correo_mantener']}\n";
        } else {
            echo "  ✗ ERROR: Empleado no encontrado\n";
        }
        
        // Actualizar usuario
        $user = User::find($act['user_id_bd']);
        if ($user) {
            $user->update([
                'name' => $act['nombre'],
            ]);
            echo "  ✓ Usuario actualizado (User ID: {$act['user_id_bd']})\n";
        } else {
            echo "  ✗ ERROR: Usuario no encontrado\n";
        }
        
        echo "\n";
    }
    
    DB::commit();
    echo "✅ ACTUALIZACIONES COMPLETADAS EXITOSAMENTE\n";
    echo "Total actualizados: " . count($actualizaciones) . " empleados\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

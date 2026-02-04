<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

echo "=== LIMPIEZA DE DUPLICADOS ===\n\n";

// Duplicados identificados (mantener el que tiene correo real, eliminar el del seeder @empresa.com)
$duplicados = [
    [
        'nombre' => 'Aneth Alejandra Herrera Hernandez',
        'mantener_user_id' => 12,  // tradecompliance2@estrategiaeinnovacion.com.mx (REAL)
        'eliminar_user_id' => 23,  // virtuales@hernandezbolanos.com.mx o @empresa.com
        'mantener_emp_id' => 9,
        'eliminar_emp_id' => 19,
    ],
    [
        'nombre' => 'Carlos Alfonso Rivera Moran',
        'mantener_user_id' => 5,   // Legal@estrategiaeinnovacion.com.mx (REAL)
        'eliminar_user_id' => 40,  // carlos.rivera@empresa.com
        'mantener_emp_id' => 2,
        'eliminar_emp_id' => 36,
    ],
    [
        'nombre' => 'Erika Liliana Mireles Sanchez',
        'mantener_user_id' => 9,   // tradecompliance4@estrategiaeinnovacion.com.mx (REAL)
        'eliminar_user_id' => 39,  // erika.mireles@empresa.com
        'mantener_emp_id' => 6,
        'eliminar_emp_id' => 35,
    ],
    [
        'nombre' => 'Guadalupe Jacqueline Mendoza Rodriguez',
        'mantener_user_id' => 18,  // Jacqueline.Mendoza@agc.com (REAL)
        'eliminar_user_id' => 10,  // tradecompliance11@estrategiaeinnovacion.com.mx
        'mantener_emp_id' => 14,
        'eliminar_emp_id' => 7,
    ],
    [
        'nombre' => 'Ivan Rodriguez Juarez',
        'mantener_user_id' => 17,  // comercio.exterior@sarrel.com (REAL)
        'eliminar_user_id' => 7,   // administracion@estrategiaeinnovacion.com.mx
        'mantener_emp_id' => 13,
        'eliminar_emp_id' => 4,
    ],
    [
        'nombre' => 'Jonathan Loredo Palacios',
        'mantener_user_id' => 1,   // sistemas@estrategiaeinnovacion.com.mx (REAL)
        'eliminar_user_id' => 36,  // jonathan.loredo@empresa.com
        'mantener_emp_id' => 1,
        'eliminar_emp_id' => 32,
    ],
    [
        'nombre' => 'Karen Cristina Bonal Mata',
        'mantener_user_id' => 22,  // karen.bonal@elkay.com (REAL)
        'eliminar_user_id' => 20,  // logistocs@dekosys.mx
        'mantener_emp_id' => 18,
        'eliminar_emp_id' => 16,
    ],
    [
        'nombre' => 'Mayra Susana Coreño Arriaga',
        'mantener_user_id' => 29,  // tradecomplaiance22@estrategiaeinovacion.com.mx (REAL)
        'eliminar_user_id' => 38,  // mayra.coreno@empresa.com
        'mantener_emp_id' => 26,
        'eliminar_emp_id' => 34,
    ],
];

DB::beginTransaction();

try {
    foreach ($duplicados as $dup) {
        echo "Procesando: {$dup['nombre']}\n";
        
        // Obtener info del registro a mantener
        $empleadoMantener = Empleado::find($dup['mantener_emp_id']);
        $userMantener = User::find($dup['mantener_user_id']);
        
        if (!$empleadoMantener || !$userMantener) {
            echo "  ERROR: No se encontró el registro a mantener\n";
            continue;
        }
        
        // Actualizar el empleado que vamos a mantener con info del seeder
        $empleadoEliminar = Empleado::find($dup['eliminar_emp_id']);
        if ($empleadoEliminar) {
            // Actualizar nombre, área, posición, id_empleado
            $empleadoMantener->update([
                'nombre' => $dup['nombre'],
                'id_empleado' => $empleadoEliminar->id_empleado, // Usar el id del seeder
                'area' => $empleadoEliminar->area,
                'posicion' => $empleadoEliminar->posicion,
            ]);
            echo "  ✓ Empleado actualizado (ID: {$dup['mantener_emp_id']})\n";
        }
        
        // Actualizar el user que vamos a mantener
        $userMantener->update(['name' => $dup['nombre']]);
        echo "  ✓ Usuario actualizado (ID: {$dup['mantener_user_id']})\n";
        
        // Actualizar referencias antes de eliminar
        DB::table('empleados')
            ->where('supervisor_id', $dup['eliminar_emp_id'])
            ->update(['supervisor_id' => $dup['mantener_emp_id']]);
        
        // Eliminar duplicados
        Empleado::destroy($dup['eliminar_emp_id']);
        User::destroy($dup['eliminar_user_id']);
        
        echo "  ✓ Duplicados eliminados\n\n";
    }
    
    DB::commit();
    echo "\n✅ LIMPIEZA COMPLETADA EXITOSAMENTE\n";
    echo "Total de registros limpiados: " . count($duplicados) * 2 . "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

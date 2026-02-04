<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== SINCRONIZACIÓN COMPLETA SEEDER -> BD ===\n\n";

// 1. ELIMINAR CUENTAS DE PRUEBA
echo "PASO 1: ELIMINANDO CUENTAS DE PRUEBA\n";
echo str_repeat("-", 120) . "\n";

$cuentasPrueba = [
    'PruebasLogistica@estrategiaeinnovacion.com.mx',
    'PruebasRH@estrategiaeinnovacion.com.mx',
    'admin@estrategiaeinnovacion.com.mx',
    'rh@estrategiaeinnovacion.com.mx',
];

DB::beginTransaction();

try {
    foreach ($cuentasPrueba as $email) {
        $user = User::where('email', $email)->first();
        if ($user) {
            $empleado = Empleado::where('correo', $email)->first();
            if ($empleado) {
                $empleado->delete();
                echo "  ✓ Empleado eliminado: {$empleado->nombre}\n";
            }
            $user->delete();
            echo "  ✓ Usuario eliminado: {$user->name} ({$email})\n";
        }
    }
    
    echo "\n";
    
    // 2. ACTUALIZAR EMPLEADOS EXISTENTES
    echo "PASO 2: ACTUALIZANDO EMPLEADOS EXISTENTES\n";
    echo str_repeat("-", 120) . "\n";
    
    $actualizaciones = [
        ['nombre_bd' => 'SILVESTRE REYES CASTILLO', 'seeder' => ['id_empleado' => '23', 'nombre' => 'Silvestre Reyes Castillo', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Auditoria']],
        ['nombre_bd' => 'Nancy Beatriz Gomez Hernandez', 'seeder' => ['id_empleado' => '30', 'nombre' => 'Nancy Beatriz Gomez Hernandez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Jazzman Jerssain Aguilar Cisneros', 'seeder' => ['id_empleado' => '56', 'nombre' => 'Jazzman Jerssain Aguilar Cisneros', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal']],
        ['nombre_bd' => 'Mario Mojica Morales', 'seeder' => ['id_empleado' => '57', 'nombre' => 'Mario Mojica Morales', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Post-Operacion']],
        ['nombre_bd' => 'Aneth Alejandra Herrera', 'seeder' => ['id_empleado' => '74', 'nombre' => 'Aneth Alejandra Herrera Hernandez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Post-Operacion']],
        ['nombre_bd' => 'ZAIRA ISABEL MARTÍNEZ URBINA', 'seeder' => ['id_empleado' => '22', 'nombre' => 'Zaira Isabel Martinez Urbina', 'area' => 'Chronos Fullfillment', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Luis Eduardo Inclan Soriano', 'seeder' => ['id_empleado' => '60', 'nombre' => 'Luis Eduardo Inclan Soriano', 'area' => 'Siegwerk', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Guadalupe Jacqueline Mendoza Rodriguez', 'seeder' => ['id_empleado' => '68', 'nombre' => 'Guadalupe Jacqueline Mendoza Rodriguez', 'area' => 'AGC', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Karen Michelle Echevarria Garcia', 'seeder' => ['id_empleado' => '70', 'nombre' => 'Karen Michelle Echevarria Garcia', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Mariana Rodriguez', 'seeder' => ['id_empleado' => '73', 'nombre' => 'Mariana Rodriguez Rueda', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Oscar Eduardo Morin Carrizales', 'seeder' => ['id_empleado' => '78', 'nombre' => 'Oscar Eduardo Morin Carrizales', 'area' => 'PPM Industries', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'ALISSON CASSIEL PINEDA MARTINEZ', 'seeder' => ['id_empleado' => '53', 'nombre' => 'Alisson Cassiel Pineda Martinez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Ivan Rodriguez Juarez', 'seeder' => ['id_empleado' => '86', 'nombre' => 'Ivan Rodriguez Juarez', 'area' => 'Sarrel', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Karen Cristina Bonal Mata', 'seeder' => ['id_empleado' => '87', 'nombre' => 'Karen Cristina Bonal Mata', 'area' => 'EB-Tecnica', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Jacob de Jesus Medina Ramirez', 'seeder' => ['id_empleado' => '96', 'nombre' => 'Jacob de Jesus Medina Ramirez', 'area' => 'AsiaWay', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Fatima Esther Torres Arriaga', 'seeder' => ['id_empleado' => '99', 'nombre' => 'Fatima Esther Torres Arriaga', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica']],
        ['nombre_bd' => 'Mariana Calderón', 'seeder' => ['id_empleado' => '84', 'nombre' => 'Mariana Calderón Ojeda', 'area' => 'Recursos Humanos', 'posicion' => 'Administracion RH']],
        ['nombre_bd' => 'Jonathan Loredo Palacios', 'seeder' => ['id_empleado' => '95', 'nombre' => 'Jonathan Loredo Palacios', 'area' => 'Estrategia e Innovacion', 'posicion' => 'TI']],
        ['nombre_bd' => 'Jessica Anahi Esparza Gonzalez', 'seeder' => ['id_empleado' => '90', 'nombre' => 'Jessica Anahi Esparza Gonzalez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Anexo 24']],
        ['nombre_bd' => 'Felipe de Jesus Rodriguez Ledesma', 'seeder' => ['id_empleado' => '98', 'nombre' => 'Felipe de Jesus Rodriguez Ledesma', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Anexo 24']],
        ['nombre_bd' => 'Ana Sofia Cuello Aguilar', 'seeder' => ['id_empleado' => '80', 'nombre' => 'Ana Sofia Cuello Aguilar', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal']],
        ['nombre_bd' => 'Jesus David Rivera Romero', 'seeder' => ['id_empleado' => '97', 'nombre' => 'Jesus David Rivera Romero', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal']],
    ];
    
    foreach ($actualizaciones as $act) {
        $empleado = Empleado::whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($act['nombre_bd']))])->first();
        
        if ($empleado) {
            $empleado->update([
                'id_empleado' => $act['seeder']['id_empleado'],
                'nombre' => $act['seeder']['nombre'],
                'area' => $act['seeder']['area'],
                'posicion' => $act['seeder']['posicion'],
            ]);
            
            if ($empleado->user) {
                $empleado->user->update(['name' => $act['seeder']['nombre']]);
            }
            
            echo "  ✓ Actualizado: {$act['seeder']['nombre']} (ID: {$act['seeder']['id_empleado']})\n";
        }
    }
    
    echo "\n";
    
    // 3. CREAR NUEVOS EMPLEADOS
    echo "PASO 3: CREANDO NUEVOS EMPLEADOS\n";
    echo str_repeat("-", 120) . "\n";
    
    $nuevosEmpleados = [
        ['id_empleado' => '0', 'nombre' => 'Guillermo Aguilera', 'correo' => 'guillermo.aguilera@empresa.com', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Direccion'],
        ['id_empleado' => '36', 'nombre' => 'Liliana Hernandez Castilla', 'correo' => 'liliana.hernandez@empresa.com', 'area' => 'Recursos Humanos', 'posicion' => 'Administracion RH'],
        ['id_empleado' => '103', 'nombre' => 'Isaac Covarrubias Quintero', 'correo' => 'isaac.covarrubias@empresa.com', 'area' => 'Estrategia e Innovacion', 'posicion' => 'TI'],
        ['id_empleado' => '100', 'nombre' => 'Mayra Susana Coreño Arriaga', 'correo' => 'mayra.coreno@empresa.com', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Post-Operacion'],
        ['id_empleado' => '101', 'nombre' => 'Erika Liliana Mireles Sanchez', 'correo' => 'erika.mireles@empresa.com', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Anexo 24'],
        ['id_empleado' => '102', 'nombre' => 'Carlos Alfonso Rivera Moran', 'correo' => 'carlos.rivera@empresa.com', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal'],
    ];
    
    foreach ($nuevosEmpleados as $nuevo) {
        // Verificar si ya existe
        $existe = Empleado::where('id_empleado', $nuevo['id_empleado'])->first();
        
        if (!$existe) {
            // Crear usuario
            $user = User::create([
                'email' => $nuevo['correo'],
                'name' => $nuevo['nombre'],
                'password' => Hash::make('Pass123456'),
            ]);
            
            // Crear empleado
            $empleado = Empleado::create([
                'id_empleado' => $nuevo['id_empleado'],
                'nombre' => $nuevo['nombre'],
                'correo' => $nuevo['correo'],
                'area' => $nuevo['area'],
                'posicion' => $nuevo['posicion'],
                'user_id' => $user->id,
            ]);
            
            echo "  ✓ Creado: {$nuevo['nombre']} (ID: {$nuevo['id_empleado']}) - {$nuevo['correo']}\n";
        } else {
            echo "  ⚠ Ya existe: {$nuevo['nombre']} (ID: {$nuevo['id_empleado']})\n";
        }
    }
    
    DB::commit();
    
    echo "\n" . str_repeat("=", 120) . "\n";
    echo "✅ SINCRONIZACIÓN COMPLETADA\n";
    echo "  - Cuentas de prueba eliminadas: 4\n";
    echo "  - Empleados actualizados: " . count($actualizaciones) . "\n";
    echo "  - Empleados nuevos creados: " . count($nuevosEmpleados) . "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

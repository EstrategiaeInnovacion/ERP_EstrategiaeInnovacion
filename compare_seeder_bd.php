<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleado;

echo "=== COMPARACIÓN SEEDER VS BASE DE DATOS ===\n\n";

// Empleados del seeder
$empleadosSeeder = [
    ['id_empleado' => '0', 'nombre' => 'Guillermo Aguilera'],
    ['id_empleado' => '36', 'nombre' => 'Liliana Hernandez Castilla'],
    ['id_empleado' => '23', 'nombre' => 'Silvestre Reyes Castillo'],
    ['id_empleado' => '30', 'nombre' => 'Nancy Beatriz Gomez Hernandez'],
    ['id_empleado' => '56', 'nombre' => 'Jazzman Jerssain Aguilar Cisneros'],
    ['id_empleado' => '57', 'nombre' => 'Mario Mojica Morales'],
    ['id_empleado' => '74', 'nombre' => 'Aneth Alejandra Herrera Hernandez'],
    ['id_empleado' => '22', 'nombre' => 'Zaira Isabel Martinez Urbina'],
    ['id_empleado' => '60', 'nombre' => 'Luis Eduardo Inclan Soriano'],
    ['id_empleado' => '68', 'nombre' => 'Guadalupe Jacqueline Mendoza Rodriguez'],
    ['id_empleado' => '70', 'nombre' => 'Karen Michelle Echevarria Garcia'],
    ['id_empleado' => '73', 'nombre' => 'Mariana Rodriguez Rueda'],
    ['id_empleado' => '78', 'nombre' => 'Oscar Eduardo Morin Carrizales'],
    ['id_empleado' => '53', 'nombre' => 'Alisson Cassiel Pineda Martinez'],
    ['id_empleado' => '86', 'nombre' => 'Ivan Rodriguez Juarez'],
    ['id_empleado' => '87', 'nombre' => 'Karen Cristina Bonal Mata'],
    ['id_empleado' => '96', 'nombre' => 'Jacob de Jesus Medina Ramirez'],
    ['id_empleado' => '99', 'nombre' => 'Fatima Esther Torres Arriaga'],
    ['id_empleado' => '84', 'nombre' => 'Mariana Calderón Ojeda'],
    ['id_empleado' => '95', 'nombre' => 'Jonathan Loredo Palacios'],
    ['id_empleado' => '103', 'nombre' => 'Isaac Covarrubias Quintero'],
    ['id_empleado' => '90', 'nombre' => 'Jessica Anahi Esparza Gonzalez'],
    ['id_empleado' => '98', 'nombre' => 'Felipe de Jesus Rodriguez Ledesma'],
    ['id_empleado' => '100', 'nombre' => 'Mayra Susana Coreño Arriaga'],
    ['id_empleado' => '101', 'nombre' => 'Erika Liliana Mireles Sanchez'],
    ['id_empleado' => '80', 'nombre' => 'Ana Sofia Cuello Aguilar'],
    ['id_empleado' => '97', 'nombre' => 'Jesus David Rivera Romero'],
    ['id_empleado' => '102', 'nombre' => 'Carlos Alfonso Rivera Moran'],
];

// Función para normalizar nombres
function normalizarNombre($nombre) {
    return strtolower(trim(preg_replace('/\s+/', ' ', $nombre)));
}

// Función para extraer primer nombre y primer apellido
function extraerNombreApellido($nombreCompleto) {
    $partes = explode(' ', trim($nombreCompleto));
    $nombre = $partes[0] ?? '';
    $apellido = $partes[1] ?? '';
    return normalizarNombre($nombre . ' ' . $apellido);
}

// Función para comparar nombres
function compararNombres($nombre1, $nombre2) {
    $n1 = normalizarNombre($nombre1);
    $n2 = normalizarNombre($nombre2);
    
    // Comparación exacta
    if ($n1 === $n2) {
        return 'exacto';
    }
    
    // Comparación por nombre y apellido
    $na1 = extraerNombreApellido($nombre1);
    $na2 = extraerNombreApellido($nombre2);
    
    if ($na1 === $na2 && strlen($na1) > 3) {
        return 'parcial';
    }
    
    // Uno contiene al otro
    if (strpos($n1, $n2) !== false || strpos($n2, $n1) !== false) {
        return 'contenido';
    }
    
    return false;
}

// Obtener datos de la BD
$empleadosBD = Empleado::all();
$usersBD = User::all();

echo "Total en Seeder: " . count($empleadosSeeder) . "\n";
echo "Total Empleados BD: " . $empleadosBD->count() . "\n";
echo "Total Users BD: " . $usersBD->count() . "\n\n";

echo str_repeat("=", 120) . "\n";
echo "1. EMPLEADOS DEL SEEDER VS EMPLEADOS BD\n";
echo str_repeat("=", 120) . "\n\n";

$coincidenciasEmpleados = [];
$noCoincidentesSeeder = [];

foreach ($empleadosSeeder as $empSeeder) {
    $encontrado = false;
    
    foreach ($empleadosBD as $empBD) {
        $tipoMatch = compararNombres($empSeeder['nombre'], $empBD->nombre);
        
        if ($tipoMatch) {
            $coincidenciasEmpleados[] = [
                'seeder' => $empSeeder['nombre'],
                'bd' => $empBD->nombre,
                'bd_correo' => $empBD->correo,
                'id_emp_seeder' => $empSeeder['id_empleado'],
                'id_emp_bd' => $empBD->id_empleado,
                'tipo' => $tipoMatch,
            ];
            $encontrado = true;
            break;
        }
    }
    
    if (!$encontrado) {
        $noCoincidentesSeeder[] = $empSeeder;
    }
}

echo "COINCIDENCIAS ENCONTRADAS (" . count($coincidenciasEmpleados) . "):\n";
echo str_repeat("-", 120) . "\n";
foreach ($coincidenciasEmpleados as $match) {
    printf("%-40s <=> %-40s [%s]\n", 
        substr($match['seeder'], 0, 40), 
        substr($match['bd'], 0, 40), 
        strtoupper($match['tipo'])
    );
    printf("  Seeder ID: %-3s | BD ID: %-3s | Correo BD: %s\n", 
        $match['id_emp_seeder'], 
        $match['id_emp_bd'], 
        $match['bd_correo']
    );
    echo str_repeat("-", 120) . "\n";
}

echo "\nNO ENCONTRADOS EN BD (DEL SEEDER) (" . count($noCoincidentesSeeder) . "):\n";
echo str_repeat("-", 120) . "\n";
foreach ($noCoincidentesSeeder as $emp) {
    printf("%-3s | %s\n", $emp['id_empleado'], $emp['nombre']);
}

echo "\n" . str_repeat("=", 120) . "\n";
echo "2. EMPLEADOS DEL SEEDER VS USERS BD\n";
echo str_repeat("=", 120) . "\n\n";

$coincidenciasUsers = [];
$noCoincidentesSeederUsers = [];

foreach ($empleadosSeeder as $empSeeder) {
    $encontrado = false;
    
    foreach ($usersBD as $user) {
        $tipoMatch = compararNombres($empSeeder['nombre'], $user->name);
        
        if ($tipoMatch) {
            $coincidenciasUsers[] = [
                'seeder' => $empSeeder['nombre'],
                'user' => $user->name,
                'user_email' => $user->email,
                'id_emp_seeder' => $empSeeder['id_empleado'],
                'user_id' => $user->id,
                'tipo' => $tipoMatch,
            ];
            $encontrado = true;
            break;
        }
    }
    
    if (!$encontrado) {
        $noCoincidentesSeederUsers[] = $empSeeder;
    }
}

echo "COINCIDENCIAS ENCONTRADAS (" . count($coincidenciasUsers) . "):\n";
echo str_repeat("-", 120) . "\n";
foreach ($coincidenciasUsers as $match) {
    printf("%-40s <=> %-40s [%s]\n", 
        substr($match['seeder'], 0, 40), 
        substr($match['user'], 0, 40), 
        strtoupper($match['tipo'])
    );
    printf("  Seeder ID: %-3s | User ID: %-3s | Email: %s\n", 
        $match['id_emp_seeder'], 
        $match['user_id'], 
        $match['user_email']
    );
    echo str_repeat("-", 120) . "\n";
}

echo "\nNO ENCONTRADOS EN USERS (DEL SEEDER) (" . count($noCoincidentesSeederUsers) . "):\n";
echo str_repeat("-", 120) . "\n";
foreach ($noCoincidentesSeederUsers as $emp) {
    printf("%-3s | %s\n", $emp['id_empleado'], $emp['nombre']);
}

echo "\n" . str_repeat("=", 120) . "\n";
echo "3. EMPLEADOS BD QUE NO ESTÁN EN EL SEEDER\n";
echo str_repeat("=", 120) . "\n\n";

$empleadosBDnoEnSeeder = [];

foreach ($empleadosBD as $empBD) {
    $encontrado = false;
    
    foreach ($empleadosSeeder as $empSeeder) {
        if (compararNombres($empBD->nombre, $empSeeder['nombre'])) {
            $encontrado = true;
            break;
        }
    }
    
    if (!$encontrado) {
        $empleadosBDnoEnSeeder[] = $empBD;
    }
}

echo "Total: " . count($empleadosBDnoEnSeeder) . "\n";
echo str_repeat("-", 120) . "\n";
foreach ($empleadosBDnoEnSeeder as $emp) {
    printf("ID: %-3s | Nombre: %-40s | Correo: %s\n", 
        $emp->id_empleado, 
        substr($emp->nombre, 0, 40), 
        $emp->correo
    );
}

echo "\n" . str_repeat("=", 120) . "\n";
echo "4. USERS BD QUE NO ESTÁN EN EL SEEDER\n";
echo str_repeat("=", 120) . "\n\n";

$usersBDnoEnSeeder = [];

foreach ($usersBD as $user) {
    $encontrado = false;
    
    foreach ($empleadosSeeder as $empSeeder) {
        if (compararNombres($user->name, $empSeeder['nombre'])) {
            $encontrado = true;
            break;
        }
    }
    
    if (!$encontrado) {
        $usersBDnoEnSeeder[] = $user;
    }
}

echo "Total: " . count($usersBDnoEnSeeder) . "\n";
echo str_repeat("-", 120) . "\n";
foreach ($usersBDnoEnSeeder as $user) {
    printf("ID: %-3s | Nombre: %-40s | Email: %s\n", 
        $user->id, 
        substr($user->name, 0, 40), 
        $user->email
    );
}

echo "\n=== FIN DE LA COMPARACIÓN ===\n";

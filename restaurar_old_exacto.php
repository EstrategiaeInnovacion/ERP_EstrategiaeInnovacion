<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESTAURAR BD PRODUCCIÓN desde BD BACKUP                               ║\n";
echo "║  BACKUP: estrategias_innovacion → PROD: estrategias_innovacion_v2      ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// Conexión BACKUP = mysql (estrategias_innovacion en localhost)
// Conexión PRODUCCIÓN = mysql_old (estrategias_innovacion_v2 en 82.197.93.18)

// Obtener empleados del BACKUP (estrategias_innovacion - localhost)
$empleadosBackup = DB::connection('mysql')->table('empleados')->get();

echo "━━━ DATOS EN BACKUP (estrategias_innovacion @ localhost) ━━━\n";
echo "Total: " . $empleadosBackup->count() . " empleados\n\n";

foreach ($empleadosBackup as $emp) {
    $area = $emp->area ?? 'NULL';
    $idEmp = $emp->id_empleado ?? 'NULL';
    $pos = $emp->posicion ?? 'NULL';
    echo "ID {$emp->id}: {$emp->nombre}\n";
    echo "    area={$area} | id_empleado={$idEmp} | posicion={$pos}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "¿Los datos de arriba son los CORRECTOS del backup?\n";
echo "Si es así, presiona ENTER para copiarlos a PRODUCCIÓN (v2)...\n";
echo "O presiona CTRL+C para cancelar.\n";
fgets(STDIN);

$actualizados = 0;
$noEncontrados = [];

foreach ($empleadosBackup as $empBackup) {
    // Buscar en PRODUCCIÓN por correo
    $empProd = DB::connection('mysql_old')
        ->table('empleados')
        ->where('correo', $empBackup->correo)
        ->first();
    
    if (!$empProd) {
        // Buscar por nombre exacto
        $empProd = DB::connection('mysql_old')
            ->table('empleados')
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($empBackup->nombre))])
            ->first();
    }
    
    if ($empProd) {
        // Copiar valores del BACKUP a PRODUCCIÓN
        DB::connection('mysql_old')
            ->table('empleados')
            ->where('id', $empProd->id)
            ->update([
                'area' => $empBackup->area,
                'id_empleado' => $empBackup->id_empleado,
                'posicion' => $empBackup->posicion,
            ]);
        
        $actualizados++;
        $area = $empBackup->area ?? 'NULL';
        echo "✓ {$empBackup->nombre} → area={$area}\n";
    } else {
        $noEncontrados[] = $empBackup->nombre;
    }
}

echo "\n╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN                                                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "✓ Actualizados en PRODUCCIÓN (v2): {$actualizados}\n";
echo "✗ No encontrados: " . count($noEncontrados) . "\n";

if (count($noEncontrados) > 0) {
    echo "\nNo encontrados en producción:\n";
    foreach ($noEncontrados as $nombre) {
        echo "  - {$nombre}\n";
    }
}

echo "\n✅ RESTAURACIÓN COMPLETADA\n";

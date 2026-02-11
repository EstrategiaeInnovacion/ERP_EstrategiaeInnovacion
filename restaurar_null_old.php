<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESTAURAR BD OLD - PONER NULL en id_empleado y posicion SOLAMENTE     ║\n";
echo "║  ¡URGENTE! Deshacer cambios del seeder (NO toca area)                  ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// Contar empleados antes
$totalAntes = DB::connection('mysql_old')
    ->table('empleados')
    ->where(function($q) {
        $q->whereNotNull('id_empleado')
          ->orWhereNotNull('posicion');
    })
    ->count();

echo "Empleados con datos en id_empleado/posicion ANTES: {$totalAntes}\n\n";

// Poner NULL SOLO en id_empleado y posicion (NO tocar area)
$affected = DB::connection('mysql_old')
    ->table('empleados')
    ->update([
        'id_empleado' => null,
        'posicion' => null,
        // NO tocar 'area' - mantener valores originales
    ]);

echo "✓ Registros actualizados: {$affected}\n\n";

// Verificar después
$totalDespues = DB::connection('mysql_old')
    ->table('empleados')
    ->where(function($q) {
        $q->whereNotNull('id_empleado')
          ->orWhereNotNull('posicion');
    })
    ->count();

echo "Empleados con datos en id_empleado/posicion DESPUÉS: {$totalDespues}\n\n";

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ BD OLD RESTAURADA - id_empleado y posicion en NULL                 ║\n";
echo "║     (area NO fue modificada)                                           ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";

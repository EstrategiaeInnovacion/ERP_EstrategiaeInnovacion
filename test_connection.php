<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  PRUEBA DE CONEXIÓN A BASE DE DATOS                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // Probar conexión principal
    echo "Probando conexión principal (mysql)...\n";
    $tables = DB::select('SHOW TABLES');
    echo "✓ Conexión exitosa! Tablas encontradas: " . count($tables) . "\n\n";
    
    echo "Primeras 30 tablas:\n";
    echo str_repeat("-", 50) . "\n";
    foreach(array_slice($tables, 0, 30) as $table) {
        $tableName = array_values((array)$table)[0];
        $count = DB::table($tableName)->count();
        echo sprintf("  %-35s %d registros\n", $tableName, $count);
    }
    
    echo "\n";
    
} catch(Exception $e) {
    echo "✗ Error de conexión: " . $e->getMessage() . "\n";
}

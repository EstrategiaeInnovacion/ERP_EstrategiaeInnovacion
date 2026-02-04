<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  CREANDO BASE DE DATOS LOCAL                               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // Conectar sin especificar base de datos
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $dbName = 'estrategias_innovacion_v2';
    
    // Crear la base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "✓ Base de datos '{$dbName}' creada/verificada exitosamente!\n";
    
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

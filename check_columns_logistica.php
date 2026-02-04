<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  CAMPOS DE LA TABLA operaciones_logisticas                             ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

$columns = DB::select('SHOW COLUMNS FROM operaciones_logisticas');

foreach ($columns as $col) {
    echo sprintf("  %-40s %s\n", $col->Field, $col->Type);
}

echo "\nTotal campos: " . count($columns) . "\n";

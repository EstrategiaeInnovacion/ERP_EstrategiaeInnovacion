<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Logistica\OperacionLogistica;
use App\Models\Logistica\Cliente;
use Illuminate\Support\Facades\DB;

echo "=== Verificando datos del campo cliente ===\n\n";

// Ver primeras 5 operaciones
$operaciones = OperacionLogistica::take(5)->get(['id', 'cliente', 'operacion']);
echo "Primeras 5 operaciones:\n";
foreach ($operaciones as $op) {
    echo "ID: {$op->id} | Cliente: '{$op->cliente}' | Tipo: " . gettype($op->cliente) . "\n";
}

echo "\n=== Estructura de tabla clientes ===\n";
$columnas = DB::select('DESCRIBE clientes');
foreach ($columnas as $col) {
    echo "  - {$col->Field} ({$col->Type})\n";
}

echo "\n=== Primeros 3 registros de clientes ===\n";
$clientes = DB::table('clientes')->take(3)->get();
foreach ($clientes as $cliente) {
    print_r($cliente);
}

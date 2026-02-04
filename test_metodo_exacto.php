<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Logistica\ColumnaVisibleEjecutivo;

// Llamar al método EXACTAMENTE como el controlador
$empleadoId = 1;
$resultado = ColumnaVisibleEjecutivo::getConfiguracionOpcionalesEjecutivo($empleadoId);

echo "Resultado completo:\n";
print_r($resultado['tipo_carga']);

echo "\nTipo de visible: " . gettype($resultado['tipo_carga']['visible']) . "\n";

// También verificar las columnas para la matriz
$columnasOrdenadas = ColumnaVisibleEjecutivo::getColumnasOrdenadasParaMatriz($empleadoId, 'es');

echo "\n=== COLUMNAS ORDENADAS PARA MATRIZ ===\n";
foreach ($columnasOrdenadas as $col) {
    echo "- {$col['columna']} ({$col['tipo']}): {$col['nombre']}\n";
}

echo "\n=== SOLO OPCIONALES ===\n";
$opcionalesEnMatriz = array_filter($columnasOrdenadas, fn($c) => $c['tipo'] === 'opcional');
foreach ($opcionalesEnMatriz as $col) {
    echo "- {$col['columna']}: {$col['nombre']}\n";
}

if (empty($opcionalesEnMatriz)) {
    echo "NO HAY COLUMNAS OPCIONALES EN LA MATRIZ\n";
}

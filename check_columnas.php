<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Logistica\ColumnaVisibleEjecutivo;
use App\Models\Empleado;

echo "=== DIAGNÓSTICO DE COLUMNAS VISIBLES ===\n\n";

// Ver todos los registros de columnas visibles
$registros = ColumnaVisibleEjecutivo::all();
echo "Total registros en columnas_visibles_ejecutivo: " . $registros->count() . "\n\n";

foreach ($registros as $r) {
    $empleado = Empleado::find($r->empleado_id);
    echo sprintf("ID: %d | Empleado: %d (%s) | Columna: %s | Visible: %s | mostrar_despues_de: %s\n", 
        $r->id, 
        $r->empleado_id, 
        $empleado->nombre ?? 'NO ENCONTRADO',
        $r->columna, 
        $r->visible ? 'SI' : 'NO', 
        $r->mostrar_despues_de ?? 'NULL'
    );
}

echo "\n=== VERIFICANDO getColumnasOrdenadasParaMatriz ===\n";

// Probar con cada empleado que tenga configuración
$empleadosConConfig = ColumnaVisibleEjecutivo::distinct()->pluck('empleado_id');

foreach ($empleadosConConfig as $empId) {
    $empleado = Empleado::find($empId);
    echo "\n--- Empleado ID: $empId (" . ($empleado->nombre ?? 'N/A') . ") ---\n";
    
    $opcionales = ColumnaVisibleEjecutivo::getConfiguracionOpcionalesEjecutivo($empId);
    echo "Opcionales configuradas: " . json_encode($opcionales, JSON_PRETTY_PRINT) . "\n";
    
    $columnasOrdenadas = ColumnaVisibleEjecutivo::getColumnasOrdenadasParaMatriz($empId, 'es');
    $opcionalesEnMatriz = array_filter($columnasOrdenadas, fn($c) => $c['tipo'] === 'opcional');
    
    echo "Columnas OPCIONALES en matriz: \n";
    foreach ($opcionalesEnMatriz as $col) {
        echo "  - {$col['columna']} ({$col['nombre']})\n";
    }
}

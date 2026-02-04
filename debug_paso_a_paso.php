<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Logistica\ColumnaVisibleEjecutivo;

echo "=== DEBUG PASO A PASO ===\n\n";

$empleadoId = 1;
$columnasOpcionales = ColumnaVisibleEjecutivo::$columnasOpcionales;

$config = ColumnaVisibleEjecutivo::where('empleado_id', $empleadoId)
    ->whereIn('columna', array_keys($columnasOpcionales))
    ->get()
    ->keyBy('columna');

$resultado = [];
foreach ($columnasOpcionales as $columna => $nombres) {
    $configColumna = $config->get($columna);
    
    echo "Procesando: $columna\n";
    echo "  configColumna existe: " . ($configColumna ? 'SI' : 'NO') . "\n";
    
    if ($configColumna) {
        echo "  visible original: ";
        var_dump($configColumna->visible);
    }
    
    $visibleValue = $configColumna ? $configColumna->visible : false;
    echo "  visible después de ternario: ";
    var_dump($visibleValue);
    
    $resultado[$columna] = [
        'nombre_es' => $nombres['es'],
        'nombre_en' => $nombres['en'],
        'visible' => $visibleValue,
        'mostrar_despues_de' => $configColumna ? $configColumna->mostrar_despues_de : 'comentarios'
    ];
    
    echo "  resultado['$columna']['visible']: ";
    var_dump($resultado[$columna]['visible']);
    echo "\n";
}

echo "\n=== RESULTADO FINAL TIPO_CARGA ===\n";
var_dump($resultado['tipo_carga']);

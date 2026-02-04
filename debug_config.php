<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Logistica\ColumnaVisibleEjecutivo;

echo "=== PROBANDO getConfiguracionOpcionalesEjecutivo ===\n\n";

$empleadoId = 1;

$config = ColumnaVisibleEjecutivo::where('empleado_id', $empleadoId)
    ->whereIn('columna', array_keys(ColumnaVisibleEjecutivo::$columnasOpcionales))
    ->get()
    ->keyBy('columna');

echo "Tipo de config: " . gettype($config) . "\n";
echo "Clase: " . get_class($config) . "\n\n";

$tipoCarga = $config->get('tipo_carga');
echo "tipo_carga encontrado: " . ($tipoCarga ? 'SI' : 'NO') . "\n";

if ($tipoCarga) {
    echo "visible: ";
    var_dump($tipoCarga->visible);
    echo "mostrar_despues_de: " . $tipoCarga->mostrar_despues_de . "\n";
}

// Ahora probar el ternario que tiene problemas
$configColumna = $config->get('tipo_carga');
$visibleValue = $configColumna ? $configColumna->visible : false;
echo "\nValor de visible con ternario: ";
var_dump($visibleValue);

// Probando el resultado completo
$resultado = ColumnaVisibleEjecutivo::getConfiguracionOpcionalesEjecutivo($empleadoId);
echo "\n=== Resultado para tipo_carga ===\n";
echo "visible: ";
var_dump($resultado['tipo_carga']['visible']);

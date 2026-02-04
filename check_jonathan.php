<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Logistica\ColumnaVisibleEjecutivo;
use App\Models\Empleado;

echo "=== EMPLEADOS DE LOGISTICA ===\n";
$empleados = Empleado::where(function($q) {
    $q->where('posicion', 'like', '%LOGISTICA%')
      ->orWhere('posicion', 'like', '%Logistica%')
      ->orWhere('area', 'Logistica');
})->get(['id', 'nombre']);

foreach ($empleados as $e) {
    echo "ID: {$e->id} | {$e->nombre}\n";
}

echo "\n=== CONFIG DE TIPO_CARGA ===\n";
$configs = ColumnaVisibleEjecutivo::where('columna', 'tipo_carga')->get();
foreach ($configs as $c) {
    $emp = Empleado::find($c->empleado_id);
    $visible = $c->getAttribute('visible');
    echo "Empleado ID: {$c->empleado_id} (" . ($emp ? $emp->nombre : 'N/A') . ") | visible: " . ($visible ? 'SI' : 'NO') . " | despues de: {$c->mostrar_despues_de}\n";
}

echo "\n=== COLUMNAS ORDENADAS PARA JONATHAN (ID 1) ===\n";
$columnasOrdenadas = ColumnaVisibleEjecutivo::getColumnasOrdenadasParaMatriz(1, 'es');
$opcionalesEnMatriz = array_filter($columnasOrdenadas, fn($c) => $c['tipo'] === 'opcional');
foreach ($opcionalesEnMatriz as $col) {
    echo "- {$col['columna']}: {$col['nombre']}\n";
}
if (empty($opcionalesEnMatriz)) {
    echo "NO HAY COLUMNAS OPCIONALES\n";
}

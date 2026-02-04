<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Logistica\ColumnaVisibleEjecutivo;

$modelo = new ColumnaVisibleEjecutivo();

echo "Atributos del modelo:\n";
echo "fillable: " . json_encode($modelo->getFillable()) . "\n";
echo "casts: " . json_encode($modelo->getCasts()) . "\n";
echo "visible: " . json_encode($modelo->getVisible()) . "\n";  // El atributo $visible del modelo
echo "hidden: " . json_encode($modelo->getHidden()) . "\n";

// Probar directamente accediendo al campo con getAttribute
$registro = ColumnaVisibleEjecutivo::where('empleado_id', 1)->where('columna', 'tipo_carga')->first();

echo "\n=== Registro tipo_carga ===\n";
echo "Campo visible raw (getRawOriginal): ";
var_dump($registro->getRawOriginal('visible'));

echo "Campo visible con getAttribute: ";
var_dump($registro->getAttribute('visible'));

echo "Campo visible directo: ";
var_dump($registro->visible);

// Ver si hay conflicto con la propiedad $visible del modelo
$reflection = new ReflectionClass($registro);
echo "\n=== Propiedades del modelo ===\n";
foreach ($reflection->getProperties() as $prop) {
    if ($prop->getName() === 'visible') {
        echo "Propiedad \$visible encontrada!\n";
        $prop->setAccessible(true);
        echo "Valor: ";
        var_dump($prop->getValue($registro));
    }
}

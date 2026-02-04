<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICANDO CAMPO VISIBLE DIRECTO EN BD ===\n\n";

$registros = DB::table('columnas_visibles_ejecutivo')
    ->where('empleado_id', 1)
    ->get();

foreach ($registros as $r) {
    echo "Columna: {$r->columna} | visible (raw): ";
    var_dump($r->visible);
}

echo "\n=== PROBANDO CON ELOQUENT ===\n";

$registros = \App\Models\Logistica\ColumnaVisibleEjecutivo::where('empleado_id', 1)->get();

foreach ($registros as $r) {
    echo "Columna: {$r->columna} | visible (cast): ";
    var_dump($r->visible);
}

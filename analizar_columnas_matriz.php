<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Logistica\ColumnaVisibleEjecutivo;
use App\Models\Logistica\OperacionLogistica;

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  ANÁLISIS DE COLUMNAS - MATRIZ DE SEGUIMIENTO                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// 1. Columnas de la tabla en BD
echo "━━━ 1. CAMPOS EN LA TABLA operaciones_logisticas (BD) ━━━\n";
$columnasBD = DB::select('SHOW COLUMNS FROM operaciones_logisticas');
$camposBD = [];
foreach ($columnasBD as $col) {
    $camposBD[] = $col->Field;
    echo sprintf("  • %-35s\n", $col->Field);
}
echo "\nTotal: " . count($camposBD) . " campos\n\n";

// 2. Columnas predeterminadas definidas en el modelo
echo "━━━ 2. COLUMNAS PREDETERMINADAS (siempre visibles) ━━━\n";
$predeterminadas = array_keys(ColumnaVisibleEjecutivo::$columnasPredeterminadas);
foreach ($predeterminadas as $col) {
    $enBD = in_array($col, $camposBD) ? '✓' : '⚠ NO EN BD';
    echo sprintf("  • %-35s %s\n", $col, $enBD);
}
echo "\nTotal: " . count($predeterminadas) . " columnas\n\n";

// 3. Columnas opcionales definidas en el modelo
echo "━━━ 3. COLUMNAS OPCIONALES (configurables por usuario) ━━━\n";
$opcionales = array_keys(ColumnaVisibleEjecutivo::$columnasOpcionales);
foreach ($opcionales as $col) {
    $enBD = in_array($col, $camposBD) ? '✓' : '⚠ NO EN BD';
    echo sprintf("  • %-35s %s\n", $col, $enBD);
}
echo "\nTotal: " . count($opcionales) . " columnas\n\n";

// 4. Campos en BD pero NO en ninguna lista
echo "━━━ 4. CAMPOS EN BD SIN CONFIGURAR (no se muestran) ━━━\n";
$todas = array_merge($predeterminadas, $opcionales);
$sinConfigurar = array_diff($camposBD, $todas);
foreach ($sinConfigurar as $col) {
    echo sprintf("  ⚠ %-35s\n", $col);
}
echo "\nTotal: " . count($sinConfigurar) . " campos\n\n";

// 5. Verificar una operación de ejemplo
echo "━━━ 5. EJEMPLO DE DATOS EN UNA OPERACIÓN ━━━\n";
$op = OperacionLogistica::first();
if ($op) {
    echo "Operación ID: {$op->id}\n\n";
    foreach ($camposBD as $campo) {
        $valor = $op->$campo ?? 'NULL';
        if (is_object($valor)) {
            $valor = $valor->format('Y-m-d');
        }
        $tieneValor = ($valor !== 'NULL' && $valor !== '' && $valor !== null) ? '✓' : '○';
        echo sprintf("  %s %-35s = %s\n", $tieneValor, $campo, Str::limit((string)$valor, 40));
    }
}

echo "\n\n╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN                                                                ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
echo "  Campos en BD:                    " . count($camposBD) . "\n";
echo "  Columnas predeterminadas:        " . count($predeterminadas) . "\n";
echo "  Columnas opcionales:             " . count($opcionales) . "\n";
echo "  CAMPOS SIN CONFIGURAR:           " . count($sinConfigurar) . "\n";

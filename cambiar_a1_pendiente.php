<?php
// Script para cambiar A1 a pendiente

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Logistica\Pedimento;

echo "=== CAMBIANDO A1 A PENDIENTE ===\n";

$pedimentoA1 = Pedimento::where('clave', 'A1')->first();

if ($pedimentoA1) {
    echo "Estado actual de A1: {$pedimentoA1->estado_pago}\n";
    
    $pedimentoA1->estado_pago = 'pendiente';
    $pedimentoA1->save();
    
    echo "Estado cambiado a: pendiente\n";
    echo "✅ Cambio completado\n";
} else {
    echo "❌ No se encontró A1\n";
}
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== ACTUALIZACIÓN DE USUARIOS TI A ADMIN ===\n\n";

// Usuarios TI que deben ser admin
$emailsTI = [
    'sistemas@estrategiaeinnovacion.com.mx',
    'isaac.covarrubias@empresa.com',
];

DB::beginTransaction();

try {
    foreach ($emailsTI as $email) {
        $user = User::where('email', $email)->first();
        
        if ($user) {
            $roleAnterior = $user->role ?? 'NULL';
            $user->update(['role' => 'admin']);
            
            echo "✓ Actualizado: {$user->name}\n";
            echo "  Email: {$email}\n";
            echo "  Role anterior: {$roleAnterior} -> Nuevo: admin\n";
            if ($user->empleado) {
                echo "  Empleado: {$user->empleado->nombre}\n";
                echo "  Área: {$user->empleado->area}\n";
                echo "  Posición: {$user->empleado->posicion}\n";
            }
            echo "\n";
        } else {
            echo "⚠ No encontrado: {$email}\n\n";
        }
    }
    
    DB::commit();
    echo "✅ ACTUALIZACIÓN COMPLETADA\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

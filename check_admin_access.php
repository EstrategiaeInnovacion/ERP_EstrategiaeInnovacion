<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "\n=== VERIFICACION DE ACCESO ADMIN ===\n\n";

$email = 'sistemas@estrategiaeinnovacion.com.mx';
$user = User::with('empleado')->where('email', $email)->first();

if (!$user) {
    echo "❌ Usuario no encontrado con email: $email\n";
    exit(1);
}

echo "✓ Usuario encontrado:\n";
echo "  - ID: {$user->id}\n";
echo "  - Nombre: {$user->name}\n";
echo "  - Email: {$user->email}\n";
echo "  - Role: {$user->role}\n";
echo "  - isAdmin(): " . ($user->isAdmin() ? 'SI' : 'NO') . "\n\n";

if ($user->empleado) {
    echo "✓ Empleado asociado:\n";
    echo "  - ID: {$user->empleado->id}\n";
    echo "  - Nombre: {$user->empleado->nombre}\n";
    echo "  - Area: {$user->empleado->area}\n";
    echo "  - Posición: {$user->empleado->posicion}\n\n";
} else {
    echo "❌ No tiene empleado asociado\n\n";
}

// Simular la lógica del middleware
echo "=== VERIFICACION DE MIDDLEWARE ===\n\n";

$passesMiddleware = false;
$reason = '';

if ($user->role !== 'admin') {
    $reason = "El usuario no tiene role='admin'";
} else {
    echo "✓ Usuario tiene role='admin'\n";
    
    if (!$user->empleado) {
        $reason = "El usuario no tiene empleado asociado";
    } else {
        $area = $user->empleado->area;
        $posicion = $user->empleado->posicion;
        
        echo "✓ Usuario tiene empleado asociado\n";
        echo "  - Area: '$area'\n";
        echo "  - Posición: '$posicion'\n\n";
        
        if ($area === 'Sistemas') {
            echo "✓ Area es 'Sistemas' - PASA\n";
            $passesMiddleware = true;
        } elseif ($posicion === 'TI') {
            echo "✓ Posición es 'TI' - PASA\n";
            $passesMiddleware = true;
        } elseif ($posicion === 'IT') {
            echo "✓ Posición es 'IT' - PASA\n";
            $passesMiddleware = true;
        } else {
            $reason = "Area no es 'Sistemas' y Posición no es 'TI' ni 'IT'";
        }
    }
}

echo "\n=== RESULTADO FINAL ===\n";
if ($passesMiddleware) {
    echo "✅ El usuario DEBE poder acceder al panel admin\n";
} else {
    echo "❌ El usuario NO puede acceder al panel admin\n";
    echo "   Razón: $reason\n";
}

echo "\n=== VERIFICACION DE RUTAS ===\n";
echo "Route: admin.dashboard - " . route('admin.dashboard') . "\n";
echo "Route: admin.tickets.index - " . route('admin.tickets.index') . "\n";
echo "Route: admin.users - " . route('admin.users') . "\n";

echo "\n";

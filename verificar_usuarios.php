<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Empleado;
use App\Models\EmpleadoBaja;

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICACIÓN Y CORRECCIÓN DE USUARIOS/BAJAS                           ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// 1. Verificar Maria Fernanda
echo "━━━ MARIA FERNANDA SANCHEZ MIRANDA ━━━\n";
$userMaria = User::where('email', 'tradecompliance4@estrategiaeinnovacion.com.mx')->first();
if ($userMaria) {
    echo "  Usuario ID: {$userMaria->id}\n";
    echo "  Nombre: {$userMaria->name}\n";
    echo "  Email: {$userMaria->email}\n";
    echo "  Status actual: {$userMaria->status}\n";
    
    // Cambiar status a rejected
    $userMaria->update(['status' => 'rejected']);
    echo "  ✓ Status cambiado a: rejected\n";
}

$empleadoMaria = Empleado::where('correo', 'tradecompliance4@estrategiaeinnovacion.com.mx')->first();
if ($empleadoMaria) {
    echo "  ⚠ Aún existe en tabla empleados - eliminando...\n";
    $empleadoMaria->delete();
    echo "  ✓ Eliminado de empleados\n";
}

echo "\n";

// 2. Verificar todas las bajas y sus usuarios
echo "━━━ VERIFICANDO USUARIOS DE BAJAS ━━━\n";
$bajas = EmpleadoBaja::all();
foreach ($bajas as $baja) {
    echo "\n  {$baja->nombre} ({$baja->correo})\n";
    
    $user = User::where('email', $baja->correo)->first();
    if ($user) {
        echo "    User ID: {$user->id} | Status: {$user->status}\n";
        if ($user->status !== 'rejected') {
            $user->update(['status' => 'rejected']);
            echo "    ✓ Cambiado a rejected\n";
        }
        // Actualizar el user_id en la baja si no está
        if (!$baja->user_id) {
            $baja->update(['user_id' => $user->id]);
            echo "    ✓ user_id actualizado en baja\n";
        }
    } else {
        echo "    ⚠ No tiene usuario asociado\n";
    }
}

echo "\n";

// 3. Mostrar resumen
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN FINAL                                                         ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

echo "━━━ USUARIOS APROBADOS ━━━\n";
$usersApproved = User::where('status', 'approved')->get();
foreach ($usersApproved as $u) {
    $emp = Empleado::where('user_id', $u->id)->first();
    echo sprintf("  • %-35s %-45s %s\n", $u->name, $u->email, $emp ? '(tiene empleado)' : '');
}
echo "Total: " . $usersApproved->count() . "\n\n";

echo "━━━ USUARIOS RECHAZADOS (BAJAS) ━━━\n";
$usersRejected = User::where('status', 'rejected')->get();
foreach ($usersRejected as $u) {
    $baja = EmpleadoBaja::where('user_id', $u->id)->orWhere('correo', $u->email)->first();
    echo sprintf("  • %-35s %-45s\n", $u->name, $u->email);
    if ($baja) {
        echo sprintf("    Motivo: %s | Fecha: %s\n", $baja->motivo_baja ?? 'N/A', $baja->fecha_baja ?? 'N/A');
    }
}
echo "Total: " . $usersRejected->count() . "\n";

echo "\n✅ VERIFICACIÓN COMPLETADA\n";

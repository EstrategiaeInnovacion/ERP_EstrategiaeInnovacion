<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empleado;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear o actualizar el Usuario Admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@estrategiaeinnovacion.com.mx'],
            [
                'name' => 'Administrador Principal',
                'password' => Hash::make('password'), // Cambiar por contraseña segura
                'role' => 'admin',
                'status' => 'approved',
            ]
        );

        // 2. Crear o actualizar la ficha de Empleado asociada
        // IMPORTANTE: Aquí es donde se define el AREA que busca el middleware
        Empleado::updateOrCreate(
            ['user_id' => $adminUser->id],
            [
                'nombre' => $adminUser->name,
                'correo' => $adminUser->email,
                'area' => 'Sistemas', // <--- ESTO ES LA CLAVE. Si tu middleware busca 'Sistemas', ponlo aquí.
                'posicion' => 'Director de TI',
                // Otros campos pueden ser null
            ]
        );
    }
}
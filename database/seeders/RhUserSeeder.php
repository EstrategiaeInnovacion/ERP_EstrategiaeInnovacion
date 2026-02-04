<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empleado;
use Illuminate\Support\Facades\Hash;

class RhUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear el usuario de acceso (Login)
        $rhUser = User::firstOrCreate(
            ['email' => 'rh@estrategiaeinnovacion.com.mx'], // Correo sugerido
            [
                'name' => 'Gerencia RH',
                'password' => Hash::make('password'), // Contraseña temporal
                'role' => 'user', // Rol 'user' es suficiente si el middleware controla el acceso por área
                'status' => 'approved', // Importante: debe estar aprobado para hacer login
            ]
        );

        // 2. Crear la ficha de empleado asociada (Permisos)
        Empleado::updateOrCreate(
            ['user_id' => $rhUser->id],
            [
                'nombre' => $rhUser->name,
                'correo' => $rhUser->email,
                // IMPORTANTE: Tu AreaRHMiddleware busca específicamente "rh" o "recursos humanos"
                'area' => 'Recursos Humanos', 
                'posicion' => 'Gerente de Recursos Humanos',
            ]
        );
    }
}
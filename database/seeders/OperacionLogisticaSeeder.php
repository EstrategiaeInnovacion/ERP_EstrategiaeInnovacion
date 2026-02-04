<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Logistica\OperacionLogistica;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OperacionLogisticaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Asegurar que existan empleados
        $empleados = Empleado::all();

        if ($empleados->isEmpty()) {
            // Crear usuario y empleado dummy si no hay nada
            $user = User::firstOrCreate(
                ['email' => 'logistica@test.com'],
                [
                    'name' => 'Ejecutivo Logística',
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'status' => 'approved'
                ]
            );

            $empleado = Empleado::create([
                'user_id' => $user->id,
                'nombre' => $user->name,
                'correo' => $user->email,
                'area' => 'Logística',
                'posicion' => 'Ejecutivo',
            ]);
            
            $empleados = collect([$empleado]);
        }

        // 2. Crear operaciones asignando el NOMBRE del ejecutivo
        OperacionLogistica::factory(50)->make()->each(function ($operacion) use ($empleados) {
            // Seleccionar un empleado al azar
            $empleadoAsignado = $empleados->random();

            // Asignar el nombre a la columna 'ejecutivo' y guardar
            $operacion->ejecutivo = $empleadoAsignado->nombre;
            
            // Si tienes lógica de cálculo de días, ejecútala aquí antes de guardar
            if (method_exists($operacion, 'calcularDiasTransito')) {
                $operacion->calcularDiasTransito();
            }
            
            $operacion->save();
        });

        $this->command->info('Se han creado 50 operaciones logísticas correctamente.');
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CARGA DE EMPLEADOS REALES (MANUAL)
        $empleados = [
            // ===== DIRECCIÓN =====
            [
                'id_empleado' => '0',
                'nombre' => 'Amos Guillermo Aguilera Gonzalez',
                'correo' => 'guillermo.aguilera@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Direccion',
                'supervisor' => null,
            ],

            // ===== REPORTAN A GUILLERMO =====
            [
                'id_empleado' => '36',
                'nombre' => 'Liliana Hernandez Castilla',
                'correo' => 'liliana.hernandez@empresa.com',
                'area' => 'Recursos Humanos',
                'posicion' => 'Administracion RH',
                'supervisor' => 'Guillermo Aguilera',
            ],
            [
                'id_empleado' => '23',
                'nombre' => 'Silvestre Reyes Castillo',
                'correo' => 'silvestre.castillo@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Auditoria', 
                'supervisor' => 'Guillermo Aguilera',
            ],
            [
                'id_empleado' => '30',
                'nombre' => 'Nancy Beatriz Gomez Hernandez',
                'correo' => 'nancy.gomez@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Logistica',
                'supervisor' => 'Guillermo Aguilera',
            ],
            [
                'id_empleado' => '56',
                'nombre' => 'Jazzman Jerssain Aguilar Cisneros',
                'correo' => 'jazzman.aguilar@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Legal',
                'supervisor' => 'Guillermo Aguilera',
            ],

            // ===== REPORTAN A SILVESTRE =====
            [
                'id_empleado' => '57',
                'nombre' => 'Mario Mojica Morales',
                'correo' => 'mario.mojica@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Post-Operacion', // Ajustado
                'supervisor' => 'Guillermo Aguilera',
            ],
            [
                'id_empleado' => '74',
                'nombre' => 'Aneth Alejandra Herrera Hernandez',
                'correo' => 'aneth.herrera@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Post-Operacion', // Ajustado
                'supervisor' => 'Mario Mojica Morales',
            ],
            // ===== REPORTAN A NANCY =====
            [
                'id_empleado' => '22',
                'nombre' => 'Zaira Isabel Martinez Urbina',
                'correo' => 'zaira.martinez@empresa.com',
                'area' => 'Chronos Fullfillment',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],
            [
                'id_empleado' => '60',
                'nombre' => 'Luis Eduardo Inclan Soriano',
                'correo' => 'luis.inclan@empresa.com',
                'area' => 'Siegwerk',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],
            [
                'id_empleado' => '68',
                'nombre' => 'Guadalupe Jacqueline Mendoza Rodriguez',
                'correo' => 'guadalupe.mendoza@empresa.com',
                'area' => 'AGC',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],
            [
                'id_empleado' => '73',
                'nombre' => 'Mariana Rodriguez Rueda',
                'correo' => 'mariana.rodriguez@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],
            [
                'id_empleado' => '78',
                'nombre' => 'Oscar Eduardo Morin Carrizales',
                'correo' => 'oscar.morin@empresa.com',
                'area' => 'PPM Industries',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],
            [
                'id_empleado' => '53',
                'nombre' => 'Alisson Cassiel Pineda Martinez',
                'correo' => 'alisson.pineda@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],
            [
                'id_empleado' => '86',
                'nombre' => 'Ivan Rodriguez Juarez',
                'correo' => 'ivan.rodriguez@empresa.com',
                'area' => 'Sarrel',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],
            [
                'id_empleado' => '87',
                'nombre' => 'Karen Cristina Bonal Mata',
                'correo' => 'karen.bonal@empresa.com',
                'area' => 'EB-Tecnica',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],
            [
                'id_empleado' => '96',
                'nombre' => 'Jacob de Jesus Medina Ramirez',
                'correo' => 'jacob.medina@empresa.com',
                'area' => 'AsiaWay',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],
            [
                'id_empleado' => '99',
                'nombre' => 'Fatima Esther Torres Arriaga',
                'correo' => 'fatima.torres@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Logistica',
                'supervisor' => 'Nancy Beatriz Gomez Hernandez',
            ],

            // ===== REPORTAN A LILIANA =====
            [
                'id_empleado' => '84',
                'nombre' => 'Mariana Calderón Ojeda',
                'correo' => 'mariana.calderon@empresa.com',
                'area' => 'Recursos Humanos',
                'posicion' => 'Administracion RH', // Ajustado de 'RR.HH.'
                'supervisor' => 'Liliana Hernandez Castilla',
            ],
            [
                'id_empleado' => '95',
                'nombre' => 'Jonathan Loredo Palacios',
                'correo' => 'jonathan.loredo@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'TI', // Ajustado de 'IT'
                'supervisor' => 'Liliana Hernandez Castilla',
            ],
            [
                'id_empleado' => '103',
                'nombre' => 'Isaac Covarrubias Quintero',
                'correo' => 'isaac.covarrubias@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'TI', // Ajustado de 'IT'
                'supervisor' => 'Liliana Hernandez Castilla',
            ],

            // ===== REPORTAN A MARIO =====
            [
                'id_empleado' => '90',
                'nombre' => 'Jessica Anahi Esparza Gonzalez',
                'correo' => 'jessica.esparza@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Anexo 24',
                'supervisor' => 'Mario Mojica Morales',
            ],
            [
                'id_empleado' => '98',
                'nombre' => 'Felipe de Jesus Rodriguez Ledesma',
                'correo' => 'felipe.rodriguez@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Anexo 24',
                'supervisor' => 'Mario Mojica Morales',
            ],
            [
                'id_empleado' => '100',
                'nombre' => 'Mayra Susana Coreño Arriaga',
                'correo' => 'mayra.coreno@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Post-Operacion',
                'supervisor' => 'Mario Mojica Morales',
            ],
            [
                'id_empleado' => '101',
                'nombre' => 'Erika Liliana Mireles Sanchez',
                'correo' => 'erika.mireles@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Anexo 24',
                'supervisor' => 'Mario Mojica Morales',
            ],

            // ===== REPORTAN A JAZZMAN =====
            [
                'id_empleado' => '80',
                'nombre' => 'Ana Sofia Cuello Aguilar',
                'correo' => 'ana.cuello@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Legal',
                'supervisor' => 'Jazzman Jerssain Aguilar Cisneros',
            ],
            [
                'id_empleado' => '97',
                'nombre' => 'Jesus David Rivera Romero',
                'correo' => 'jesus.rivera@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Legal',
                'supervisor' => 'Jazzman Jerssain Aguilar Cisneros',
            ],
            [
                'id_empleado' => '102',
                'nombre' => 'Carlos Alfonso Rivera Moran',
                'correo' => 'carlos.rivera@empresa.com',
                'area' => 'Estrategia e Innovacion',
                'posicion' => 'Legal',
                'supervisor' => 'Jazzman Jerssain Aguilar Cisneros',
            ],
        ];

        $mapaEmpleados = [];

        // Crear/Actualizar Empleados Reales
        foreach ($empleados as $emp) {
            // Normalizar nombre para búsqueda
            $nombreNormalizado = strtolower(trim($emp['nombre']));
            
            // 1. BUSCAR EMPLEADO POR ID_EMPLEADO (prioridad)
            $empleadoExistente = Empleado::where('id_empleado', $emp['id_empleado'])->first();
            
            // 2. Si no existe, buscar por nombre similar (más estricto)
            if (!$empleadoExistente) {
                $empleadoExistente = Empleado::whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreNormalizado])->first();
            }
            
            // 3. BUSCAR USUARIO
            if ($empleadoExistente && $empleadoExistente->user_id) {
                // Ya tiene usuario asignado, usarlo
                $user = User::find($empleadoExistente->user_id);
                if ($user) {
                    $user->update(['name' => $emp['nombre']]);
                }
            } else {
                // Buscar usuario por email o nombre
                $user = User::where('email', $empleadoExistente ? $empleadoExistente->correo : $emp['correo'])->first();
                
                if (!$user) {
                    // Buscar por similitud de nombre
                    $user = User::whereRaw('LOWER(TRIM(name)) = ?', [$nombreNormalizado])->first();
                }
                
                if ($user) {
                    // Usuario existe: solo actualizar nombre
                    $user->update(['name' => $emp['nombre']]);
                } else {
                    // Usuario no existe: crear nuevo
                    $user = User::create([
                        'email' => $emp['correo'],
                        'name' => $emp['nombre'],
                        'password' => Hash::make('password'),
                    ]);
                }
            }

            // 4. ACTUALIZAR O CREAR EMPLEADO
            if ($empleadoExistente) {
                // Empleado existe: actualizar toda la info, MANTENER CORREO REAL
                $empleadoExistente->update([
                    'id_empleado' => $emp['id_empleado'],
                    'nombre' => $emp['nombre'],
                    'area' => $emp['area'],
                    'posicion' => $emp['posicion'],
                    'user_id' => $user->id,
                    // NO actualizar correo para mantener el real
                ]);
                $empleado = $empleadoExistente;
            } else {
                // Empleado no existe: crear nuevo
                $empleado = Empleado::create([
                    'id_empleado' => $emp['id_empleado'],
                    'nombre' => $emp['nombre'],
                    'correo' => $emp['correo'],
                    'area' => $emp['area'],
                    'posicion' => $emp['posicion'],
                    'telefono' => null,
                    'direccion' => null,
                    'correo_personal' => null,
                    'foto_path' => null,
                    'subdepartamento_id' => null,
                    'user_id' => $user->id,
                ]);
            }

            $mapaEmpleados[$emp['nombre']] = $empleado->id;
            
            // 5. ASIGNAR ROL ADMIN SI ES NECESARIO
            // Solo: Nancy (jefe de logística), Administración RH, y TI
            $posicionNorm = strtolower(trim($emp['posicion']));
            $nombreNorm = strtolower(trim($emp['nombre']));
            
            // Nancy es la única admin de logística
            if ($nombreNorm === 'nancy beatriz gomez hernandez') {
                $user->update(['role' => 'admin']);
            }
            // Administración RH y TI también son admin
            elseif (str_contains($posicionNorm, 'administracion rh') || str_contains($posicionNorm, 'ti')) {
                $user->update(['role' => 'admin']);
            }
        }

        // Asignar Supervisores
        foreach ($empleados as $emp) {
            if ($emp['supervisor']) {
                Empleado::where('id_empleado', $emp['id_empleado'])
                    ->update([
                        'supervisor_id' => $mapaEmpleados[$emp['supervisor']] ?? null
                    ]);
            }
        }
    }
}
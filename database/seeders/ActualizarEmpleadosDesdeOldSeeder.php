<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActualizarEmpleadosDesdeOldSeeder extends Seeder
{
    /**
     * Actualiza id_empleado, posicion y area EN LA BD OLD
     * usando los datos del Seeder de Empleados como fuente correcta.
     * 
     * SEEDER EMPLEADOS (correcto) → BD OLD (actualiza)
     * 
     * Uso: php artisan db:seed --class=ActualizarEmpleadosDesdeOldSeeder
     */
    
    // Lista de empleados con datos correctos (copiada del EmpleadoSeeder)
    private array $empleadosSeeder = [
        ['id_empleado' => '0', 'nombre' => 'Amos Guillermo Aguilera Gonzalez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Direccion'],
        ['id_empleado' => '36', 'nombre' => 'Liliana Hernandez Castilla', 'area' => 'Recursos Humanos', 'posicion' => 'Administracion RH'],
        ['id_empleado' => '23', 'nombre' => 'Silvestre Reyes Castillo', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Auditoria'],
        ['id_empleado' => '30', 'nombre' => 'Nancy Beatriz Gomez Hernandez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica'],
        ['id_empleado' => '56', 'nombre' => 'Jazzman Jerssain Aguilar Cisneros', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal'],
        ['id_empleado' => '57', 'nombre' => 'Mario Mojica Morales', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Post-Operacion'],
        ['id_empleado' => '74', 'nombre' => 'Aneth Alejandra Herrera Hernandez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Post-Operacion'],
        ['id_empleado' => '22', 'nombre' => 'Zaira Isabel Martinez Urbina', 'area' => 'Chronos Fullfillment', 'posicion' => 'Logistica'],
        ['id_empleado' => '60', 'nombre' => 'Luis Eduardo Inclan Soriano', 'area' => 'Siegwerk', 'posicion' => 'Logistica'],
        ['id_empleado' => '68', 'nombre' => 'Guadalupe Jacqueline Mendoza Rodriguez', 'area' => 'AGC', 'posicion' => 'Logistica'],
        ['id_empleado' => '73', 'nombre' => 'Mariana Rodriguez Rueda', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica'],
        ['id_empleado' => '78', 'nombre' => 'Oscar Eduardo Morin Carrizales', 'area' => 'PPM Industries', 'posicion' => 'Logistica'],
        ['id_empleado' => '53', 'nombre' => 'Alisson Cassiel Pineda Martinez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica'],
        ['id_empleado' => '86', 'nombre' => 'Ivan Rodriguez Juarez', 'area' => 'Sarrel', 'posicion' => 'Logistica'],
        ['id_empleado' => '87', 'nombre' => 'Karen Cristina Bonal Mata', 'area' => 'EB-Tecnica', 'posicion' => 'Logistica'],
        ['id_empleado' => '96', 'nombre' => 'Jacob de Jesus Medina Ramirez', 'area' => 'AsiaWay', 'posicion' => 'Logistica'],
        ['id_empleado' => '99', 'nombre' => 'Fatima Esther Torres Arriaga', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Logistica'],
        ['id_empleado' => '84', 'nombre' => 'Mariana Calderón Ojeda', 'area' => 'Recursos Humanos', 'posicion' => 'Administracion RH'],
        ['id_empleado' => '95', 'nombre' => 'Jonathan Loredo Palacios', 'area' => 'Estrategia e Innovacion', 'posicion' => 'TI'],
        ['id_empleado' => '103', 'nombre' => 'Isaac Covarrubias Quintero', 'area' => 'Estrategia e Innovacion', 'posicion' => 'TI'],
        ['id_empleado' => '90', 'nombre' => 'Jessica Anahi Esparza Gonzalez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Anexo 24'],
        ['id_empleado' => '98', 'nombre' => 'Felipe de Jesus Rodriguez Ledesma', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Anexo 24'],
        ['id_empleado' => '100', 'nombre' => 'Mayra Susana Coreño Arriaga', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Post-Operacion'],
        ['id_empleado' => '101', 'nombre' => 'Erika Liliana Mireles Sanchez', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Anexo 24'],
        ['id_empleado' => '80', 'nombre' => 'Ana Sofia Cuello Aguilar', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal'],
        ['id_empleado' => '97', 'nombre' => 'Jesus David Rivera Romero', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal'],
        ['id_empleado' => '102', 'nombre' => 'Carlos Alfonso Rivera Moran', 'area' => 'Estrategia e Innovacion', 'posicion' => 'Legal'],
    ];

    public function run(): void
    {
        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║  ACTUALIZAR BD OLD (id_empleado, posicion, area)           ║');
        $this->command->info('║  Desde Seeder de Empleados                                 ║');
        $this->command->info('╚════════════════════════════════════════════════════════════╝');
        $this->command->info('');

        // Obtener empleados de la BD OLD
        $empleadosOld = DB::connection('mysql_old')->table('empleados')->get();
        
        $this->command->info("Empleados en Seeder: " . count($this->empleadosSeeder));
        $this->command->info("Empleados en BD OLD: " . $empleadosOld->count());
        $this->command->info('');

        $actualizados = 0;
        $noEncontrados = [];

        foreach ($this->empleadosSeeder as $empSeeder) {
            $nombreSeeder = $empSeeder['nombre'];
            
            // Buscar coincidencia en BD OLD
            $empOld = $this->buscarEnOld($nombreSeeder, $empleadosOld);
            
            if ($empOld) {
                // Actualizar id_empleado, posicion y area EN BD OLD
                $cambios = [];
                
                if ($empOld->id_empleado != $empSeeder['id_empleado']) {
                    $cambios['id_empleado'] = $empSeeder['id_empleado'];
                }
                if ($empOld->posicion != $empSeeder['posicion']) {
                    $cambios['posicion'] = $empSeeder['posicion'];
                }
                if ($empOld->area != $empSeeder['area']) {
                    $cambios['area'] = $empSeeder['area'];
                }
                
                if (!empty($cambios)) {
                    DB::connection('mysql_old')
                        ->table('empleados')
                        ->where('id', $empOld->id)
                        ->update($cambios);
                    
                    $actualizados++;
                    $this->command->info("✓ {$nombreSeeder}");
                    $this->command->info("  → Encontrado como: {$empOld->nombre} (ID: {$empOld->id})");
                    foreach ($cambios as $campo => $valor) {
                        $valorAnterior = $empOld->$campo ?? 'NULL';
                        $this->command->info("    {$campo}: {$valorAnterior} → {$valor}");
                    }
                } else {
                    $this->command->line("= {$nombreSeeder} (sin cambios)");
                }
            } else {
                $noEncontrados[] = $nombreSeeder;
                $this->command->warn("✗ {$nombreSeeder} - NO encontrado en BD OLD");
            }
        }

        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║  RESUMEN                                                   ║');
        $this->command->info('╚════════════════════════════════════════════════════════════╝');
        $this->command->info("✓ Actualizados en BD OLD: {$actualizados}");
        $this->command->info("✗ No encontrados: " . count($noEncontrados));
        
        if (count($noEncontrados) > 0) {
            $this->command->warn('');
            $this->command->warn('Empleados del Seeder no encontrados en BD OLD:');
            foreach ($noEncontrados as $nombre) {
                $this->command->warn("  - {$nombre}");
            }
        }
    }

    /**
     * Busca un empleado en la colección de BD OLD usando múltiples estrategias
     */
    private function buscarEnOld(string $nombreSeeder, $empleadosOld)
    {
        $nombreNorm = $this->normalizar($nombreSeeder);
        $palabrasSeeder = $this->obtenerPalabras($nombreNorm);
        
        // Estrategia 1: Coincidencia exacta (normalizada)
        foreach ($empleadosOld as $emp) {
            if ($this->normalizar($emp->nombre) === $nombreNorm) {
                return $emp;
            }
        }
        
        // Estrategia 2: Todas las palabras del seeder están en el nombre OLD
        foreach ($empleadosOld as $emp) {
            $nombreOldNorm = $this->normalizar($emp->nombre);
            $todasCoinciden = true;
            
            foreach ($palabrasSeeder as $palabra) {
                if (strlen($palabra) >= 3 && strpos($nombreOldNorm, $palabra) === false) {
                    $todasCoinciden = false;
                    break;
                }
            }
            
            if ($todasCoinciden && count($palabrasSeeder) >= 2) {
                return $emp;
            }
        }
        
        // Estrategia 3: Primer nombre + último apellido
        if (count($palabrasSeeder) >= 2) {
            $primerNombre = $palabrasSeeder[0];
            $ultimoApellido = end($palabrasSeeder);
            
            foreach ($empleadosOld as $emp) {
                $nombreOldNorm = $this->normalizar($emp->nombre);
                
                // El nombre OLD debe empezar con el primer nombre Y contener el último apellido
                if (strpos($nombreOldNorm, $primerNombre) === 0 && 
                    strpos($nombreOldNorm, $ultimoApellido) !== false) {
                    return $emp;
                }
            }
        }
        
        // Estrategia 4: Primer nombre + cualquier palabra que coincida (>= 4 chars)
        if (count($palabrasSeeder) >= 2) {
            $primerNombre = $palabrasSeeder[0];
            
            foreach ($empleadosOld as $emp) {
                $nombreOldNorm = $this->normalizar($emp->nombre);
                $palabrasOld = $this->obtenerPalabras($nombreOldNorm);
                
                // Primer nombre debe coincidir al inicio
                if (strpos($nombreOldNorm, $primerNombre) !== 0) {
                    continue;
                }
                
                // Al menos un apellido (palabra >= 4 chars) debe coincidir
                foreach ($palabrasSeeder as $i => $palabra) {
                    if ($i === 0) continue; // Saltar primer nombre
                    if (strlen($palabra) >= 4) {
                        foreach ($palabrasOld as $j => $palabraOld) {
                            if ($j === 0) continue; // Saltar primer nombre
                            if (strlen($palabraOld) >= 4 && $palabra === $palabraOld) {
                                return $emp;
                            }
                        }
                    }
                }
            }
        }
        
        // Estrategia 5: Solo primer nombre si es único y tiene >= 5 caracteres
        if (!empty($palabrasSeeder[0]) && strlen($palabrasSeeder[0]) >= 5) {
            $primerNombre = $palabrasSeeder[0];
            $coincidencias = [];
            
            foreach ($empleadosOld as $emp) {
                $nombreOldNorm = $this->normalizar($emp->nombre);
                if (strpos($nombreOldNorm, $primerNombre) === 0) {
                    $coincidencias[] = $emp;
                }
            }
            
            // Solo si hay exactamente una coincidencia
            if (count($coincidencias) === 1) {
                return $coincidencias[0];
            }
        }
        
        return null;
    }

    /**
     * Normaliza un nombre: minúsculas, sin acentos, sin caracteres especiales
     */
    private function normalizar(string $nombre): string
    {
        $nombre = mb_strtolower(trim($nombre), 'UTF-8');
        
        // Remover acentos
        $acentos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ];
        
        $nombre = strtr($nombre, $acentos);
        
        // Solo letras y espacios
        $nombre = preg_replace('/[^a-z\s]/', '', $nombre);
        
        // Normalizar espacios múltiples
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        
        return trim($nombre);
    }

    /**
     * Obtiene las palabras de un nombre normalizado
     */
    private function obtenerPalabras(string $nombreNorm): array
    {
        return array_filter(explode(' ', $nombreNorm), fn($p) => strlen($p) >= 2);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SincronizarIdEmpleadoSeeder extends Seeder
{
    /**
     * Sincroniza el id_empleado en la BD local del servidor (estrategias_innovacion_v2)
     * usando los datos correctos del EmpleadoSeeder.
     * 
     * Busca por nombre (completo o parcial, ignora mayúsculas/acentos)
     * y actualiza el id_empleado correspondiente.
     * 
     * EJECUTAR EN EL SERVIDOR:
     * php artisan db:seed --class=SincronizarIdEmpleadoSeeder
     */
    
    // Lista de empleados con id_empleado correcto (del EmpleadoSeeder)
    private array $empleadosCorrectos = [
        ['id_empleado' => '0', 'nombre' => 'Amos Guillermo Aguilera Gonzalez'],
        ['id_empleado' => '36', 'nombre' => 'Liliana Hernandez Castilla'],
        ['id_empleado' => '23', 'nombre' => 'Silvestre Reyes Castillo'],
        ['id_empleado' => '30', 'nombre' => 'Nancy Beatriz Gomez Hernandez'],
        ['id_empleado' => '56', 'nombre' => 'Jazzman Jerssain Aguilar Cisneros'],
        ['id_empleado' => '57', 'nombre' => 'Mario Mojica Morales'],
        ['id_empleado' => '74', 'nombre' => 'Aneth Alejandra Herrera Hernandez'],
        ['id_empleado' => '22', 'nombre' => 'Zaira Isabel Martinez Urbina'],
        ['id_empleado' => '60', 'nombre' => 'Luis Eduardo Inclan Soriano'],
        ['id_empleado' => '68', 'nombre' => 'Guadalupe Jacqueline Mendoza Rodriguez'],
        ['id_empleado' => '73', 'nombre' => 'Mariana Rodriguez Rueda'],
        ['id_empleado' => '78', 'nombre' => 'Oscar Eduardo Morin Carrizales'],
        ['id_empleado' => '53', 'nombre' => 'Alisson Cassiel Pineda Martinez'],
        ['id_empleado' => '86', 'nombre' => 'Ivan Rodriguez Juarez'],
        ['id_empleado' => '87', 'nombre' => 'Karen Cristina Bonal Mata'],
        ['id_empleado' => '96', 'nombre' => 'Jacob de Jesus Medina Ramirez'],
        ['id_empleado' => '99', 'nombre' => 'Fatima Esther Torres Arriaga'],
        ['id_empleado' => '84', 'nombre' => 'Mariana Calderón Ojeda'],
        ['id_empleado' => '95', 'nombre' => 'Jonathan Loredo Palacios'],
        ['id_empleado' => '103', 'nombre' => 'Isaac Covarrubias Quintero'],
        ['id_empleado' => '90', 'nombre' => 'Jessica Anahi Esparza Gonzalez'],
        ['id_empleado' => '98', 'nombre' => 'Felipe de Jesus Rodriguez Ledesma'],
        ['id_empleado' => '100', 'nombre' => 'Mayra Susana Coreño Arriaga'],
        ['id_empleado' => '101', 'nombre' => 'Erika Liliana Mireles Sanchez'],
        ['id_empleado' => '80', 'nombre' => 'Ana Sofia Cuello Aguilar'],
        ['id_empleado' => '102', 'nombre' => 'Carlos Alfonso Rivera Moran'],
    ];

    public function run(): void
    {
        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║  SINCRONIZAR id_empleado EN BD LOCAL                       ║');
        $this->command->info('║  Seeder → estrategias_innovacion_v2 (127.0.0.1)            ║');
        $this->command->info('╚════════════════════════════════════════════════════════════╝');
        $this->command->info('');

        // Obtener empleados de la BD local
        $empleadosBD = DB::table('empleados')->get();

        $this->command->info("Empleados en Seeder: " . count($this->empleadosCorrectos));
        $this->command->info("Empleados en BD Local: " . $empleadosBD->count());
        $this->command->info('');
        $this->command->info('─────────────────────────────────────────────────────────────');

        $actualizados = 0;
        $sinCambios = 0;
        $noEncontrados = [];
        $multiples = [];

        foreach ($this->empleadosCorrectos as $empSeeder) {
            $nombreSeeder = $empSeeder['nombre'];
            $idEmpleadoCorrecto = $empSeeder['id_empleado'];
            
            // Buscar coincidencia en BD local
            $resultado = $this->buscarEnBD($nombreSeeder, $empleadosBD);
            
            if ($resultado['tipo'] === 'unico') {
                $empBD = $resultado['empleado'];
                
                // Verificar si hay cambio
                if ($empBD->id_empleado != $idEmpleadoCorrecto) {
                    $idAnterior = $empBD->id_empleado ?? 'NULL';
                    
                    DB::table('empleados')
                        ->where('id', $empBD->id)
                        ->update(['id_empleado' => $idEmpleadoCorrecto]);
                    
                    $actualizados++;
                    $this->command->info("✓ {$nombreSeeder}");
                    $this->command->info("  → Encontrado como: {$empBD->nombre}");
                    $this->command->info("  → id_empleado: {$idAnterior} → {$idEmpleadoCorrecto}");
                } else {
                    $sinCambios++;
                    $this->command->line("= {$nombreSeeder} (id_empleado ya correcto: {$idEmpleadoCorrecto})");
                }
            } elseif ($resultado['tipo'] === 'multiple') {
                $multiples[] = [
                    'nombre' => $nombreSeeder,
                    'id_empleado' => $idEmpleadoCorrecto,
                    'coincidencias' => $resultado['coincidencias']
                ];
                $this->command->warn("? {$nombreSeeder} - Múltiples coincidencias");
            } else {
                $noEncontrados[] = ['nombre' => $nombreSeeder, 'id_empleado' => $idEmpleadoCorrecto];
                $this->command->error("✗ {$nombreSeeder} - NO encontrado en BD");
            }
        }

        // Resumen
        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║  RESUMEN                                                   ║');
        $this->command->info('╚════════════════════════════════════════════════════════════╝');
        $this->command->info("✓ Actualizados: {$actualizados}");
        $this->command->info("= Sin cambios:  {$sinCambios}");
        $this->command->warn("? Múltiples:    " . count($multiples));
        $this->command->error("✗ No encontrados: " . count($noEncontrados));

        // Detalles de no encontrados
        if (count($noEncontrados) > 0) {
            $this->command->info('');
            $this->command->warn('Empleados del Seeder no encontrados en BD:');
            foreach ($noEncontrados as $emp) {
                $this->command->warn("  - {$emp['nombre']} (id_empleado: {$emp['id_empleado']})");
            }
        }

        // Detalles de múltiples coincidencias
        if (count($multiples) > 0) {
            $this->command->info('');
            $this->command->warn('Empleados con múltiples coincidencias (revisar manualmente):');
            foreach ($multiples as $item) {
                $this->command->warn("  {$item['nombre']} (debe ser id_empleado: {$item['id_empleado']}):");
                foreach ($item['coincidencias'] as $coincidencia) {
                    $this->command->warn("    → ID:{$coincidencia->id} - {$coincidencia->nombre}");
                }
            }
        }
    }

    /**
     * Busca un empleado en la BD usando múltiples estrategias de coincidencia
     */
    private function buscarEnBD(string $nombreSeeder, $empleadosBD): array
    {
        $nombreNorm = $this->normalizar($nombreSeeder);
        $palabrasSeeder = $this->obtenerPalabras($nombreNorm);
        
        // Estrategia 1: Coincidencia exacta (normalizada)
        foreach ($empleadosBD as $emp) {
            if ($this->normalizar($emp->nombre) === $nombreNorm) {
                return ['tipo' => 'unico', 'empleado' => $emp];
            }
        }
        
        // Estrategia 2: Todas las palabras del Seeder (>= 3 chars) están en el nombre BD
        $coincidenciasE2 = [];
        foreach ($empleadosBD as $emp) {
            $nombreBDNorm = $this->normalizar($emp->nombre);
            $todasCoinciden = true;
            $palabrasValidas = 0;
            
            foreach ($palabrasSeeder as $palabra) {
                if (strlen($palabra) >= 3) {
                    $palabrasValidas++;
                    if (strpos($nombreBDNorm, $palabra) === false) {
                        $todasCoinciden = false;
                        break;
                    }
                }
            }
            
            if ($todasCoinciden && $palabrasValidas >= 2) {
                $coincidenciasE2[] = $emp;
            }
        }
        
        if (count($coincidenciasE2) === 1) {
            return ['tipo' => 'unico', 'empleado' => $coincidenciasE2[0]];
        } elseif (count($coincidenciasE2) > 1) {
            return ['tipo' => 'multiple', 'coincidencias' => $coincidenciasE2];
        }
        
        // Estrategia 3: Primer nombre + último apellido
        if (count($palabrasSeeder) >= 2) {
            $primerNombre = $palabrasSeeder[0];
            $ultimoApellido = end($palabrasSeeder);
            
            $coincidenciasE3 = [];
            foreach ($empleadosBD as $emp) {
                $nombreBDNorm = $this->normalizar($emp->nombre);
                
                if (strpos($nombreBDNorm, $primerNombre) === 0 && 
                    strpos($nombreBDNorm, $ultimoApellido) !== false) {
                    $coincidenciasE3[] = $emp;
                }
            }
            
            if (count($coincidenciasE3) === 1) {
                return ['tipo' => 'unico', 'empleado' => $coincidenciasE3[0]];
            } elseif (count($coincidenciasE3) > 1) {
                return ['tipo' => 'multiple', 'coincidencias' => $coincidenciasE3];
            }
        }
        
        // Estrategia 4: Solo primer nombre + cualquier apellido (>= 4 chars)
        if (count($palabrasSeeder) >= 2) {
            $primerNombre = $palabrasSeeder[0];
            
            $coincidenciasE4 = [];
            foreach ($empleadosBD as $emp) {
                $nombreBDNorm = $this->normalizar($emp->nombre);
                $palabrasBD = $this->obtenerPalabras($nombreBDNorm);
                
                // Primer nombre debe coincidir al inicio
                if (strpos($nombreBDNorm, $primerNombre) !== 0) {
                    continue;
                }
                
                // Al menos un apellido (>= 4 chars) debe coincidir
                foreach ($palabrasSeeder as $i => $palabra) {
                    if ($i === 0) continue;
                    if (strlen($palabra) >= 4) {
                        foreach ($palabrasBD as $j => $palabraBD) {
                            if ($j === 0) continue;
                            if (strlen($palabraBD) >= 4 && $palabra === $palabraBD) {
                                $coincidenciasE4[] = $emp;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            if (count($coincidenciasE4) === 1) {
                return ['tipo' => 'unico', 'empleado' => $coincidenciasE4[0]];
            } elseif (count($coincidenciasE4) > 1) {
                return ['tipo' => 'multiple', 'coincidencias' => $coincidenciasE4];
            }
        }
        
        // Estrategia 5: Nombre corto - solo primer nombre si es único y largo
        if (!empty($palabrasSeeder[0]) && strlen($palabrasSeeder[0]) >= 5) {
            $primerNombre = $palabrasSeeder[0];
            $coincidencias = [];
            
            foreach ($empleadosBD as $emp) {
                $nombreBDNorm = $this->normalizar($emp->nombre);
                if (strpos($nombreBDNorm, $primerNombre) === 0) {
                    $coincidencias[] = $emp;
                }
            }
            
            if (count($coincidencias) === 1) {
                return ['tipo' => 'unico', 'empleado' => $coincidencias[0]];
            } elseif (count($coincidencias) > 1) {
                // Intentar filtrar por segundo nombre/apellido
                if (count($palabrasSeeder) >= 2) {
                    $segundaPalabra = $palabrasSeeder[1];
                    $filtradas = array_filter($coincidencias, function($emp) use ($segundaPalabra) {
                        return strpos($this->normalizar($emp->nombre), $segundaPalabra) !== false;
                    });
                    
                    if (count($filtradas) === 1) {
                        return ['tipo' => 'unico', 'empleado' => array_values($filtradas)[0]];
                    }
                }
                
                return ['tipo' => 'multiple', 'coincidencias' => $coincidencias];
            }
        }
        
        return ['tipo' => 'no_encontrado'];
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
     * Obtiene las palabras de un nombre normalizado (mínimo 2 caracteres)
     */
    private function obtenerPalabras(string $nombreNorm): array
    {
        return array_values(array_filter(explode(' ', $nombreNorm), fn($p) => strlen($p) >= 2));
    }
}

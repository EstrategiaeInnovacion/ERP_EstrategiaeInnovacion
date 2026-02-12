<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Empleado;

class SincronizarIdEmpleadoSeeder extends Seeder
{
    /**
     * Sincroniza el id_empleado de la tabla empleados
     * con los datos de la BD local (mysql_old)
     * 
     * Busca coincidencias por nombre de forma flexible:
     * - Ignora mayúsculas/minúsculas
     * - Ignora acentos
     * - Permite nombres parciales (solo primer nombre + apellido)
     * 
     * Uso: php artisan db:seed --class=SincronizarIdEmpleadoSeeder
     */
    public function run(): void
    {
        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║  SINCRONIZAR id_empleado DESDE BD LOCAL                    ║');
        $this->command->info('║  BD Local (mysql_old) → ERP (empleados)                    ║');
        $this->command->info('╚════════════════════════════════════════════════════════════╝');
        $this->command->info('');

        // Verificar conexión a mysql_old
        try {
            $empleadosLocal = DB::connection('mysql_old')->table('empleados')->get();
            $this->command->info("✓ Conexión a mysql_old exitosa");
        } catch (\Exception $e) {
            $this->command->error("✗ No se pudo conectar a mysql_old: " . $e->getMessage());
            return;
        }

        // Obtener empleados del ERP
        $empleadosERP = Empleado::all();

        $this->command->info("Empleados en BD Local: " . $empleadosLocal->count());
        $this->command->info("Empleados en ERP: " . $empleadosERP->count());
        $this->command->info('');
        $this->command->info('─────────────────────────────────────────────────────────────');

        $actualizados = 0;
        $sinCambios = 0;
        $noEncontrados = [];
        $multiples = [];

        foreach ($empleadosERP as $empERP) {
            $nombreERP = $empERP->nombre;
            
            // Buscar coincidencia en BD Local
            $resultado = $this->buscarEnLocal($nombreERP, $empleadosLocal);
            
            if ($resultado['tipo'] === 'unico') {
                $empLocal = $resultado['empleado'];
                
                // Verificar si hay cambio
                if ($empERP->id_empleado != $empLocal->id_empleado) {
                    $idAnterior = $empERP->id_empleado ?? 'NULL';
                    
                    $empERP->id_empleado = $empLocal->id_empleado;
                    $empERP->save();
                    
                    $actualizados++;
                    $this->command->info("✓ {$nombreERP}");
                    $this->command->info("  → Encontrado como: {$empLocal->nombre}");
                    $this->command->info("  → id_empleado: {$idAnterior} → {$empLocal->id_empleado}");
                } else {
                    $sinCambios++;
                    $this->command->line("= {$nombreERP} (id_empleado ya correcto: {$empERP->id_empleado})");
                }
            } elseif ($resultado['tipo'] === 'multiple') {
                $multiples[] = [
                    'nombre' => $nombreERP,
                    'coincidencias' => $resultado['coincidencias']
                ];
                $this->command->warn("? {$nombreERP} - Múltiples coincidencias encontradas");
            } else {
                $noEncontrados[] = $nombreERP;
                $this->command->error("✗ {$nombreERP} - NO encontrado en BD Local");
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
            $this->command->warn('Empleados ERP no encontrados en BD Local:');
            foreach ($noEncontrados as $nombre) {
                $this->command->warn("  - {$nombre}");
            }
        }

        // Detalles de múltiples coincidencias
        if (count($multiples) > 0) {
            $this->command->info('');
            $this->command->warn('Empleados con múltiples coincidencias (revisar manualmente):');
            foreach ($multiples as $item) {
                $this->command->warn("  {$item['nombre']}:");
                foreach ($item['coincidencias'] as $coincidencia) {
                    $this->command->warn("    → {$coincidencia->nombre} (id_empleado: {$coincidencia->id_empleado})");
                }
            }
        }
    }

    /**
     * Busca un empleado en la BD local usando múltiples estrategias de coincidencia
     */
    private function buscarEnLocal(string $nombreERP, $empleadosLocal): array
    {
        $nombreNorm = $this->normalizar($nombreERP);
        $palabrasERP = $this->obtenerPalabras($nombreNorm);
        
        // Estrategia 1: Coincidencia exacta (normalizada)
        foreach ($empleadosLocal as $emp) {
            if ($this->normalizar($emp->nombre) === $nombreNorm) {
                return ['tipo' => 'unico', 'empleado' => $emp];
            }
        }
        
        // Estrategia 2: Todas las palabras del ERP (>= 3 chars) están en el nombre local
        $coincidenciasE2 = [];
        foreach ($empleadosLocal as $emp) {
            $nombreLocalNorm = $this->normalizar($emp->nombre);
            $todasCoinciden = true;
            $palabrasValidas = 0;
            
            foreach ($palabrasERP as $palabra) {
                if (strlen($palabra) >= 3) {
                    $palabrasValidas++;
                    if (strpos($nombreLocalNorm, $palabra) === false) {
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
        if (count($palabrasERP) >= 2) {
            $primerNombre = $palabrasERP[0];
            $ultimoApellido = end($palabrasERP);
            
            $coincidenciasE3 = [];
            foreach ($empleadosLocal as $emp) {
                $nombreLocalNorm = $this->normalizar($emp->nombre);
                
                if (strpos($nombreLocalNorm, $primerNombre) === 0 && 
                    strpos($nombreLocalNorm, $ultimoApellido) !== false) {
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
        if (count($palabrasERP) >= 2) {
            $primerNombre = $palabrasERP[0];
            
            $coincidenciasE4 = [];
            foreach ($empleadosLocal as $emp) {
                $nombreLocalNorm = $this->normalizar($emp->nombre);
                $palabrasLocal = $this->obtenerPalabras($nombreLocalNorm);
                
                // Primer nombre debe coincidir al inicio
                if (strpos($nombreLocalNorm, $primerNombre) !== 0) {
                    continue;
                }
                
                // Al menos un apellido (>= 4 chars) debe coincidir
                foreach ($palabrasERP as $i => $palabra) {
                    if ($i === 0) continue;
                    if (strlen($palabra) >= 4) {
                        foreach ($palabrasLocal as $j => $palabraLocal) {
                            if ($j === 0) continue;
                            if (strlen($palabraLocal) >= 4 && $palabra === $palabraLocal) {
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
        if (!empty($palabrasERP[0]) && strlen($palabrasERP[0]) >= 5) {
            $primerNombre = $palabrasERP[0];
            $coincidencias = [];
            
            foreach ($empleadosLocal as $emp) {
                $nombreLocalNorm = $this->normalizar($emp->nombre);
                if (strpos($nombreLocalNorm, $primerNombre) === 0) {
                    $coincidencias[] = $emp;
                }
            }
            
            if (count($coincidencias) === 1) {
                return ['tipo' => 'unico', 'empleado' => $coincidencias[0]];
            } elseif (count($coincidencias) > 1) {
                // Si hay múltiples, intentar filtrar por segundo nombre/apellido
                if (count($palabrasERP) >= 2) {
                    $segundaPalabra = $palabrasERP[1];
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

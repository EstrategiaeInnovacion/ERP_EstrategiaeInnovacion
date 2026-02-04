<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigracionExternaSeeder extends Seeder
{
    /**
     * Migra todos los datos de la BD vieja (mysql_old) a la BD nueva (mysql).
     * Incluye: users, empleados, tickets, operaciones_logisticas y todas las tablas relacionadas.
     */
    public function run(): void
    {
        // Aumentar memoria y tiempo para migraciones grandes
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║  MIGRACIÓN COMPLETA: BD VIEJA → BD NUEVA                   ║');
        $this->command->info('╚════════════════════════════════════════════════════════════╝');

        // Desactivar FK checks globalmente
        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");

        try {
            // ═══════════════════════════════════════════════════════════════
            // 1. USERS (Tabla principal de autenticación)
            // ═══════════════════════════════════════════════════════════════
            $this->migrarTabla('users', [
                'id', 'name', 'email', 'role', 'status', 'approved_at', 'rejected_at',
                'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'
            ]);

            // ═══════════════════════════════════════════════════════════════
            // 2. SUBDEPARTAMENTOS (Antes de empleados por FK)
            // ═══════════════════════════════════════════════════════════════
            $this->migrarTabla('subdepartamentos');

            // ═══════════════════════════════════════════════════════════════
            // 3. EMPLEADOS (Relacionado con users)
            // ═══════════════════════════════════════════════════════════════
            $this->migrarTabla('empleados', [
                'id', 'user_id', 'nombre', 'correo', 'area', 'id_empleado',
                'subdepartamento_id', 'posicion', 'telefono', 'correo_personal',
                'foto_path', 'direccion', 'created_at', 'updated_at'
            ]);

            // ═══════════════════════════════════════════════════════════════
            // 4. TABLAS DE SISTEMAS IT - TICKETS
            // ═══════════════════════════════════════════════════════════════
            
            // 4.1 Computer Profiles (Antes de tickets por FK)
            $this->migrarTabla('computer_profiles');

            // 4.2 Maintenance Slots (Antes de tickets por FK)
            $this->migrarTabla('maintenance_slots');

            // 4.3 TICKETS (Tabla principal)
            $this->migrarTabla('tickets', [
                'id', 'folio', 'nombre_solicitante', 'correo_solicitante', 'nombre_programa',
                'descripcion_problema', 'imagenes', 'estado', 'closed_by_user', 'is_read',
                'user_has_updates', 'user_notified_at', 'user_last_read_at', 'user_notification_summary',
                'notified_at', 'read_at', 'fecha_apertura', 'fecha_cierre', 'closed_by_user_at',
                'observaciones', 'tipo_problema', 'prioridad', 'created_at', 'updated_at',
                'user_id', 'equipment_password', 'imagenes_admin', 'maintenance_slot_id',
                'maintenance_scheduled_at', 'maintenance_details', 'equipment_identifier',
                'equipment_brand', 'equipment_model', 'disk_type', 'ram_capacity', 'battery_status',
                'aesthetic_observations', 'maintenance_report', 'closure_observations',
                'replacement_components', 'computer_profile_id'
            ]);

            // 4.4 Maintenance Bookings
            $this->migrarTabla('maintenance_bookings');

            // 4.5 Inventory Items
            $this->migrarTabla('inventory_items');

            // 4.6 Blocked Emails
            $this->migrarTabla('blocked_emails');

            // ═══════════════════════════════════════════════════════════════
            // 5. TABLAS DE LOGÍSTICA
            // ═══════════════════════════════════════════════════════════════
            
            // 5.1 Clientes
            $this->migrarTabla('clientes');

            // 5.2 Aduanas
            $this->migrarTabla('aduanas');

            // 5.3 Agentes Aduanales
            $this->migrarTabla('agentes_aduanales');

            // 5.4 Transportes
            $this->migrarTabla('transportes');

            // 5.5 Post Operaciones
            $this->migrarTabla('post_operaciones');

            // 5.6 OPERACIONES LOGÍSTICAS (Tabla principal)
            $this->migrarTabla('operaciones_logisticas', [
                'id', 'ejecutivo', 'operacion', 'cliente', 'proveedor_o_cliente', 'no_factura',
                'tipo_carga', 'tipo_incoterm', 'tipo_operacion_enum', 'clave', 'referencia_interna',
                'aduana', 'agente_aduanal', 'referencia_aa', 'no_pedimento', 'transporte', 'guia_bl',
                'puerto_salida', 'status_calculado', 'status_manual', 'fecha_status_manual',
                'color_status', 'dias_transcurridos_calculados', 'fecha_ultimo_calculo', 'comentarios',
                'fecha_embarque', 'fecha_arribo_aduana', 'fecha_modulacion', 'fecha_arribo_planta',
                'resultado', 'target', 'dias_transito', 'created_at', 'updated_at',
                'post_operacion_id', 'post_operacion_status',
                // Nuevos campos opcionales (del Excel)
                'in_charge', 'proveedor', 'tipo_previo', 'fecha_etd', 'fecha_zarpe',
                'pedimento_en_carpeta', 'referencia_cliente', 'mail_subject'
            ]);

            // 5.7 Post Operacion Operacion (Tabla pivot)
            $this->migrarTabla('post_operacion_operacion');

            // 5.8 Operacion Comentarios
            $this->migrarTabla('operacion_comentarios');

            // 5.9 Pedimentos
            $this->migrarTabla('pedimentos');

            // 5.10 Pedimentos Operaciones
            $this->migrarTabla('pedimentos_operaciones');

            // 5.11 Historico Matriz SGM
            $this->migrarTabla('historico_matriz_sgm');

            // 5.12 Campos Personalizados Matriz
            $this->migrarTabla('campos_personalizados_matriz');

            // 5.13 Campo Personalizado Ejecutivo
            $this->migrarTabla('campo_personalizado_ejecutivo');

            // 5.14 Columnas Visibles Ejecutivo
            $this->migrarTabla('columnas_visibles_ejecutivo');

            // 5.15 Valores Campos Personalizados
            $this->migrarTabla('valores_campos_personalizados');

            // 5.16 Logistica Correos CC
            $this->migrarTabla('logistica_correos_cc');

            // ═══════════════════════════════════════════════════════════════
            // 6. TABLAS DE RECURSOS HUMANOS
            // ═══════════════════════════════════════════════════════════════
            
            // 6.1 Asistencias
            $this->migrarTabla('asistencias');

            // Reactivar FK checks
            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");

            $this->command->newLine();
            $this->command->info('╔════════════════════════════════════════════════════════════╗');
            $this->command->info('║  ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE                      ║');
            $this->command->info('╚════════════════════════════════════════════════════════════╝');

        } catch (\Exception $e) {
            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
            $this->command->error('');
            $this->command->error('╔════════════════════════════════════════════════════════════╗');
            $this->command->error('║  ❌ ERROR EN MIGRACIÓN                                     ║');
            $this->command->error('╚════════════════════════════════════════════════════════════╝');
            $this->command->error('Mensaje: ' . $e->getMessage());
            $this->command->error('Archivo: ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }

    /**
     * Migra una tabla completa de mysql_old a mysql (nueva).
     * 
     * @param string $tabla Nombre de la tabla
     * @param array|null $columnas Columnas específicas a migrar (null = todas)
     */
    private function migrarTabla(string $tabla, ?array $columnas = null): void
    {
        $this->command->info('');
        $this->command->info("━━━ Migrando tabla: {$tabla} ━━━");

        // Verificar si la tabla existe en la BD vieja
        $existeVieja = DB::connection('mysql_old')
            ->select("SHOW TABLES LIKE '{$tabla}'");
        
        if (empty($existeVieja)) {
            $this->command->warn("  ⚠ Tabla '{$tabla}' no existe en BD vieja. Saltando...");
            return;
        }

        // Verificar si la tabla existe en la BD nueva
        $existeNueva = DB::select("SHOW TABLES LIKE '{$tabla}'");
        
        if (empty($existeNueva)) {
            $this->command->warn("  ⚠ Tabla '{$tabla}' no existe en BD nueva. Saltando...");
            return;
        }

        // Obtener datos de la BD vieja
        $datosViejos = DB::connection('mysql_old')->table($tabla)->get();
        $total = $datosViejos->count();

        if ($total === 0) {
            $this->command->info("  → Sin registros en BD vieja.");
            return;
        }

        $this->command->info("  → Encontrados {$total} registros");

        // Obtener columnas de la tabla nueva para validar
        $columnasNuevas = collect(DB::select("SHOW COLUMNS FROM {$tabla}"))
            ->pluck('Field')
            ->toArray();

        // Limpiar tabla nueva antes de insertar (para evitar duplicados)
        DB::table($tabla)->truncate();
        
        $insertados = 0;
        $errores = 0;

        // Insertar en chunks para mejor rendimiento
        $datosViejos->chunk(100)->each(function ($chunk) use ($tabla, $columnas, $columnasNuevas, &$insertados, &$errores) {
            foreach ($chunk as $registro) {
                try {
                    $datos = (array) $registro;
                    
                    // Si se especificaron columnas, filtrar solo esas
                    if ($columnas !== null) {
                        $datos = array_intersect_key($datos, array_flip($columnas));
                    }
                    
                    // Filtrar solo columnas que existen en la tabla nueva
                    $datos = array_intersect_key($datos, array_flip($columnasNuevas));
                    
                    if (!empty($datos)) {
                        DB::table($tabla)->insert($datos);
                        $insertados++;
                    }
                } catch (\Exception $e) {
                    $errores++;
                    // Solo mostrar los primeros 3 errores para no saturar
                    if ($errores <= 3) {
                        $this->command->warn("  ⚠ Error en registro: " . substr($e->getMessage(), 0, 100));
                    }
                }
            }
        });

        // Resetear auto_increment al máximo ID + 1
        $maxId = DB::table($tabla)->max('id');
        if ($maxId) {
            DB::statement("ALTER TABLE {$tabla} AUTO_INCREMENT = " . ($maxId + 1));
        }

        $this->command->info("  ✓ Insertados: {$insertados} | Errores: {$errores}");
    }
}
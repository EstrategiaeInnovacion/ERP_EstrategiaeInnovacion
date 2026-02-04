<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SincronizarDatosNuevosSeeder extends Seeder
{
    /**
     * Sincroniza solo los datos NUEVOS de la BD vieja (mysql_old) a la BD nueva.
     * No elimina ni modifica datos existentes, solo agrega los que faltan.
     * 
     * Uso: php artisan db:seed --class=SincronizarDatosNuevosSeeder
     */
    public function run(): void
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║  SINCRONIZACIÓN DE DATOS NUEVOS: BD VIEJA → BD NUEVA       ║');
        $this->command->info('╚════════════════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('Solo se agregarán registros que NO existan en la BD nueva.');
        $this->command->info('');

        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");

        try {
            // ═══════════════════════════════════════════════════════════════
            // 1. USERS
            // ═══════════════════════════════════════════════════════════════
            $this->sincronizarTabla('users', 'id', [
                'id', 'name', 'email', 'role', 'status', 'approved_at', 'rejected_at',
                'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'
            ]);

            // ═══════════════════════════════════════════════════════════════
            // 2. SUBDEPARTAMENTOS
            // ═══════════════════════════════════════════════════════════════
            $this->sincronizarTabla('subdepartamentos', 'id');

            // ═══════════════════════════════════════════════════════════════
            // 3. EMPLEADOS
            // ═══════════════════════════════════════════════════════════════
            $this->sincronizarTabla('empleados', 'id', [
                'id', 'user_id', 'nombre', 'correo', 'area', 'id_empleado',
                'subdepartamento_id', 'posicion', 'telefono', 'correo_personal',
                'foto_path', 'direccion', 'created_at', 'updated_at'
            ]);

            // ═══════════════════════════════════════════════════════════════
            // 4. SISTEMAS IT - TICKETS
            // ═══════════════════════════════════════════════════════════════
            $this->sincronizarTabla('computer_profiles', 'id');
            $this->sincronizarTabla('maintenance_slots', 'id');
            
            $this->sincronizarTabla('tickets', 'id', [
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

            $this->sincronizarTabla('maintenance_bookings', 'id');
            $this->sincronizarTabla('inventory_items', 'id');
            $this->sincronizarTabla('blocked_emails', 'id');

            // ═══════════════════════════════════════════════════════════════
            // 5. LOGÍSTICA
            // ═══════════════════════════════════════════════════════════════
            $this->sincronizarTabla('clientes', 'id');
            $this->sincronizarTabla('aduanas', 'id');
            $this->sincronizarTabla('agentes_aduanales', 'id');
            $this->sincronizarTabla('transportes', 'id');
            $this->sincronizarTabla('post_operaciones', 'id');

            $this->sincronizarTabla('operaciones_logisticas', 'id', [
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

            $this->sincronizarTabla('post_operacion_operacion', 'id');
            $this->sincronizarTabla('operacion_comentarios', 'id');
            $this->sincronizarTabla('pedimentos', 'id');
            $this->sincronizarTabla('pedimentos_operaciones', 'id');
            $this->sincronizarTabla('historico_matriz_sgm', 'id');
            $this->sincronizarTabla('campos_personalizados_matriz', 'id');
            $this->sincronizarTabla('campo_personalizado_ejecutivo', 'id');
            $this->sincronizarTabla('columnas_visibles_ejecutivo', 'id');
            $this->sincronizarTabla('valores_campos_personalizados', 'id');
            $this->sincronizarTabla('logistica_correos_cc', 'id');

            // ═══════════════════════════════════════════════════════════════
            // 6. RECURSOS HUMANOS
            // ═══════════════════════════════════════════════════════════════
            $this->sincronizarTabla('asistencias', 'id');

            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");

            $this->command->newLine();
            $this->command->info('╔════════════════════════════════════════════════════════════╗');
            $this->command->info('║  ✅ SINCRONIZACIÓN COMPLETADA                              ║');
            $this->command->info('╚════════════════════════════════════════════════════════════╝');

        } catch (\Exception $e) {
            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
            $this->command->error('');
            $this->command->error('╔════════════════════════════════════════════════════════════╗');
            $this->command->error('║  ❌ ERROR EN SINCRONIZACIÓN                                ║');
            $this->command->error('╚════════════════════════════════════════════════════════════╝');
            $this->command->error('Mensaje: ' . $e->getMessage());
            $this->command->error('Archivo: ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }

    /**
     * Sincroniza datos nuevos de una tabla (solo inserta los que no existen).
     * 
     * @param string $tabla Nombre de la tabla
     * @param string $campoId Campo identificador único (generalmente 'id')
     * @param array|null $columnas Columnas específicas a sincronizar (null = todas)
     */
    private function sincronizarTabla(string $tabla, string $campoId = 'id', ?array $columnas = null): void
    {
        $this->command->info('');
        $this->command->info("━━━ Sincronizando: {$tabla} ━━━");

        // Verificar si la tabla existe en ambas BDs
        $existeVieja = DB::connection('mysql_old')->select("SHOW TABLES LIKE '{$tabla}'");
        $existeNueva = DB::select("SHOW TABLES LIKE '{$tabla}'");
        
        if (empty($existeVieja)) {
            $this->command->warn("  ⚠ Tabla '{$tabla}' no existe en BD vieja. Saltando...");
            return;
        }
        
        if (empty($existeNueva)) {
            $this->command->warn("  ⚠ Tabla '{$tabla}' no existe en BD nueva. Saltando...");
            return;
        }

        // Obtener IDs existentes en la BD nueva
        $idsExistentes = DB::table($tabla)->pluck($campoId)->toArray();
        
        // Obtener registros de la BD vieja que NO están en la nueva
        $datosNuevos = DB::connection('mysql_old')
            ->table($tabla)
            ->whereNotIn($campoId, $idsExistentes)
            ->get();

        $totalNuevos = $datosNuevos->count();

        if ($totalNuevos === 0) {
            $this->command->info("  → Sin registros nuevos.");
            return;
        }

        $this->command->info("  → Encontrados {$totalNuevos} registros nuevos");

        // Obtener columnas de la tabla nueva
        $columnasNuevas = collect(DB::select("SHOW COLUMNS FROM {$tabla}"))
            ->pluck('Field')
            ->toArray();

        $insertados = 0;
        $errores = 0;

        foreach ($datosNuevos as $registro) {
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
                if ($errores <= 3) {
                    $this->command->warn("  ⚠ Error: " . substr($e->getMessage(), 0, 80));
                }
            }
        }

        // Actualizar auto_increment si se insertaron registros
        if ($insertados > 0) {
            $maxId = DB::table($tabla)->max($campoId);
            if ($maxId && is_numeric($maxId)) {
                DB::statement("ALTER TABLE {$tabla} AUTO_INCREMENT = " . ($maxId + 1));
            }
        }

        $this->command->info("  ✓ Nuevos insertados: {$insertados} | Errores: {$errores}");
    }
}

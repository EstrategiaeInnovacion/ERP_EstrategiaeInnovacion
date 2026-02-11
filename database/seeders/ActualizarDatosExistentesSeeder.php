<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActualizarDatosExistentesSeeder extends Seeder
{
    /**
     * Actualiza SOLO los registros que YA EXISTEN en la BD nueva con datos de la BD vieja.
     * NO agrega registros nuevos, solo actualiza los existentes.
     * 
     * Uso: php artisan db:seed --class=ActualizarDatosExistentesSeeder
     */
    public function run(): void
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║  ACTUALIZACIÓN DE DATOS EXISTENTES: BD VIEJA → BD NUEVA    ║');
        $this->command->info('╚════════════════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('Solo se ACTUALIZARÁN registros que YA EXISTAN en la BD nueva.');
        $this->command->info('NO se agregarán registros nuevos.');
        $this->command->info('');

        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");

        try {
            // ═══════════════════════════════════════════════════════════════
            // 1. USERS
            // ═══════════════════════════════════════════════════════════════
            $this->actualizarTabla('users', 'id', [
                'id', 'name', 'email', 'role', 'status', 'approved_at', 'rejected_at',
                'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'
            ]);

            // ═══════════════════════════════════════════════════════════════
            // 2. SUBDEPARTAMENTOS
            // ═══════════════════════════════════════════════════════════════
            $this->actualizarTabla('subdepartamentos', 'id');

            // ═══════════════════════════════════════════════════════════════
            // 3. EMPLEADOS
            // ═══════════════════════════════════════════════════════════════
            $this->actualizarTabla('empleados', 'id', [
                'id', 'user_id', 'nombre', 'correo', 'area', 'id_empleado',
                'subdepartamento_id', 'posicion', 'telefono', 'correo_personal',
                'foto_path', 'direccion', 'created_at', 'updated_at'
            ]);

            // ═══════════════════════════════════════════════════════════════
            // 4. SISTEMAS IT - TICKETS
            // ═══════════════════════════════════════════════════════════════
            $this->actualizarTabla('computer_profiles', 'id');
            $this->actualizarTabla('maintenance_slots', 'id');
            
            $this->actualizarTabla('tickets', 'id', [
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

            $this->actualizarTabla('maintenance_bookings', 'id');
            $this->actualizarTabla('inventory_items', 'id');
            $this->actualizarTabla('blocked_emails', 'id');

            // ═══════════════════════════════════════════════════════════════
            // 5. LOGÍSTICA
            // ═══════════════════════════════════════════════════════════════
            $this->actualizarTabla('clientes', 'id');
            $this->actualizarTabla('aduanas', 'id');
            $this->actualizarTabla('agentes_aduanales', 'id');
            $this->actualizarTabla('transportes', 'id');
            $this->actualizarTabla('post_operaciones', 'id');

            $this->actualizarTabla('operaciones_logisticas', 'id', [
                'id', 'ejecutivo', 'operacion', 'cliente', 'proveedor_o_cliente', 'no_factura',
                'tipo_carga', 'tipo_incoterm', 'tipo_operacion_enum', 'clave', 'referencia_interna',
                'aduana', 'agente_aduanal', 'referencia_aa', 'no_pedimento', 'transporte', 'guia_bl',
                'puerto_salida', 'status_calculado', 'status_manual', 'fecha_status_manual',
                'color_status', 'dias_transcurridos_calculados', 'fecha_ultimo_calculo', 'comentarios',
                'fecha_embarque', 'fecha_arribo_aduana', 'fecha_modulacion', 'fecha_arribo_planta',
                'resultado', 'target', 'dias_transito', 'created_at', 'updated_at',
                'post_operacion_id', 'post_operacion_status',
                'in_charge', 'proveedor', 'tipo_previo', 'fecha_etd', 'fecha_zarpe',
                'pedimento_en_carpeta', 'referencia_cliente', 'mail_subject'
            ]);

            $this->actualizarTabla('post_operacion_operacion', 'id');
            $this->actualizarTabla('operacion_comentarios', 'id');
            $this->actualizarTabla('pedimentos', 'id');
            $this->actualizarTabla('pedimentos_operaciones', 'id');
            $this->actualizarTabla('historico_matriz_sgm', 'id');
            $this->actualizarTabla('campos_personalizados_matriz', 'id');
            $this->actualizarTabla('campo_personalizado_ejecutivo', 'id');
            $this->actualizarTabla('columnas_visibles_ejecutivo', 'id');
            $this->actualizarTabla('valores_campos_personalizados', 'id');
            $this->actualizarTabla('logistica_correos_cc', 'id');

            // ═══════════════════════════════════════════════════════════════
            // 6. RECURSOS HUMANOS
            // ═══════════════════════════════════════════════════════════════
            $this->actualizarTabla('asistencias', 'id');

            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");

            $this->command->newLine();
            $this->command->info('╔════════════════════════════════════════════════════════════╗');
            $this->command->info('║  ✅ ACTUALIZACIÓN COMPLETADA                               ║');
            $this->command->info('╚════════════════════════════════════════════════════════════╝');

        } catch (\Exception $e) {
            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
            $this->command->error('');
            $this->command->error('╔════════════════════════════════════════════════════════════╗');
            $this->command->error('║  ❌ ERROR EN ACTUALIZACIÓN                                 ║');
            $this->command->error('╚════════════════════════════════════════════════════════════╝');
            $this->command->error('Mensaje: ' . $e->getMessage());
            $this->command->error('Archivo: ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }

    /**
     * Actualiza SOLO los registros existentes en la BD nueva con datos de la BD vieja.
     * 
     * @param string $tabla Nombre de la tabla
     * @param string $campoId Campo identificador único (generalmente 'id')
     * @param array|null $columnas Columnas específicas a actualizar (null = todas)
     */
    private function actualizarTabla(string $tabla, string $campoId = 'id', ?array $columnas = null): void
    {
        $this->command->info('');
        $this->command->info("━━━ Actualizando: {$tabla} ━━━");

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
        
        if (empty($idsExistentes)) {
            $this->command->info("  → Sin registros en BD nueva para actualizar.");
            return;
        }

        $this->command->info("  → Registros en BD nueva: " . count($idsExistentes));

        // Obtener registros de la BD vieja que SÍ están en la nueva (para actualizar)
        $datosVieja = DB::connection('mysql_old')
            ->table($tabla)
            ->whereIn($campoId, $idsExistentes)
            ->get();

        $totalParaActualizar = $datosVieja->count();

        if ($totalParaActualizar === 0) {
            $this->command->info("  → Sin registros coincidentes en BD vieja.");
            return;
        }

        $this->command->info("  → Registros a actualizar: {$totalParaActualizar}");

        // Obtener columnas de la tabla nueva
        $columnasNuevas = collect(DB::select("SHOW COLUMNS FROM {$tabla}"))
            ->pluck('Field')
            ->toArray();

        $actualizados = 0;
        $sinCambios = 0;
        $errores = 0;

        foreach ($datosVieja as $registro) {
            try {
                $datos = (array) $registro;
                $idRegistro = $datos[$campoId];
                
                // Remover el campo ID del update (no se debe actualizar)
                unset($datos[$campoId]);
                
                // Si se especificaron columnas, filtrar solo esas
                if ($columnas !== null) {
                    // Remover el campo ID de la lista de columnas permitidas para el update
                    $columnasParaUpdate = array_filter($columnas, fn($col) => $col !== $campoId);
                    $datos = array_intersect_key($datos, array_flip($columnasParaUpdate));
                }
                
                // Filtrar solo columnas que existen en la tabla nueva
                $datos = array_intersect_key($datos, array_flip($columnasNuevas));
                
                if (!empty($datos)) {
                    $affected = DB::table($tabla)
                        ->where($campoId, $idRegistro)
                        ->update($datos);
                    
                    if ($affected > 0) {
                        $actualizados++;
                    } else {
                        $sinCambios++;
                    }
                }
            } catch (\Exception $e) {
                $errores++;
                if ($errores <= 3) {
                    $this->command->warn("  ⚠ Error en ID {$registro->$campoId}: " . substr($e->getMessage(), 0, 80));
                }
            }
        }

        $this->command->info("  ✓ Actualizados: {$actualizados} | Sin cambios: {$sinCambios} | Errores: {$errores}");
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. it_equipos_asignados: agregar fechas de mantenimiento ────────
        if (!Schema::hasColumn('it_equipos_asignados', 'last_maintenance_at')) {
            Schema::table('it_equipos_asignados', function (Blueprint $table) {
                $table->timestamp('last_maintenance_at')->nullable()->after('es_principal');
                $table->timestamp('next_maintenance_at')->nullable()->after('last_maintenance_at');
                $table->date('maintenance_reminder_sent_at')->nullable()->after('next_maintenance_at');
            });

            // Migrar fechas de mantenimiento existentes
            DB::statement("
                UPDATE it_equipos_asignados ea
                INNER JOIN computer_profiles cp ON cp.equipo_asignado_id = ea.id
                SET
                    ea.last_maintenance_at = cp.last_maintenance_at,
                    ea.next_maintenance_at = cp.next_maintenance_at,
                    ea.maintenance_reminder_sent_at = cp.maintenance_reminder_sent_at
                WHERE cp.equipo_asignado_id IS NOT NULL
            ");
        }

        // ── 2. ti_expedientes: reemplazar computer_profile_id → equipo_asignado_id ─
        if (!Schema::hasColumn('ti_expedientes', 'equipo_asignado_id')) {
            Schema::table('ti_expedientes', function (Blueprint $table) {
                $table->unsignedBigInteger('equipo_asignado_id')->nullable()->after('id');
            });

            DB::statement("
                UPDATE ti_expedientes e
                INNER JOIN computer_profiles cp ON e.computer_profile_id = cp.id
                SET e.equipo_asignado_id = cp.equipo_asignado_id
                WHERE cp.equipo_asignado_id IS NOT NULL
            ");

            Schema::table('ti_expedientes', function (Blueprint $table) {
                $table->dropForeign(['computer_profile_id']);
            });

            Schema::table('ti_expedientes', function (Blueprint $table) {
                $table->dropColumn('computer_profile_id');
                $table->unique('equipo_asignado_id');
                $table->foreign('equipo_asignado_id')->references('id')->on('it_equipos_asignados')->onDelete('cascade');
            });
        }

        // ── 3. tickets: reemplazar computer_profile_id → equipo_asignado_id ─
        if (Schema::hasColumn('tickets', 'computer_profile_id')) {
            if (!Schema::hasColumn('tickets', 'equipo_asignado_id')) {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->unsignedBigInteger('equipo_asignado_id')->nullable()->after('computer_profile_id');
                });

                DB::statement("
                    UPDATE tickets t
                    INNER JOIN computer_profiles cp ON t.computer_profile_id = cp.id
                    SET t.equipo_asignado_id = cp.equipo_asignado_id
                    WHERE cp.equipo_asignado_id IS NOT NULL
                ");
            }

            Schema::table('tickets', function (Blueprint $table) {
                $table->dropForeign(['computer_profile_id']);
                $table->dropColumn('computer_profile_id');
            });
        }

        // Agregar FK de equipo_asignado_id en tickets si no existe
        if (Schema::hasColumn('tickets', 'equipo_asignado_id')) {
            $fks = DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'tickets'
                  AND COLUMN_NAME = 'equipo_asignado_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            if (empty($fks)) {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->foreign('equipo_asignado_id')->references('id')->on('it_equipos_asignados')->onDelete('set null');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('it_equipos_asignados', function (Blueprint $table) {
            $table->dropColumn(['last_maintenance_at', 'next_maintenance_at', 'maintenance_reminder_sent_at']);
        });

        Schema::table('ti_expedientes', function (Blueprint $table) {
            $table->dropForeign(['equipo_asignado_id']);
            $table->dropUnique(['equipo_asignado_id']);
            $table->dropColumn('equipo_asignado_id');
            $table->unsignedBigInteger('computer_profile_id')->nullable();
            $table->foreign('computer_profile_id')->references('id')->on('computer_profiles')->onDelete('cascade');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['equipo_asignado_id']);
            $table->dropColumn('equipo_asignado_id');
            $table->unsignedBigInteger('computer_profile_id')->nullable();
        });
    }
};

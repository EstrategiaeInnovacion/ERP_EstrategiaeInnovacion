<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('evaluaciones', 'tipo')) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->string('tipo', 20)->default('supervisor')->after('ventana_id');
            });
        }

        $fks = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones'
             AND COLUMN_NAME = 'ventana_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
        ));

        foreach ($fks as $fk) {
            Schema::table('evaluaciones', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            });
        }

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->index('ventana_id', 'eval_ventana_id_idx');
        });

        $oldIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_ventana'"));
        if ($oldIndex->isNotEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->dropUnique('eval_unica_ventana');
            });
        }

        $newIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_vt'"));
        if ($newIndex->isEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->unique(['empleado_id', 'evaluador_id', 'ventana_id', 'tipo'], 'eval_unica_vt');
            });
        }

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->foreign('ventana_id')->references('id')->on('evaluacion_ventanas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        $fks = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones'
             AND COLUMN_NAME = 'ventana_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
        ));

        foreach ($fks as $fk) {
            Schema::table('evaluaciones', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            });
        }

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropIndex('eval_ventana_id_idx');
        });

        $newIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_vt'"));
        if ($newIndex->isNotEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->dropUnique('eval_unica_vt');
            });
        }

        $restoreIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_ventana'"));
        if ($restoreIndex->isEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->unique(['empleado_id', 'evaluador_id', 'ventana_id'], 'eval_unica_ventana');
            });
        }

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->foreign('ventana_id')->references('id')->on('evaluacion_ventanas')->onDelete('set null');
        });

        if (Schema::hasColumn('evaluaciones', 'tipo')) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }
};

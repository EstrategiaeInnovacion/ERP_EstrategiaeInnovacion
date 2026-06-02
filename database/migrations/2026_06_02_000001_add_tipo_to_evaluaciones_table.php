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

        $fkName = 'evaluaciones_ventana_id_foreign';
        $fks = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND CONSTRAINT_NAME = ?", [$fkName]));

        if ($fks->isNotEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        }

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

        $fkRestored = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND CONSTRAINT_NAME = ?", [$fkName]));
        if ($fkRestored->isEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->foreign('ventana_id')->references('id')->on('evaluacion_ventanas')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        $fkName = 'evaluaciones_ventana_id_foreign';
        $fks = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND CONSTRAINT_NAME = ?", [$fkName]));

        if ($fks->isNotEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        }

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

        $fkRestored = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND CONSTRAINT_NAME = ?", [$fkName]));
        if ($fkRestored->isEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->foreign('ventana_id')->references('id')->on('evaluacion_ventanas')->onDelete('set null');
            });
        }

        if (Schema::hasColumn('evaluaciones', 'tipo')) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }
};

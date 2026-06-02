<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->string('tipo', 20)->default('supervisor')->after('ventana_id');
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
    }

    public function down(): void
    {
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

        if (Schema::hasColumn('evaluaciones', 'tipo')) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }
};

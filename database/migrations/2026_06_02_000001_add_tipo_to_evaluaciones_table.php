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

        // Drop ventana_id FK if it exists (from earlier partial states)
        $createSql = DB::select("SHOW CREATE TABLE evaluaciones")[0]->{'Create Table'};
        preg_match('/CONSTRAINT `([^`]+)` FOREIGN KEY \(`ventana_id`\)/', $createSql, $m);
        $fkVentana = $m[1] ?? null;
        if ($fkVentana) {
            DB::statement("ALTER TABLE `evaluaciones` DROP FOREIGN KEY `{$fkVentana}`");
        }

        // Ensure a standalone index on empleado_id exists BEFORE dropping the unique index.
        // IMPORTANT: check by key name — the unique index eval_unica_ventana also starts with
        // empleado_id, so a column-based check would incorrectly think the standalone index exists.
        $hasStandaloneIdx = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_empleado_id_idx'"));
        if ($hasStandaloneIdx->isEmpty()) {
            DB::statement("ALTER TABLE `evaluaciones` ADD INDEX `eval_empleado_id_idx` (`empleado_id`)");
        }

        // Drop the unique index only if it still exists
        $uniIdx = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_ventana'"));
        if ($uniIdx->isNotEmpty()) {
            DB::statement("ALTER TABLE `evaluaciones` DROP INDEX `eval_unica_ventana`");
        }

        $newIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_vt'"));
        if ($newIndex->isEmpty()) {
            DB::statement("ALTER TABLE `evaluaciones` ADD UNIQUE INDEX `eval_unica_vt` (`empleado_id`, `evaluador_id`, `ventana_id`, `tipo`)");
        }

        DB::statement("ALTER TABLE `evaluaciones` ADD CONSTRAINT `evaluaciones_ventana_id_foreign` FOREIGN KEY (`ventana_id`) REFERENCES `evaluacion_ventanas` (`id`) ON DELETE SET NULL");
    }

    public function down(): void
    {
        $createSql = DB::select("SHOW CREATE TABLE evaluaciones")[0]->{'Create Table'};
        preg_match('/CONSTRAINT `([^`]+)` FOREIGN KEY \(`ventana_id`\)/', $createSql, $m);
        $fkName = $m[1] ?? null;
        if ($fkName) {
            DB::statement("ALTER TABLE `evaluaciones` DROP FOREIGN KEY `{$fkName}`");
        }

        $newIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_vt'"));
        if ($newIndex->isNotEmpty()) {
            DB::statement("ALTER TABLE `evaluaciones` DROP INDEX `eval_unica_vt`");
        }

        $restoreIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_ventana'"));
        if ($restoreIndex->isEmpty()) {
            DB::statement("ALTER TABLE `evaluaciones` ADD UNIQUE INDEX `eval_unica_ventana` (`empleado_id`, `evaluador_id`, `ventana_id`)");
        }

        DB::statement("ALTER TABLE `evaluaciones` ADD CONSTRAINT `evaluaciones_ventana_id_foreign` FOREIGN KEY (`ventana_id`) REFERENCES `evaluacion_ventanas` (`id`) ON DELETE SET NULL");

        if (Schema::hasColumn('evaluaciones', 'tipo')) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }
};

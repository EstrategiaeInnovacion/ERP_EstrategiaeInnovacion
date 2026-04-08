<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('ventana_id')->nullable()->after('periodo');
            $table->foreign('ventana_id')->references('id')->on('evaluacion_ventanas')->onDelete('set null');

            // Reemplazar constraint por periodo con constraint por ventana
            $table->dropUnique('eval_unica_par');
            $table->unique(['empleado_id', 'evaluador_id', 'ventana_id'], 'eval_unica_ventana');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropForeign(['ventana_id']);
            $table->dropUnique('eval_unica_ventana');
            $table->dropColumn('ventana_id');
            $table->unique(['empleado_id', 'evaluador_id', 'periodo'], 'eval_unica_par');
        });
    }
};

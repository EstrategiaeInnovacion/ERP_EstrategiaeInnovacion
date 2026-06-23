<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auditoria_cambios_propuestos', function (Blueprint $table) {
            $table->boolean('es_importante')->default(false)->after('comentario_visible_cliente');
        });
        Schema::table('auditoria_comentarios', function (Blueprint $table) {
            $table->boolean('es_importante')->default(false)->after('visible_cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditoria_cambios_propuestos', function (Blueprint $table) {
            $table->dropColumn('es_importante');
        });
        Schema::table('auditoria_comentarios', function (Blueprint $table) {
            $table->dropColumn('es_importante');
        });
    }
};

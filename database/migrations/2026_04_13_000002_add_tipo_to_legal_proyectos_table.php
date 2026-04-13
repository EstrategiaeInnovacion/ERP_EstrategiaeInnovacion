<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_proyectos', function (Blueprint $table) {
            $table->enum('tipo', ['consulta', 'escritos', 'ambos'])->default('consulta')->after('empresa');
        });
    }

    public function down(): void
    {
        Schema::table('legal_proyectos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_proyectos', function (Blueprint $table) {
            $table->string('cliente')->nullable()->after('empresa');
            $table->text('detalles')->nullable()->after('resultado');
        });
    }

    public function down(): void
    {
        Schema::table('legal_proyectos', function (Blueprint $table) {
            $table->dropColumn(['cliente', 'detalles']);
        });
    }
};

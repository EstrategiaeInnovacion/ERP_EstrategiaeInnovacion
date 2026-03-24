<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recordatorios', function (Blueprint $table) {
            $table->string('color_evento')->nullable()->after('activo');
            $table->boolean('es_manual')->default(false)->after('color_evento');
        });
    }

    public function down(): void
    {
        Schema::table('recordatorios', function (Blueprint $table) {
            $table->dropColumn(['color_evento', 'es_manual']);
        });
    }
};

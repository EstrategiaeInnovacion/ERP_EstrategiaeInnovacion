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
        Schema::table('matriz_seguimiento', function (Blueprint $table) {
            $table->unsignedTinyInteger('dias_libres')->nullable()->after('eta');
        });
    }

    public function down(): void
    {
        Schema::table('matriz_seguimiento', function (Blueprint $table) {
            $table->dropColumn('dias_libres');
        });
    }
};

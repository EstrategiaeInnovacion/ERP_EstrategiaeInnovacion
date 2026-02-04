<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('activities', function (Blueprint $table) {
            // Guardamos el ID de quien creó/asignó la tarea
            $table->foreignId('asignado_por')->nullable()->after('user_id')->constrained('users');
        });
    }

    public function down()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['asignado_por']);
            $table->dropColumn('asignado_por');
        });
    }
};

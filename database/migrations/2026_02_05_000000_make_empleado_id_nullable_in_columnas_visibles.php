<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Permitir empleado_id NULL para configuraciones globales (visibles para todos)
     */
    public function up(): void
    {
        Schema::table('columnas_visibles_ejecutivo', function (Blueprint $table) {
            // Primero eliminar la foreign key (esto libera el índice)
            $table->dropForeign(['empleado_id']);
        });

        Schema::table('columnas_visibles_ejecutivo', function (Blueprint $table) {
            // Ahora sí podemos eliminar el índice único
            $table->dropUnique('col_vis_ejec_unique');
        });

        Schema::table('columnas_visibles_ejecutivo', function (Blueprint $table) {
            // Hacer empleado_id nullable
            $table->unsignedBigInteger('empleado_id')->nullable()->change();
            
            // Recrear la foreign key que permita NULL
            $table->foreign('empleado_id')
                  ->references('id')
                  ->on('empleados')
                  ->onDelete('cascade');
            
            // Crear un nuevo índice único que permita múltiples NULLs para diferentes columnas
            // En MySQL, los valores NULL no se consideran duplicados en índices únicos
            $table->unique(['empleado_id', 'columna'], 'col_vis_ejec_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primero eliminar registros con empleado_id NULL
        \DB::table('columnas_visibles_ejecutivo')->whereNull('empleado_id')->delete();
        
        Schema::table('columnas_visibles_ejecutivo', function (Blueprint $table) {
            $table->dropUnique('col_vis_ejec_unique');
            $table->dropForeign(['empleado_id']);
        });

        Schema::table('columnas_visibles_ejecutivo', function (Blueprint $table) {
            $table->unsignedBigInteger('empleado_id')->nullable(false)->change();
            
            $table->foreign('empleado_id')
                  ->references('id')
                  ->on('empleados')
                  ->onDelete('cascade');
                  
            $table->unique(['empleado_id', 'columna'], 'col_vis_ejec_unique');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('recurrencia', ['semanal', 'quincenal', 'mensual'])->default('mensual');
            $table->text('notas')->nullable();
            $table->boolean('archivado')->default(false);
            $table->timestamps();
        });

        Schema::create('proyecto_usuarios', function (Blueprint $table) {
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->primary(['proyecto_id', 'usuario_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['proyecto_id']);
            $table->dropColumn('proyecto_id');
        });
        Schema::dropIfExists('proyecto_usuarios');
        Schema::dropIfExists('proyectos');
    }
};

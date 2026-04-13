<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_proyectos', function (Blueprint $table) {
            $table->string('empresa')->nullable()->change();
            $table->text('resultado')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('legal_proyectos', function (Blueprint $table) {
            $table->string('empresa')->nullable(false)->change();
            $table->text('resultado')->nullable(false)->change();
        });
    }
};

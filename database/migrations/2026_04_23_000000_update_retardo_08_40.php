<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE asistencias 
            SET es_retardo = TRUE 
            WHERE entrada > '08:40:00' 
            AND es_retardo = FALSE
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE asistencias 
            SET es_retardo = FALSE 
            WHERE entrada > '08:40:00' 
            AND entrada < '08:45:00' 
            AND es_retardo = TRUE
        ");
    }
};
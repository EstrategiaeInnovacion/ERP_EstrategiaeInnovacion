<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ampliar ENUM para aceptar ambos valores temporalmente
        DB::statement("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EX','EXP') NULL");
        // 2. Migrar datos existentes
        DB::statement("UPDATE `matriz_seguimiento` SET `impo_ex` = 'EXP' WHERE `impo_ex` = 'EX'");
        // 3. Quitar el valor viejo
        DB::statement("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EXP') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EX','EXP') NULL");
        DB::statement("UPDATE `matriz_seguimiento` SET `impo_ex` = 'EX' WHERE `impo_ex` = 'EXP'");
        DB::statement("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EX') NULL");
    }
};

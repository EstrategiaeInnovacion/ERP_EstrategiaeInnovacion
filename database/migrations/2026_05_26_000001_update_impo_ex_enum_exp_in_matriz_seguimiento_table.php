<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EXP') NULL");
        DB::statement("UPDATE `matriz_seguimiento` SET `impo_ex` = 'EXP' WHERE `impo_ex` = 'EX'");
    }

    public function down(): void
    {
        DB::statement("UPDATE `matriz_seguimiento` SET `impo_ex` = 'EX' WHERE `impo_ex` = 'EXP'");
        DB::statement("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EX') NULL");
    }
};

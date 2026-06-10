<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EX') NULL");
            DB::statement("UPDATE `matriz_seguimiento` SET `impo_ex` = 'IMP' WHERE `impo_ex` = 'IMPO'");
        }
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE `matriz_seguimiento` SET `impo_ex` = 'IMPO' WHERE `impo_ex` = 'IMP'");
            DB::statement("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMPO','EX') NULL");
        }
    }
};

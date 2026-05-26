<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pdo = DB::connection()->getPdo();

        // 1. Ampliar ENUM para aceptar ambos valores temporalmente
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        $pdo->exec("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EX','EXP') NULL");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // 2. Migrar datos existentes
        DB::statement("UPDATE `matriz_seguimiento` SET `impo_ex` = 'EXP' WHERE `impo_ex` = 'EX'");

        // 3. Limpiar valores inválidos que quedaron de migraciones anteriores fallidas (p.ej. '')
        DB::statement("UPDATE `matriz_seguimiento` SET `impo_ex` = NULL WHERE `impo_ex` NOT IN ('IMP','EXP') AND `impo_ex` IS NOT NULL");

        // 4. Quitar el valor viejo
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        $pdo->exec("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EXP') NULL");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function down(): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        $pdo->exec("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EX','EXP') NULL");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        DB::statement("UPDATE `matriz_seguimiento` SET `impo_ex` = 'EX' WHERE `impo_ex` = 'EXP'");

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        $pdo->exec("ALTER TABLE `matriz_seguimiento` MODIFY `impo_ex` ENUM('IMP','EX') NULL");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
};

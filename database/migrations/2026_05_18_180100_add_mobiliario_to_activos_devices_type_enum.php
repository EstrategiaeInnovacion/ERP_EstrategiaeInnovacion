<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'activos';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::connection($this->connection)->getPdo();
        } catch (\Exception) {
            return; // BD de Activos no disponible en este entorno
        }

        if (DB::connection($this->connection)->getDriverName() === 'mysql') {
            DB::connection($this->connection)->statement(
                "ALTER TABLE devices MODIFY COLUMN type ENUM('computer', 'peripheral', 'printer', 'mobiliario', 'other') NOT NULL"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::connection($this->connection)->getPdo();
        } catch (\Exception) {
            return;
        }

        if (DB::connection($this->connection)->getDriverName() === 'mysql') {
            DB::connection($this->connection)->statement(
                "UPDATE devices SET type = 'other' WHERE type = 'mobiliario'"
            );

            DB::connection($this->connection)->statement(
                "ALTER TABLE devices MODIFY COLUMN type ENUM('computer', 'peripheral', 'printer', 'other') NOT NULL"
            );
        }
    }
};
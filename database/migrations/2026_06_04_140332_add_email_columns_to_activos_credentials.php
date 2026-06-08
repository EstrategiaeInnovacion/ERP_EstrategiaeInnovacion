<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'activos';

    public function up(): void
    {
        try {
            DB::connection('activos')->getPdo();
        } catch (\Exception) {
            return; // BD de Activos no disponible en este entorno
        }

        // Añadir email si no existe
        if (! Schema::connection('activos')->hasColumn('credentials', 'email')) {
            Schema::connection('activos')->table('credentials', function (Blueprint $table) {
                $table->string('email', 500)->nullable()->after('username');
            });
        }

        // Añadir email_password si no existe
        if (! Schema::connection('activos')->hasColumn('credentials', 'email_password')) {
            Schema::connection('activos')->table('credentials', function (Blueprint $table) {
                $table->text('email_password')->nullable()->after('email');
            });
        }

        // Cambiar password y email_password a TEXT para evitar truncamiento del encrypt()
        // (varchar 255 puede ser insuficiente para el token cifrado de Laravel)
        DB::connection('activos')->statement(
            "ALTER TABLE credentials
             MODIFY COLUMN password TEXT NULL,
             MODIFY COLUMN email_password TEXT NULL"
        );
    }

    public function down(): void
    {
        try {
            DB::connection('activos')->getPdo();
        } catch (\Exception) {
            return;
        }

        Schema::connection('activos')->table('credentials', function (Blueprint $table) {
            if (Schema::connection('activos')->hasColumn('credentials', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::connection('activos')->hasColumn('credentials', 'email_password')) {
                $table->dropColumn('email_password');
            }
        });
    }
};

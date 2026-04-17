<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computer_profiles', function (Blueprint $table) {
            $table->dateTime('next_maintenance_at')->nullable()->after('last_maintenance_at')
                ->comment('Fecha calculada del próximo mantenimiento (last_maintenance_at + 4 meses)');
            $table->date('maintenance_reminder_sent_at')->nullable()->after('next_maintenance_at')
                ->comment('Fecha en que se envió el último recordatorio (evita duplicados el mismo día)');
        });
    }

    public function down(): void
    {
        Schema::table('computer_profiles', function (Blueprint $table) {
            $table->dropColumn(['next_maintenance_at', 'maintenance_reminder_sent_at']);
        });
    }
};

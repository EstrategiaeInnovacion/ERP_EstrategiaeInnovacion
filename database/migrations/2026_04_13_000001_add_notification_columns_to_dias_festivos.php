<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dias_festivos', function (Blueprint $table) {
            if (! Schema::hasColumn('dias_festivos', 'notificacion_enviada')) {
                $table->boolean('notificacion_enviada')->default(false);
            }
            if (! Schema::hasColumn('dias_festivos', 'notificacion_enviada_at')) {
                $table->timestamp('notificacion_enviada_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dias_festivos', function (Blueprint $table) {
            $table->dropColumn(['notificacion_enviada', 'notificacion_enviada_at']);
        });
    }
};

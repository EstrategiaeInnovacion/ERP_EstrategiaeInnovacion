<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para bloquear horarios específicos o rangos de fechas
        Schema::create('maintenance_blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date_start');
            $table->date('date_end')->nullable(); // Si es null, solo bloquea date_start
            $table->time('time_slot')->nullable(); // Si es null, bloquea todo el día
            $table->string('reason')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['date_start', 'date_end']);
            $table->index('time_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_blocked_slots');
    }
};

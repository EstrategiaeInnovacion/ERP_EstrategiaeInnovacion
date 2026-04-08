<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->index('status');
            $table->index('type');
            $table->index('warranty_expiration');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->index('returned_at');
            $table->index('assigned_at');
            $table->index(['device_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropIndex(['warranty_expiration']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['returned_at']);
            $table->dropIndex(['assigned_at']);
            $table->dropIndex(['device_id', 'returned_at']);
        });
    }
};

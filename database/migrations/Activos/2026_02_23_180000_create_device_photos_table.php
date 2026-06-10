<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('device_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('caption')->nullable();
            $dbMain = config('database.connections.mysql.database', 'erp');
            $table->foreignId('uploaded_by')->nullable()->constrained($dbMain . '.users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_photos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('device_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->enum('type', ['factura', 'garantia', 'contrato', 'manual', 'otro'])->default('otro');
            $dbMain = config('database.connections.mysql.database', 'erp');
            $table->foreignId('uploaded_by')->nullable()->constrained($dbMain . '.users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_documents');
    }
};

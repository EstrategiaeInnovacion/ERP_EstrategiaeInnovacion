<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('brand');
            $table->string('model');
            $table->string('serial_number')->unique();
            $table->enum('type', ['computer', 'peripheral', 'printer', 'other']);
            $table->enum('status', ['available', 'assigned', 'maintenance', 'broken'])->default('available');
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiration')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

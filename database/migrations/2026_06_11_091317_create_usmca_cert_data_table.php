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
        Schema::create('usmca_cert_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id')->unique();
            $table->json('cert_data');
            $table->timestamps();

            $table->foreign('bom_id')->references('id')->on('boms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usmca_cert_data');
    }
};

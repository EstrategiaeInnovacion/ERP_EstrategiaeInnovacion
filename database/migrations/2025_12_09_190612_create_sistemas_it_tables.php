<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Blocked Emails
        Schema::create('blocked_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('reason')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 2. Maintenance Slots
        Schema::create('maintenance_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('booked_count')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            
            $table->unique(['date', 'start_time', 'end_time']);
        });

        // 3. Inventory Items
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_producto')->index();
            $table->string('identificador')->nullable();
            $table->string('nombre');
            $table->string('categoria');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable();
            $table->enum('estado', ['disponible', 'prestado', 'mantenimiento', 'reservado', 'dañado'])->default('disponible')->index();
            $table->boolean('es_funcional')->default(1)->index();
            $table->string('ubicacion')->nullable();
            $table->text('descripcion_general')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        // ... código anterior (inventory_items)

        // 3.5 Help Sections (Restaurado)
        Schema::create('help_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->integer('section_order')->default(0);
            $table->boolean('is_active')->default(1);
            $table->longText('images')->nullable(); // Para guardar el JSON de imágenes
            $table->timestamps();
        });

        // 4. Computer Profiles (Nota: Tiene dependencia circular con Tickets, se resuelve al final)
        Schema::create('computer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('disk_type')->nullable();
            $table->string('ram_capacity')->nullable();
            $table->string('battery_status')->nullable();
            $table->text('aesthetic_observations')->nullable();
            $table->longText('replacement_components')->nullable(); // JSON
            $table->dateTime('last_maintenance_at')->nullable();
            $table->boolean('is_loaned')->default(0);
            $table->string('loaned_to_name')->nullable();
            $table->string('loaned_to_email')->nullable();
            $table->unsignedBigInteger('last_ticket_id')->nullable(); // FK to tickets added later
            $table->timestamps();
        });

        // 5. Tickets
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->string('nombre_solicitante');
            $table->string('correo_solicitante');
            $table->string('nombre_programa')->nullable();
            $table->text('descripcion_problema');
            $table->longText('imagenes')->nullable(); // JSON o Base64
            $table->enum('estado', ['abierto', 'en_proceso', 'cerrado'])->default('abierto');
            $table->boolean('closed_by_user')->default(0);
            $table->boolean('is_read')->default(0);
            $table->boolean('user_has_updates')->default(0);
            $table->timestamp('user_notified_at')->nullable();
            $table->timestamp('user_last_read_at')->nullable();
            $table->text('user_notification_summary')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->timestamp('closed_by_user_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('tipo_problema', ['software', 'hardware', 'mantenimiento']);
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->nullable();
            
            // Relaciones
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('maintenance_slot_id')->nullable()->constrained('maintenance_slots')->onDelete('set null');
            $table->foreignId('computer_profile_id')->nullable()->constrained('computer_profiles')->onDelete('set null');

            // Campos extra de mantenimiento/admin
            $table->string('equipment_password')->nullable();
            $table->longText('imagenes_admin')->nullable();
            $table->dateTime('maintenance_scheduled_at')->nullable();
            $table->text('maintenance_details')->nullable();
            $table->string('equipment_identifier')->nullable();
            $table->string('equipment_brand')->nullable();
            $table->string('equipment_model')->nullable();
            $table->string('disk_type')->nullable();
            $table->string('ram_capacity')->nullable();
            $table->string('battery_status')->nullable();
            $table->text('aesthetic_observations')->nullable();
            $table->longText('replacement_components')->nullable();

            $table->timestamps();
        });

        // 6. Maintenance Bookings
        Schema::create('maintenance_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_slot_id')->constrained('maintenance_slots')->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->text('additional_details')->nullable();
            $table->timestamps();
        });

        // Agregar la llave foránea faltante en computer_profiles
        Schema::table('computer_profiles', function (Blueprint $table) {
            $table->foreign('last_ticket_id')->references('id')->on('tickets')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_bookings');
        // Romper referencia circular antes de borrar
        Schema::table('computer_profiles', function (Blueprint $table) {
            $table->dropForeign(['last_ticket_id']);
        });
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('computer_profiles');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('maintenance_slots');
        Schema::dropIfExists('blocked_emails');
    }
};
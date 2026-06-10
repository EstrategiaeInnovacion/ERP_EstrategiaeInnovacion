<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('valores_campos_personalizados');
        Schema::dropIfExists('post_operacion_operacion');
        Schema::dropIfExists('historico_matriz_sgm');
        Schema::dropIfExists('operacion_comentarios');
        Schema::dropIfExists('operaciones_logisticas');
        Schema::dropIfExists('post_operaciones');
        Schema::dropIfExists('columnas_visibles_ejecutivo');
        Schema::dropIfExists('campo_personalizado_ejecutivo');
        Schema::dropIfExists('campos_personalizados_matriz');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Datos eliminados intencionalmente — existen respaldos de BD
    }
};

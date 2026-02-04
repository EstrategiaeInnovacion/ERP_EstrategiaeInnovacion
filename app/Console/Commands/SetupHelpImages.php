<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupHelpImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'help:setup-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear directorio para imágenes del manual de ayuda y configurar permisos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Configurando directorio para imágenes del manual de ayuda...');

        // Crear directorio si no existe
        $helpImagesPath = storage_path('app/public/help-images');
        
        if (!File::exists($helpImagesPath)) {
            File::makeDirectory($helpImagesPath, 0755, true);
            $this->info("✅ Directorio creado: {$helpImagesPath}");
        } else {
            $this->info("✅ Directorio ya existe: {$helpImagesPath}");
        }

        // Verificar permisos
        if (is_writable($helpImagesPath)) {
            $this->info('✅ Permisos de escritura correctos');
        } else {
            $this->warn('⚠️  Advertencia: El directorio no tiene permisos de escritura');
        }

        // Verificar enlace simbólico
        $publicStoragePath = public_path('storage');
        if (File::exists($publicStoragePath)) {
            $this->info('✅ Enlace simbólico de storage existe');
        } else {
            $this->warn('⚠️  Ejecuta: php artisan storage:link');
        }

        // Crear archivo .gitkeep para mantener el directorio en git
        $gitkeepPath = $helpImagesPath . '/.gitkeep';
        if (!File::exists($gitkeepPath)) {
            File::put($gitkeepPath, '');
            $this->info('✅ Archivo .gitkeep creado');
        }

        $this->info('🎉 Configuración completada. El sistema está listo para subir imágenes.');
        
        return Command::SUCCESS;
    }
}
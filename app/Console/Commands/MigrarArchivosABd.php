<?php

namespace App\Console\Commands;

use App\Models\Capacitacion;
use App\Models\CapacitacionAdjunto;
use App\Models\Legal\LegalArchivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrarArchivosABd extends Command
{
    protected $signature   = 'archivos:migrar-a-bd {--modulo=all : legal|capacitacion|activos|all}';
    protected $description = 'Convierte archivos en disco a contenido binario en la base de datos.';

    public function handle(): int
    {
        $modulo = $this->option('modulo');

        if ($modulo === 'all' || $modulo === 'legal') {
            $this->migrarLegal();
        }

        if ($modulo === 'all' || $modulo === 'capacitacion') {
            $this->migrarCapacitacion();
        }

        if ($modulo === 'all' || $modulo === 'activos') {
            $this->migrarActivos();
        }

        $this->info('✓ Migración de archivos completada.');
        return self::SUCCESS;
    }

    // ---------------------------------------------------------------
    // LEGAL
    // ---------------------------------------------------------------

    private function migrarLegal(): void
    {
        $this->info('→ Módulo Legal: convirtiendo archivos...');

        $archivos = LegalArchivo::withoutGlobalScope('sin_contenido')
            ->select(['id', 'nombre', 'ruta', 'mime_type', 'contenido'])
            ->whereNull('contenido')
            ->where('es_url', false)
            ->get();

        $convertidos = 0;
        $perdidos    = 0;

        foreach ($archivos as $archivo) {
            if (! $archivo->ruta) {
                continue;
            }

            $diskPath = storage_path('app/public/' . $archivo->ruta);

            if (file_exists($diskPath) && is_file($diskPath)) {
                $contenido = file_get_contents($diskPath);
                if ($contenido !== false) {
                    DB::table('legal_archivos')
                        ->where('id', $archivo->id)
                        ->update(['contenido' => $contenido]);
                    $convertidos++;
                    $this->line("  [legal] ID {$archivo->id} → convertido ({$archivo->nombre})");
                    continue;
                }
            }

            // Archivo perdido: limpiar ruta para que no dé 404
            DB::table('legal_archivos')
                ->where('id', $archivo->id)
                ->update(['ruta' => null]);
            $perdidos++;
            $this->warn("  [legal] ID {$archivo->id} → PERDIDO, referencia limpiada ({$archivo->nombre})");
        }

        $this->info("  Legal: {$convertidos} convertidos, {$perdidos} referencias limpiadas.");
    }

    // ---------------------------------------------------------------
    // CAPACITACIÓN
    // ---------------------------------------------------------------

    private function migrarCapacitacion(): void
    {
        $this->info('→ Módulo Capacitación: convirtiendo videos y adjuntos...');

        // Videos
        $videos = Capacitacion::withoutGlobalScope('sin_contenido')
            ->select(['id', 'titulo', 'archivo_path', 'archivo_contenido', 'archivo_mime_type'])
            ->whereNotNull('archivo_path')
            ->whereNull('archivo_contenido')
            ->get();

        $convertidos = 0;
        $perdidos    = 0;

        foreach ($videos as $video) {
            $ruta = storage_path('app/public/' . $video->archivo_path);

            if (file_exists($ruta) && is_file($ruta)) {
                $contenido = file_get_contents($ruta);
                if ($contenido !== false) {
                    $mime = mime_content_type($ruta) ?: 'video/mp4';
                    DB::table('capacitaciones')
                        ->where('id', $video->id)
                        ->update(['archivo_contenido' => $contenido, 'archivo_mime_type' => $mime]);
                    $convertidos++;
                    $this->line("  [cap-video] ID {$video->id} → convertido ({$video->titulo})");
                    continue;
                }
            }

            // Video perdido: limpiar path
            DB::table('capacitaciones')
                ->where('id', $video->id)
                ->update(['archivo_path' => null]);
            $perdidos++;
            $this->warn("  [cap-video] ID {$video->id} → PERDIDO, referencia limpiada ({$video->titulo})");
        }

        // Adjuntos
        $adjuntos = CapacitacionAdjunto::withoutGlobalScope('sin_contenido')
            ->select(['id', 'titulo', 'archivo_path', 'archivo_contenido', 'archivo_mime_type'])
            ->whereNotNull('archivo_path')
            ->whereNull('archivo_contenido')
            ->get();

        $convAdj = 0;
        $perdAdj = 0;

        foreach ($adjuntos as $adj) {
            $ruta = storage_path('app/public/' . $adj->archivo_path);

            if (file_exists($ruta) && is_file($ruta)) {
                $contenido = file_get_contents($ruta);
                if ($contenido !== false) {
                    $mime = mime_content_type($ruta) ?: 'application/octet-stream';
                    DB::table('capacitacion_adjuntos')
                        ->where('id', $adj->id)
                        ->update(['archivo_contenido' => $contenido, 'archivo_mime_type' => $mime]);
                    $convAdj++;
                    $this->line("  [cap-adj] ID {$adj->id} → convertido ({$adj->titulo})");
                    continue;
                }
            }

            DB::table('capacitacion_adjuntos')
                ->where('id', $adj->id)
                ->update(['archivo_path' => null]);
            $perdAdj++;
            $this->warn("  [cap-adj] ID {$adj->id} → PERDIDO, referencia limpiada ({$adj->titulo})");
        }

        $this->info("  Videos: {$convertidos} convertidos, {$perdidos} perdidos.");
        $this->info("  Adjuntos: {$convAdj} convertidos, {$perdAdj} perdidos.");
    }

    // ---------------------------------------------------------------
    // ACTIVOS (device_photos en BD externa)
    // ---------------------------------------------------------------

    private function migrarActivos(): void
    {
        $this->info('→ Módulo Activos: convirtiendo fotos...');

        $fotos = DB::connection('activos')
            ->table('device_photos')
            ->whereNull('file_data')
            ->whereNotNull('file_path')
            ->select(['id', 'file_path'])
            ->get();

        $convertidos = 0;
        $perdidos    = 0;

        $localBase = realpath(storage_path('app/private'));

        foreach ($fotos as $foto) {
            $filePath = $foto->file_path;

            // Intentar desde storage local del ERP (activos-fotos/)
            if ($localBase) {
                $localPath = $localBase . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $filePath), '\/ ');
                $realFile  = realpath($localPath);
                if ($realFile && str_starts_with($realFile, $localBase) && is_file($realFile)) {
                    $contenido = file_get_contents($realFile);
                    if ($contenido !== false) {
                        $mime = mime_content_type($realFile) ?: 'image/jpeg';
                        DB::connection('activos')
                            ->table('device_photos')
                            ->where('id', $foto->id)
                            ->update(['file_data' => $contenido, 'mime_type' => $mime]);
                        $convertidos++;
                        $this->line("  [activos] foto ID {$foto->id} → convertida desde disco");
                        continue;
                    }
                }
            }

            // Foto de sistema externo (device-photos/) ya no existe: limpiar file_path
            DB::connection('activos')
                ->table('device_photos')
                ->where('id', $foto->id)
                ->update(['file_path' => null]);
            $perdidos++;
            $this->warn("  [activos] foto ID {$foto->id} → PERDIDA ({$filePath}), referencia limpiada");
        }

        $this->info("  Activos: {$convertidos} convertidas, {$perdidos} referencias limpiadas.");
    }
}

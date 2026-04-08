<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CheckPdfDependencies extends Command
{
    protected $signature   = 'pdf:check-deps';
    protected $description = 'Verifica si Ghostscript, Poppler (pdfimages) y qpdf están instalados en el servidor';

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Verificación de dependencias para Digitalización de documentos (VUCEM)');
        $this->newLine();

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $allOk     = true;

        // ── 1. Ghostscript ────────────────────────────────────────────────────
        $this->line('<fg=cyan;options=bold>[ 1/4 ] Ghostscript</> — requerido para convertir, comprimir y validar PDFs');
        $gs = $this->findGhostscript($isWindows);
        $allOk = $this->reportBinary($gs, 'GHOSTSCRIPT_PATH', $isWindows
            ? 'winget install --id ArtifexSoftware.GhostScript  (o descarga desde ghostscript.com)'
            : 'apt install ghostscript  /  yum install ghostscript') && $allOk;

        // ── 2. pdfimages (Poppler) ────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan;options=bold>[ 2/4 ] pdfimages</> (Poppler) — requerido para verificar DPI exacto en validación');
        $pdfimages = $this->findPdfimages($isWindows);
        $allOk = $this->reportBinary($pdfimages, 'PDFIMAGES_PATH', $isWindows
            ? 'Descarga Poppler para Windows: https://github.com/oschwartz10612/poppler-windows/releases'
            : 'apt install poppler-utils  /  yum install poppler-utils') && $allOk;

        // ── 3. qpdf ───────────────────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan;options=bold>[ 3/4 ] qpdf</> — requerido para detectar encriptado en validación');
        $qpdf = $this->findQpdf($isWindows);
        $allOk = $this->reportBinary($qpdf, 'QPDF_PATH', $isWindows
            ? 'Descarga qpdf para Windows: https://github.com/qpdf/qpdf/releases'
            : 'apt install qpdf  /  yum install qpdf') && $allOk;

        // ── 4. PHP extensions ─────────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan;options=bold>[ 4/4 ] Extensiones PHP</> — zip y fileinfo');
        $zipOk      = extension_loaded('zip');
        $fileinfoOk = extension_loaded('fileinfo');
        $this->line('  zip      : ' . ($zipOk      ? '<fg=green>✔ cargada</>'  : '<fg=red>✘ no disponible — instala php-zip</>'));
        $this->line('  fileinfo : ' . ($fileinfoOk ? '<fg=green>✔ cargada</>'  : '<fg=red>✘ no disponible — instala php-fileinfo</>'));
        if (!$zipOk || !$fileinfoOk) $allOk = false;

        // ── Variables .env actuales ───────────────────────────────────────────
        $this->newLine();
        $this->components->info('Variables actuales en .env / config/pdftools.php');
        $this->table(
            ['Variable', 'Valor configurado', 'Estado'],
            [
                ['GHOSTSCRIPT_PATH', config('pdftools.ghostscript') ?: '(vacío — autodetección)', $gs  ? '<fg=green>OK</>' : '<fg=yellow>Sin ruta explícita</>'],
                ['PDFIMAGES_PATH',   config('pdftools.pdfimages')   ?: '(vacío — autodetección)', $pdfimages ? '<fg=green>OK</>' : '<fg=yellow>Sin ruta explícita</>'],
                ['QPDF_PATH',        config('pdftools.qpdf')        ?: '(vacío — autodetección)', $qpdf ? '<fg=green>OK</>' : '<fg=yellow>Sin ruta explícita</>'],
                ['IMAGEMAGICK_PATH', config('pdftools.imagemagick') ?: '(vacío — no requerido)',   '<fg=gray>Opcional</>'],
            ]
        );

        // ── Resumen ───────────────────────────────────────────────────────────
        $this->newLine();
        if ($allOk) {
            $this->components->success('Todas las dependencias esenciales están disponibles. El módulo de Digitalización funcionará correctamente.');
        } else {
            $this->components->warn('Faltan dependencias. El módulo puede funcionar parcialmente.');
            $this->newLine();
            $this->line('<fg=yellow>Para configurar rutas explícitas, añade al archivo .env del ERP:</>');
            $this->line('  GHOSTSCRIPT_PATH=/ruta/completa/a/gswin64c.exe');
            $this->line('  PDFIMAGES_PATH=/ruta/completa/a/pdfimages.exe');
            $this->line('  QPDF_PATH=/ruta/completa/a/qpdf.exe');
            $this->newLine();
            $this->line('Luego ejecuta: <fg=cyan>php artisan config:clear</>');
        }

        $this->newLine();
        return $allOk ? self::SUCCESS : self::FAILURE;
    }

    // ── Helpers de búsqueda ───────────────────────────────────────────────────

    protected function findGhostscript(bool $isWindows): ?string
    {
        $configured = config('pdftools.ghostscript');
        if (!empty($configured) && file_exists($configured)) {
            return $configured;
        }

        if ($isWindows) {
            $paths = glob('C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe') ?: [];
            rsort($paths, SORT_NATURAL);
            foreach ($paths as $p) {
                if (file_exists($p)) return $p;
            }
            $proc = new Process(['gswin64c', '--version']);
            $proc->run();
            if ($proc->isSuccessful()) return 'gswin64c (en PATH)';
        } else {
            $proc = Process::fromShellCommandline('which gs 2>/dev/null');
            $proc->run();
            $path = trim($proc->getOutput());
            if (!empty($path)) return $path;
        }

        return null;
    }

    protected function findPdfimages(bool $isWindows): ?string
    {
        $configured = config('pdftools.pdfimages');
        if (!empty($configured) && file_exists($configured)) {
            return $configured;
        }

        if ($isWindows) {
            $globs = glob('C:\\Poppler\\Release-*\\poppler-*\\Library\\bin\\pdfimages.exe') ?: [];
            rsort($globs, SORT_NATURAL);
            foreach ($globs as $p) {
                if (file_exists($p)) return $p;
            }
            foreach (['C:\\Poppler\\Library\\bin\\pdfimages.exe', 'C:\\Program Files\\poppler\\bin\\pdfimages.exe'] as $p) {
                if (file_exists($p)) return $p;
            }
            $proc = new Process(['pdfimages', '-v']);
            $proc->run();
            if ($proc->isSuccessful() || str_contains($proc->getErrorOutput(), 'pdfimages')) {
                return 'pdfimages (en PATH)';
            }
        } else {
            $proc = Process::fromShellCommandline('which pdfimages 2>/dev/null');
            $proc->run();
            $path = trim($proc->getOutput());
            if (!empty($path)) return $path;
        }

        return null;
    }

    protected function findQpdf(bool $isWindows): ?string
    {
        $configured = config('pdftools.qpdf');
        if (!empty($configured) && file_exists($configured)) {
            return $configured;
        }

        if ($isWindows) {
            foreach (['C:\\Program Files\\qpdf\\bin\\qpdf.exe', 'C:\\qpdf\\bin\\qpdf.exe'] as $p) {
                if (file_exists($p)) return $p;
            }
            $proc = new Process(['qpdf', '--version']);
            $proc->run();
            if ($proc->isSuccessful()) return 'qpdf (en PATH)';
        } else {
            $proc = Process::fromShellCommandline('which qpdf 2>/dev/null');
            $proc->run();
            $path = trim($proc->getOutput());
            if (!empty($path)) return $path;
        }

        return null;
    }

    protected function reportBinary(?string $path, string $envVar, string $installHint): bool
    {
        if ($path) {
            $this->line("  <fg=green>✔ Encontrado:</> {$path}");
            return true;
        }

        $this->line("  <fg=red>✘ No encontrado.</>");
        $this->line("  <fg=yellow>  Instalar:</> {$installHint}");
        $this->line("  <fg=yellow>  O configura:</> {$envVar}=/ruta/completa en el .env");
        return false;
    }
}

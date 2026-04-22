<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Services\VucemPdfConverter;
use App\Services\VucemImageExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DigitalizacionController extends Controller
{
    protected VucemPdfConverter $converter;
    protected VucemImageExtractor $extractor;

    public function __construct(VucemPdfConverter $converter, VucemImageExtractor $extractor)
    {
        $this->converter = $converter;
        $this->extractor = $extractor;
    }

    // =========================================================================
    // VISTA PRINCIPAL
    // =========================================================================

    public function index()
    {
        return view('Legal.digitalizacion.index');
    }

    // =========================================================================
    // 1. CONVERTIR PDF A FORMATO VUCEM
    // =========================================================================

    // Límites de tamaño por modo
    protected const MAX_VUCEM_BYTES    = 3  * 1024 * 1024;  // 3 MB  — VUCEM / MVE
    protected const MAX_GENERAL_BYTES  = 10 * 1024 * 1024;  // 10 MB — Otros trámites

    protected function maxBytesForMode(string $modo): int
    {
        return $modo === 'general' ? self::MAX_GENERAL_BYTES : self::MAX_VUCEM_BYTES;
    }

    public function convert(Request $request)
    {
        $request->validate([
            'file'          => 'required|file|mimes:pdf|max:51200',
            'splitEnabled'  => 'nullable|boolean',
            'numberOfParts' => 'nullable|integer|min:2|max:18',
            'orientation'   => 'nullable|string|in:auto,portrait,landscape',
            'modo'          => 'nullable|in:vucem,general',
        ]);

        $modo            = $request->input('modo', 'vucem');
        $maxBytes        = $this->maxBytesForMode($modo);

        // Ajustar el umbral de auto-división según el modo
        Config::set('vucem.auto_split_threshold', $maxBytes);

        $file            = $request->file('file');
        $originalName    = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $splitEnabled    = filter_var($request->input('splitEnabled', false), FILTER_VALIDATE_BOOLEAN);
        $numberOfParts   = (int) $request->input('numberOfParts', 2);
        $orientation     = $request->input('orientation', 'auto');
        $originalSize    = $file->getSize();

        $uniqueId        = uniqid();
        $inputFileName   = $uniqueId . '_input.pdf';
        $outputFileName  = $uniqueId . '_VUCEM.pdf';

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $inputPath  = $tempDir . DIRECTORY_SEPARATOR . $inputFileName;
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . $outputFileName;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            copy($file->getRealPath(), $inputPath);
            @unlink($file->getRealPath());
        } else {
            $file->move($tempDir, $inputFileName);
        }

        try {
            $result = $this->converter->convertToVucemOptimized(
                $inputPath, $outputPath, $splitEnabled, $numberOfParts, $orientation
            );

            if ($result['auto_divided'] && !empty($result['parts'])) {
                return $this->buildDividedResponse($result, $originalName, $inputPath, $outputPath, $modo, $maxBytes);
            }
            if ($splitEnabled && isset($result['parts']) && count($result['parts']) > 0) {
                return $this->buildDividedResponse($result, $originalName, $inputPath, $outputPath, $modo, $maxBytes);
            }
            if (!file_exists($outputPath)) {
                throw new \Exception('Error al convertir el archivo.');
            }
            return $this->buildSingleResponse($result, $originalName, $outputPath, $inputPath, $modo, $maxBytes);

        } catch (\Exception $e) {
            Log::error('DigitalizacionController::convert error', ['error' => $e->getMessage()]);
            $this->cleanupFiles([$inputPath, $outputPath]);
            return response()->json(['success' => false, 'error' => 'Error durante la conversión: ' . $e->getMessage()], 500);
        }
    }

    protected function buildDividedResponse(array $result, string $originalName, string $inputPath, string $outputPath, string $modo = 'vucem', int $maxBytes = self::MAX_VUCEM_BYTES): \Illuminate\Http\JsonResponse
    {
        $suffix = $modo === 'general' ? '_10MB' : '_VUCEM';
        $files  = [];
        foreach ($result['parts'] as $partInfo) {
            $validation = $this->converter->validateVucemCompliance($partInfo['path']);
            $sizeMb     = $partInfo['size_mb'] ?? round(filesize($partInfo['path']) / (1024 * 1024), 2);
            $files[] = [
                'name'       => $originalName . '_parte' . $partInfo['part'] . $suffix . '.pdf',
                'content'    => base64_encode(file_get_contents($partInfo['path'])),
                'size'       => $partInfo['size'],
                'size_mb'    => $sizeMb,
                'part'       => $partInfo['part'],
                'pages'      => $partInfo['pages'],
                'valid'      => $validation['valid'] ?? false,
                'exceeds_limit' => $sizeMb > ($maxBytes / (1024 * 1024)),
            ];
            @unlink($partInfo['path']);
        }
        $this->cleanupFiles([$inputPath, $outputPath]);

        return response()->json([
            'success'              => true,
            'split'                => true,
            'modo'                 => $modo,
            'max_size_mb'          => $maxBytes / (1024 * 1024),
            'auto_divided'         => $result['auto_divided'],
            'files'                => $files,
            'total_parts'          => count($files),
            'original_size_mb'     => $result['original_size_mb'],
            'converted_size_mb'    => $result['converted_size_mb'],
            'size_change_percent'  => $result['size_change_percent'],
            'was_reduced'          => $result['was_reduced'],
            'compression_attempts' => $result['compression_attempts'],
            'final_quality'        => $result['final_quality'],
            'warnings'             => $result['warnings'],
            'messages'             => $result['messages'],
        ]);
    }

    protected function buildSingleResponse(array $result, string $originalName, string $outputPath, string $inputPath, string $modo = 'vucem', int $maxBytes = self::MAX_VUCEM_BYTES): \Illuminate\Http\JsonResponse
    {
        $validation   = $this->converter->validateVucemCompliance($outputPath);
        $dpiValidation = $this->converter->validateDpi($outputPath);
        $fileSize     = filesize($outputPath);
        $sizeMB       = round($fileSize / (1024 * 1024), 2);
        $maxSizeMB    = $maxBytes / (1024 * 1024);
        $exceedsLimit = $sizeMB > $maxSizeMB;

        $validationMessages = array_merge($result['messages'], $result['warnings']);

        if (isset($dpiValidation['total_images']) && $dpiValidation['total_images'] > 0) {
            if ($dpiValidation['valid']) {
                $validationMessages[] = "✓ Todas las imágenes ({$dpiValidation['total_images']}) están a exactamente 300 DPI";
            } else {
                $validationMessages[] = "⚠️ {$dpiValidation['invalid_count']} de {$dpiValidation['total_images']} imágenes no están a 300 DPI exactos";
            }
        }

        if ($exceedsLimit) {
            $validationMessages[] = "⚠️ El archivo ({$sizeMB} MB) supera el límite de {$maxSizeMB} MB para " . ($modo === 'vucem' ? 'VUCEM/MVE' : 'otros trámites') . ". Considera dividirlo en partes.";
        }

        $suffix           = $modo === 'general' ? '_10MB' : '_VUCEM_300DPI';
        $convertedContent = file_get_contents($outputPath);
        $this->cleanupFiles([$inputPath, $outputPath]);

        return response()->json([
            'success'              => true,
            'split'                => false,
            'modo'                 => $modo,
            'max_size_mb'          => $maxSizeMB,
            'exceeds_limit'        => $exceedsLimit,
            'file'                 => [
                'name'    => $originalName . $suffix . '.pdf',
                'content' => base64_encode($convertedContent),
                'size'    => $fileSize,
                'size_mb' => $sizeMB,
            ],
            'original_size_mb'     => $result['original_size_mb'],
            'converted_size_mb'    => $result['converted_size_mb'],
            'size_change_percent'  => $result['size_change_percent'],
            'was_reduced'          => $result['was_reduced'],
            'compression_attempts' => $result['compression_attempts'],
            'final_quality'        => $result['final_quality'],
            'total_pages'          => $result['total_pages'] ?? 0,
            'warnings'             => $result['warnings'],
            'messages'             => $validationMessages,
            'exceeded_threshold'   => $result['exceeded_threshold'],
            'valid'                => $validation['valid'] ?? false,
        ]);
    }

    // =========================================================================
    // 2. VALIDAR PDF CONTRA ESTÁNDAR VUCEM
    // =========================================================================

    public function validatePdf(Request $request)
    {
        $request->validate([
            'pdf'  => 'required|file|mimes:pdf|max:51200',
            'modo' => 'nullable|in:vucem,general',
        ]);

        $modo      = $request->input('modo', 'vucem');
        $maxBytes  = $this->maxBytesForMode($modo);
        $maxMbLabel = ($maxBytes / (1024 * 1024)) . ' MB';

        $file = $request->file('pdf');

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $uniqueId = uniqid();
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $uniqueId . '_validar.pdf';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            copy($file->getRealPath(), $tempPath);
        } else {
            $file->move($tempDir, $uniqueId . '_validar.pdf');
        }

        $checks = [];

        try {
            // 1) Tamaño (límite depende del modo)
            $sizeBytes = filesize($tempPath);
            $sizeMb    = round($sizeBytes / (1024 * 1024), 2);
            $checks['size'] = [
                'label' => 'Tamaño < ' . $maxMbLabel,
                'ok'    => $sizeBytes <= $maxBytes,
                'value' => $sizeMb . ' MB',
            ];

            // 2) Versión del PDF (VUCEM acepta 1.4 en adelante)
            $version = $this->getPdfVersion($tempPath);
            $checks['version'] = [
                'label' => 'Versión PDF 1.4 o superior',
                'ok'    => $version !== null && version_compare($version, '1.4', '>='),
                'value' => $version ?: 'No detectada',
            ];

            // 3) Escala de grises
            $grayResult = $this->checkGrayscale($tempPath);
            $checks['grayscale'] = [
                'label' => 'Contenido en escala de grises',
                'ok'    => $grayResult['is_gray'],
                'value' => $grayResult['detail'],
            ];

            // 4) Resolución DPI (exactamente 300)
            $dpiResult = $this->checkDpi($tempPath);
            $checks['dpi'] = [
                'label'  => 'Resolución exacta 300 DPI',
                'ok'     => $dpiResult['is_valid'],
                'value'  => $dpiResult['detail'],
                'status' => $dpiResult['status'] ?? ($dpiResult['is_valid'] ? 'ok' : 'error'),
                'pages'  => $dpiResult['pages'] ?? [],
                'images' => $dpiResult['images'] ?? [],
            ];

            // 5) Sin contraseña / sin encriptado
            $encryption = $this->checkEncryption($tempPath);
            $checks['encryption'] = [
                'label' => 'Sin contraseña / sin encriptar',
                'ok'    => $encryption['is_unencrypted'],
                'value' => $encryption['detail'],
            ];

            $allOk = collect($checks)->every(fn($c) => $c['ok']);

            return response()->json([
                'success'      => true,
                'allOk'        => $allOk,
                'fileName'     => $file->getClientOriginalName(),
                'modo'         => $modo,
                'max_size_mb'  => $maxBytes / (1024 * 1024),
                'checks'       => $checks,
            ]);

        } catch (\Exception $e) {
            Log::error('DigitalizacionController::validate error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Error durante la validación: ' . $e->getMessage()], 500);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    // =========================================================================
    // 3. COMPRIMIR PDF
    // =========================================================================

    public function compress(Request $request)
    {
        $request->validate([
            'file'             => 'required|file|mimes:pdf|max:102400',
            'compressionLevel' => 'required|in:screen,ebook,printer,prepress',
        ]);

        $file             = $request->file('file');
        $originalName     = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $compressionLevel = $request->input('compressionLevel', 'printer');
        $originalSize     = $file->getSize();

        $uniqueId       = uniqid();
        $tempDir        = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $inputFileName  = $uniqueId . '_input.pdf';
        $outputFileName = $uniqueId . '_compressed.pdf';
        $inputPath      = $tempDir . DIRECTORY_SEPARATOR . $inputFileName;
        $outputPath     = $tempDir . DIRECTORY_SEPARATOR . $outputFileName;

        $file->move($tempDir, $inputFileName);

        try {
            $this->converter->compressPdf($inputPath, $outputPath, $compressionLevel);

            if (!file_exists($outputPath)) {
                throw new \Exception('Error al comprimir el archivo.');
            }

            $outputSize      = filesize($outputPath);
            $sizeMB          = round($outputSize / (1024 * 1024), 2);
            $inputSizeMB     = round($originalSize / (1024 * 1024), 2);
            $reductionPercent = round((($originalSize - $outputSize) / $originalSize) * 100, 2);
            $compressedContent = file_get_contents($outputPath);

            $this->cleanupFiles([$inputPath, $outputPath]);

            return response()->json([
                'success'          => true,
                'file'             => [
                    'name'    => $originalName . '_compressed.pdf',
                    'content' => base64_encode($compressedContent),
                    'size_mb' => $sizeMB,
                ],
                'input_size_mb'    => $inputSizeMB,
                'output_size_mb'   => $sizeMB,
                'reduction_percent'=> $reductionPercent,
                'level'            => $compressionLevel,
            ]);

        } catch (\Exception $e) {
            $this->cleanupFiles([$inputPath, $outputPath]);
            Log::error('DigitalizacionController::compress error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Error durante la compresión: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 4. COMBINAR PDFs
    // =========================================================================

    public function merge(Request $request)
    {
        $request->validate([
            'files'      => 'required|array|min:2|max:50',
            'files.*'    => 'required|file|mimes:pdf|max:51200',
            'outputName' => 'nullable|string|max:200',
        ]);

        $files      = $request->file('files');
        $outputName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $request->input('outputName', 'documento_combinado'));

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $uniqueId   = uniqid();
        $inputPaths = [];
        $totalSize  = 0;

        foreach ($files as $index => $file) {
            $inputFileName = $uniqueId . '_input_' . $index . '.pdf';
            $inputPath     = $tempDir . DIRECTORY_SEPARATOR . $inputFileName;
            $file->move($tempDir, $inputFileName);
            $inputPaths[] = $inputPath;
            $totalSize   += filesize($inputPath);
        }

        $outputFileName = $uniqueId . '_merged.pdf';
        $outputPath     = $tempDir . DIRECTORY_SEPARATOR . $outputFileName;

        try {
            $this->converter->mergePdfsKeepDpi($inputPaths, $outputPath);

            if (!file_exists($outputPath)) {
                throw new \Exception('Error al combinar los archivos.');
            }

            $outputSize    = filesize($outputPath);
            $sizeMB        = round($outputSize / (1024 * 1024), 2);
            $mergedContent = file_get_contents($outputPath);

            $this->cleanupFiles(array_merge($inputPaths, [$outputPath]));

            return response()->json([
                'success'       => true,
                'file'          => [
                    'name'    => $outputName . '_combinado.pdf',
                    'content' => base64_encode($mergedContent),
                    'size_mb' => $sizeMB,
                ],
                'files_merged'  => count($files),
                'total_size_mb' => round($totalSize / (1024 * 1024), 2),
                'output_size_mb'=> $sizeMB,
            ]);

        } catch (\Exception $e) {
            $this->cleanupFiles(array_merge($inputPaths, [$outputPath]));
            Log::error('DigitalizacionController::merge error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Error durante la combinación: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 5. EXTRAER IMÁGENES DE PDF
    // =========================================================================

    public function extractImages(Request $request)
    {
        set_time_limit(1200);

        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:102400',
        ]);

        $file         = $request->file('pdf');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $uniqueId      = uniqid() . '_input.pdf';
        $inputPath     = $tempDir . DIRECTORY_SEPARATOR . $uniqueId;
        $outputZipName = 'imagenes_' . $originalName . '_' . uniqid() . '.zip';
        $outputZipPath = $tempDir . DIRECTORY_SEPARATOR . $outputZipName;

        $file->move(dirname($inputPath), basename($inputPath));

        try {
            $result = $this->extractor->extractImagesToZip($inputPath, $outputZipPath);

            if (!$result['success']) {
                throw new \Exception('Error al extraer imágenes.');
            }

            $zipContent = file_get_contents($outputZipPath);

            $this->cleanupFiles([$inputPath, $outputZipPath]);

            return response()->json([
                'success'      => true,
                'file'         => [
                    'name'    => 'imagenes_' . $originalName . '.zip',
                    'content' => base64_encode($zipContent),
                    'size_mb' => $result['zip_size_mb'],
                ],
                'images_count' => $result['images_count'],
            ]);

        } catch (\Exception $e) {
            Log::error('DigitalizacionController::extractImages error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Error al extraer imágenes: ' . $e->getMessage()], 500);
        } finally {
            if (isset($inputPath) && file_exists($inputPath)) {
                @unlink($inputPath);
            }
        }
    }

    // =========================================================================
    // HELPERS: VALIDACIÓN (portados de VucemValidatorController)
    // =========================================================================

    protected function getPdfVersion(string $path): ?string
    {
        $gsPath = $this->findGhostscript();

        if (!$gsPath) {
            return $this->readPdfVersionFromFile($path);
        }

        $env = $this->buildEnv();
        $code = '(' . str_replace('\\', '/', $path) . ') (r) file runpdfbegin pdfdict /Version get == quit';

        $process = new Process([$gsPath, '-q', '-dNODISPLAY', '-dNOSAFER', '-c', $code], null, $env);
        $process->setTimeout(30);
        $process->run();

        if ($process->isSuccessful()) {
            $output = trim($process->getOutput(), "\" \r\n");
            if (preg_match('/^[\d\.]+$/', $output)) {
                return $output;
            }
        }

        return $this->readPdfVersionFromFile($path);
    }

    protected function readPdfVersionFromFile(string $path): ?string
    {
        $handle = fopen($path, 'rb');
        if (!$handle) return null;
        $header = fread($handle, 20);
        fclose($handle);

        if (preg_match('/%PDF-(\d+\.\d+)/', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function checkGrayscale(string $path): array
    {
        $gsPath = $this->findGhostscript();
        if (!$gsPath) {
            return ['is_gray' => false, 'detail' => 'Ghostscript no encontrado'];
        }

        $env     = $this->buildEnv();
        $process = new Process([$gsPath, '-o', '-', '-sDEVICE=inkcov', $path], null, $env);
        $process->setTimeout(180);
        $process->run();

        $output = $process->getOutput() . "\n" . $process->getErrorOutput();

        if (empty(trim($output))) {
            return ['is_gray' => false, 'detail' => 'No se pudo analizar el documento'];
        }

        $lines          = preg_split('/\r\n|\r|\n/', trim($output));
        $isGray         = true;
        $pagesWithColor = 0;
        $totalPages     = 0;

        foreach ($lines as $line) {
            if (preg_match('/(\d+\.?\d*)\s+(\d+\.?\d*)\s+(\d+\.?\d*)\s+(\d+\.?\d*)\s*(?:CMYK)?/i', $line, $m)) {
                $totalPages++;
                if (floatval($m[1]) > 0.0001 || floatval($m[2]) > 0.0001 || floatval($m[3]) > 0.0001) {
                    $isGray = false;
                    $pagesWithColor++;
                }
            }
        }

        if ($totalPages === 0) {
            return $this->checkGrayscaleAlternative($path, $gsPath, $env);
        }

        return $isGray
            ? ['is_gray' => true, 'detail' => "Analizado: {$totalPages} página(s) - En escala de grises ✓"]
            : ['is_gray' => false, 'detail' => "Color detectado en {$pagesWithColor} de {$totalPages} página(s)"];
    }

    protected function checkGrayscaleAlternative(string $path, string $gsPath, array $env): array
    {
        $tempImage = sys_get_temp_dir() . '/gs_check_' . uniqid() . '.ppm';

        $process = new Process([
            $gsPath, '-q', '-dNOPAUSE', '-dBATCH', '-dFirstPage=1', '-dLastPage=1',
            '-sDEVICE=ppmraw', '-r72', '-sOutputFile=' . $tempImage, $path,
        ], null, $env);
        $process->setTimeout(60);
        $process->run();

        if (!file_exists($tempImage)) {
            return ['is_gray' => false, 'detail' => 'No se pudo analizar - se asume color'];
        }

        $hasColor = $this->ppmHasColor($tempImage);
        @unlink($tempImage);

        return $hasColor
            ? ['is_gray' => false, 'detail' => 'Se detectó contenido a color.']
            : ['is_gray' => true, 'detail' => 'El documento parece estar en escala de grises.'];
    }

    protected function ppmHasColor(string $ppmPath): bool
    {
        $handle = fopen($ppmPath, 'rb');
        if (!$handle) return true;

        $header = fgets($handle);
        if (strpos($header, 'P6') === false && strpos($header, 'P3') === false) {
            fclose($handle);
            return true;
        }
        do { $line = fgets($handle); } while ($line !== false && $line[0] === '#');
        fgets($handle); // max value

        $colorPixels = 0;
        $sampleSize  = 10000;
        for ($i = 0; $i < $sampleSize; $i++) {
            $rgb = fread($handle, 3);
            if (strlen($rgb) < 3) break;
            $r = ord($rgb[0]); $g = ord($rgb[1]); $b = ord($rgb[2]);
            if (abs($r - $g) > 5 || abs($r - $b) > 5 || abs($g - $b) > 5) {
                $colorPixels++;
            }
        }
        fclose($handle);
        return $colorPixels > ($sampleSize * 0.01);
    }

    protected function checkDpi(string $path): array
    {
        $gsPath = $this->findGhostscript();
        if (!$gsPath) {
            return ['is_valid' => false, 'detail' => 'No se pudo verificar (Ghostscript no disponible)'];
        }
        $env    = $this->buildEnv();

        $result = $this->checkDpiWithPdfimages($path);
        if ($result !== null) return $result;

        return $this->checkDpiWithGhostscript($path, $gsPath, $env);
    }

    protected function checkDpiWithPdfimages(string $path): ?array
    {
        $pdfimages = $this->findPdfimages();
        if (!$pdfimages) return null;

        try {
            $process = new Process([$pdfimages, '-list', $path]);
            $process->setTimeout(120);
            $process->run();
            if (!$process->isSuccessful() || $process->getExitCode() < 0) return null;
        } catch (\Exception $e) {
            return null;
        }

        $output    = $process->getOutput();
        $lines     = explode("\n", $output);
        $images    = []; $pages = [];
        $hasImages = false; $isValid = true;

        foreach ($lines as $line) {
            if (preg_match('/^\s*(\d+)\s+(\d+)\s+(\w+)\s+(\d+)\s+(\d+)\s+(\w+)\s+(\d+)\s+(\d+)\s+(\w+)\s+(\w+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $m)) {
                $hasImages = true;
                $pageNum   = intval($m[1]);
                $dpi       = intval(round((intval($m[13]) + intval($m[14])) / 2));
                $imageValid = ($dpi === 300);
                if (!$imageValid) $isValid = false;

                $images[] = ['page' => $pageNum, 'dpi' => $dpi, 'valid' => $imageValid];

                if (!isset($pages[$pageNum])) {
                    $pages[$pageNum] = ['min_dpi' => $dpi, 'max_dpi' => $dpi, 'valid' => true];
                }
                if ($dpi < $pages[$pageNum]['min_dpi']) $pages[$pageNum]['min_dpi'] = $dpi;
                if ($dpi > $pages[$pageNum]['max_dpi']) $pages[$pageNum]['max_dpi'] = $dpi;
                if (!$imageValid) $pages[$pageNum]['valid'] = false;
            }
        }

        if (!$hasImages) {
            return ['is_valid' => true, 'detail' => 'Sin imágenes rasterizadas (solo texto/vectores) ✓', 'status' => 'ok', 'pages' => [], 'images' => []];
        }

        ksort($pages);
        $detailLines = [];
        foreach ($pages as $pn => $pd) {
            $dpiStr = $pd['min_dpi'] !== $pd['max_dpi'] ? $pd['min_dpi'] . '-' . $pd['max_dpi'] : $pd['min_dpi'];
            $detailLines[] = "Página {$pn} → {$dpiStr} DPI " . ($pd['valid'] ? '✓' : '(inválido)');
        }
        $detail = implode("\n", $detailLines);
        if (!$isValid) {
            $invalidCount = count(array_filter($images, fn($i) => !$i['valid']));
            $detail .= "\n\n⚠️ {$invalidCount} imagen(es) con DPI ≠ 300.";
        }

        return ['is_valid' => $isValid, 'detail' => $detail, 'status' => $isValid ? 'ok' : 'error', 'pages' => $pages, 'images' => $images];
    }

    protected function checkDpiWithGhostscript(string $path, string $gsPath, array $env): array
    {
        $images    = [];
        $pageCount = $this->getPdfPageCount($path, $gsPath, $env);
        $pagesToCheck = min(max($pageCount, 1), 10);

        for ($page = 1; $page <= $pagesToCheck; $page++) {
            $pageInfo = $this->getPageDimensions($path, $page, $gsPath, $env);
            if (!$pageInfo) continue;

            $psScript = '(' . str_replace('\\', '/', $path) . ') (r) file runpdfbegin ' . $page . ' pdfgetpage dup /Resources pget { /XObject pget { { exch pop dup type /dicttype eq { dup /Subtype pget { /Image eq { (===IMAGE===) = dup /Width pget { (W:) print = } if dup /Height pget { (H:) print = } if } if } if } if pop } forall } if } if quit';

            $proc = new Process([$gsPath, '-q', '-dNODISPLAY', '-dNOSAFER', '-dBATCH', '-c', $psScript], null, $env);
            $proc->setTimeout(60);
            $proc->run();

            foreach (explode('===IMAGE===', $proc->getOutput()) as $block) {
                $w = 0; $h = 0;
                if (preg_match('/W:\s*(\d+)/', $block, $m)) $w = intval($m[1]);
                if (preg_match('/H:\s*(\d+)/', $block, $m)) $h = intval($m[1]);
                if ($w > 50 && $h > 50) {
                    $dpi = round(($w / $pageInfo['width_in'] + $h / $pageInfo['height_in']) / 2);
                    $images[] = ['width' => $w, 'height' => $h, 'dpi' => $dpi, 'page' => $page];
                }
            }
        }

        if (empty($images)) {
            return ['is_valid' => true, 'detail' => 'Sin imágenes rasterizadas (solo texto/vectores) ✓', 'status' => 'ok', 'pages' => [], 'images' => []];
        }

        $dpis    = array_column($images, 'dpi');
        $minDpi  = min($dpis);
        $maxDpi  = max($dpis);
        $isValid = ($minDpi === 300 && $maxDpi === 300);
        $dpiStr  = $minDpi === $maxDpi ? $minDpi . ' DPI' : "{$minDpi}–{$maxDpi} DPI";

        return [
            'is_valid' => $isValid,
            'detail'   => $isValid ? "Resolución: 300 DPI ✓" : "Resolución: {$dpiStr} (se requiere exactamente 300 DPI)",
            'status'   => $isValid ? 'ok' : 'error',
            'pages'    => [],
            'images'   => $images,
        ];
    }

    protected function checkEncryption(string $path): array
    {
        $qpdf = $this->findQpdf();
        if (!$qpdf) return $this->checkEncryptionGhostscript($path);

        $process = new Process([$qpdf, '--show-encryption', $path]);
        $process->run();
        if (!$process->isSuccessful()) return $this->checkEncryptionGhostscript($path);

        $output = $process->getOutput();
        if (stripos($output, 'File is not encrypted') !== false) {
            return ['is_unencrypted' => true, 'detail' => 'El archivo no está encriptado.'];
        }
        return ['is_unencrypted' => false, 'detail' => 'El archivo está encriptado o protegido.'];
    }

    protected function checkEncryptionGhostscript(string $path): array
    {
        $gsPath = $this->findGhostscript();
        if (!$gsPath) {
            return ['is_unencrypted' => true, 'detail' => 'No se pudo verificar. Se asume sin encriptar.'];
        }

        $env     = $this->buildEnv();
        $process = new Process([$gsPath, '-q', '-dNODISPLAY', '-dBATCH', '-dNOPAUSE', '-c', '(' . $path . ') (r) file runpdfbegin quit'], null, $env);
        $process->run();

        if ($process->isSuccessful()) {
            return ['is_unencrypted' => true, 'detail' => 'El archivo no está encriptado (verificado con Ghostscript).'];
        }

        $err = $process->getErrorOutput();
        if (stripos($err, 'password') !== false || stripos($err, 'encrypt') !== false) {
            return ['is_unencrypted' => false, 'detail' => 'El archivo está encriptado o protegido con contraseña.'];
        }

        return ['is_unencrypted' => true, 'detail' => 'El archivo parece no estar encriptado.'];
    }

    protected function getPdfPageCount(string $path, string $gsPath, array $env): int
    {
        $process = new Process([$gsPath, '-q', '-dNODISPLAY', '-dNOSAFER', '-c',
            '(' . str_replace('\\', '/', $path) . ') (r) file runpdfbegin pdfpagecount = quit'], null, $env);
        $process->setTimeout(30);
        $process->run();
        return intval(trim($process->getOutput()));
    }

    protected function getPageDimensions(string $path, int $page, string $gsPath, array $env): ?array
    {
        $process = new Process([$gsPath, '-q', '-dNODISPLAY', '-dNOSAFER', '-c',
            '(' . str_replace('\\', '/', $path) . ') (r) file runpdfbegin ' . $page . ' pdfgetpage /MediaBox pget pop == quit'], null, $env);
        $process->setTimeout(30);
        $process->run();

        $box = trim($process->getOutput());
        if (preg_match('/\[([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\]/', $box, $m)) {
            $wPt = abs(floatval($m[3]) - floatval($m[1]));
            $hPt = abs(floatval($m[4]) - floatval($m[2]));
            return ['width_pt' => $wPt, 'height_pt' => $hPt, 'width_in' => $wPt / 72, 'height_in' => $hPt / 72];
        }
        return null;
    }

    protected function findGhostscript(): ?string
    {
        $configured = config('pdftools.ghostscript');
        if (!empty($configured) && (file_exists($configured) || $this->commandExists($configured))) {
            return $configured;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $paths = glob('C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe') ?: [];
            rsort($paths, SORT_NATURAL);
            foreach ($paths as $p) {
                if (file_exists($p)) return $p;
            }
            $proc = new Process(['gswin64c', '--version']);
            $proc->run();
            if ($proc->isSuccessful()) return 'gswin64c';
        } else {
            $proc = Process::fromShellCommandline('gs --version 2>/dev/null');
            $proc->run();
            if ($proc->isSuccessful() && !empty(trim($proc->getOutput()))) return 'gs';

            foreach (['/usr/bin/gs', '/usr/local/bin/gs'] as $p) {
                if (file_exists($p)) return $p;
            }
        }
        return null;
    }

    protected function findPdfimages(): ?string
    {
        $configured = config('pdftools.pdfimages');
        if (!empty($configured) && (file_exists($configured) || $this->commandExists($configured))) {
            return $configured;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $paths = [
                'C:\\Poppler\\Release-25.12.0-0\\poppler-25.12.0\\Library\\bin\\pdfimages.exe',
                'C:\\Poppler\\Library\\bin\\pdfimages.exe',
                'C:\\Program Files\\poppler\\bin\\pdfimages.exe',
            ];
            $globPaths = glob('C:\\Poppler\\Release-*\\poppler-*\\Library\\bin\\pdfimages.exe') ?: [];
            rsort($globPaths, SORT_NATURAL);
            $paths = array_merge($globPaths, $paths);
            foreach ($paths as $p) {
                if (file_exists($p)) return $p;
            }
        } else {
            $proc = Process::fromShellCommandline('pdfimages -v 2>&1');
            $proc->run();
            if (str_contains($proc->getOutput() . $proc->getErrorOutput(), 'pdfimages')) return 'pdfimages';
            foreach (['/usr/bin/pdfimages', '/usr/local/bin/pdfimages'] as $p) {
                if (file_exists($p)) return $p;
            }
        }
        return null;
    }

    protected function findQpdf(): ?string
    {
        $configured = config('pdftools.qpdf');
        if (!empty($configured) && (file_exists($configured) || $this->commandExists($configured))) {
            return $configured;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            foreach (['C:\\Program Files\\qpdf\\bin\\qpdf.exe', 'C:\\qpdf\\bin\\qpdf.exe'] as $p) {
                if (file_exists($p)) return $p;
            }
            $proc = new Process(['qpdf', '--version']);
            $proc->run();
            if ($proc->isSuccessful()) return 'qpdf';
        } else {
            $proc = Process::fromShellCommandline('qpdf --version 2>/dev/null');
            $proc->run();
            if ($proc->isSuccessful()) return 'qpdf';
            foreach (['/usr/bin/qpdf', '/usr/local/bin/qpdf'] as $p) {
                if (file_exists($p)) return $p;
            }
        }
        return null;
    }

    protected function buildEnv(): array
    {
        return array_merge($_SERVER, $_ENV, [
            'TEMP' => sys_get_temp_dir(),
            'TMP'  => sys_get_temp_dir(),
        ]);
    }

    protected function commandExists(string $command): bool
    {
        $process = new Process([$command, '--version']);
        $process->run();
        return $process->isSuccessful();
    }

    protected function cleanupFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file && file_exists($file)) {
                @unlink($file);
            }
        }
    }
}

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check storage setup
$disk = Storage::disk('local');
echo "Default disk: local\n";
echo "Root: " . $disk->path('') . "\n";
echo "Temp path: " . $disk->path('temp') . "\n";

// Check if temp directory exists and is writable
$tempPath = $disk->path('temp');
if (!is_dir($tempPath)) {
    echo "Creating temp directory...\n";
    mkdir($tempPath, 0755, true);
}
echo "Temp dir writable: " . (is_writable($tempPath) ? 'yes' : 'no') . "\n";

// Test write
$testFile = 'temp/test_' . uniqid() . '.txt';
try {
    $disk->put($testFile, 'test');
    echo "Write test: OK\n";
    $fullPath = $disk->path($testFile);
    echo "Full path: $fullPath\n";
    echo "File exists: " . (file_exists($fullPath) ? 'yes' : 'no') . "\n";
    $disk->delete($testFile);
    echo "Delete test: OK\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";

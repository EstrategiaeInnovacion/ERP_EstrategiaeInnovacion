<?php
// Robust view mover: moves all *.blade.php except erp_menu.blade.php into Sistemas_IT preserving structure.
$base = realpath(__DIR__ . '/../resources/views');
$targetBase = $base . DIRECTORY_SEPARATOR . 'Sistemas_IT';

function rr($path) {
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );
    $files = [];
    foreach ($rii as $file) {
        if ($file->isFile()) {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

if (!is_dir($targetBase)) {
    mkdir($targetBase, 0777, true);
}

echo "Base: $base\n";
$all = rr($base);
echo "Found files: " . count($all) . "\n";

foreach ($all as $full) {
    $normalizedFull = str_replace('\\', '/', $full);
    $normalizedBase = str_replace('\\', '/', $base);
    $rel = ltrim(str_replace($normalizedBase, '', $normalizedFull), '/');

    if ($rel === '') continue;
    if (strpos($rel, 'Sistemas_IT/') === 0) continue; // skip already moved
    if ($rel === 'erp_menu.blade.php') continue; // keep original menu outside Sistemas_IT
    // Skip already duplicated admin/dashboard that remains in old location to be removed manually later
    if ($rel === 'admin/dashboard.blade.php') continue; 
    if (substr($rel, -10) !== '.blade.php') continue; // only blade templates

    $target = $targetBase . '/' . $rel;
    $targetDir = dirname($target);
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    if (file_exists($target)) {
        echo "SKIP (exists): $rel\n";
        continue;
    }
    if (!rename($full, $target)) {
        if (copy($full, $target)) {
            unlink($full);
            echo "COPIED: $rel\n";
        } else {
            echo "ERROR moving: $rel\n";
        }
    } else {
        echo "MOVED: $rel\n";
    }
}

echo "Done.\n";

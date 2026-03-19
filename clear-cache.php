<?php
// Script pentru clear cache complet
$cacheDir = __DIR__ . '/data/cache';

echo "=== CLEAR CACHE COMPLET ===\n\n";

if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $deleted = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $deleted++;
        }
    }
    
    echo "Cache files deleted: $deleted\n";
} else {
    echo "Cache directory not found.\n";
}

// Regenerează cache cu sondajele active
include(__DIR__ . '/polls_api.php');
echo "Cache regenerated.\n";

echo "\n=== CACHE CLEARED SUCCESSFULLY ===\n";
?>

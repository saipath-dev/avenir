<?php
// Clear PHP OpCache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache cleared successfully!<br>";
} else {
    echo "OpCache not enabled<br>";
}

// Clear CodeIgniter cache
$cacheDir = '../writable/cache/';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "CodeIgniter cache cleared successfully!<br>";
}

echo "<br>Please delete this file after use for security.";

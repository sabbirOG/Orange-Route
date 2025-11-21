<?php
// Direct test of map.php with error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    include __DIR__ . '/pages/map.php';
} catch (Throwable $e) {
    echo "FATAL ERROR:<br>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

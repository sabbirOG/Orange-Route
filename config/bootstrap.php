<?php

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Manual autoload for OrangeRoute classes
spl_autoload_register(function ($class) {
    $prefix = 'OrangeRoute\\';
    $base_dir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load .env file
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Error handling
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Start session
OrangeRoute\Session::start();

// Lightweight auto-migration to ensure required columns exist
try {
    $pdo = OrangeRoute\Database::get();
    // Ensure route_locations.created_at exists (older installs may lack it or only have updated_at)
    $createdCol = $pdo->query("SHOW COLUMNS FROM route_locations LIKE 'created_at'")->fetch();
    if (!$createdCol) {
        $updatedCol = $pdo->query("SHOW COLUMNS FROM route_locations LIKE 'updated_at'")->fetch();
        // Add created_at column (temporarily NULL to allow backfill)
        $pdo->exec("ALTER TABLE route_locations ADD COLUMN created_at DATETIME NULL");
        if ($updatedCol) {
            // Backfill from updated_at if present
            $pdo->exec("UPDATE route_locations SET created_at = updated_at WHERE created_at IS NULL");
        }
        // Enforce NOT NULL with default now
        $pdo->exec("ALTER TABLE route_locations MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        // Create index if not already present
        try { $pdo->exec("CREATE INDEX idx_created ON route_locations(created_at)"); } catch (\PDOException $ie) { /* ignore duplicate */ }
    }
} catch (\Throwable $migrateErr) {
    // In debug surface migration warnings; in production fail silently
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        error_log('[AutoMigration] ' . $migrateErr->getMessage());
    }
}

// Helper functions
function redirect(string $path): void {
    // If path starts with 'pages/', add 'public/' prefix
    if (strpos($path, 'pages/') === 0) {
        $path = 'public/' . $path;
    }
    header("Location: /OrangeRoute/{$path}");
    exit;
}

function e(?string $val): string {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

function json_response($data, int $code = 200): void {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function requireAuth(?string $role = null): void {
    if (!OrangeRoute\Auth::check()) {
        redirect('pages/login.php');
    }
    if ($role && !OrangeRoute\Auth::isRole($role)) {
        redirect('pages/login.php');
    }
}


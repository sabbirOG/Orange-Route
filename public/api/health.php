<?php
require_once __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Database connectivity check
try {
    $dbCheck = OrangeRoute\Database::fetchValue('SELECT 1');
    $health['checks']['database'] = [
        'status' => 'ok',
        'message' => 'Connected'
    ];
} catch (Throwable $e) {
    $health['status'] = 'error';
    $health['checks']['database'] = [
        'status' => 'error',
        'message' => 'Connection failed'
    ];
}

// Session check
try {
    OrangeRoute\Session::set('health_check', time());
    $check = OrangeRoute\Session::get('health_check');
    $health['checks']['session'] = [
        'status' => $check ? 'ok' : 'error',
        'message' => $check ? 'Working' : 'Failed'
    ];
} catch (Throwable $e) {
    $health['status'] = 'error';
    $health['checks']['session'] = [
        'status' => 'error',
        'message' => 'Session failed'
    ];
}

// PHP version
$health['checks']['php'] = [
    'status' => 'ok',
    'version' => PHP_VERSION
];

// Environment
$health['checks']['environment'] = [
    'mode' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true'
];

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);

<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

header('Content-Type: application/json');

$user = OrangeRoute\Auth::user();
if (!$user || !in_array($user['role'], ['admin', 'driver'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$route_id = (int)($_POST['route_id'] ?? 0);
$active = (int)($_POST['active'] ?? 0);

if (!$route_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing route_id']);
    exit;
}

// Only allow drivers to toggle their own assignment
if ($user['role'] === 'driver') {
    $assignment = OrangeRoute\Database::fetch("SELECT * FROM route_assignments WHERE route_id = ? AND driver_id = ? AND is_current = 1", [$route_id, $user['id']]);
    if (!$assignment) {
        http_response_code(403);
        echo json_encode(['error' => 'Not your assignment']);
        exit;
    }
}

// Update route active status
OrangeRoute\Database::query("UPDATE routes SET is_active = ? WHERE id = ?", [$active, $route_id]);

echo json_encode(['success' => true, 'active' => $active]);

<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = OrangeRoute\Auth::user();
$input = json_decode(file_get_contents('php://input'), true);

$allowed = ['privacy_profile', 'language', 'notifications_enabled'];
$updates = [];
$values = [];

foreach ($input as $key => $value) {
    if (in_array($key, $allowed)) {
        $updates[] = "$key = ?";
        $values[] = $value;
    }
}

if (empty($updates)) {
    echo json_encode(['error' => 'No valid fields to update']);
    exit;
}

// Add user_id at the end
$values[] = $user['id'];

try {
    OrangeRoute\Database::query(
        "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?",
        $values
    );
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update settings',
        'details' => $e->getMessage()
    ]);
}

<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = OrangeRoute\Auth::user();

// Check if file uploaded
if (!isset($_FILES['profile_picture'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['profile_picture'];

// Validate file
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Use JPEG, PNG, GIF or WebP']);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum 5MB']);
    exit;
}

// Create upload directory
$uploadDir = __DIR__ . '/../../uploads/profile_pictures/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $user['id'] . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// Delete old profile picture
if ($user['profile_picture']) {
    $oldFile = $uploadDir . basename($user['profile_picture']);
    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
}

// Update database
$relativePath = '/uploads/profile_pictures/' . $filename;
OrangeRoute\Database::query(
    "UPDATE users SET profile_picture = ? WHERE id = ?",
    [$relativePath, $user['id']]
);

echo json_encode([
    'success' => true,
    'url' => $relativePath
]);

<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $user = OrangeRoute\Auth::user();

    // Check if user has profile picture
    if (!$user['profile_picture']) {
        echo json_encode(['error' => 'No profile picture to remove']);
        exit;
    }

    // Delete the file
    $uploadDir = __DIR__ . '/../../uploads/profile_pictures/';
    $filepath = $uploadDir . basename($user['profile_picture']);

    if (file_exists($filepath)) {
        if (!unlink($filepath)) {
            echo json_encode(['error' => 'Failed to delete file', 'filepath' => $filepath]);
            exit;
        }
    }

    // Update database
    $updated = OrangeRoute\Database::query(
        "UPDATE users SET profile_picture = NULL WHERE id = ?",
        [$user['id']]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Profile picture removed successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}


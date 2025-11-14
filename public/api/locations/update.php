<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json');

requireAuth('driver');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;

if (!$lat || !$lng) {
    json_response(['success' => false, 'error' => 'Invalid coordinates'], 400);
}

$userId = OrangeRoute\Session::userId();

// Get driver's shuttle assignment
$assignment = OrangeRoute\Database::fetch(
    "SELECT shuttle_id FROM shuttle_assignments WHERE driver_id = ? AND is_current = 1",
    [$userId]
);

if (!$assignment) {
    json_response(['success' => false, 'error' => 'No shuttle assigned'], 400);
}

// Insert location
try {
    OrangeRoute\Database::query(
        "INSERT INTO shuttle_locations (shuttle_id, latitude, longitude, created_at) 
         VALUES (?, ?, ?, NOW())",
        [$assignment['shuttle_id'], $lat, $lng]
    );
    
    json_response(['success' => true, 'message' => 'Location updated']);
} catch (\Exception $e) {
    json_response(['success' => false, 'error' => 'Update failed'], 500);
}

<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json');

requireAuth('driver');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Accept both legacy (lat,lng) and new (latitude,longitude) keys plus optional telemetry
$data = json_decode(file_get_contents('php://input'), true);
$lat = $data['latitude'] ?? $data['lat'] ?? null;
$lng = $data['longitude'] ?? $data['lng'] ?? null;
$speed = $data['speed'] ?? null;
$heading = $data['heading'] ?? null;
$accuracy = $data['accuracy'] ?? null;

if (!$lat || !$lng) {
    json_response(['success' => false, 'error' => 'Invalid coordinates'], 400);
}

$userId = OrangeRoute\Session::userId();

// Get driver's route assignment
$assignment = OrangeRoute\Database::fetch(
    "SELECT route_id FROM route_assignments WHERE driver_id = ? AND is_current = 1",
    [$userId]
);

if (!$assignment) {
    json_response(['success' => false, 'error' => 'No route assigned'], 400);
}

// Insert location (schema requires driver_id; include telemetry if provided)
try {
    OrangeRoute\Database::query(
        "INSERT INTO route_locations (route_id, driver_id, latitude, longitude, speed, heading, accuracy) 
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [
            $assignment['route_id'],
            $userId,
            $lat,
            $lng,
            $speed !== null ? $speed : null,
            $heading !== null ? $heading : null,
            $accuracy !== null ? $accuracy : null
        ]
    );
    json_response(['success' => true, 'message' => 'Location updated']);
} catch (\Exception $e) {
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        json_response(['success' => false, 'error' => 'Update failed: ' . $e->getMessage()], 500);
    }
    json_response(['success' => false, 'error' => 'Update failed'], 500);
}

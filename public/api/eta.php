<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json');

$shuttleId = $_GET['shuttle_id'] ?? 0;
$userLat = (float)($_GET['lat'] ?? 0);
$userLng = (float)($_GET['lng'] ?? 0);

if (!$shuttleId || !$userLat || !$userLng) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Get shuttle's latest location and current route (if assigned)
$shuttle = OrangeRoute\Database::fetch(
    "SELECT s.shuttle_name, sa.route_id, sl.latitude, sl.longitude,
            ST_Distance_Sphere(POINT(?, ?), POINT(sl.longitude, sl.latitude)) / 1000 AS distance_km
     FROM shuttles s
     JOIN shuttle_locations sl ON s.id = sl.shuttle_id
     LEFT JOIN shuttle_assignments sa ON sa.shuttle_id = s.id AND sa.is_current = 1
     WHERE s.id = ? AND sl.id = (
         SELECT id FROM shuttle_locations
         WHERE shuttle_id = s.id
         ORDER BY created_at DESC LIMIT 1
     )",
    [$userLng, $userLat, $shuttleId]
);

if (!$shuttle) {
    echo json_encode(['error' => 'Shuttle not found']);
    exit;
}

// Calculate ETA (assume average speed of 30 km/h in city traffic)
$avgSpeed = 30; // km/h
$distanceKm = (float)$shuttle['distance_km'];
$etaMinutes = (int)round(($distanceKm / $avgSpeed) * 60);

// Get next stop if on route
$nextStop = null;
if (!empty($shuttle['route_id'])) {
    $nextStop = OrangeRoute\Database::fetch(
        "SELECT stop_name, estimated_time
         FROM route_stops
         WHERE route_id = ?
         ORDER BY stop_order ASC
         LIMIT 1",
        [$shuttle['route_id']]
    );
}

echo json_encode([
    'shuttle_name' => $shuttle['shuttle_name'],
    'distance_km' => round($distanceKm, 2),
    'distance_text' => $distanceKm < 1
        ? round($distanceKm * 1000) . ' meters'
        : round($distanceKm, 1) . ' km',
    'eta_minutes' => $etaMinutes,
    'eta_text' => $etaMinutes < 1
        ? 'Less than 1 min'
        : ($etaMinutes < 60 ? $etaMinutes . ' min' : round($etaMinutes / 60, 1) . ' hours'),
    'next_stop' => $nextStop
]);

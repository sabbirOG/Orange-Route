<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json');

$routeId = $_GET['route_id'] ?? 0;
$userLat = (float)($_GET['lat'] ?? 0);
$userLng = (float)($_GET['lng'] ?? 0);

if (!$routeId || !$userLat || !$userLng) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Get route's latest location from active driver
$route = OrangeRoute\Database::fetch(
    "SELECT r.route_name, r.distance_type as category, rl.latitude, rl.longitude,
            ST_Distance_Sphere(POINT(?, ?), POINT(rl.longitude, rl.latitude)) / 1000 AS distance_km
     FROM routes r
     JOIN route_locations rl ON r.id = rl.route_id
     JOIN route_assignments ra ON ra.route_id = r.id AND ra.is_current = 1
     WHERE r.id = ? AND rl.id = (
         SELECT id FROM route_locations
         WHERE route_id = r.id
         ORDER BY updated_at DESC LIMIT 1
     )",
    [$userLng, $userLat, $routeId]
);

if (!$route) {
    echo json_encode(['error' => 'Route not found or not active']);
    exit;
}

// Calculate ETA (assume average speed of 30 km/h in city traffic)
$avgSpeed = 30; // km/h
$distanceKm = (float)$route['distance_km'];
$etaMinutes = (int)round(($distanceKm / $avgSpeed) * 60);

// Get next stop if on route
$nextStop = OrangeRoute\Database::fetch(
    "SELECT stop_name, estimated_time
     FROM route_stops
     WHERE route_id = ?
     ORDER BY stop_order ASC
     LIMIT 1",
    [$routeId]
);

echo json_encode([
    'route_name' => $route['route_name'],
    'category' => $route['category'],
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

<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json');

requireAuth();

try {
    // Get latest location for each route
    $locations = OrangeRoute\Database::fetchAll("
        SELECT 
            rl.route_id,
            rl.latitude,
            rl.longitude,
            rl.created_at,
            r.route_name,
            r.distance_type as category,
            r.description
        FROM route_locations rl
        INNER JOIN routes r ON r.id = rl.route_id
        WHERE rl.id IN (
            SELECT MAX(id) FROM route_locations 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            GROUP BY route_id
        )
        AND r.is_active = 1
    ");
    
    json_response([
        'success' => true,
        'data' => array_map(function($loc) {
            return [
                'id' => $loc['route_id'],
                'route_name' => $loc['route_name'],
                'category' => $loc['category'],
                'description' => $loc['description'],
                'latitude' => (float) $loc['latitude'],
                'longitude' => (float) $loc['longitude'],
                'updated_at' => $loc['created_at'],
                'created_at' => $loc['created_at']
            ];
        }, $locations)
    ]);
} catch (\Exception $e) {
    json_response(['success' => false, 'error' => 'Failed to fetch locations'], 500);
}

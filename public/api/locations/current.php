<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json');

requireAuth();

try {
    // Get latest location for each shuttle
    $locations = OrangeRoute\Database::fetchAll("
        SELECT 
            sl.shuttle_id,
            sl.latitude,
            sl.longitude,
            sl.created_at,
            s.shuttle_name,
            s.registration_number,
            s.route_description
        FROM shuttle_locations sl
        INNER JOIN shuttles s ON s.id = sl.shuttle_id
        WHERE sl.id IN (
            SELECT MAX(id) FROM shuttle_locations 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            GROUP BY shuttle_id
        )
        AND s.is_active = 1
    ");
    
    json_response([
        'success' => true,
        'data' => array_map(function($loc) {
            return [
                'id' => $loc['shuttle_id'],
                'shuttle_name' => $loc['shuttle_name'],
                'registration_number' => $loc['registration_number'],
                'route_description' => $loc['route_description'],
                'latitude' => (float) $loc['latitude'],
                'longitude' => (float) $loc['longitude'],
                'updated_at' => $loc['created_at']
            ];
        }, $locations)
    ]);
} catch (\Exception $e) {
    json_response(['success' => false, 'error' => 'Failed to fetch locations'], 500);
}

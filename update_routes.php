<?php
require_once __DIR__ . '/config/bootstrap.php';

// Update Kuril routes
OrangeRoute\Database::query(
    "UPDATE routes SET from_location='UIU', to_location='Kuril', distance_type='long' WHERE route_name LIKE '%kuril%'"
);

// Update Aftabnagar routes
OrangeRoute\Database::query(
    "UPDATE routes SET from_location='UIU', to_location='Aftabnagar', distance_type='short' WHERE route_name LIKE '%aftabnagar%'"
);

// Update Natunbazar routes
OrangeRoute\Database::query(
    "UPDATE routes SET from_location='UIU', to_location='Natunbazar', distance_type='long' WHERE route_name LIKE '%natunbazar%'"
);

// Update any remaining routes without locations
OrangeRoute\Database::query(
    "UPDATE routes SET from_location='UIU', to_location='Unknown', distance_type='short' WHERE from_location IS NULL OR from_location = ''"
);

echo "Routes updated successfully!\n\n";

// Display updated routes
$routes = OrangeRoute\Database::fetchAll(
    "SELECT id, route_name, from_location, to_location, distance_type FROM routes ORDER BY id"
);

foreach ($routes as $route) {
    echo sprintf(
        "ID: %2d | %-30s | %s → %s | %s\n",
        $route['id'],
        $route['route_name'],
        $route['from_location'],
        $route['to_location'],
        strtoupper($route['distance_type'])
    );
}

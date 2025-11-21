<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Testing...<br>";

try {
    require_once __DIR__ . '/../config/bootstrap.php';
    echo "Bootstrap loaded<br>";
    
    requireAuth();
    echo "Auth checked<br>";
    
    $user = OrangeRoute\Auth::user();
    echo "User: " . print_r($user, true) . "<br>";
    
    $role = $user['role'] ?? 'student';
    echo "Role: $role<br>";
    
    if ($role === 'admin') {
        echo "Testing admin query...<br>";
        $stats = [];
        $stats['total_users'] = OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM users") ?? 0;
        echo "Total users: " . $stats['total_users'] . "<br>";
    } elseif ($role === 'driver') {
        echo "Testing driver query...<br>";
        $current_assignment = OrangeRoute\Database::fetch("
            SELECT 
                ra.id,
                r.id as route_id,
                r.route_name,
                r.distance_type as category,
                r.description as route_description,
                r.is_active,
                ra.assigned_at
            FROM route_assignments ra
            JOIN routes r ON ra.route_id = r.id
            WHERE ra.driver_id = ? AND ra.is_current = 1
            LIMIT 1
        ", [$user['id']]);
        echo "Assignment: " . print_r($current_assignment, true) . "<br>";
    } else {
        echo "Testing student query...<br>";
        $active_routes = OrangeRoute\Database::fetchAll("
            SELECT 
                r.id,
                r.route_name,
                r.distance_type as category
            FROM routes r
            WHERE r.is_active = 1
            LIMIT 3
        ");
        echo "Routes: " . print_r($active_routes, true) . "<br>";
    }
    
    echo "<br>SUCCESS - No errors!";
    
} catch (Exception $e) {
    echo "<br><strong>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

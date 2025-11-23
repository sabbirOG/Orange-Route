<?php
// This page is deprecated - redirect to new route_edit.php
$route_id = $_GET['route_id'] ?? 0;
header("Location: route_edit.php?route_id=" . $route_id);
exit;

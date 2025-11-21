<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth('driver');

$user = OrangeRoute\Auth::user();
$assignment = OrangeRoute\Database::fetch(
    "SELECT ra.*, r.route_name, r.distance_type as category, r.description, r.id as route_id
     FROM route_assignments ra 
     INNER JOIN routes r ON ra.route_id = r.id
     WHERE ra.driver_id = ? AND ra.is_current = 1",
    [$user['id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Driver Mode - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
</head>
<body>
    <?php $title = 'Driver Mode'; $backHref = 'map.php'; $rightActionHtml = '<button id="toggle-tracking" class="btn btn-primary" style="width: auto; padding: 8px 16px; min-height: auto;">Start Tracking</button>'; include __DIR__ . '/_partials/top_bar.php'; ?>
    
    <div class="container">
        <?php if ($assignment): ?>
            <div class="card">
                <h3><?= e($assignment['route_name']) ?></h3>
                <p class="text-muted"><?= e($assignment['description']) ?></p>
                <div class="badge badge-<?= $assignment['category'] === 'long' ? 'primary' : 'success' ?>">
                    <?= $assignment['category'] === 'long' ? 'Long Route' : 'Short Route' ?>
                </div>
            </div>
            
            <div class="card">
                <h3>Location Tracking</h3>
                <p class="text-muted" id="tracking-status">Not tracking</p>
                <p class="text-muted" id="last-update">No updates yet</p>
            </div>
        <?php else: ?>
            <div class="alert alert-error">No route assigned. Contact admin.</div>
        <?php endif; ?>
    </div>
    
    <!-- Bottom Navigation -->
    <?php $active = 'dashboard'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
    
    <script src="/OrangeRoute/assets/js/app.js"></script>
    <script>
        let isTracking = false;
        let watchId = null;
        const btn = document.getElementById('toggle-tracking');
        const status = document.getElementById('tracking-status');
        const lastUpdate = document.getElementById('last-update');
        
        btn.addEventListener('click', () => {
            if (!isTracking) {
                startTracking();
            } else {
                stopTracking();
            }
        });
        
        function startTracking() {
            watchId = App.watchLocation(async (pos) => {
                try {
                    const response = await App.api('/locations/update.php', {
                        method: 'POST',
                        body: JSON.stringify(pos)
                    });
                    if (response.success) {
                        lastUpdate.textContent = 'Updated: ' + new Date().toLocaleTimeString();
                    }
                } catch (error) {
                    App.showAlert('Failed to update location');
                }
            });
            isTracking = true;
            btn.textContent = 'Stop Tracking';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
            status.textContent = 'Tracking active';
            status.style.color = 'var(--success)';
        }
        
        function stopTracking() {
            App.stopWatching(watchId);
            watchId = null;
            isTracking = false;
            btn.textContent = 'Start Tracking';
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary');
            status.textContent = 'Not tracking';
            status.style.color = 'var(--text-muted)';
        }
    </script>
</body>
</html>

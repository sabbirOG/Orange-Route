<?php
// Usage: set $active (dashboard|routes|notifications|profile)
// Example:
//   $active = 'dashboard';
//   include __DIR__ . '/_partials/bottom_nav.php';

function is_active($name, $active) {
    return ($active ?? '') === $name ? ' active' : '';
}
?>
<div class="bottom-nav">
    <a href="map.php" class="nav-item<?= is_active('dashboard', $active ?? '') ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9.5L12 3l9 6.5"></path>
                <path d="M5 10v10h14V10"></path>
            </svg>
        </span>
        Dashboard
    </a>
    <a href="routes.php" class="nav-item<?= is_active('routes', $active ?? '') ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                <circle cx="12" cy="11" r="2"></circle>
            </svg>
        </span>
        Routes
    </a>
    <a href="notifications.php" class="nav-item<?= is_active('notifications', $active ?? '') ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14v-2a6 6 0 1 0-12 0v2c0 .53-.21 1.04-.6 1.4L4 17h5"></path>
                <path d="M10 21h4"></path>
            </svg>
        </span>
        Notifications
    </a>
    <a href="profile.php" class="nav-item<?= is_active('profile', $active ?? '') ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"></circle>
                <path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>
            </svg>
        </span>
        Profile
    </a>
</div>
<?php unset($active); ?>

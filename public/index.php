 <?php
require_once __DIR__ . '/../config/bootstrap.php';

// Redirect to map if logged in, else to login
if (OrangeRoute\Auth::check()) {
    redirect('pages/map.php');
} else {
    redirect('pages/login.php');
}


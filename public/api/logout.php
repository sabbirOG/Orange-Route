<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (OrangeRoute\Auth::check()) {
    OrangeRoute\Auth::logout();
    // Start a new session after destroying the old one to set flash message
    OrangeRoute\Session::start();
    OrangeRoute\Session::setFlash('success', 'You have been logged out successfully.');
}

redirect('pages/login.php');

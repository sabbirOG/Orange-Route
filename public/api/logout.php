<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (OrangeRoute\Auth::check()) {
    OrangeRoute\Auth::logout();
}

redirect('pages/login.php');

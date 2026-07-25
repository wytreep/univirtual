<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/includes/auth.php';
if (estaLogueado()) {
    header('Location: '.BASE_URL.'/pages/'.rolActual().'/dashboard.php');
} else {
    header('Location: '.BASE_URL.'/pages/auth/login.php');
}
exit;

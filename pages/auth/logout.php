<?php
require_once __DIR__.'/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Limpiar todas las variables de sesión primero
session_unset();

// Destruir la sesión
session_destroy();

// Eliminar la cookie de sesión del browser
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']
    );
}

// Headers para evitar caché de páginas protegidas
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Location: '.BASE_URL.'/pages/auth/login.php');
exit;
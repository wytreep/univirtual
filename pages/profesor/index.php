<?php
// index.php — Entry point del panel del profesor.
// Redirige a dashboard.php que contiene la clase ProfesorView.
require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../includes/auth.php';
requireLogin('profesor');
header('Location: '.BASE_URL.'/pages/profesor/dashboard.php');
exit;

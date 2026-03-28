<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config/config.php';
echo "config OK<br>";
echo "BASE_PATH: " . BASE_PATH . "<br>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_PORT: " . DB_PORT . "<br>";
try {
    $pdo = db();
    echo "BD OK<br>";
    $n = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    echo "Usuarios: $n<br>";
} catch(Exception $e) {
    echo "ERROR BD: " . $e->getMessage() . "<br>";
}
?>
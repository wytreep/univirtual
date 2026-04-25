<?php
echo "paso 1 — PHP OK<br>";
require_once __DIR__ . '/config/config.php';
echo "paso 2 — config OK<br>";

echo "intentando conectar...<br>";
flush(); ob_flush();

$dsn = "mysql:host=127.0.0.1;port=3307;dbname=univirtual;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3,
    ]);
    echo "CONECTADO OK<br>";
    echo "Usuarios: " . $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

try {
    $pdo = db();
    echo "paso 3 — BD conectada<br>";
    $n = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    echo "paso 4 — usuarios en BD: $n<br>";
} catch(Exception $e) {
    echo "paso 3 FALLÓ — " . $e->getMessage() . "<br>";
}

// Test sesión
session_start();
echo "paso 5 — sesión OK<br>";

// Test modelos
require_once __DIR__ . '/models/Model.php';
echo "paso 6 — Model.php OK<br>";

require_once __DIR__ . '/models/Usuario.php';
echo "paso 7 — Usuario.php OK<br>";

echo "<br><strong>Todo OK — el problema es otro</strong>";
?>
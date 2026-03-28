<?php
require_once __DIR__ . '/config/config.php';

echo "<h2>Diagnóstico UNI-VIRTUAL</h2>";

// Test 1 — Constantes
echo "<p>BASE_URL: " . BASE_URL . "</p>";
echo "<p>BASE_PATH: " . BASE_PATH . "</p>";
echo "<p>DB_HOST: " . DB_HOST . " / Puerto: " . DB_PORT . "</p>";

// Test 2 — Conexión BD
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p style='color:green'>✅ Conexión a MySQL OK</p>";

    // Test 3 — Tablas
    $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tablas encontradas (" . count($tablas) . "): " . implode(', ', $tablas) . "</p>";

    // Test 4 — Datos
    $usuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    echo "<p>Usuarios en BD: $usuarios</p>";

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error BD: " . $e->getMessage() . "</p>";
}

// Test 5 — Sesión
session_start();
echo "<p>Sesión activa: " . (empty($_SESSION) ? 'No (normal si no has hecho login)' : 'Sí — idUsuario: ' . ($_SESSION['idUsuario'] ?? 'no definido')) . "</p>";

// Test 6 — PHP version
echo "<p>PHP: " . PHP_VERSION . "</p>";
?>
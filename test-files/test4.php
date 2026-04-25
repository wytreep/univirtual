<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(5);

echo "1 PHP OK<br>"; flush();

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'univirtual');

echo "2 constantes OK<br>"; flush();

try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3307;dbname=univirtual;charset=utf8mb4",
        'root', '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    echo "3 BD conectada OK<br>"; flush();
    $n = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    echo "4 usuarios: $n<br>"; flush();
} catch(Exception $e) {
    echo "3 ERROR BD: " . $e->getMessage() . "<br>"; flush();
}

echo "5 listo<br>";
?>
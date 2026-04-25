<?php
require 'config/config.php';
$db = db();
try {
    $db->exec("ALTER TABLE entregas ADD COLUMN entregado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "Agregada entregado_en.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->exec("ALTER TABLE entregas ADD COLUMN nombreOriginal VARCHAR(255)");
    echo "Agregada nombreOriginal.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->exec("ALTER TABLE entregas ADD COLUMN archivoEntrega VARCHAR(500) NOT NULL DEFAULT ''");
    echo "Agregada archivoEntrega.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->exec("ALTER TABLE actividades ADD COLUMN puntaje_max DECIMAL(3,1) DEFAULT 5.0");
    echo "Agregada puntaje_max.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

echo "Reparación completa.\n";

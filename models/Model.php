<?php
/**
 * Clase base abstracta para todos los modelos.
 * Implementa el patrón Singleton para la conexión PDO.
 */
abstract class Model {
    protected PDO $db;

    public function __construct() {
        $this->db = db(); // Singleton definido en config/config.php
    }
}

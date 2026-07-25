<?php
abstract class Model {
    protected PDO    $db;
    protected string $table      = '';
    protected string $primaryKey = 'id';

    public function __construct() {
        $this->db = db(); // Singleton definido en config/config.php
    }

    /** Busca un registro por su clave primaria. */
    public function find(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Retorna todos los registros de la tabla. */
    public function findAll(): array {
        return $this->db->query(
            "SELECT * FROM {$this->table}"
        )->fetchAll();
    }

    /**
     * INSERT si $data no trae la PK, UPDATE si la trae.
     * Retorna el ID del registro insertado o actualizado.
     */
    public function save(array $data): int {
        if (isset($data[$this->primaryKey])) {
            $id = (int) $data[$this->primaryKey];
            unset($data[$this->primaryKey]);
            $sets = implode(', ', array_map(fn($col) => "`$col` = ?", array_keys($data)));
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET $sets WHERE {$this->primaryKey} = ?"
            );
            $stmt->execute([...array_values($data), $id]);
            return $id;
        }

        $cols         = implode(', ', array_map(fn($col) => "`$col`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} ($cols) VALUES ($placeholders)"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /** Elimina un registro por su clave primaria. */
    public function delete(int $id): void {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        $stmt->execute([$id]);
    }
}

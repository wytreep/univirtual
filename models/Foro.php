<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Foro
 * Espacio de discusión temática dentro de un aula virtual.
 */
class Foro extends Model {

    public int    $idForo;
    public int    $idAula;
    public string $titulo;
    public string $descripcion;
    public bool   $abierto;
    public string $creado_en;

    /**
     * Obtiene todos los foros de un aula con conteo de mensajes.
     */
    public function getByAula(int $idAula): array {
        $stmt = $this->db->prepare(
            "SELECT foros.*,
                    (SELECT COUNT(*) FROM mensajes WHERE mensajes.idForo = foros.idForo) AS total_msgs
             FROM foros
             WHERE foros.idAula = ?
             ORDER BY foros.creado_en DESC"
        );
        $stmt->execute([$idAula]);
        return $stmt->fetchAll();
    }

    /**
     * Crea un nuevo foro.
     */
    public function crear(int $idAula, string $titulo, string $descripcion): int {
        $stmt = $this->db->prepare(
            "INSERT INTO foros (idAula, titulo, descripcion) VALUES (?, ?, ?)"
        );
        $stmt->execute([$idAula, $titulo, $descripcion]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Abre o cierra un foro.
     */
    public function setAbierto(int $idForo, bool $abierto): void {
        $this->db->prepare("UPDATE foros SET abierto = ? WHERE idForo = ?")->execute([$abierto ? 1 : 0, $idForo]);
    }

    /**
     * Obtiene un foro por su ID.
     */
    public function findById(int $idForo): ?array {
        $stmt = $this->db->prepare("SELECT * FROM foros WHERE idForo = ?");
        $stmt->execute([$idForo]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}


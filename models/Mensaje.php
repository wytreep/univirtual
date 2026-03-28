<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Mensaje
 * Publicación dentro de un foro.
 * Soporta hilos de conversación mediante auto-referencia (idPadre).
 * Patrón Composite: idPadre = NULL → mensaje raíz | idPadre = INT → respuesta.
 */
class Mensaje extends Model {

    public int    $idMensaje;
    public int    $idForo;
    public int    $idUsuario;
    public ?int   $idPadre;
    public string $contenido;
    public string $publicado_en;

    /**
     * Obtiene todos los mensajes de un foro con datos del autor.
     */
    public function getByForo(int $idForo): array {
        $stmt = $this->db->prepare(
            "SELECT mensajes.*, u.nombre AS autor, u.rol AS rol_autor
             FROM mensajes
             JOIN usuarios u ON mensajes.idUsuario = u.idUsuario
             WHERE mensajes.idForo = ?
             ORDER BY mensajes.publicado_en ASC"
        );
        $stmt->execute([$idForo]);
        return $stmt->fetchAll();
    }

    /**
     * Publica un nuevo mensaje.
     * idPadre = null → mensaje raíz; idPadre = int → respuesta a otro mensaje.
     */
    public function publicar(int $idForo, int $idUsuario, string $contenido, ?int $idPadre = null): int {
        $stmt = $this->db->prepare(
            "INSERT INTO mensajes (idForo, idUsuario, contenido, idPadre) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$idForo, $idUsuario, $contenido, $idPadre]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Obtiene solo los mensajes raíz (sin padre) de un foro.
     */
    public function getRaices(int $idForo): array {
        $stmt = $this->db->prepare(
            "SELECT mensajes.*, u.nombre AS autor, u.rol AS rol_autor
             FROM mensajes
             JOIN usuarios u ON mensajes.idUsuario = u.idUsuario
             WHERE mensajes.idForo = ? AND mensajes.idPadre IS NULL
             ORDER BY mensajes.publicado_en ASC"
        );
        $stmt->execute([$idForo]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene las respuestas a un mensaje específico (hijos directos).
     */
    public function getRespuestas(int $idPadre): array {
        $stmt = $this->db->prepare(
            "SELECT mensajes.*, u.nombre AS autor, u.rol AS rol_autor
             FROM mensajes
             JOIN usuarios u ON mensajes.idUsuario = u.idUsuario
             WHERE mensajes.idPadre = ?
             ORDER BY mensajes.publicado_en ASC"
        );
        $stmt->execute([$idPadre]);
        return $stmt->fetchAll();
    }
}

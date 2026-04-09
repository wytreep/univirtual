<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Entrega
 * Registra la respuesta de un estudiante a una actividad.
 * Gestiona subida de archivos, calificación y retroalimentación.
 */
class Entrega extends Model {

    public int    $idEntrega;
    public int    $idActividad;
    public int    $idEstudiante;
    public string $archivoEntrega;
    public string $nombreOriginal;
    public ?string $comentario;
    public ?float  $nota;
    public ?string $feedback;
    public string  $entregado_en;

    /**
     * Registra una nueva entrega (o actualiza si ya existe — ON DUPLICATE KEY).
     */
    public function crear(int $idActividad, int $idEstudiante,
                          string $archivoEntrega, string $nombreOriginal, ?string $comentario): int {
        $stmt = $this->db->prepare(
            "INSERT INTO entregas (idActividad, idEstudiante, archivoEntrega, nombreOriginal, comentario)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               archivoEntrega = VALUES(archivoEntrega),
               nombreOriginal = VALUES(nombreOriginal),
               comentario     = VALUES(comentario),
               entregado_en   = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$idActividad, $idEstudiante, $archivoEntrega, $nombreOriginal, $comentario]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Obtiene todas las entregas de una actividad con datos del estudiante.
     */
    public function getByActividad(int $idActividad): array {
        $stmt = $this->db->prepare(
            "SELECT entregas.*, u.nombre AS estudiante, est.codigoEst
             FROM entregas
             JOIN estudiantes est ON entregas.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE entregas.idActividad = ?
             ORDER BY entregas.entregado_en DESC"
        );
        $stmt->execute([$idActividad]);
        return $stmt->fetchAll();
    }

    /**
     * Califica una entrega y guarda retroalimentación.
     */
    public function calificar(int $idEntrega, float $nota, string $feedback): void {
        $stmt = $this->db->prepare(
            "UPDATE entregas SET nota = ?, feedback = ? WHERE idEntrega = ?"
        );
        $stmt->execute([$nota, $feedback, $idEntrega]);
    }

    /**
     * Obtiene una entrega por su ID con datos del estudiante y la actividad.
     */
    public function findById(int $idEntrega): ?array {
        $stmt = $this->db->prepare(
            "SELECT entregas.*, u.nombre AS estudiante, actividades.titulo AS actividad,
                    actividades.puntaje_max
             FROM entregas
             JOIN estudiantes est ON entregas.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             JOIN actividades ON entregas.idActividad = actividades.idActividad
             WHERE entregas.idEntrega = ?"
        );
        $stmt->execute([$idEntrega]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Verifica si un estudiante ya entregó una actividad.
     */
    public function yaEntrego(int $idActividad, int $idEstudiante): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM entregas WHERE idActividad = ? AND idEstudiante = ?"
        );
        $stmt->execute([$idActividad, $idEstudiante]);
        return (bool)$stmt->fetch();
    }

    /**
     * Obtiene el conteo de entregas hechas vs total de actividades de un estudiante.
     */
    public function getConteoEstudiante(int $idEstudiante): array {
        $stmt = $this->db->prepare(
            "SELECT
               COUNT(DISTINCT e.idEntrega) AS entregas_hechas,
               COUNT(DISTINCT a.idActividad) AS total_actividades
             FROM actividades a
             JOIN aulas_virtuales av ON a.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             JOIN inscripciones i ON m.idMateria = i.idMateria
             LEFT JOIN entregas e ON a.idActividad = e.idActividad
               AND e.idEstudiante = ?
             WHERE i.idEstudiante = ?"
        );
        $stmt->execute([$idEstudiante, $idEstudiante]);
        return $stmt->fetch() ?: ['entregas_hechas' => 0, 'total_actividades' => 0];
    }
}

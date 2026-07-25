<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Actividad
 * Representa una tarea o evaluación del aula virtual.
 * Gestiona el ciclo completo: creación, entrega y calificación.
 */
class Actividad extends Model {
    protected string $table      = 'actividades';
    protected string $primaryKey = 'idActividad';

    public int    $idActividad;
    public int    $idAula;
    public string $titulo;
    public string $descripcion;
    public string $fechaEntrega;
    public float  $puntaje_max;
    public ?string $enunciado;
    public string $creada_en;

    /**
     * Obtiene todas las actividades de un aula, con estado de entrega del estudiante.
     */
    public function getByAulaConEntrega(int $idAula, int $idEstudiante): array {
        $stmt = $this->db->prepare(
            "SELECT actividades.*, entregas.idEntrega, entregas.nota, entregas.entregado_en
             FROM actividades
             LEFT JOIN entregas ON actividades.idActividad = entregas.idActividad
                               AND entregas.idEstudiante = ?
             WHERE actividades.idAula = ?
             ORDER BY actividades.fechaEntrega ASC"
        );
        $stmt->execute([$idEstudiante, $idAula]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene actividades de un aula con conteo de entregas (vista profesor).
     */
    public function getByAulaConConteo(int $idAula): array {
        $stmt = $this->db->prepare(
            "SELECT actividades.*,
                    (SELECT COUNT(*) FROM entregas WHERE entregas.idActividad = actividades.idActividad) AS total_entregas,
                    (SELECT COUNT(*) FROM entregas WHERE entregas.idActividad = actividades.idActividad AND entregas.nota IS NOT NULL) AS total_calificadas
             FROM actividades
             WHERE actividades.idAula = ?
             ORDER BY actividades.fechaEntrega ASC"
        );
        $stmt->execute([$idAula]);
        return $stmt->fetchAll();
    }

    /**
     * Crea una nueva actividad.
     */
    public function crear(int $idAula, string $titulo, string $descripcion,
                          string $fechaEntrega, float $puntajeMax, ?string $enunciado = null): int {
        $stmt = $this->db->prepare(
            "INSERT INTO actividades (idAula, titulo, descripcion, fechaEntrega, puntaje_max, enunciado)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$idAula, $titulo, $descripcion, $fechaEntrega, $puntajeMax, $enunciado]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Obtiene una actividad por ID.
     */
    public function findById(int $idActividad): ?array {
        $stmt = $this->db->prepare("SELECT * FROM actividades WHERE idActividad = ?");
        $stmt->execute([$idActividad]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Retorna si la actividad ya venció.
     */
    public function estaVencida(string $fechaEntrega): bool {
        return strtotime($fechaEntrega) < time();
    }

    /**
     * Elimina una actividad (y sus entregas en cascada).
     */
    public function eliminar(int $idActividad): void {
        $this->db->prepare("DELETE FROM actividades WHERE idActividad = ?")->execute([$idActividad]);
    }

    /**
     * Obtiene las próximas actividades sin entregar de un estudiante.
     */
    public function getProximasSinEntregar(int $idEstudiante, int $limit = 5): array {
        $stmt = $this->db->prepare(
            "SELECT a.idActividad, a.titulo, a.fechaEntrega, a.puntaje_max,
                    m.nombre AS materia, m.color, av.idAula
             FROM actividades a
             JOIN aulas_virtuales av ON a.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             JOIN inscripciones i ON m.idMateria = i.idMateria
             WHERE i.idEstudiante = ?
               AND a.fechaEntrega >= NOW()
               AND a.idActividad NOT IN (
                   SELECT idActividad FROM entregas WHERE idEstudiante = ?
               )
             ORDER BY a.fechaEntrega ASC LIMIT ?"
        );
        $stmt->execute([$idEstudiante, $idEstudiante, $limit]);
        return $stmt->fetchAll();
    }
}

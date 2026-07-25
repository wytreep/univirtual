<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Estudiante
 * Extiende Usuario con datos académicos del rol estudiantil.
 * Gestiona inscripciones, entregas y asistencia.
 */
class Estudiante extends Model {
    protected string $table      = 'estudiantes';
    protected string $primaryKey = 'idEstudiante';

    public int    $idEstudiante;
    public int    $idUsuario;
    public string $codigoEst;
    public int    $semestre;
    public string $programa;

    /**
     * Obtiene el perfil del estudiante a partir de su idUsuario.
     */
    public function findByUsuario(int $idUsuario): ?array {
        $stmt = $this->db->prepare(
            "SELECT est.*, u.nombre, u.email, u.rol
             FROM estudiantes est
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE est.idUsuario = ?"
        );
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtiene todas las materias en las que está inscrito el estudiante,
     * con datos del profesor y notas.
     */
    public function getMaterias(int $idEstudiante): array {
        $stmt = $this->db->prepare(
            "SELECT m.*, i.nota_parcial1, i.nota_parcial2, i.nota_talleres, i.nota_final,
                    u.nombre AS prof_nombre, av.idAula,
                    (SELECT COUNT(*) FROM actividades
                     JOIN aulas_virtuales av2 ON actividades.idAula = av2.idAula
                     WHERE av2.idMateria = m.idMateria
                       AND actividades.fechaEntrega >= NOW()
                       AND actividades.idActividad NOT IN (
                           SELECT idActividad FROM entregas WHERE idEstudiante = i.idEstudiante
                       )
                    ) AS pendientes
             FROM inscripciones i
             JOIN materias m ON i.idMateria = m.idMateria
             JOIN profesores p ON m.idProfesor = p.idProfesor
             JOIN usuarios u ON p.idUsuario = u.idUsuario
             JOIN aulas_virtuales av ON m.idMateria = av.idMateria
             WHERE i.idEstudiante = ?
             ORDER BY m.nombre"
        );
        $stmt->execute([$idEstudiante]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene las próximas entregas pendientes del estudiante.
     */
    public function getTareasPendientes(int $idEstudiante): array {
        $stmt = $this->db->prepare(
            "SELECT actividades.*, m.nombre AS materia, m.color, av.idAula,
                    entregas.idEntrega
             FROM actividades
             JOIN aulas_virtuales av ON actividades.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             JOIN inscripciones i ON m.idMateria = i.idMateria AND i.idEstudiante = ?
             LEFT JOIN entregas ON actividades.idActividad = entregas.idActividad
                               AND entregas.idEstudiante = ?
             WHERE entregas.idEntrega IS NULL
             ORDER BY actividades.fechaEntrega ASC"
        );
        $stmt->execute([$idEstudiante, $idEstudiante]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el porcentaje de asistencia del estudiante en una materia.
     */
    public function getPorcentajeAsistencia(int $idEstudiante, int $idMateria): float {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN asistio = 1 THEN 1 ELSE 0 END) AS asistidas
             FROM asistencia
             WHERE idEstudiante = ? AND idMateria = ?"
        );
        $stmt->execute([$idEstudiante, $idMateria]);
        $row = $stmt->fetch();
        if (!$row || $row['total'] == 0) return 0.0;
        return round($row['asistidas'] / $row['total'] * 100, 1);
    }

    /**
     * Retorna todas las notas del estudiante.
     */
    public function getNotas(int $idEstudiante): array {
        $stmt = $this->db->prepare(
            "SELECT m.nombre, m.codigo, m.color,
                    i.nota_parcial1, i.nota_parcial2, i.nota_talleres, i.nota_final
             FROM inscripciones i
             JOIN materias m ON i.idMateria = m.idMateria
             WHERE i.idEstudiante = ?
             ORDER BY m.nombre"
        );
        $stmt->execute([$idEstudiante]);
        return $stmt->fetchAll();
    }
}

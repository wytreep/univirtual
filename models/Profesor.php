<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Profesor
 * Extiende Usuario con datos del docente.
 * Gestiona materias, materiales, actividades y calificaciones.
 */
class Profesor extends Model {

    public int    $idProfesor;
    public int    $idUsuario;
    public string $codigoProf;
    public string $departamento;

    /**
     * Obtiene el perfil del profesor a partir de su idUsuario.
     */
    public function findByUsuario(int $idUsuario): ?array {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.nombre, u.email
             FROM profesores p
             JOIN usuarios u ON p.idUsuario = u.idUsuario
             WHERE p.idUsuario = ?"
        );
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtiene todas las materias que dicta el profesor.
     */
    public function getMaterias(int $idProfesor): array {
        $stmt = $this->db->prepare(
            "SELECT materias.*, av.idAula,
                    (SELECT COUNT(*) FROM inscripciones WHERE inscripciones.idMateria = materias.idMateria) AS total_estudiantes,
                    (SELECT COUNT(*) FROM materiales WHERE materiales.idAula = av.idAula) AS total_materiales,
                    (SELECT COUNT(*) FROM actividades WHERE actividades.idAula = av.idAula) AS total_actividades
             FROM materias
             JOIN aulas_virtuales av ON materias.idMateria = av.idMateria
             WHERE materias.idProfesor = ?
             ORDER BY materias.nombre"
        );
        $stmt->execute([$idProfesor]);
        return $stmt->fetchAll();
    }

    /**
     * Cuenta entregas pendientes de calificar para todas las materias del profesor.
     */
    public function getTotalSinCalificar(int $idProfesor): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM entregas
             JOIN actividades ON entregas.idActividad = actividades.idActividad
             JOIN aulas_virtuales ON actividades.idAula = aulas_virtuales.idAula
             JOIN materias ON aulas_virtuales.idMateria = materias.idMateria
             WHERE materias.idProfesor = ? AND entregas.nota IS NULL"
        );
        $stmt->execute([$idProfesor]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene las últimas entregas recibidas en las materias del profesor.
     */
    public function getEntregasRecientes(int $idProfesor, int $limite = 5): array {
        $stmt = $this->db->prepare(
            "SELECT entregas.*, u.nombre AS estudiante,
                    actividades.titulo AS actividad, materias.nombre AS materia
             FROM entregas
             JOIN estudiantes ON entregas.idEstudiante = estudiantes.idEstudiante
             JOIN usuarios u ON estudiantes.idUsuario = u.idUsuario
             JOIN actividades ON entregas.idActividad = actividades.idActividad
             JOIN aulas_virtuales ON actividades.idAula = aulas_virtuales.idAula
             JOIN materias ON aulas_virtuales.idMateria = materias.idMateria
             WHERE materias.idProfesor = ?
             ORDER BY entregas.entregado_en DESC
             LIMIT ?"
        );
        $stmt->execute([$idProfesor, $limite]);
        return $stmt->fetchAll();
    }
}

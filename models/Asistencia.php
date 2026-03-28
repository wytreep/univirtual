<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Asistencia
 * Registra la presencia de un estudiante en cada sesión de clase.
 * Restricción UNIQUE sobre (idMateria, idEstudiante, fecha) — un registro por día.
 */
class Asistencia extends Model {

    public int    $idAsistencia;
    public int    $idMateria;
    public int    $idEstudiante;
    public string $fecha;
    public bool   $asistio;

    /**
     * Registra o actualiza la asistencia de un estudiante en una sesión.
     * Usa ON DUPLICATE KEY para evitar duplicados.
     */
    public function registrar(int $idMateria, int $idEstudiante, string $fecha, bool $asistio): void {
        $stmt = $this->db->prepare(
            "INSERT INTO asistencia (idMateria, idEstudiante, fecha, asistio)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE asistio = VALUES(asistio)"
        );
        $stmt->execute([$idMateria, $idEstudiante, $fecha, $asistio ? 1 : 0]);
    }

    /**
     * Obtiene el reporte de asistencia de una materia en un rango de fechas.
     */
    public function getReporte(int $idMateria, string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT asistencia.*, u.nombre AS estudiante, est.codigoEst
             FROM asistencia
             JOIN estudiantes est ON asistencia.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE asistencia.idMateria = ? AND asistencia.fecha BETWEEN ? AND ?
             ORDER BY u.nombre, asistencia.fecha"
        );
        $stmt->execute([$idMateria, $desde, $hasta]);
        return $stmt->fetchAll();
    }

    /**
     * Calcula el porcentaje de asistencia de un estudiante en una materia.
     */
    public function getPorcentaje(int $idEstudiante, int $idMateria): float {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, SUM(asistio) AS asistidas
             FROM asistencia WHERE idEstudiante = ? AND idMateria = ?"
        );
        $stmt->execute([$idEstudiante, $idMateria]);
        $row = $stmt->fetch();
        if (!$row || $row['total'] == 0) return 0.0;
        return round($row['asistidas'] / $row['total'] * 100, 1);
    }

    /**
     * Obtiene el resumen global de asistencia de un estudiante (todas las materias).
     */
    public function getResumenEstudiante(int $idEstudiante): array {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, SUM(asistio) AS asistidas
             FROM asistencia WHERE idEstudiante = ?"
        );
        $stmt->execute([$idEstudiante]);
        return $stmt->fetch();
    }
}

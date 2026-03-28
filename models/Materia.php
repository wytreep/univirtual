<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Materia
 * Representa una asignatura académica del sistema.
 * Eje central alrededor del cual se organiza el contenido.
 */
class Materia extends Model {

    public int    $idMateria;
    public int    $idProfesor;
    public string $nombre;
    public string $codigo;
    public int    $creditos;
    public int    $semestre;
    public string $descripcion;
    public string $color;
    public bool   $activa;

    /**
     * Obtiene una materia por su ID.
     */
    public function findById(int $idMateria): ?array {
        $stmt = $this->db->prepare("SELECT * FROM materias WHERE idMateria = ?");
        $stmt->execute([$idMateria]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Retorna todos los estudiantes inscritos en la materia.
     */
    public function getEstudiantes(int $idMateria): array {
        $stmt = $this->db->prepare(
            "SELECT u.nombre, est.codigoEst, est.idEstudiante,
                    i.nota_parcial1, i.nota_parcial2, i.nota_talleres, i.nota_final
             FROM inscripciones i
             JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE i.idMateria = ?
             ORDER BY u.nombre"
        );
        $stmt->execute([$idMateria]);
        return $stmt->fetchAll();
    }

    /**
     * Actualiza las notas de un estudiante en la materia.
     */
    public function actualizarNota(int $idEstudiante, int $idMateria, string $campo, float $nota): void {
        $campos = ['nota_parcial1', 'nota_parcial2', 'nota_talleres', 'nota_final'];
        if (!in_array($campo, $campos)) return;
        $stmt = $this->db->prepare(
            "UPDATE inscripciones SET $campo = ? WHERE idEstudiante = ? AND idMateria = ?"
        );
        $stmt->execute([$nota, $idEstudiante, $idMateria]);
    }

    /**
     * Crea una nueva materia y su aula virtual automáticamente.
     */
    public function crear(int $idProfesor, string $nombre, string $codigo, int $creditos, string $descripcion, string $color): int {
        $stmt = $this->db->prepare(
            "INSERT INTO materias (idProfesor, nombre, codigo, creditos, descripcion, color)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$idProfesor, $nombre, $codigo, $creditos, $descripcion, $color]);
        $idMateria = (int)$this->db->lastInsertId();

        // Crear aula virtual automáticamente
        $this->db->prepare("INSERT INTO aulas_virtuales (idMateria) VALUES (?)")->execute([$idMateria]);

        return $idMateria;
    }
}

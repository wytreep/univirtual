<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Material
 * Representa un recurso educativo digital dentro de un aula virtual.
 * Gestiona la subida, descarga y eliminación de archivos.
 */
class Material extends Model {

    public int    $idMaterial;
    public int    $idAula;
    public int    $idProfesor;
    public string $titulo;
    public string $descripcion;
    public string $tipo;          // 'pdf' | 'video' | 'presentacion' | 'otro'
    public string $urlArchivo;
    public string $nombreOriginal;
    public int    $tamano;
    public int    $descargas;
    public string $subido_en;

    /**
     * Obtiene todos los materiales de un aula.
     */
    public function getByAula(int $idAula): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM materiales WHERE idAula = ? ORDER BY subido_en DESC"
        );
        $stmt->execute([$idAula]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un material por su ID.
     */
    public function findById(int $idMaterial): ?array {
        $stmt = $this->db->prepare("SELECT * FROM materiales WHERE idMaterial = ?");
        $stmt->execute([$idMaterial]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Registra un nuevo material en la base de datos.
     */
    public function crear(int $idAula, int $idProfesor, string $titulo, string $descripcion,
                          string $tipo, string $urlArchivo, string $nombreOriginal, int $tamano): int {
        $stmt = $this->db->prepare(
            "INSERT INTO materiales (idAula, idProfesor, titulo, descripcion, tipo, urlArchivo, nombreOriginal, tamano)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$idAula, $idProfesor, $titulo, $descripcion, $tipo, $urlArchivo, $nombreOriginal, $tamano]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Incrementa el contador de descargas del material.
     */
    public function registrarDescarga(int $idMaterial): void {
        $this->db->prepare(
            "UPDATE materiales SET descargas = descargas + 1 WHERE idMaterial = ?"
        )->execute([$idMaterial]);
    }

    /**
     * Elimina un material de la BD.
     * Retorna la ruta del archivo para borrarlo del disco.
     */
    public function eliminar(int $idMaterial): ?string {
        $material = $this->findById($idMaterial);
        if (!$material) return null;
        $this->db->prepare("DELETE FROM materiales WHERE idMaterial = ?")->execute([$idMaterial]);
        return $material['urlArchivo'];
    }

    /**
     * Verifica si un estudiante tiene acceso al material (está inscrito en la materia).
     */
    public function verificarAccesoEstudiante(int $idMaterial, int $idEstudiante): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM materiales
             JOIN aulas_virtuales ON materiales.idAula = aulas_virtuales.idAula
             JOIN inscripciones ON aulas_virtuales.idMateria = inscripciones.idMateria
             WHERE materiales.idMaterial = ? AND inscripciones.idEstudiante = ?"
        );
        $stmt->execute([$idMaterial, $idEstudiante]);
        return (bool)$stmt->fetch();
    }
}

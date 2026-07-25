<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Notificacion
 * Gestiona las alertas automáticas del sistema para los usuarios.
 */
class Notificacion extends Model {
    protected string $table      = 'notificaciones';
    protected string $primaryKey = 'idNotif';

    public int    $idNotif;
    public int    $idUsuario;
    public string $titulo;
    public string $mensaje;
    public string $tipo;      // 'info'|'tarea'|'nota'|'foro'|'material'|'sistema'
    public bool   $leida;
    public ?string $url;
    public string  $creado_en;

    /**
     * Crea una nueva notificación para un usuario.
     */
    public function crear(int $idUsuario, string $titulo, string $mensaje,
                          string $tipo = 'info', string $url = ''): int {
        $stmt = $this->db->prepare(
            "INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo, url)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$idUsuario, $titulo, $mensaje, $tipo, $url]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Obtiene las notificaciones de un usuario (más recientes primero).
     * @param int|null $limit Límite de registros (null = todas).
     */
    public function getByUsuario(int $idUsuario, ?int $limit = null): array {
        $sql = "SELECT * FROM notificaciones WHERE idUsuario = ? ORDER BY creado_en DESC";
        if ($limit !== null) $sql .= " LIMIT $limit";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idUsuario]);
        return $stmt->fetchAll();
    }

    /**
     * Cuenta las notificaciones no leídas de un usuario.
     */
    public function contarNoLeidas(int $idUsuario): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notificaciones WHERE idUsuario = ? AND leida = 0"
        );
        $stmt->execute([$idUsuario]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Marca todas las notificaciones de un usuario como leídas.
     */
    public function marcarTodasLeidas(int $idUsuario): void {
        $this->db->prepare(
            "UPDATE notificaciones SET leida = 1 WHERE idUsuario = ?"
        )->execute([$idUsuario]);
    }

    /**
     * Marca una notificación específica como leída.
     */
    public function marcarLeida(int $idNotif): void {
        $this->db->prepare(
            "UPDATE notificaciones SET leida = 1 WHERE idNotif = ?"
        )->execute([$idNotif]);
    }

    /**
     * Envía una notificación a todos los estudiantes inscritos en una materia.
     */
    public function notificarEstudiantesMateria(int $idMateria, string $titulo,
                                                 string $mensaje, string $tipo, string $url = ''): void {
        $stmt = $this->db->prepare(
            "SELECT u.idUsuario FROM inscripciones i
             JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE i.idMateria = ?"
        );
        $stmt->execute([$idMateria]);
        $usuarios = $stmt->fetchAll();
        foreach ($usuarios as $u) {
            $this->crear($u['idUsuario'], $titulo, $mensaje, $tipo, $url);
        }
    }
}


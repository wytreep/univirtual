<?php
require_once __DIR__.'/Model.php';
/**
 * Clase Usuario
 * Representa a cualquier persona registrada en el sistema.
 * Superclase de Estudiante y Profesor.
 */
class Usuario extends Model {
    protected string $table      = 'usuarios';
    protected string $primaryKey = 'idUsuario';

    public int    $idUsuario;
    public string $nombre;
    public string $email;
    public string $password;
    public string $rol;
    public bool   $activo;
    public string $creado_en;

    /**
     * Busca un usuario por su email.
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM usuarios WHERE email = ? AND activo = 1"
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Verifica si la contraseña ingresada coincide con el hash almacenado.
     */
    public function verificarPassword(string $passwordIngresado, string $hashAlmacenado): bool {
        return password_verify($passwordIngresado, $hashAlmacenado);
    }

    /**
     * Busca un usuario por su ID.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE idUsuario = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Crea un nuevo usuario en la base de datos.
     */
    public function crear(string $nombre, string $email, string $password, string $rol): int {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (nombre, email, `password`, rol) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$nombre, $email, $hash, $rol]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Retorna todos los usuarios del sistema.
     */
    public function getAll(): array {
        return $this->db->query(
            "SELECT * FROM usuarios ORDER BY nombre"
        )->fetchAll();
    }

    /**
     * Retorna todos los usuarios de un rol específico con datos extendidos.
     * Incluye codigoEst/codigoProf y semestre según corresponda.
     * Usado por DirectivoController — SCRUM-128.
     */
    public function getByRol(string $rol): array {
        if ($rol === 'estudiante') {
            $stmt = $this->db->prepare(
                "SELECT u.idUsuario, u.nombre, u.email, u.activo,
                        e.codigoEst, e.semestre, e.creditos_aprobados
                 FROM usuarios u
                 JOIN estudiantes e ON e.idUsuario = u.idUsuario
                 WHERE u.rol = 'estudiante'
                 ORDER BY u.nombre"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        }

        if ($rol === 'profesor') {
            $stmt = $this->db->prepare(
                "SELECT u.idUsuario, u.nombre, u.email, u.activo,
                        p.codigoProf, p.departamento
                 FROM usuarios u
                 JOIN profesores p ON p.idUsuario = u.idUsuario
                 WHERE u.rol = 'profesor'
                 ORDER BY u.nombre"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        }

        // Para cualquier otro rol — consulta simple
        $stmt = $this->db->prepare(
            "SELECT * FROM usuarios WHERE rol = ? ORDER BY nombre"
        );
        $stmt->execute([$rol]);
        return $stmt->fetchAll();
    }

    /**
     * Activa o desactiva un usuario.
     */
    public function setActivo(int $idUsuario, bool $activo): void {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET activo = ? WHERE idUsuario = ?"
        );
        $stmt->execute([$activo ? 1 : 0, $idUsuario]);
    }

    /**
     * Actualiza el nombre de un usuario.
     */
    public function actualizarNombre(int $idUsuario, string $nombre): void {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET nombre = ? WHERE idUsuario = ?"
        );
        $stmt->execute([trim($nombre), $idUsuario]);
    }

    /**
     * Cambia la contraseña verificando la actual primero.
     * Retorna true si el cambio fue exitoso, false si la contraseña actual no coincide.
     */
    public function cambiarPassword(int $idUsuario, string $actual, string $nueva): bool {
        $stmt = $this->db->prepare(
            "SELECT `password` FROM usuarios WHERE idUsuario = ?"
        );
        $stmt->execute([$idUsuario]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($actual, $hash)) {
            return false;
        }

        $nuevoHash = password_hash($nueva, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET `password` = ? WHERE idUsuario = ?"
        );
        $stmt->execute([$nuevoHash, $idUsuario]);
        return true;
    }
}
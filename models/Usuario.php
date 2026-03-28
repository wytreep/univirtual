<?php
require_once __DIR__.'/Model.php';

/**
 * Clase Usuario
 * Representa a cualquier persona registrada en el sistema.
 * Superclase de Estudiante y Profesor.
 */
class Usuario extends Model {

    // Atributos de la clase
    public int    $idUsuario;
    public string $nombre;
    public string $email;
    public string $password;
    public string $rol;       // 'estudiante' | 'profesor' | 'admin'
    public bool   $activo;
    public string $creado_en;

    /**
     * Busca un usuario por su email.
     * Retorna array con los datos o null si no existe.
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
            "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$nombre, $email, $hash, $rol]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Retorna todos los usuarios del sistema.
     */
    public function getAll(): array {
        return $this->db->query("SELECT * FROM usuarios ORDER BY nombre")->fetchAll();
    }

    /**
     * Activa o desactiva un usuario.
     */
    public function setActivo(int $idUsuario, bool $activo): void {
        $stmt = $this->db->prepare("UPDATE usuarios SET activo = ? WHERE idUsuario = ?");
        $stmt->execute([$activo ? 1 : 0, $idUsuario]);
    }
}

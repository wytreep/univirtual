<?php
require_once __DIR__.'/Controller.php';

/**
 * UsuarioController
 * API REST para gestión de usuarios (admin).
 *
 * GET    /api/v1/usuarios.php          → listar todos
 * GET    /api/v1/usuarios.php?id=1     → obtener uno
 * POST   /api/v1/usuarios.php          → crear usuario
 * PUT    /api/v1/usuarios.php?id=1     → actualizar usuario
 * DELETE /api/v1/usuarios.php?id=1     → eliminar/desactivar
 */
class UsuarioController extends Controller {

    private Usuario $modelo;

    public function __construct() {
        parent::__construct();
        $this->modelo = new Usuario();
    }

    public function handle(): void {
        // Solo admin puede gestionar usuarios
        $session = $this->requireAuth(['admin', 'directivo']);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($this->method() === 'GET') {
            if ($id) { $this->getOne($id); return; }
            $action = $_GET['action'] ?? '';
            $idF = (int)($_GET['idFacultad'] ?? 0);
            if ($action === 'profesores_facultad') { $this->getProfesoresFacultad($idF); return; }
            if ($action === 'estudiantes_facultad') { $this->getEstudiantesFacultad($idF); return; }
            $this->getAll();
            return;
        }

        match($this->method()) {
            'POST'   => $this->crear(),
            'PUT'    => $this->actualizar($id),
            'DELETE' => $this->eliminar($id),
            default  => $this->error('Método no permitido', 405)
        };
    }

    /**
     * GET /usuarios?action=profesores_facultad — Lista profesores específicos de una facultad.
     */
    private function getProfesoresFacultad(int $idFacultad): void {
        try {
            $stmt = db()->query(
                "SELECT p.idProfesor, u.nombre, p.codigoProf, p.departamento, u.email,
                        (SELECT COUNT(*) FROM materias WHERE idProfesor = p.idProfesor) AS total_materias
                 FROM profesores p
                 JOIN usuarios u ON p.idUsuario = u.idUsuario
                 WHERE p.idFacultad=$idFacultad
                 ORDER BY u.nombre"
            );
            $this->ok($stmt->fetchAll());
        } catch(Exception $e) { $this->ok([]); }
    }

    /**
     * GET /usuarios?action=estudiantes_facultad — Lista estudiantes específicos de una facultad.
     */
    private function getEstudiantesFacultad(int $idFacultad): void {
        try {
            $stmt = db()->query(
                "SELECT e.idEstudiante, u.nombre, e.codigoEst, e.semestre, e.creditos_aprobados, p.nombre AS carrera
                 FROM estudiantes e
                 JOIN usuarios u ON e.idUsuario = u.idUsuario
                 JOIN programas_academicos p ON e.idPrograma = p.idPrograma
                 WHERE p.idFacultad=$idFacultad
                 ORDER BY u.nombre"
            );
            $this->ok($stmt->fetchAll());
        } catch(Exception $ex) { $this->ok([]); }
    }

    /**
     * GET /usuarios — Lista todos los usuarios con estadísticas.
     */
    private function getAll(): void {
        $db   = db();
        $stmt = $db->query(
            "SELECT u.idUsuario, u.nombre, u.email, u.rol, u.activo, u.creado_en,
                    est.codigoEst, est.semestre,
                    p.codigoProf, p.departamento
             FROM usuarios u
             LEFT JOIN estudiantes est ON u.idUsuario = est.idUsuario
             LEFT JOIN profesores p   ON u.idUsuario = p.idUsuario
             ORDER BY u.creado_en DESC"
        );
        $usuarios = $stmt->fetchAll();
        $this->ok($usuarios);
    }

    /**
     * GET /usuarios?id=1 — Obtiene un usuario por ID.
     */
    private function getOne(int $id): void {
        $usuario = $this->modelo->findById($id);
        if (!$usuario) $this->error('Usuario no encontrado', 404);
        // No retornar el hash de la contraseña
        unset($usuario['password']);
        $this->ok($usuario);
    }

    /**
     * POST /usuarios — Crea un nuevo usuario.
     * Body: { nombre, email, password, rol, codigo, semestre?, departamento? }
     */
    private function crear(): void {
        $body = $this->getBody();

        // Validaciones
        if (empty($body['nombre']))   $this->error('El nombre es requerido');
        if (empty($body['email']))    $this->error('El email es requerido');
        if (empty($body['password'])) $this->error('La contraseña es requerida');
        if (empty($body['rol']))      $this->error('El rol es requerido');
        if (!in_array($body['rol'], ['estudiante', 'profesor', 'admin'])) {
            $this->error('Rol inválido');
        }

        // Verificar email único
        $existente = $this->modelo->findByEmail($body['email']);
        if ($existente) $this->error('El email ya está registrado');

        $db = db();
        $db->beginTransaction();
        try {
            // Crear usuario base
            $idUsuario = $this->modelo->crear(
                $body['nombre'],
                $body['email'],
                $body['password'],
                $body['rol']
            );

            // Crear perfil específico según rol
            if ($body['rol'] === 'estudiante') {
                if (empty($body['codigo'])) $this->error('El código del estudiante es requerido');
                $db->prepare(
                    "INSERT INTO estudiantes (idUsuario, codigoEst, semestre, programa)
                     VALUES (?, ?, ?, ?)"
                )->execute([
                    $idUsuario,
                    $body['codigo'],
                    $body['semestre'] ?? 1,
                    $body['programa'] ?? 'Ingeniería de Sistemas'
                ]);
            } elseif ($body['rol'] === 'profesor') {
                if (empty($body['codigo'])) $this->error('El código del profesor es requerido');
                $db->prepare(
                    "INSERT INTO profesores (idUsuario, codigoProf, departamento)
                     VALUES (?, ?, ?)"
                )->execute([
                    $idUsuario,
                    $body['codigo'],
                    $body['departamento'] ?? 'Ingeniería de Sistemas'
                ]);
            }

            $db->commit();
            $this->ok(['idUsuario' => $idUsuario], 'Usuario creado exitosamente');

        } catch (Exception $e) {
            $db->rollBack();
            $this->error('Error al crear usuario: '.$e->getMessage());
        }
    }

    /**
     * PUT /usuarios?id=1 — Actualiza un usuario.
     * Body: { nombre?, email?, activo?, semestre?, departamento? }
     */
    private function actualizar(?int $id): void {
        if (!$id) $this->error('ID requerido');
        $body    = $this->getBody();
        $usuario = $this->modelo->findById($id);
        if (!$usuario) $this->error('Usuario no encontrado', 404);

        $db = db();

        // Actualizar campos básicos
        if (isset($body['nombre']) || isset($body['email']) || isset($body['activo'])) {
            $nombre = $body['nombre'] ?? $usuario['nombre'];
            $email  = $body['email']  ?? $usuario['email'];
            $activo = $body['activo'] ?? $usuario['activo'];
            $db->prepare(
                "UPDATE usuarios SET nombre=?, email=?, activo=? WHERE idUsuario=?"
            )->execute([$nombre, $email, $activo ? 1 : 0, $id]);
        }

        // Actualizar contraseña si viene
        if (!empty($body['password'])) {
            $hash = password_hash($body['password'], PASSWORD_BCRYPT);
            $db->prepare("UPDATE usuarios SET password=? WHERE idUsuario=?")->execute([$hash, $id]);
        }

        // Actualizar perfil específico
        if ($usuario['rol'] === 'estudiante' && isset($body['semestre'])) {
            $db->prepare("UPDATE estudiantes SET semestre=? WHERE idUsuario=?")->execute([$body['semestre'], $id]);
        }
        if ($usuario['rol'] === 'profesor' && isset($body['departamento'])) {
            $db->prepare("UPDATE profesores SET departamento=? WHERE idUsuario=?")->execute([$body['departamento'], $id]);
        }

        $this->ok(null, 'Usuario actualizado');
    }

    /**
     * DELETE /usuarios?id=1 — Desactiva un usuario (soft delete).
     */
    private function eliminar(?int $id): void {
        if (!$id) $this->error('ID requerido');
        $usuario = $this->modelo->findById($id);
        if (!$usuario) $this->error('Usuario no encontrado', 404);

        // Soft delete — no borrar físicamente, solo desactivar
        $this->modelo->setActivo($id, false);
        $this->ok(null, 'Usuario desactivado');
    }
}

// Instanciar y ejecutar
(new UsuarioController())->handle();

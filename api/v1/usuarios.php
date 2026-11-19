<?php
require_once __DIR__.'/Controller.php';

/**
 * UsuarioController — copia ejecutable Sprints 1 y 2.
 *
 * Incluye el parche de UV-9:
 *  - acepta el contrato `codigo` (y compatibilidad con codigoEst/codigoProf)
 *  - inserta `idCarrera` e `idFacultad` según el esquema v8
 *  - crea estudiantes, profesores, directivos y administradores
 *  - corrige el JOIN de carreras
 */
class UsuarioController extends Controller {

    private Usuario $modelo;

    public function __construct() {
        parent::__construct();
        $this->modelo = new Usuario();
    }

    public function handle(): void {
        $this->requireAuth(['admin', 'directivo']);

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if ($this->method() === 'GET') {
            if ($id) {
                $this->getOne($id);
                return;
            }

            $action = $_GET['action'] ?? '';
            $idF    = (int) ($_GET['idFacultad'] ?? 0);

            if ($action === 'profesores_facultad')  { $this->getProfesoresFacultad($idF); return; }
            if ($action === 'estudiantes_facultad') { $this->getEstudiantesFacultad($idF); return; }
            if ($action === 'catalogos')            { $this->getCatalogos(); return; }

            $this->getAll();
            return;
        }

        match ($this->method()) {
            'POST'   => $this->crear(),
            'PUT'    => $this->actualizar($id),
            'DELETE' => $this->eliminar($id),
            default  => $this->error('Método no permitido', 405)
        };
    }

    /** GET /usuarios?action=catalogos — carreras y facultades para el formulario. */
    private function getCatalogos(): void {
        $carreras = db()->query(
            "SELECT c.idCarrera, c.idFacultad, c.nombre, c.codigo
               FROM carreras c
              WHERE c.activa = 1
              ORDER BY c.nombre"
        )->fetchAll();

        $facultades = db()->query(
            "SELECT idFacultad, nombre, codigo
               FROM facultades
              ORDER BY nombre"
        )->fetchAll();

        $this->ok(['carreras' => $carreras, 'facultades' => $facultades]);
    }

    /** GET /usuarios?action=profesores_facultad */
    private function getProfesoresFacultad(int $idFacultad): void {
        $stmt = db()->prepare(
            "SELECT p.idProfesor, u.nombre, p.codigoProf, p.departamento, u.email,
                    (SELECT COUNT(*) FROM materias WHERE idProfesor = p.idProfesor) AS total_materias
               FROM profesores p
               JOIN usuarios u ON p.idUsuario = u.idUsuario
              WHERE p.idFacultad = ?
              ORDER BY u.nombre"
        );
        $stmt->execute([$idFacultad]);
        $this->ok($stmt->fetchAll());
    }

    /** GET /usuarios?action=estudiantes_facultad */
    private function getEstudiantesFacultad(int $idFacultad): void {
        $stmt = db()->prepare(
            "SELECT e.idEstudiante, u.nombre, e.codigoEst, e.semestre,
                    e.creditos_aprobados, e.idCarrera, c.nombre AS carrera
               FROM estudiantes e
               JOIN usuarios u  ON e.idUsuario = u.idUsuario
               JOIN carreras c  ON e.idCarrera = c.idCarrera
              WHERE c.idFacultad = ?
              ORDER BY u.nombre"
        );
        $stmt->execute([$idFacultad]);
        $this->ok($stmt->fetchAll());
    }

    /** GET /usuarios — listado con perfil. */
    private function getAll(): void {
        $stmt = db()->query(
            "SELECT u.idUsuario, u.nombre, u.email, u.rol, u.activo, u.creado_en,
                    est.codigoEst, est.semestre, est.idCarrera, car.nombre AS carrera,
                    p.codigoProf, p.departamento, p.idFacultad
               FROM usuarios u
               LEFT JOIN estudiantes est ON u.idUsuario = est.idUsuario
               LEFT JOIN carreras car    ON est.idCarrera = car.idCarrera
               LEFT JOIN profesores p    ON u.idUsuario = p.idUsuario
              ORDER BY u.creado_en DESC"
        );
        $this->ok($stmt->fetchAll());
    }

    /** GET /usuarios?id=1 */
    private function getOne(int $id): void {
        $usuario = $this->modelo->findById($id);
        if (!$usuario) $this->error('Usuario no encontrado', 404);

        unset($usuario['password']);
        $this->ok($usuario);
    }

    /** POST /usuarios */
    private function crear(): void {
        $body = $this->getBody();

        $nombre   = trim($body['nombre'] ?? '');
        $email    = trim($body['email'] ?? '');
        $password = (string) ($body['password'] ?? '');
        $rol      = (string) ($body['rol'] ?? '');

        if ($nombre === '')   $this->error('El nombre es requerido');
        if ($email === '')    $this->error('El email es requerido');
        if ($password === '') $this->error('La contraseña es requerida');
        if ($rol === '')      $this->error('El rol es requerido');

        if (!in_array($rol, ['estudiante', 'profesor', 'directivo', 'admin'], true)) {
            $this->error('Rol inválido');
        }

        // Verificar email incluso si existe una cuenta inactiva.
        $existe = db()->prepare('SELECT idUsuario FROM usuarios WHERE email = ?');
        $existe->execute([$email]);
        if ($existe->fetch()) $this->error('El email ya está registrado');

        // Contrato unificado: el frontend puede enviar codigo, codigoEst o codigoProf.
        $codigo = trim((string) ($body['codigo'] ?? $body['codigoEst'] ?? $body['codigoProf'] ?? ''));

        if ($rol === 'estudiante' && $codigo === '') {
            $this->error('El código del estudiante es requerido');
        }
        if ($rol === 'profesor' && $codigo === '') {
            $codigo = 'PROF-' . strtoupper(substr(uniqid(), -6));
        }

        $idCarrera  = (int) ($body['idCarrera'] ?? 0);
        $idFacultad = (int) ($body['idFacultad'] ?? 0);

        if ($rol === 'estudiante' && $idCarrera <= 0) {
            $idCarrera = (int) (db()->query(
                'SELECT idCarrera FROM carreras WHERE activa = 1 ORDER BY idCarrera LIMIT 1'
            )->fetchColumn() ?: 0);
        }
        if (in_array($rol, ['profesor', 'directivo'], true) && $idFacultad <= 0) {
            $idFacultad = (int) (db()->query(
                'SELECT idFacultad FROM facultades ORDER BY idFacultad LIMIT 1'
            )->fetchColumn() ?: 0);
        }
        if ($rol === 'directivo' && $idFacultad <= 0) {
            $this->error('No hay facultades registradas para asignar al directivo');
        }

        $db = db();
        $db->beginTransaction();

        try {
            $idUsuario = $this->modelo->crear($nombre, $email, $password, $rol);

            if ($rol === 'estudiante') {
                $db->prepare(
                    'INSERT INTO estudiantes (idUsuario, idCarrera, codigoEst, semestre, creditos_aprobados)
                     VALUES (?, ?, ?, ?, 0)'
                )->execute([
                    $idUsuario,
                    $idCarrera ?: null,
                    $codigo,
                    (int) ($body['semestre'] ?? 1),
                ]);
            } elseif ($rol === 'profesor') {
                $db->prepare(
                    'INSERT INTO profesores (idUsuario, idFacultad, codigoProf, departamento)
                     VALUES (?, ?, ?, ?)'
                )->execute([
                    $idUsuario,
                    $idFacultad ?: null,
                    $codigo,
                    trim((string) ($body['departamento'] ?? 'Sistemas')),
                ]);
            } elseif ($rol === 'directivo') {
                $db->prepare(
                    'INSERT INTO directivos (idUsuario, idFacultad, cargo)
                     VALUES (?, ?, ?)'
                )->execute([
                    $idUsuario,
                    $idFacultad,
                    trim((string) ($body['cargo'] ?? 'Decano')),
                ]);
            }

            $db->commit();
            $this->ok(['idUsuario' => $idUsuario], 'Usuario creado exitosamente');
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[UV-9] Error al crear usuario: ' . $e->getMessage());
            $this->error('No fue posible crear el usuario. Revisa los datos e intenta de nuevo.', 500);
        }
    }

    /** PUT /usuarios?id=1 */
    private function actualizar(?int $id): void {
        if (!$id) $this->error('ID requerido');

        $body    = $this->getBody();
        $usuario = $this->modelo->findById($id);
        if (!$usuario) $this->error('Usuario no encontrado', 404);

        $db = db();

        if (isset($body['nombre']) || isset($body['email']) || isset($body['activo'])) {
            $nombre = $body['nombre'] ?? $usuario['nombre'];
            $email  = $body['email']  ?? $usuario['email'];
            $activo = $body['activo'] ?? $usuario['activo'];

            $db->prepare('UPDATE usuarios SET nombre = ?, email = ?, activo = ? WHERE idUsuario = ?')
               ->execute([$nombre, $email, $activo ? 1 : 0, $id]);
        }

        if (!empty($body['password'])) {
            $hash = password_hash($body['password'], PASSWORD_BCRYPT);
            $db->prepare('UPDATE usuarios SET password = ? WHERE idUsuario = ?')
               ->execute([$hash, $id]);
        }

        if ($usuario['rol'] === 'estudiante') {
            if (isset($body['semestre'])) {
                $db->prepare('UPDATE estudiantes SET semestre = ? WHERE idUsuario = ?')
                   ->execute([(int) $body['semestre'], $id]);
            }
            if (!empty($body['idCarrera'])) {
                $db->prepare('UPDATE estudiantes SET idCarrera = ? WHERE idUsuario = ?')
                   ->execute([(int) $body['idCarrera'], $id]);
            }
        }

        if ($usuario['rol'] === 'profesor') {
            if (isset($body['departamento'])) {
                $db->prepare('UPDATE profesores SET departamento = ? WHERE idUsuario = ?')
                   ->execute([$body['departamento'], $id]);
            }
            if (!empty($body['idFacultad'])) {
                $db->prepare('UPDATE profesores SET idFacultad = ? WHERE idUsuario = ?')
                   ->execute([(int) $body['idFacultad'], $id]);
            }
        }

        if ($usuario['rol'] === 'directivo') {
            if (isset($body['cargo'])) {
                $db->prepare('UPDATE directivos SET cargo = ? WHERE idUsuario = ?')
                   ->execute([$body['cargo'], $id]);
            }
            if (!empty($body['idFacultad'])) {
                $db->prepare('UPDATE directivos SET idFacultad = ? WHERE idUsuario = ?')
                   ->execute([(int) $body['idFacultad'], $id]);
            }
        }

        $this->ok(null, 'Usuario actualizado');
    }

    /** DELETE /usuarios?id=1 — desactivación lógica. */
    private function eliminar(?int $id): void {
        if (!$id) $this->error('ID requerido');

        $usuario = $this->modelo->findById($id);
        if (!$usuario) $this->error('Usuario no encontrado', 404);

        $this->modelo->setActivo($id, false);
        $this->ok(null, 'Usuario desactivado');
    }
}

(new UsuarioController())->handle();

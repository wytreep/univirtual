<?php
require_once __DIR__.'/Controller.php';

/**
 * DirectivoController
 * API REST para el panel del directivo.
 *
 * GET /api/v1/directivo.php?action=dashboard
 * GET /api/v1/directivo.php?action=profesores
 * GET /api/v1/directivo.php?action=estudiantes
 * GET /api/v1/directivo.php?action=materias
 * POST /api/v1/directivo.php?action=actualizar_perfil
 * POST /api/v1/directivo.php?action=cambiar_password
 */
class DirectivoController extends Controller {

    private int $idDirectivo;
    private int $idUsuario;
    private int $idFacultad;

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $session = $this->requireAuth('directivo');
        $this->idDirectivo = (int)($session['idEspecifico'] ?? 0);
        $this->idUsuario   = (int)($session['idUsuario'] ?? 0);
        $this->idFacultad  = (int)($session['idFacultad'] ?? 0);

        $body   = [];
        if ($this->method() === 'POST') {
            $raw  = file_get_contents('php://input');
            $body = json_decode($raw, true) ?? [];
        }
        $action = $_GET['action'] ?? $_POST['action'] ?? $body['action'] ?? '';

        if ($this->method() === 'GET') {
            match($action) {
                'dashboard'   => $this->dashboard(),
                'profesores'  => $this->profesores(),
                'estudiantes' => $this->estudiantes(),
                'materias'    => $this->materias(),
                default       => $this->error('Acción no válida')
            };
        } elseif ($this->method() === 'POST') {
            match($action) {
                'actualizar_perfil' => $this->actualizarPerfil(),
                'cambiar_password'  => $this->cambiarPassword(),
                default             => $this->error('Acción no válida')
            };
        } else {
            $this->error('Método no permitido', 405);
        }
    }

    // ── GET dashboard ──────────────────────────────────────────────────────────
    private function dashboard(): void {
        $db = db();

        // Totales de la facultad
        $stmt = $db->prepare(
            "SELECT
                (SELECT COUNT(*) FROM profesores WHERE idFacultad = ?) AS totalProfesores,
                (SELECT COUNT(*) FROM estudiantes e
                 JOIN carreras c ON e.idCarrera = c.idCarrera
                 WHERE c.idFacultad = ?) AS totalEstudiantes,
                (SELECT COUNT(*) FROM materias m
                 JOIN carreras c ON m.idCarrera = c.idCarrera
                 WHERE c.idFacultad = ?) AS totalMaterias"
        );
        $stmt->execute([$this->idFacultad, $this->idFacultad, $this->idFacultad]);
        $stats = $stmt->fetch();

        // Tasa de aprobación promedio
        $stmt = $db->prepare(
            "SELECT AVG(nota_final) as promedio FROM inscripciones i
             JOIN estudiantes e ON i.idEstudiante = e.idEstudiante
             JOIN carreras c ON e.idCarrera = c.idCarrera
             WHERE c.idFacultad = ? AND nota_final IS NOT NULL"
        );
        $stmt->execute([$this->idFacultad]);
        $prom = $stmt->fetch();
        $stats['tasaAprobacion'] = $prom['promedio'] ? round($prom['promedio'] / 5 * 100, 1) : 0;

        $this->ok($stats);
    }

    // ── GET profesores ─────────────────────────────────────────────────────────
    private function profesores(): void {
        $stmt = db()->prepare(
            "SELECT p.idProfesor, u.nombre, u.email, p.codigoProf, p.departamento,
                    (SELECT COUNT(*) FROM materias WHERE idProfesor = p.idProfesor) AS total_materias
             FROM profesores p
             JOIN usuarios u ON p.idUsuario = u.idUsuario
             JOIN carreras c ON p.idFacultad = c.idFacultad
             WHERE c.idFacultad = ?
             ORDER BY u.nombre"
        );
        $stmt->execute([$this->idFacultad]);
        $this->ok($stmt->fetchAll());
    }

    // ── GET estudiantes ────────────────────────────────────────────────────────
    private function estudiantes(): void {
        $stmt = db()->prepare(
            "SELECT e.idEstudiante, u.nombre, u.email, e.codigoEst, e.semestre,
                    c.nombre AS carrera, e.creditos_aprobados
             FROM estudiantes e
             JOIN usuarios u ON e.idUsuario = u.idUsuario
             JOIN carreras c ON e.idCarrera = c.idCarrera
             WHERE c.idFacultad = ?
             ORDER BY u.nombre"
        );
        $stmt->execute([$this->idFacultad]);
        $this->ok($stmt->fetchAll());
    }

    // ── GET materias ───────────────────────────────────────────────────────────
    private function materias(): void {
        $stmt = db()->prepare(
            "SELECT m.idMateria, m.nombre, m.codigo, m.creditos, m.semestre,
                    u.nombre AS profesor,
                    (SELECT COUNT(*) FROM inscripciones WHERE idMateria = m.idMateria) AS totalEst
             FROM materias m
             JOIN profesores p ON m.idProfesor = p.idProfesor
             JOIN usuarios u ON p.idUsuario = u.idUsuario
             JOIN carreras c ON m.idCarrera = c.idCarrera
             WHERE c.idFacultad = ?
             ORDER BY m.semestre, m.nombre"
        );
        $stmt->execute([$this->idFacultad]);
        $this->ok($stmt->fetchAll());
    }

    // ── POST actualizar_perfil ─────────────────────────────────────────────────
    private function actualizarPerfil(): void {
        $body   = $this->getBody();
        $nombre = trim($body['nombre'] ?? '');
        if (!$nombre) { $this->error('Nombre requerido', 422); return; }
        db()->prepare('UPDATE usuarios SET nombre=? WHERE idUsuario=?')
            ->execute([$nombre, $this->idUsuario]);
        $this->ok(['nombre' => $nombre], 'Perfil actualizado');
    }

    // ── POST cambiar_password ──────────────────────────────────────────────────
    private function cambiarPassword(): void {
        $body   = $this->getBody();
        $actual = $body['actual'] ?? '';
        $nueva  = $body['nueva']  ?? '';
        if (strlen($nueva) < 6) {
            $this->error('Mínimo 6 caracteres', 422); return;
        }
        $row = db()->prepare('SELECT password FROM usuarios WHERE idUsuario=?');
        $row->execute([$this->idUsuario]);
        $hash = $row->fetchColumn();
        if (!password_verify($actual, $hash)) {
            $this->error('Contraseña actual incorrecta', 401); return;
        }
        db()->prepare('UPDATE usuarios SET password=? WHERE idUsuario=?')
            ->execute([password_hash($nueva, PASSWORD_BCRYPT), $this->idUsuario]);
        $this->ok(null, 'Contraseña actualizada');
    }
}

(new DirectivoController())->handle();

<?php
require_once __DIR__.'/Controller.php';
require_once dirname(__DIR__, 2).'/models/Usuario.php';
require_once dirname(__DIR__, 2).'/models/Materia.php';

/**
 * DirectivoController — api/v1/directivo.php
 *
 * SCRUM-128: Controller propio para el rol directivo.
 * Antes el directivo usaba endpoints del admin (usuarios.php, materias.php)
 * violando S e I de SOLID. Ahora tiene su propio controller con acceso
 * restringido exclusivamente al rol directivo.
 *
 * SOLID:
 * S — responsabilidad única: gestión de consultas del directivo
 * O — extiende Controller sin modificarlo
 * L — sustituible por Controller en cualquier contexto
 * I — expone solo lo que el directivo necesita (no CRUD de usuarios)
 * D — depende de modelos Usuario y Materia, no de SQL directo
 *
 * Acciones GET:
 *   ?action=profesores    → lista de profesores con codigoProf y departamento
 *   ?action=estudiantes   → lista de estudiantes con codigoEst y semestre
 *   ?action=materias      → materias con nombre del profesor y totalInscritos
 *   ?action=resumen       → conteos globales para el dashboard
 *
 * Acciones POST:
 *   { action: 'actualizar_perfil', nombre }        → cambia nombre
 *   { action: 'cambiar_password', actual, nueva }  → cambia contraseña
 */
class DirectivoController extends Controller {

    private Usuario $usuarioModel;
    private Materia $materiaModel;

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
        $this->materiaModel = new Materia();
    }

    public function handle(): void {
        $session = $this->requireAuth(['directivo']);

        if ($this->method() === 'GET') {
            $action = $_GET['action'] ?? 'resumen';

            match($action) {
                'profesores'  => $this->getProfesores(),
                'estudiantes' => $this->getEstudiantes(),
                'materias'    => $this->getMaterias(),
                'resumen'     => $this->getResumen(),
                default       => $this->error('Acción no válida', 400),
            };
            return;
        }

        if ($this->method() === 'POST') {
            $body   = $this->getBody();
            $action = $body['action'] ?? '';

            match($action) {
                'actualizar_perfil' => $this->actualizarPerfil($session, $body),
                'cambiar_password'  => $this->cambiarPassword($session, $body),
                default             => $this->error('Acción no válida', 400),
            };
            return;
        }

        $this->error('Método no permitido', 405);
    }

    // ── GET ──────────────────────────────────────────────────────────

    private function getProfesores(): void {
        $profesores = $this->usuarioModel->getByRol('profesor');
        $this->ok($profesores);
    }

    private function getEstudiantes(): void {
        $estudiantes = $this->usuarioModel->getByRol('estudiante');
        $this->ok($estudiantes);
    }

    private function getMaterias(): void {
        $materias = $this->materiaModel->getAll();
        $this->ok($materias);
    }

    private function getResumen(): void {
        $db = db();

        $totalProfesores  = (int)$db->query("SELECT COUNT(*) FROM profesores")->fetchColumn();
        $totalEstudiantes = (int)$db->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
        $totalMaterias    = (int)$db->query("SELECT COUNT(*) FROM materias")->fetchColumn();

        // Tasa de aprobación: inscripciones con nota_final >= 3.0
        $totalInsc = (int)$db->query("SELECT COUNT(*) FROM inscripciones WHERE nota_final IS NOT NULL")->fetchColumn();
        $aprobados = (int)$db->query("SELECT COUNT(*) FROM inscripciones WHERE nota_final >= 3.0")->fetchColumn();
        $tasa      = $totalInsc > 0 ? round(($aprobados / $totalInsc) * 100, 1) : 0;

        $this->ok([
            'totalProfesores'  => $totalProfesores,
            'totalEstudiantes' => $totalEstudiantes,
            'totalMaterias'    => $totalMaterias,
            'tasaAprobacion'   => $tasa,
        ]);
    }

    // ── POST ─────────────────────────────────────────────────────────

    private function actualizarPerfil(array $session, array $body): void {
        $nombre = trim($body['nombre'] ?? '');
        if (!$nombre) {
            $this->error('El nombre no puede estar vacío');
            return;
        }
        $this->usuarioModel->actualizarNombre($session['idUsuario'], $nombre);
        $this->ok([], 'Perfil actualizado correctamente');
    }

    private function cambiarPassword(array $session, array $body): void {
        $actual = $body['actual'] ?? '';
        $nueva  = $body['nueva']  ?? '';

        if (!$actual || !$nueva) {
            $this->error('Completa todos los campos');
            return;
        }
        if (strlen($nueva) < 6) {
            $this->error('La nueva contraseña debe tener al menos 6 caracteres');
            return;
        }

        $ok = $this->usuarioModel->cambiarPassword($session['idUsuario'], $actual, $nueva);

        if (!$ok) {
            $this->error('La contraseña actual no es correcta');
            return;
        }

        $this->ok([], 'Contraseña actualizada correctamente');
    }
}

(new DirectivoController())->handle();

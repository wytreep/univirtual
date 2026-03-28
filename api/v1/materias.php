<?php
require_once __DIR__.'/Controller.php';

/**
 * MateriaController
 * API REST para gestión de materias.
 *
 * GET    /api/v1/materias.php          → listar todas
 * POST   /api/v1/materias.php          → crear materia
 * PUT    /api/v1/materias.php?id=1     → actualizar
 * DELETE /api/v1/materias.php?id=1     → eliminar
 */
class MateriaController extends Controller {

    private Materia $modelo;

    public function __construct() {
        parent::__construct();
        $this->modelo = new Materia();
    }

    public function handle(): void {
$session = $this->requireAuth(['admin', 'directivo']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $action = $_GET['action'] ?? '';

        if ($this->method() === 'GET' && $action === 'profesores') {
            $this->ok(db()->query("SELECT p.idProfesor, u.nombre, p.codigoProf, p.departamento FROM profesores p JOIN usuarios u ON p.idUsuario = u.idUsuario")->fetchAll());
            return;
        }
        if ($this->method() === 'GET' && $action === 'listar_facultad') {
            $idFac = (int)($_GET['idFacultad'] ?? 0);
            try {
                $stmt = db()->query(
                    "SELECT m.*, u.nombre AS prof_nombre,
                            (SELECT COUNT(*) FROM inscripciones WHERE idMateria = m.idMateria) AS total_inscritos
                     FROM materias m
                     LEFT JOIN profesores p ON m.idProfesor = p.idProfesor
                     LEFT JOIN usuarios u ON p.idUsuario = u.idUsuario
                     LEFT JOIN carreras c ON m.idCarrera = c.idCarrera
                     WHERE c.idFacultad=$idFac OR p.idFacultad=$idFac
                     ORDER BY m.nombre"
                );
                $this->ok($stmt->fetchAll());
            } catch(Exception $e) { $this->ok([]); }
            return;
        }

        match($this->method()) {
            'GET'    => $this->getAll(),
            'POST'   => $this->crear(),
            'PUT'    => $this->actualizar($id),
            'DELETE' => $this->eliminar($id),
            default  => $this->error('Método no permitido', 405)
        };
    }

    private function getAll(): void {
        $stmt = db()->query(
            "SELECT materias.*, u.nombre AS profesor,
                    (SELECT COUNT(*) FROM inscripciones WHERE inscripciones.idMateria = materias.idMateria) AS inscritos,
                    (SELECT COUNT(*) FROM materiales
                     JOIN aulas_virtuales av ON materiales.idAula = av.idAula
                     WHERE av.idMateria = materias.idMateria) AS total_materiales
             FROM materias
             JOIN profesores p ON materias.idProfesor = p.idProfesor
             JOIN usuarios u ON p.idUsuario = u.idUsuario
             ORDER BY materias.nombre"
        );
        $this->ok($stmt->fetchAll());
    }

    private function crear(): void {
        $body = $this->getBody();
        if (empty($body['nombre']))     $this->error('Nombre requerido');
        if (empty($body['codigo']))     $this->error('Código requerido');
        if (empty($body['idProfesor'])) $this->error('Profesor requerido');

        $idMateria = $this->modelo->crear(
            (int)$body['idProfesor'],
            $body['nombre'],
            $body['codigo'],
            (int)($body['creditos'] ?? 3),
            $body['descripcion'] ?? '',
            $body['color'] ?? '#2560a8'
        );
        $this->ok(['idMateria' => $idMateria], 'Materia creada');
    }

    private function actualizar(?int $id): void {
        if (!$id) $this->error('ID requerido');
        $body = $this->getBody();
        $db   = db();
        $db->prepare(
            "UPDATE materias SET nombre=COALESCE(?,nombre), codigo=COALESCE(?,codigo),
             creditos=COALESCE(?,creditos), descripcion=COALESCE(?,descripcion),
             color=COALESCE(?,color), activa=COALESCE(?,activa)
             WHERE idMateria=?"
        )->execute([
            $body['nombre']      ?? null,
            $body['codigo']      ?? null,
            $body['creditos']    ?? null,
            $body['descripcion'] ?? null,
            $body['color']       ?? null,
            isset($body['activa']) ? ($body['activa'] ? 1 : 0) : null,
            $id
        ]);
        $this->ok(null, 'Materia actualizada');
    }

    private function eliminar(?int $id): void {
        if (!$id) $this->error('ID requerido');
        db()->prepare("DELETE FROM materias WHERE idMateria=?")->execute([$id]);
        $this->ok(null, 'Materia eliminada');
    }
}

(new MateriaController())->handle();

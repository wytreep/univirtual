<?php
require_once __DIR__.'/Controller.php';

/**
 * InscripcionesController — v8
 *
 * GET  ?idMateria=1                        → estudiantes inscritos
 * GET  ?disponibles=1&idMateria=1          → estudiantes NO inscritos con validación de créditos
 * POST {action:'inscribir', idEstudiante, idMateria}   → inscribir (valida créditos mínimos)
 * DELETE ?idEstudiante=1&idMateria=1       → desinscribir
 */
class InscripcionesController extends Controller {

    public function __construct() { parent::__construct(); }

    public function handle(): void {
        $this->requireAuth('admin');
        match($this->method()) {
            'GET'    => $this->getInscripciones(),
            'POST'   => $this->inscribir(),
            'DELETE' => $this->desinscribir(),
            default  => $this->error('Método no permitido', 405)
        };
    }

    private function getInscripciones(): void {
        $idMateria   = (int)($_GET['idMateria'] ?? 0);
        $disponibles = isset($_GET['disponibles']);

        if (!$idMateria) $this->error('idMateria requerido');

        $db = db();

        // Créditos mínimos de la materia
        $mStmt = $db->prepare("SELECT creditos_minimos FROM materias WHERE idMateria=?");
        $mStmt->execute([$idMateria]);
        $materia = $mStmt->fetch();
        $creditosMin = (int)($materia['creditos_minimos'] ?? 0);

        if ($disponibles) {
            // Estudiantes NO inscritos en esta materia
            // FIX: excluir explícitamente los ya inscritos con NOT IN
            $stmt = $db->prepare(
                "SELECT est.idEstudiante, u.nombre, u.email,
                        est.codigoEst, est.semestre, est.creditos_aprobados,
                        c.nombre AS carrera,
                        CASE WHEN est.creditos_aprobados >= :cmin THEN 1 ELSE 0 END AS puede_inscribirse
                 FROM estudiantes est
                 JOIN usuarios u ON est.idUsuario = u.idUsuario
                 LEFT JOIN carreras c ON est.idCarrera = c.idCarrera
                 WHERE u.activo = 1
                   AND est.idEstudiante NOT IN (
                       SELECT idEstudiante FROM inscripciones WHERE idMateria = :idMat
                   )
                 ORDER BY u.nombre"
            );
            $stmt->execute([':cmin' => $creditosMin, ':idMat' => $idMateria]);
            $this->ok($stmt->fetchAll());
        } else {
            // Estudiantes YA inscritos
            $stmt = $db->prepare(
                "SELECT i.*, u.nombre, u.email, est.codigoEst, est.semestre,
                        est.creditos_aprobados, c.nombre AS carrera
                 FROM inscripciones i
                 JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
                 JOIN usuarios u ON est.idUsuario = u.idUsuario
                 LEFT JOIN carreras c ON est.idCarrera = c.idCarrera
                 WHERE i.idMateria = ?
                 ORDER BY u.nombre"
            );
            $stmt->execute([$idMateria]);
            $this->ok($stmt->fetchAll());
        }
    }

    private function inscribir(): void {
        $body        = $this->getBody();
        $idEstudiante= (int)($body['idEstudiante'] ?? 0);
        $idMateria   = (int)($body['idMateria'] ?? 0);
        if (!$idEstudiante || !$idMateria) $this->error('idEstudiante e idMateria requeridos');

        $db = db();

        // Verificar si ya está inscrito (evitar duplicado)
        $chk = $db->prepare("SELECT COUNT(*) FROM inscripciones WHERE idEstudiante=? AND idMateria=?");
        $chk->execute([$idEstudiante, $idMateria]);
        if ((int)$chk->fetchColumn() > 0) {
            $this->error('El estudiante ya está inscrito en esta materia');
        }

        // Verificar créditos mínimos
        $m = $db->prepare("SELECT creditos_minimos, nombre FROM materias WHERE idMateria=?");
        $m->execute([$idMateria]);
        $mat = $m->fetch();
        if (!$mat) $this->error('Materia no encontrada');

        $e = $db->prepare("SELECT creditos_aprobados FROM estudiantes WHERE idEstudiante=?");
        $e->execute([$idEstudiante]);
        $est = $e->fetch();
        if (!$est) $this->error('Estudiante no encontrado');

        if ((int)$mat['creditos_minimos'] > 0 &&
            (int)$est['creditos_aprobados'] < (int)$mat['creditos_minimos']) {
            $this->error(
                "El estudiante no cumple los créditos mínimos requeridos: " .
                "necesita {$mat['creditos_minimos']} créditos aprobados, " .
                "tiene {$est['creditos_aprobados']}."
            );
        }

        // Inscribir
        $db->prepare("INSERT INTO inscripciones (idEstudiante, idMateria) VALUES (?,?)")
           ->execute([$idEstudiante, $idMateria]);

        // Notificar al estudiante
        $uStmt = $db->prepare(
            "SELECT u.idUsuario FROM estudiantes est
             JOIN usuarios u ON est.idUsuario=u.idUsuario
             WHERE est.idEstudiante=?"
        );
        $uStmt->execute([$idEstudiante]);
        $idUsuario = (int)($uStmt->fetchColumn());
        if ($idUsuario) {
            notificar($idUsuario,
                'Inscripción confirmada',
                "Has sido inscrito en la materia \"{$mat['nombre']}\".",
                'info',
                BASE_URL.'/pages/estudiante/dashboard.php'
            );
        }

        $this->ok(['idInscripcion' => (int)$db->lastInsertId()], 'Inscripción realizada');
    }

    private function desinscribir(): void {
        $idEstudiante = (int)($_GET['idEstudiante'] ?? 0);
        $idMateria    = (int)($_GET['idMateria'] ?? 0);
        if (!$idEstudiante || !$idMateria) $this->error('idEstudiante e idMateria requeridos');
        $db = db();
        $db->prepare("DELETE FROM inscripciones WHERE idEstudiante=? AND idMateria=?")
           ->execute([$idEstudiante, $idMateria]);
        $this->ok(null, 'Desinscripción realizada');
    }
}

(new InscripcionesController())->handle();
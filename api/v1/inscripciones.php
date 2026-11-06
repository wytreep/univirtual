<?php
require_once __DIR__.'/Controller.php';

/**
 * InscripcionesController — v9
 *
 * Reglas de matrícula:
 *   1. No se puede inscribir dos veces la misma materia.
 *   2. Se exigen los créditos aprobados mínimos configurados en la materia.
 *   3. Se limita la carga del semestre a MAX_CREDITOS_SEMESTRE créditos.
 *
 * GET  ?idMateria=1                        → estudiantes inscritos
 * GET  ?disponibles=1&idMateria=1          → estudiantes NO inscritos con validación
 * POST {action:'inscribir', idEstudiante, idMateria}
 * DELETE ?idEstudiante=1&idMateria=1
 */
class InscripcionesController extends Controller {

    /** Carga máxima permitida por estudiante y semestre. */
    private const MAX_CREDITOS_SEMESTRE = 20;

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

    /**
     * Créditos que el estudiante ya tiene inscritos en el semestre.
     */
    private function creditosInscritos(PDO $db, int $idEstudiante): int {
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(m.creditos), 0)
               FROM inscripciones i
               JOIN materias m ON i.idMateria = m.idMateria
              WHERE i.idEstudiante = ?"
        );
        $stmt->execute([$idEstudiante]);
        return (int)$stmt->fetchColumn();
    }

    private function getInscripciones(): void {
        $idMateria   = (int)($_GET['idMateria'] ?? 0);
        $disponibles = isset($_GET['disponibles']) || ($_GET['tipo'] ?? '') === 'disponibles';

        if (!$idMateria) $this->error('idMateria requerido');

        $db = db();

        // Datos de la materia: créditos, requisito y estado.
        $mStmt = $db->prepare(
            "SELECT nombre, creditos, creditos_minimos, activa
               FROM materias WHERE idMateria = ?"
        );
        $mStmt->execute([$idMateria]);
        $materia = $mStmt->fetch();
        if (!$materia) $this->error('Materia no encontrada', 404);

        $creditosMin     = (int)($materia['creditos_minimos'] ?? 0);
        $creditosMateria = (int)($materia['creditos'] ?? 0);

        if ($disponibles) {
            // Estudiantes NO inscritos en esta materia.
            $stmt = $db->prepare(
                "SELECT est.idEstudiante, u.nombre, u.email,
                        est.codigoEst, est.semestre, est.creditos_aprobados,
                        c.nombre AS carrera,
                        (SELECT COALESCE(SUM(m2.creditos), 0)
                           FROM inscripciones i2
                           JOIN materias m2 ON i2.idMateria = m2.idMateria
                          WHERE i2.idEstudiante = est.idEstudiante) AS creditos_inscritos
                   FROM estudiantes est
                   JOIN usuarios u ON est.idUsuario = u.idUsuario
                   LEFT JOIN carreras c ON est.idCarrera = c.idCarrera
                  WHERE u.activo = 1
                    AND est.idEstudiante NOT IN (
                        SELECT idEstudiante FROM inscripciones WHERE idMateria = ?
                    )
                  ORDER BY u.nombre"
            );
            $stmt->execute([$idMateria]);

            $filas = $stmt->fetchAll();
            foreach ($filas as &$fila) {
                $inscritos = (int)$fila['creditos_inscritos'];
                $motivos   = [];

                if (!(int)$materia['activa']) {
                    $motivos[] = 'La materia está inactiva';
                }
                if ($creditosMin > 0 && (int)$fila['creditos_aprobados'] < $creditosMin) {
                    $motivos[] = "Requiere {$creditosMin} créditos aprobados (tiene {$fila['creditos_aprobados']})";
                }
                if ($inscritos + $creditosMateria > self::MAX_CREDITOS_SEMESTRE) {
                    $motivos[] = "Supera el límite de " . self::MAX_CREDITOS_SEMESTRE .
                                 " créditos del semestre ({$inscritos} inscritos + {$creditosMateria} de esta materia)";
                }

                $fila['creditos_materia']     = $creditosMateria;
                $fila['creditos_disponibles'] = max(0, self::MAX_CREDITOS_SEMESTRE - $inscritos);
                $fila['puede_inscribirse']    = $motivos ? 0 : 1;
                $fila['motivo']               = implode('. ', $motivos);
            }
            unset($fila);

            $this->ok($filas);
        } else {
            // Estudiantes YA inscritos.
            $stmt = $db->prepare(
                "SELECT i.*, u.nombre, u.email, est.codigoEst, est.semestre,
                        est.creditos_aprobados, c.nombre AS carrera,
                        (SELECT COALESCE(SUM(m2.creditos), 0)
                           FROM inscripciones i2
                           JOIN materias m2 ON i2.idMateria = m2.idMateria
                          WHERE i2.idEstudiante = est.idEstudiante) AS creditos_inscritos
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
        $body         = $this->getBody();
        $idEstudiante = (int)($body['idEstudiante'] ?? 0);
        $idMateria    = (int)($body['idMateria'] ?? 0);
        if (!$idEstudiante || !$idMateria) $this->error('idEstudiante e idMateria requeridos');

        $db = db();

        // Estudiante activo.
        $e = $db->prepare(
            "SELECT est.idEstudiante, est.creditos_aprobados, u.activo
               FROM estudiantes est
               JOIN usuarios u ON est.idUsuario = u.idUsuario
              WHERE est.idEstudiante = ?"
        );
        $e->execute([$idEstudiante]);
        $est = $e->fetch();
        if (!$est) $this->error('Estudiante no encontrado', 404);
        if (!(int)$est['activo']) $this->error('El estudiante está inactivo y no puede matricularse');

        // Materia existente y activa.
        $m = $db->prepare(
            "SELECT nombre, codigo, creditos, creditos_minimos, activa
               FROM materias WHERE idMateria = ?"
        );
        $m->execute([$idMateria]);
        $mat = $m->fetch();
        if (!$mat) $this->error('Materia no encontrada', 404);
        if (!(int)$mat['activa']) $this->error('La materia está inactiva');

        // Evitar duplicado.
        $chk = $db->prepare("SELECT COUNT(*) FROM inscripciones WHERE idEstudiante=? AND idMateria=?");
        $chk->execute([$idEstudiante, $idMateria]);
        if ((int)$chk->fetchColumn() > 0) {
            $this->error('El estudiante ya está inscrito en esta materia');
        }

        // Requisito de créditos aprobados.
        if ((int)$mat['creditos_minimos'] > 0 &&
            (int)$est['creditos_aprobados'] < (int)$mat['creditos_minimos']) {
            $this->error(
                "El estudiante no cumple los créditos mínimos requeridos: " .
                "necesita {$mat['creditos_minimos']} créditos aprobados, " .
                "tiene {$est['creditos_aprobados']}."
            );
        }

        // Límite de carga del semestre.
        $inscritos = $this->creditosInscritos($db, $idEstudiante);
        $nuevoTotal = $inscritos + (int)$mat['creditos'];
        if ($nuevoTotal > self::MAX_CREDITOS_SEMESTRE) {
            $this->error(
                "No se puede inscribir: el estudiante quedaría con {$nuevoTotal} créditos " .
                "y el límite por semestre es " . self::MAX_CREDITOS_SEMESTRE .
                " (ya tiene {$inscritos} inscritos y esta materia suma {$mat['creditos']})."
            );
        }

        // Inscribir.
        $db->prepare("INSERT INTO inscripciones (idEstudiante, idMateria) VALUES (?,?)")
           ->execute([$idEstudiante, $idMateria]);

        // Notificar al estudiante.
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

        $this->ok([
            'idInscripcion'     => (int)$db->lastInsertId(),
            'creditos_semestre' => $nuevoTotal,
        ], 'Inscripción realizada');
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

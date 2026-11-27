<?php
require_once __DIR__.'/Controller.php';
require_once dirname(__DIR__, 2).'/models/Usuario.php';
require_once dirname(__DIR__, 2).'/models/Materia.php';

/**
 * DirectivoController — api/v1/directivo.php
 *
 * SCRUM-128: Controller propio para el rol directivo.
 * UV-31: segmentación por facultad y reportes académicos.
 *
 * La facultad se resuelve desde `directivos.idFacultad` usando el idUsuario
 * de la sesión. Es la única fuente de verdad: estudiantes se filtran por
 * `carreras.idFacultad`, y profesores/materias por `profesores.idFacultad`
 * (una materia también pertenece a la facultad si su carrera es de ella).
 *
 * SOLID:
 * S — responsabilidad única: consultas y reportes del directivo
 * O — extiende Controller sin modificarlo
 * L — sustituible por Controller en cualquier contexto
 * I — expone solo lo que el directivo necesita (no CRUD de usuarios)
 * D — reutiliza modelos y encapsula el SQL de reportes en métodos privados
 *
 * Acciones GET:
 *   ?action=profesores    → profesores de la facultad
 *   ?action=estudiantes   → estudiantes de las carreras de la facultad
 *   ?action=materias      → materias de la facultad con inscritos
 *   ?action=resumen       → KPIs de la facultad + periodo vigente
 *   ?action=reportes      → reportes por carrera, materia, profesor,
 *                           distribución de notas y asistencia
 *   ?action=facultad      → facultad y periodo del directivo
 *
 * Acciones POST:
 *   { action: 'actualizar_perfil', nombre }        → cambia nombre
 *   { action: 'cambiar_password', actual, nueva }  → cambia contraseña
 */
class DirectivoController extends Controller {

    private Usuario $usuarioModel;
    private Materia $materiaModel;

    private int   $idFacultad = 0;
    private array $facultad   = [];
    private array $periodo    = [];

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
        $this->materiaModel = new Materia();
        $this->periodo      = periodoAcademico();
    }

    public function handle(): void {
        $session = $this->requireAuth(['directivo']);
        $this->cargarFacultad((int)$session['idUsuario']);

        if ($this->method() === 'GET') {
            $action = $_GET['action'] ?? 'resumen';

            match($action) {
                'profesores'  => $this->getProfesores(),
                'estudiantes' => $this->getEstudiantes(),
                'materias'    => $this->getMaterias(),
                'resumen'     => $this->getResumen(),
                'reportes'    => $this->getReportes(),
                'facultad'    => $this->getFacultad(),
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

    // ── Segmentación por facultad ────────────────────────────────────

    /**
     * Resuelve la facultad del directivo autenticado.
     * Si no tiene facultad asignada, deja idFacultad = 0 y las consultas
     * devuelven conjuntos vacíos (nunca datos de otras facultades).
     */
    private function cargarFacultad(int $idUsuario): void {
        $stmt = db()->prepare(
            "SELECT d.idFacultad, d.cargo, f.nombre, f.codigo, f.descripcion
             FROM directivos d
             JOIN facultades f ON f.idFacultad = d.idFacultad
             WHERE d.idUsuario = ?"
        );
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch();

        if ($row) {
            $this->idFacultad = (int)$row['idFacultad'];
            $this->facultad   = [
                'idFacultad'  => $this->idFacultad,
                'nombre'      => $row['nombre'],
                'codigo'      => $row['codigo'],
                'descripcion' => $row['descripcion'],
                'cargo'       => $row['cargo'],
            ];
        } else {
            $this->idFacultad = 0;
            $this->facultad   = [
                'idFacultad'  => 0,
                'nombre'      => 'Sin facultad asignada',
                'codigo'      => '—',
                'descripcion' => '',
                'cargo'       => '',
            ];
        }
    }

    /**
     * Condición SQL para materias de la facultad: por carrera o por profesor.
     * Consume dos parámetros posicionales ($idFacultad, $idFacultad).
     */
    private function condicionMaterias(): string {
        return "(m.idCarrera IN (SELECT idCarrera FROM carreras WHERE idFacultad = ?)
                 OR p.idFacultad = ?)";
    }

    private function paramsMaterias(): array {
        return [$this->idFacultad, $this->idFacultad];
    }

    private function facultadPayload(): array {
        return $this->facultad;
    }

    private function periodoPayload(): array {
        return $this->periodo;
    }

    // ── GET ──────────────────────────────────────────────────────────

    private function getFacultad(): void {
        $this->ok([
            'facultad' => $this->facultadPayload(),
            'periodo'  => $this->periodoPayload(),
        ]);
    }

    private function getProfesores(): void {
        $profesores = $this->usuarioModel->getByRol('profesor', $this->idFacultad);
        $this->ok($profesores);
    }

    private function getEstudiantes(): void {
        $estudiantes = $this->usuarioModel->getByRol('estudiante', $this->idFacultad);
        $this->ok($estudiantes);
    }

    private function getMaterias(): void {
        $materias = $this->materiaModel->getAll($this->idFacultad);
        $this->ok($materias);
    }

    private function getResumen(): void {
        $resumen = $this->calcularResumen();

        $this->ok(array_merge(
            [
                'facultad' => $this->facultadPayload(),
                'periodo'  => $this->periodoPayload(),
            ],
            $resumen
        ));
    }

    /**
     * Reportes académicos de la facultad.
     */
    private function getReportes(): void {
        $resumen = $this->calcularResumen();

        $this->ok([
            'facultad'          => $this->facultadPayload(),
            'periodo'           => $this->periodoPayload(),
            'resumen'           => $resumen,
            'carreras'          => $this->reporteCarreras(),
            'materias'          => $this->reporteMaterias(),
            'profesores'        => $this->reporteProfesores(),
            'distribucionNotas' => $this->reporteDistribucionNotas(),
            'asistencia'        => $this->reporteAsistencia(),
        ]);
    }

    // ── Cálculos de reportes ─────────────────────────────────────────

    private function calcularResumen(): array {
        $f = $this->idFacultad;

        $totalProfesores  = (int)$this->scalar(
            "SELECT COUNT(*) FROM profesores WHERE idFacultad = ?", [$f]
        );
        $totalEstudiantes = (int)$this->scalar(
            "SELECT COUNT(*) FROM estudiantes e
             JOIN carreras c ON c.idCarrera = e.idCarrera
             WHERE c.idFacultad = ?", [$f]
        );
        $totalMaterias = (int)$this->scalar(
            "SELECT COUNT(*) FROM materias m
             LEFT JOIN profesores p ON p.idProfesor = m.idProfesor
             WHERE " . $this->condicionMaterias(),
            $this->paramsMaterias()
        );
        $totalCarreras = (int)$this->scalar(
            "SELECT COUNT(*) FROM carreras WHERE idFacultad = ? AND activa = 1", [$f]
        );

        $totalInscripciones = (int)$this->scalar(
            "SELECT COUNT(*) FROM inscripciones i
             JOIN materias m ON m.idMateria = i.idMateria
             LEFT JOIN profesores p ON p.idProfesor = m.idProfesor
             WHERE " . $this->condicionMaterias(),
            $this->paramsMaterias()
        );
        $calificadas = (int)$this->scalar(
            "SELECT COUNT(*) FROM inscripciones i
             JOIN materias m ON m.idMateria = i.idMateria
             LEFT JOIN profesores p ON p.idProfesor = m.idProfesor
             WHERE " . $this->condicionMaterias() . " AND i.nota_final IS NOT NULL",
            $this->paramsMaterias()
        );
        $aprobados = (int)$this->scalar(
            "SELECT COUNT(*) FROM inscripciones i
             JOIN materias m ON m.idMateria = i.idMateria
             LEFT JOIN profesores p ON p.idProfesor = m.idProfesor
             WHERE " . $this->condicionMaterias() . " AND i.nota_final >= 3.0",
            $this->paramsMaterias()
        );
        $promedio = $this->scalar(
            "SELECT ROUND(AVG(i.nota_final), 2) FROM inscripciones i
             JOIN materias m ON m.idMateria = i.idMateria
             LEFT JOIN profesores p ON p.idProfesor = m.idProfesor
             WHERE " . $this->condicionMaterias() . " AND i.nota_final IS NOT NULL",
            $this->paramsMaterias()
        );

        $tasa = $calificadas > 0 ? round(($aprobados / $calificadas) * 100, 1) : 0.0;

        return [
            'totalProfesores'    => $totalProfesores,
            'totalEstudiantes'   => $totalEstudiantes,
            'totalMaterias'      => $totalMaterias,
            'totalCarreras'      => $totalCarreras,
            'totalInscripciones' => $totalInscripciones,
            'calificadas'        => $calificadas,
            'aprobados'          => $aprobados,
            'tasaAprobacion'     => $tasa,
            'promedioGeneral'    => $promedio !== null && $promedio !== false ? (float)$promedio : null,
        ];
    }

    /**
     * Reporte por carrera: estudiantes, materias, inscripciones y aprobación.
     */
    private function reporteCarreras(): array {
        $f = $this->idFacultad;

        $sql = "SELECT c.idCarrera, c.nombre, c.codigo, c.semestres,
                       (SELECT COUNT(*) FROM estudiantes e
                         WHERE e.idCarrera = c.idCarrera) AS estudiantes,
                       (SELECT COUNT(*) FROM materias m
                         WHERE m.idCarrera = c.idCarrera) AS materias,
                       (SELECT COUNT(*) FROM inscripciones i
                          JOIN materias m2 ON m2.idMateria = i.idMateria
                         WHERE m2.idCarrera = c.idCarrera) AS inscripciones,
                       (SELECT COUNT(*) FROM inscripciones i
                          JOIN materias m2 ON m2.idMateria = i.idMateria
                         WHERE m2.idCarrera = c.idCarrera
                           AND i.nota_final IS NOT NULL) AS calificadas,
                       (SELECT COUNT(*) FROM inscripciones i
                          JOIN materias m2 ON m2.idMateria = i.idMateria
                         WHERE m2.idCarrera = c.idCarrera
                           AND i.nota_final >= 3.0) AS aprobados,
                       (SELECT ROUND(AVG(i.nota_final), 2) FROM inscripciones i
                          JOIN materias m2 ON m2.idMateria = i.idMateria
                         WHERE m2.idCarrera = c.idCarrera
                           AND i.nota_final IS NOT NULL) AS promedio
                FROM carreras c
                WHERE c.idFacultad = ?
                ORDER BY c.nombre";

        return array_map(function (array $r): array {
            $calificadas = (int)$r['calificadas'];
            $aprobados   = (int)$r['aprobados'];
            return [
                'idCarrera'     => (int)$r['idCarrera'],
                'nombre'        => $r['nombre'],
                'codigo'        => $r['codigo'],
                'semestres'     => (int)$r['semestres'],
                'estudiantes'   => (int)$r['estudiantes'],
                'materias'      => (int)$r['materias'],
                'inscripciones' => (int)$r['inscripciones'],
                'calificadas'   => $calificadas,
                'aprobados'     => $aprobados,
                'tasaAprobacion'=> $calificadas > 0 ? round(($aprobados / $calificadas) * 100, 1) : 0.0,
                'promedio'      => $r['promedio'] !== null ? (float)$r['promedio'] : null,
            ];
        }, $this->rows($sql, [$f]));
    }

    /**
     * Reporte por materia: inscritos, promedio, aprobados y asistencia.
     */
    private function reporteMaterias(): array {
        $sql = "SELECT m.idMateria, m.nombre, m.codigo, m.semestre, m.creditos,
                       u.nombre AS profesor, c.nombre AS carrera,
                       (SELECT COUNT(DISTINCT i.idEstudiante) FROM inscripciones i
                         WHERE i.idMateria = m.idMateria) AS inscritos,
                       (SELECT ROUND(AVG(i.nota_final), 2) FROM inscripciones i
                         WHERE i.idMateria = m.idMateria
                           AND i.nota_final IS NOT NULL) AS promedio,
                       (SELECT COUNT(*) FROM inscripciones i
                         WHERE i.idMateria = m.idMateria
                           AND i.nota_final >= 3.0) AS aprobados,
                       (SELECT COUNT(*) FROM inscripciones i
                         WHERE i.idMateria = m.idMateria
                           AND i.nota_final < 3.0) AS reprobados,
                       (SELECT ROUND(AVG(a.asistio) * 100, 1) FROM asistencia a
                         WHERE a.idMateria = m.idMateria) AS asistencia
                FROM materias m
                LEFT JOIN profesores p ON p.idProfesor = m.idProfesor
                LEFT JOIN usuarios u   ON u.idUsuario  = p.idUsuario
                LEFT JOIN carreras c   ON c.idCarrera  = m.idCarrera
                WHERE " . $this->condicionMaterias() . "
                ORDER BY m.nombre";

        return array_map(function (array $r): array {
            return [
                'idMateria'  => (int)$r['idMateria'],
                'nombre'     => $r['nombre'],
                'codigo'     => $r['codigo'],
                'semestre'   => (int)$r['semestre'],
                'creditos'   => (int)$r['creditos'],
                'profesor'   => $r['profesor'],
                'carrera'    => $r['carrera'],
                'inscritos'  => (int)$r['inscritos'],
                'promedio'   => $r['promedio'] !== null ? (float)$r['promedio'] : null,
                'aprobados'  => (int)$r['aprobados'],
                'reprobados' => (int)$r['reprobados'],
                'asistencia' => $r['asistencia'] !== null ? (float)$r['asistencia'] : null,
            ];
        }, $this->rows($sql, $this->paramsMaterias()));
    }

    /**
     * Reporte por profesor: materias, estudiantes, promedio y asistencia.
     */
    private function reporteProfesores(): array {
        $sql = "SELECT p.idProfesor, u.nombre, u.email, p.codigoProf, p.departamento,
                       (SELECT COUNT(*) FROM materias m
                         WHERE m.idProfesor = p.idProfesor) AS materias,
                       (SELECT COUNT(DISTINCT i.idEstudiante) FROM inscripciones i
                          JOIN materias m2 ON m2.idMateria = i.idMateria
                         WHERE m2.idProfesor = p.idProfesor) AS estudiantes,
                       (SELECT ROUND(AVG(i.nota_final), 2) FROM inscripciones i
                          JOIN materias m2 ON m2.idMateria = i.idMateria
                         WHERE m2.idProfesor = p.idProfesor
                           AND i.nota_final IS NOT NULL) AS promedio,
                       (SELECT ROUND(AVG(a.asistio) * 100, 1) FROM asistencia a
                          JOIN materias m2 ON m2.idMateria = a.idMateria
                         WHERE m2.idProfesor = p.idProfesor) AS asistencia
                FROM profesores p
                JOIN usuarios u ON u.idUsuario = p.idUsuario
                WHERE p.idFacultad = ?
                ORDER BY u.nombre";

        return array_map(function (array $r): array {
            return [
                'idProfesor'   => (int)$r['idProfesor'],
                'nombre'       => $r['nombre'],
                'email'        => $r['email'],
                'codigoProf'   => $r['codigoProf'],
                'departamento' => $r['departamento'],
                'materias'     => (int)$r['materias'],
                'estudiantes'  => (int)$r['estudiantes'],
                'promedio'     => $r['promedio'] !== null ? (float)$r['promedio'] : null,
                'asistencia'   => $r['asistencia'] !== null ? (float)$r['asistencia'] : null,
            ];
        }, $this->rows($sql, [$this->idFacultad]));
    }

    /**
     * Distribución de notas finales en cuatro rangos.
     */
    private function reporteDistribucionNotas(): array {
        $sql = "SELECT CASE
                           WHEN i.nota_final < 3.0 THEN '0.0 - 2.9'
                           WHEN i.nota_final < 4.0 THEN '3.0 - 3.9'
                           WHEN i.nota_final < 4.6 THEN '4.0 - 4.5'
                           ELSE '4.6 - 5.0'
                       END AS rango,
                       COUNT(*) AS total
                FROM inscripciones i
                JOIN materias m ON m.idMateria = i.idMateria
                LEFT JOIN profesores p ON p.idProfesor = m.idProfesor
                WHERE " . $this->condicionMaterias() . "
                  AND i.nota_final IS NOT NULL
                GROUP BY rango";

        $conteo = [];
        foreach ($this->rows($sql, $this->paramsMaterias()) as $row) {
            $conteo[$row['rango']] = (int)$row['total'];
        }

        $distribucion = [];
        foreach (['0.0 - 2.9', '3.0 - 3.9', '4.0 - 4.5', '4.6 - 5.0'] as $rango) {
            $distribucion[] = [
                'rango' => $rango,
                'total' => $conteo[$rango] ?? 0,
            ];
        }

        return $distribucion;
    }

    /**
     * Resumen global de asistencia de la facultad.
     */
    private function reporteAsistencia(): array {
        $sql = "SELECT COUNT(*) AS registros,
                       COALESCE(SUM(a.asistio), 0) AS asistencias,
                       ROUND(AVG(a.asistio) * 100, 1) AS porcentaje
                FROM asistencia a
                JOIN materias m ON m.idMateria = a.idMateria
                LEFT JOIN profesores p ON p.idProfesor = m.idProfesor
                WHERE " . $this->condicionMaterias();

        $row = $this->row($sql, $this->paramsMaterias());

        return [
            'registros'    => (int)($row['registros'] ?? 0),
            'asistencias'  => (int)($row['asistencias'] ?? 0),
            'ausencias'    => (int)($row['registros'] ?? 0) - (int)($row['asistencias'] ?? 0),
            'porcentaje'   => $row['porcentaje'] !== null ? (float)$row['porcentaje'] : 0.0,
        ];
    }

    // ── Utilidades SQL ───────────────────────────────────────────────

    private function rows(string $sql, array $params = []): array {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function row(string $sql, array $params = []): array {
        $rows = $this->rows($sql, $params);
        return $rows[0] ?? [];
    }

    private function scalar(string $sql, array $params = []): mixed {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
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

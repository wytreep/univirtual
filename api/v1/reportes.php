<?php
require_once __DIR__.'/Controller.php';

/**
 * ReportesController
 * API REST para reportes globales del admin.
 *
 * GET /api/v1/reportes.php?tipo=asistencia&idMateria=1&desde=2026-01-01&hasta=2026-12-31
 * GET /api/v1/reportes.php?tipo=notas&idMateria=1
 * GET /api/v1/reportes.php?tipo=materias   → resumen general de todas las materias
 */
class ReportesController extends Controller {

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->requireAuth('admin');
        if ($this->method() !== 'GET') $this->error('Método no permitido', 405);

        $tipo = $_GET['tipo'] ?? '';

        match($tipo) {
            'asistencia' => $this->reporteAsistencia(),
            'notas'      => $this->reporteNotas(),
            'materias'   => $this->reporteMaterias(),
            'actividad'  => $this->reporteActividad(),
            default      => $this->error('Tipo de reporte inválido')
        };
    }

    /**
     * Reporte de asistencia por materia y rango de fechas.
     */
    private function reporteAsistencia(): void {
        $idMateria = (int)($_GET['idMateria'] ?? 0);
        $desde     = $_GET['desde'] ?? date('Y-m-01');
        $hasta     = $_GET['hasta'] ?? date('Y-m-d');

        if (!$idMateria) $this->error('idMateria requerido');

        $db = db();

        // Info de la materia
        $materia = $db->prepare(
            "SELECT materias.nombre, materias.codigo, u.nombre AS profesor
             FROM materias
             JOIN profesores p ON materias.idProfesor = p.idProfesor
             JOIN usuarios u ON p.idUsuario = u.idUsuario
             WHERE materias.idMateria = ?"
        );
        $materia->execute([$idMateria]);
        $info = $materia->fetch();

        // Estudiantes inscritos
        $stmtEsts = $db->prepare(
            "SELECT u.nombre, est.codigoEst,
                    (SELECT COUNT(*) FROM asistencia WHERE idEstudiante = est.idEstudiante AND idMateria = ? AND asistio = 1 AND fecha BETWEEN ? AND ?) AS presentes,
                    (SELECT COUNT(*) FROM asistencia WHERE idEstudiante = est.idEstudiante AND idMateria = ? AND asistio = 0 AND fecha BETWEEN ? AND ?) AS ausentes,
                    (SELECT COUNT(DISTINCT fecha) FROM asistencia WHERE idMateria = ? AND fecha BETWEEN ? AND ?) AS total_clases
             FROM inscripciones i
             JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE i.idMateria = ?
             ORDER BY u.nombre"
        );
        $stmtEsts->execute([
            $idMateria, $desde, $hasta,
            $idMateria, $desde, $hasta,
            $idMateria, $desde, $hasta,
            $idMateria
        ]);
        $estudiantes = $stmtEsts->fetchAll();

        // Calcular porcentaje
        foreach ($estudiantes as &$est) {
            $est['porcentaje'] = $est['total_clases'] > 0
                ? round(($est['presentes'] / $est['total_clases']) * 100, 1)
                : 0;
        }

        $this->ok([
            'materia'     => $info,
            'rango'       => ['desde' => $desde, 'hasta' => $hasta],
            'estudiantes' => $estudiantes
        ]);
    }

    /**
     * Reporte de notas por materia.
     */
    private function reporteNotas(): void {
        $idMateria = (int)($_GET['idMateria'] ?? 0);
        if (!$idMateria) $this->error('idMateria requerido');

        $db = db();

        $info = $db->prepare(
            "SELECT materias.nombre, materias.codigo, u.nombre AS profesor
             FROM materias
             JOIN profesores p ON materias.idProfesor = p.idProfesor
             JOIN usuarios u ON p.idUsuario = u.idUsuario
             WHERE materias.idMateria = ?"
        );
        $info->execute([$idMateria]);

        $stmt = $db->prepare(
            "SELECT u.nombre, est.codigoEst,
                    i.nota_parcial1, i.nota_parcial2, i.nota_final,
                    i.nota_habilitacion,
                    (SELECT COUNT(*) FROM entregas e
                     JOIN actividades a ON e.idActividad = a.idActividad
                     JOIN aulas_virtuales av ON a.idAula = av.idAula
                     WHERE e.idEstudiante = est.idEstudiante AND av.idMateria = ?) AS total_entregas,
                    (SELECT COUNT(*) FROM actividades a
                     JOIN aulas_virtuales av ON a.idAula = av.idAula
                     WHERE av.idMateria = ?) AS total_actividades
             FROM inscripciones i
             JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE i.idMateria = ?
             ORDER BY u.nombre"
        );
        $stmt->execute([$idMateria, $idMateria, $idMateria]);

        $this->ok([
            'materia'     => $info->fetch(),
            'estudiantes' => $stmt->fetchAll()
        ]);
    }

    /**
     * Resumen general de todas las materias.
     */
    private function reporteMaterias(): void {
        $stmt = db()->query(
            "SELECT materias.idMateria, materias.nombre, materias.codigo, materias.color,
                    u.nombre AS profesor,
                    COUNT(DISTINCT i.idEstudiante) AS inscritos,
                    COUNT(DISTINCT mat.idMaterial) AS materiales,
                    COUNT(DISTINCT act.idActividad) AS actividades,
                    COUNT(DISTINCT e.idEntrega) AS entregas,
                    SUM(CASE WHEN e.nota IS NULL THEN 1 ELSE 0 END) AS sin_calificar
             FROM materias
             LEFT JOIN profesores p ON materias.idProfesor = p.idProfesor
             LEFT JOIN usuarios u ON p.idUsuario = u.idUsuario
             LEFT JOIN inscripciones i ON materias.idMateria = i.idMateria
             LEFT JOIN aulas_virtuales av ON materias.idMateria = av.idMateria
             LEFT JOIN materiales mat ON av.idAula = mat.idAula
             LEFT JOIN actividades act ON av.idAula = act.idAula
             LEFT JOIN entregas e ON act.idActividad = e.idActividad
             GROUP BY materias.idMateria
             ORDER BY inscritos DESC"
        );
        $this->ok($stmt->fetchAll());
    }

    /**
     * Reporte de actividad global del sistema.
     */
    private function reporteActividad(): void {
        $db   = db();
        $dias = (int)($_GET['dias'] ?? 30);

        $entregas = $db->prepare(
            "SELECT DATE(entregado_en) AS fecha, COUNT(*) AS total
             FROM entregas
             WHERE entregado_en >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(entregado_en)
             ORDER BY fecha"
        );
        $entregas->execute([$dias]);

        $logins = $db->prepare(
            "SELECT DATE(creado_en) AS fecha, COUNT(*) AS total
             FROM usuarios
             WHERE creado_en >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(creado_en)"
        );
        $logins->execute([$dias]);

        $this->ok([
            'entregas' => $entregas->fetchAll(),
            'registros' => $logins->fetchAll()
        ]);
    }
}

(new ReportesController())->handle();

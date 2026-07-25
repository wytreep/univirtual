<?php
require_once __DIR__.'/Controller.php';

/**
 * StatsController
 * API REST para estadísticas del panel admin.
 * GET /api/v1/stats.php
 */
class StatsController extends Controller {

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
$session = $this->requireAuth(['admin', 'directivo']);
        if ($this->method() !== 'GET') $this->error('Método no permitido', 405);

        $action = $_GET['action'] ?? '';
        $db = db();

        if ($action === 'facultad') {
            $idFac = (int)($_GET['idFacultad'] ?? 0);
            try {
                $totalProfesores = (int)$db->query("SELECT COUNT(*) FROM profesores WHERE idFacultad=$idFac")->fetchColumn();
                $totalEstudiantes = (int)$db->query("SELECT COUNT(*) FROM estudiantes e JOIN carreras c ON e.idCarrera = c.idCarrera WHERE c.idFacultad=$idFac")->fetchColumn();
                $totalMaterias = (int)$db->query("SELECT COUNT(*) FROM materias m LEFT JOIN carreras c ON m.idCarrera = c.idCarrera LEFT JOIN profesores p ON m.idProfesor = p.idProfesor WHERE c.idFacultad=$idFac OR p.idFacultad=$idFac")->fetchColumn();
                $totalInscripciones = (int)$db->query("SELECT COUNT(*) FROM inscripciones i JOIN materias m ON i.idMateria = m.idMateria LEFT JOIN carreras c ON m.idCarrera = c.idCarrera LEFT JOIN profesores p ON m.idProfesor = p.idProfesor WHERE c.idFacultad=$idFac OR p.idFacultad=$idFac")->fetchColumn();
                $aprobados = (int)$db->query("SELECT COUNT(*) FROM inscripciones i JOIN materias m ON i.idMateria = m.idMateria LEFT JOIN carreras c ON m.idCarrera = c.idCarrera LEFT JOIN profesores p ON m.idProfesor = p.idProfesor WHERE (c.idFacultad=$idFac OR p.idFacultad=$idFac) AND i.nota_final >= 3.0")->fetchColumn();
                $tasa = $totalInscripciones > 0 ? round(($aprobados / $totalInscripciones) * 100, 1) : 0;
                $this->ok(['totalProfesores' => $totalProfesores, 'totalEstudiantes' => $totalEstudiantes, 'totalMaterias' => $totalMaterias, 'totalInscripciones' => $totalInscripciones, 'tasaAprobacion' => $tasa]);
            } catch(Exception $e) {
                $this->ok(['totalProfesores'=>0, 'totalEstudiantes'=>0, 'totalMaterias'=>0, 'totalInscripciones'=>0, 'tasaAprobacion'=>0]);
            }
            return;
        }

        // Conteos generales
        $totales = [];
        foreach (['usuarios','estudiantes','profesores','materias','materiales','entregas','notificaciones'] as $tabla) {
            $totales[$tabla] = (int)$db->query("SELECT COUNT(*) FROM $tabla")->fetchColumn();
        }

        // Usuarios activos vs inactivos
        $activos   = (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetchColumn();
        $inactivos = (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE activo=0")->fetchColumn();

        // Distribución por rol
        $roles = $db->query(
            "SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol"
        )->fetchAll();

        // Entregas sin calificar
        $sinCalificar = (int)$db->query(
            "SELECT COUNT(*) FROM entregas WHERE nota IS NULL"
        )->fetchColumn();

        // Materias con más estudiantes (top 5)
        $topMaterias = $db->query(
            "SELECT materias.idMateria, materias.nombre, materias.color,
                    COUNT(inscripciones.idEstudiante) AS inscritos
             FROM materias
             LEFT JOIN inscripciones ON materias.idMateria = inscripciones.idMateria
             GROUP BY materias.idMateria
             ORDER BY inscritos DESC
             LIMIT 5"
        )->fetchAll();

        // Actividad reciente (últimos 7 días)
        $actividadReciente = $db->query(
            "SELECT DATE(entregado_en) AS dia, COUNT(*) AS entregas
             FROM entregas
             WHERE entregado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(entregado_en)
             ORDER BY dia ASC"
        )->fetchAll();

        // Usuarios registrados por mes (últimos 6 meses)
        $usuariosPorMes = $db->query(
            "SELECT DATE_FORMAT(creado_en, '%Y-%m') AS mes,
                    COUNT(*) AS total
             FROM usuarios
             WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY mes
             ORDER BY mes ASC"
        )->fetchAll();

        $this->ok([
            'totales'           => $totales,
            'usuarios'          => ['activos' => $activos, 'inactivos' => $inactivos],
            'roles'             => $roles,
            'sinCalificar'      => $sinCalificar,
            'topMaterias'       => $topMaterias,
            'actividadReciente' => $actividadReciente,
            'usuariosPorMes'    => $usuariosPorMes,
        ]);
    }
}

(new StatsController())->handle();

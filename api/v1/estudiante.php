<?php
require_once __DIR__.'/Controller.php';

/**
 * EstudianteController
 * API REST para el panel del estudiante.
 *
 * GET /api/v1/estudiante.php?action=dashboard
 * GET /api/v1/estudiante.php?action=materias
 * GET /api/v1/estudiante.php?action=tareas
 * GET /api/v1/estudiante.php?action=notas
 * GET /api/v1/estudiante.php?action=aula&idAula=1
 * GET /api/v1/estudiante.php?action=notificaciones
 * POST /api/v1/estudiante.php?action=entregar
 * POST /api/v1/estudiante.php?action=publicar_mensaje
 * POST /api/v1/estudiante.php?action=marcar_notifs
 */
class EstudianteController extends Controller {

    private int $idEst;
    private int $idUsuario;

    public function __construct() {
        parent::__construct();
    }

public function handle(): void {
    $session         = $this->requireAuth('estudiante');
    $this->idEst     = (int)($session['idEspecifico'] ?? 0);
    $this->idUsuario = (int)($session['idUsuario'] ?? 0);

    // ── Leer action desde GET, POST form, o body JSON ──
    $body   = [];
    if ($this->method() === 'POST') {
        $raw   = file_get_contents('php://input');
        $body  = json_decode($raw, true) ?? [];
    }
    $action = $_GET['action'] ?? $_POST['action'] ?? $body['action'] ?? '';

    if ($this->method() === 'GET') {
        match($action) {
            'dashboard'      => $this->dashboard(),
            'materias'       => $this->materias(),
            'tareas'         => $this->tareas(),
            'notas'          => $this->notas(),
            'aula'           => $this->aula(),
            'notificaciones' => $this->notificaciones(),
            default          => $this->error('Acción no válida')
        };
    } elseif ($this->method() === 'POST') {
        match($action) {
            'entregar'         => $this->entregar(),
            'publicar_mensaje' => $this->publicarMensaje(),
            'marcar_notifs'    => $this->marcarNotifs(),
            'actualizar_perfil'=> $this->actualizarPerfil(),
            'cambiar_password' => $this->cambiarPassword(),
            default            => $this->error('Acción no válida')
        };
    } else {
        $this->error('Método no permitido', 405);
    }
}

    // ── GET dashboard ──────────────────────────────────────────────────────────
    private function dashboard(): void {
        require_once dirname(__DIR__, 2) . '/models/Asistencia.php';
        require_once dirname(__DIR__, 2) . '/models/Notificacion.php';

        $db  = db();
        $est = new Estudiante();

        $materias = $est->getMaterias($this->idEst);
        $tareas   = $est->getTareasPendientes($this->idEst);

        // Promedio general sobre parciales registrados
        $vals = array_filter(array_merge(
            array_column($materias, 'nota_parcial1'),
            array_column($materias, 'nota_parcial2'),
            array_column($materias, 'nota_talleres')
        ), fn($v) => $v !== null && $v !== '');
        $promedio = count($vals) ? round(array_sum($vals) / count($vals), 1) : null;

        // Asistencia global del estudiante
        $asistenciaModel = new Asistencia();
        $pctAsist = $asistenciaModel->getPorcentaje($this->idEst, null);

        // Próximas actividades sin entregar
        $sProx = $db->prepare(
            "SELECT a.idActividad, a.titulo, a.fechaEntrega, a.puntaje_max,
                    m.nombre AS materia, m.color, av.idAula
             FROM actividades a
             JOIN aulas_virtuales av ON a.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             JOIN inscripciones i ON m.idMateria = i.idMateria
             WHERE i.idEstudiante = ?
               AND a.fechaEntrega >= NOW()
               AND a.idActividad NOT IN (
                   SELECT idActividad FROM entregas WHERE idEstudiante = ?
               )
             ORDER BY a.fechaEntrega ASC LIMIT 5"
        );
        $sProx->execute([$this->idEst, $this->idEst]);
        $proximasTareas = $sProx->fetchAll();

        // Notificaciones recientes
        $notificacionModel = new Notificacion();
        $notificaciones = $notificacionModel->getByUsuario($this->idUsuario, 6);
        $noLeidas = $notificacionModel->contarNoLeidas($this->idUsuario);

        // Conteo real de entregas hechas y total de actividades
        $sEnt = $db->prepare(
            "SELECT
               COUNT(DISTINCT e.idEntrega) AS entregas_hechas,
               COUNT(DISTINCT a.idActividad) AS total_actividades
             FROM actividades a
             JOIN aulas_virtuales av ON a.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             JOIN inscripciones i ON m.idMateria = i.idMateria
             LEFT JOIN entregas e ON a.idActividad = e.idActividad
               AND e.idEstudiante = ?
             WHERE i.idEstudiante = ?"
        );
        $sEnt->execute([$this->idEst, $this->idEst]);
        $conteos = $sEnt->fetch();

        // Estadísticas de avance del semestre
        $semanaInicio = strtotime('2026-01-19');
        $semanaActual = max(1, (int)floor((time() - $semanaInicio) / 604800));
        $totalSemanas = 20;

        $this->ok([
            'materias'       => $materias,
            'tareas'         => $tareas,
            'proximasTareas' => $proximasTareas,
            'notificaciones' => $notificaciones,
            'estadisticas'   => [
                'totalMaterias'        => count($materias),
                'pendientes'           => count($tareas),
                'tareasProximas'       => count($proximasTareas),
                'promedio'             => $promedio,
                'asistencia'           => $pctAsist,
                'creditos'             => array_sum(array_column($materias, 'creditos')),
                'creditosInscritos'    => array_sum(array_column($materias, 'creditos')),
                'noLeidas'             => $noLeidas,
                'semanasTranscurridas' => min($semanaActual, $totalSemanas),
                'totalSemanas'         => $totalSemanas,
                'entregasHechas'       => (int)($conteos['entregas_hechas']   ?? 0),
                'totalActividades'     => (int)($conteos['total_actividades'] ?? 0),
            ],
        ]);
    }

    // ── GET materias ───────────────────────────────────────────────────────────
    private function materias(): void {
        $est = new Estudiante();
        $this->ok($est->getMaterias($this->idEst));
    }

    // ── GET tareas ─────────────────────────────────────────────────────────────
    private function tareas(): void {
        $db   = db();
        $stmt = $db->prepare(
            "SELECT a.idActividad, a.titulo, a.descripcion, a.fechaEntrega, a.puntaje_max,
                    m.nombre AS materia, m.color, av.idAula,
                    e.idEntrega, e.nota, e.feedback, e.entregado_en, e.nombreOriginal
             FROM actividades a
             JOIN aulas_virtuales av ON a.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             JOIN inscripciones i ON m.idMateria = i.idMateria
             LEFT JOIN entregas e ON a.idActividad = e.idActividad AND e.idEstudiante = ?
             WHERE i.idEstudiante = ?
             ORDER BY a.fechaEntrega ASC"
        );
        $stmt->execute([$this->idEst, $this->idEst]);
        $todas = $stmt->fetchAll();

        $ahora = time();
        $pendientes = array_values(array_filter($todas, fn($a) => empty($a['idEntrega']) && strtotime($a['fechaEntrega']) >= $ahora));
        $entregadas = array_values(array_filter($todas, fn($a) => !empty($a['idEntrega'])));
        $vencidas   = array_values(array_filter($todas, fn($a) => empty($a['idEntrega']) && strtotime($a['fechaEntrega']) < $ahora));

        $this->ok([
            'pendientes' => $pendientes,
            'entregadas' => $entregadas,
            'vencidas'   => $vencidas,
        ]);
    }

    // ── GET notas ──────────────────────────────────────────────────────────────
    private function notas(): void {
        // SCHEMA v3: inscripciones tiene nota_parcial1, nota_parcial2, nota_talleres, nota_final
        // NO existe nota_habilitacion en el schema
        $stmt = db()->prepare(
            "SELECT m.idMateria, m.nombre, m.color, m.codigo, m.creditos,
                    i.nota_parcial1, i.nota_parcial2, i.nota_talleres, i.nota_final,
                    (SELECT COUNT(*) FROM entregas e
                     JOIN actividades a ON e.idActividad = a.idActividad
                     JOIN aulas_virtuales av ON a.idAula = av.idAula
                     WHERE e.idEstudiante = ? AND av.idMateria = m.idMateria) AS entregas_hechas,
                    (SELECT COUNT(*) FROM actividades a
                     JOIN aulas_virtuales av ON a.idAula = av.idAula
                     WHERE av.idMateria = m.idMateria) AS total_actividades
             FROM inscripciones i
             JOIN materias m ON i.idMateria = m.idMateria
             WHERE i.idEstudiante = ?
             ORDER BY m.nombre"
        );
        $stmt->execute([$this->idEst, $this->idEst]);
        $materias = $stmt->fetchAll();

        // Calcular promedio y estado por materia
        foreach ($materias as &$n) {
            $vals = array_filter([
                $n['nota_parcial1'],
                $n['nota_parcial2'],
                $n['nota_talleres'],
            ], fn($v) => $v !== null && $v !== '');

            $n['promedio'] = count($vals)
                ? round(array_sum($vals) / count($vals), 2)
                : null;

            $nota = $n['nota_final'] ?? $n['promedio'];
            $n['estado'] = match(true) {
                $nota === null         => 'en_curso',
                (float)$nota >= 3.0    => 'aprobado',
                default                => 'reprobando',
            };
        }
        unset($n);

        // Promedio global del semestre
        $vals = array_filter(
            array_map(fn($m) => $m['nota_final'] ?? $m['promedio'], $materias),
            fn($v) => $v !== null
        );
        $promedioGlobal = count($vals)
            ? round(array_sum($vals) / count($vals), 1)
            : null;

        // React espera: data.materias y data.promedio
        $this->ok([
            'materias' => $materias,
            'promedio' => $promedioGlobal,
        ]);
    }

    // ── GET aula ───────────────────────────────────────────────────────────────
    private function aula(): void {
        $idAula = (int)($_GET['idAula'] ?? 0);
        if (!$idAula) $this->error('idAula requerido');

        $db = db();

        // Verificar que el estudiante esté inscrito
        $check = $db->prepare(
            "SELECT av.*, m.nombre AS materia, m.color, m.codigo, m.idMateria
             FROM aulas_virtuales av
             JOIN materias m ON av.idMateria = m.idMateria
             JOIN inscripciones i ON m.idMateria = i.idMateria
             WHERE av.idAula = ? AND i.idEstudiante = ?"
        );
        $check->execute([$idAula, $this->idEst]);
        $aula = $check->fetch();
        if (!$aula) $this->error('Sin acceso a esta aula', 403);

        // Materiales
        $mats = $db->prepare("SELECT * FROM materiales WHERE idAula = ? ORDER BY subido_en DESC");
        $mats->execute([$idAula]);

        // Actividades con estado de entrega del estudiante
        $acts = $db->prepare(
            "SELECT a.*,
                    e.idEntrega, e.nota, e.feedback, e.entregado_en, e.nombreOriginal
             FROM actividades a
             LEFT JOIN entregas e ON a.idActividad = e.idActividad AND e.idEstudiante = ?
             WHERE a.idAula = ?
             ORDER BY a.fechaEntrega"
        );
        $acts->execute([$this->idEst, $idAula]);

        // Foros con mensajes
        $foros = $db->prepare(
            "SELECT foros.*, (SELECT COUNT(*) FROM mensajes WHERE idForo = foros.idForo) AS total_msgs
             FROM foros WHERE idAula = ? ORDER BY creado_en DESC"
        );
        $foros->execute([$idAula]);
        $forosData = $foros->fetchAll();

        foreach ($forosData as &$foro) {
            $msgs = $db->prepare(
                "SELECT msg.*, u.nombre, u.rol
                 FROM mensajes msg JOIN usuarios u ON msg.idUsuario = u.idUsuario
                 WHERE msg.idForo = ? ORDER BY msg.publicado_en ASC"
            );
            $msgs->execute([$foro['idForo']]);
            $foro['mensajes'] = $msgs->fetchAll();
        }

        // Asistencia del estudiante en esta materia + porcentaje
        $asist = $db->prepare(
            "SELECT fecha, asistio FROM asistencia
             WHERE idEstudiante = ? AND idMateria = ?
             ORDER BY fecha DESC LIMIT 20"
        );
        $asist->execute([$this->idEst, $aula['idMateria']]);
        $asistData = $asist->fetchAll();

        // Calcular porcentaje para el Ring
        $totalClases   = count($asistData);
        $asistidas     = count(array_filter($asistData, fn($r) => (bool)$r['asistio']));
        $pctAsistencia = $totalClases > 0
            ? (int)round(($asistidas / $totalClases) * 100)
            : 0;

        // Extraer mensajes como array plano para que React pueda filtrar por idForo
        $mensajesPlano = [];
        foreach ($forosData as $foro) {
            foreach ($foro['mensajes'] ?? [] as $msg) {
                $mensajesPlano[] = $msg;
            }
        }

        $this->ok([
            'aula'                 => $aula,
            'materiales'           => $mats->fetchAll(),
            'actividades'          => $acts->fetchAll(),
            'foros'                => $forosData,
            'mensajes'             => $mensajesPlano,
            'asistencia'           => $asistData,
            'porcentajeAsistencia' => $pctAsistencia,
            'totalClases'          => $totalClases,
            'clasesAsistidas'      => $asistidas,
        ]);
    }

    // ── GET notificaciones ────────────────────────────────────────────────────
    private function notificaciones(): void {
        $stmt = db()->prepare(
            "SELECT * FROM notificaciones WHERE idUsuario = ?
             ORDER BY creado_en DESC LIMIT 30"
        );
        $stmt->execute([$this->idUsuario]);
        $this->ok($stmt->fetchAll());
    }

    // ── POST entregar ──────────────────────────────────────────────────────────
    private function entregar(): void {
        // Para entregas con archivo usamos multipart/form-data
        $idActividad = (int)($_POST['idActividad'] ?? 0);
        $comentario  = trim($_POST['comentario'] ?? '');

        if (!$idActividad) $this->error('idActividad requerido');

        $db = db();

        // Verificar que no haya entregado ya
        $ya = $db->prepare(
            "SELECT idEntrega FROM entregas WHERE idActividad = ? AND idEstudiante = ?"
        );
        $ya->execute([$idActividad, $this->idEst]);
        if ($ya->fetch()) $this->error('Ya has entregado esta actividad');

        // Verificar fecha límite
        $act = $db->prepare("SELECT fechaEntrega FROM actividades WHERE idActividad = ?");
        $act->execute([$idActividad]);
        $actividad = $act->fetch();
        if (!$actividad) $this->error('Actividad no encontrada', 404);
        if (strtotime($actividad['fechaEntrega']) < time()) {
            $this->error('La fecha límite de entrega ha pasado');
        }

        $archivoPath    = '';
        $nombreOriginal = '';

        // Manejar archivo si viene
        if (!empty($_FILES['archivo']['name'])) {
            $uploadDir = BASE_PATH . '/uploads/entregas/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext            = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
            $nombreArchivo  = 'entrega_' . $this->idEst . '_' . $idActividad . '_' . time() . '.' . $ext;
            $archivoPath    = 'uploads/entregas/' . $nombreArchivo;
            $nombreOriginal = $_FILES['archivo']['name'];

            if (!move_uploaded_file($_FILES['archivo']['tmp_name'], BASE_PATH . '/' . $archivoPath)) {
                $this->error('Error al guardar el archivo');
            }
        }

        $db->prepare(
            "INSERT INTO entregas (idActividad, idEstudiante, archivoEntrega, nombreOriginal, comentario)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$idActividad, $this->idEst, $archivoPath, $nombreOriginal, $comentario]);

        $this->ok(['idEntrega' => (int)$db->lastInsertId()], 'Actividad entregada exitosamente');
    }

    // ── POST publicar_mensaje ─────────────────────────────────────────────────
    private function publicarMensaje(): void {
        $body      = $this->getBody();
        $idForo    = (int)($body['idForo'] ?? 0);
        $contenido = trim($body['contenido'] ?? '');
        $idPadre   = !empty($body['idPadre']) ? (int)$body['idPadre'] : null;

        if (!$idForo || !$contenido) $this->error('idForo y contenido requeridos');

        $db = db();
        $db->prepare(
            "INSERT INTO mensajes (idForo, idUsuario, idPadre, contenido) VALUES (?, ?, ?, ?)"
        )->execute([$idForo, $this->idUsuario, $idPadre, $contenido]);

        $msg = $db->prepare(
            "SELECT msg.*, u.nombre, u.rol FROM mensajes msg
             JOIN usuarios u ON msg.idUsuario = u.idUsuario
             WHERE msg.idMensaje = ?"
        );
        $msg->execute([(int)$db->lastInsertId()]);

        $this->ok($msg->fetch(), 'Mensaje publicado');
    }

    // ── POST marcar_notifs ────────────────────────────────────────────────────
    private function marcarNotifs(): void {
        db()->prepare(
            "UPDATE notificaciones SET leida = 1 WHERE idUsuario = ?"
        )->execute([$this->idUsuario]);
        $this->ok(null, 'Notificaciones marcadas como leídas');
    }

    // ── POST actualizar_perfil (FIX 3) ───────────────────────────────────────
    private function actualizarPerfil(): void {
        $body   = $this->getBody();
        $nombre = trim($body['nombre'] ?? '');
        if (!$nombre) { $this->error('Nombre requerido', 422); return; }
        db()->prepare('UPDATE usuarios SET nombre=? WHERE idUsuario=?')
            ->execute([$nombre, $this->idUsuario]);
        $this->ok(['nombre' => $nombre], 'Perfil actualizado');
    }

    // ── POST cambiar_password (FIX 3) ────────────────────────────────────────
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

(new EstudianteController())->handle();
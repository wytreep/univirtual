<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__.'/Controller.php';

/**
 * ProfesorController
 * API REST para el panel del profesor.
 *
 * GET /api/v1/profesor.php?action=dashboard
 * GET /api/v1/profesor.php?action=aula&idAula=1
 * GET /api/v1/profesor.php?action=entregas&idMateria=1
 * POST /api/v1/profesor.php?action=subir_material      (multipart/form-data)
 * POST /api/v1/profesor.php?action=eliminar_material
 * POST /api/v1/profesor.php?action=crear_actividad
 * POST /api/v1/profesor.php?action=eliminar_actividad
 * POST /api/v1/profesor.php?action=calificar_entrega
 * POST /api/v1/profesor.php?action=guardar_nota
 * POST /api/v1/profesor.php?action=crear_foro
 * POST /api/v1/profesor.php?action=publicar_mensaje
 * POST /api/v1/profesor.php?action=registrar_asistencia
 */
class ProfesorController extends Controller {

    private int $idProf;

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $session     = $this->requireAuth('profesor');
        $this->idProf = (int)$session['idEspecifico'];
        $this->idUsuario = (int)$session['idUsuario'];
        // action puede venir en query string (?action=X) o en body de FormData
          $body   = [];
    if ($this->method() === 'POST') {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? [];
    }
    $action = $_GET['action'] ?? $_POST['action'] ?? $body['action'] ?? '';

        if ($this->method() === 'GET') {
            match($action) {
                'dashboard'       => $this->dashboard(),
                'aula'            => $this->aula(),
                'entregas'        => $this->entregas(),
                'asistencia'      => $this->asistencia(),
                'asistencia_fecha'=> $this->asistenciaFecha(),
                'notificaciones'  => $this->notificaciones(),
                'materias'        => $this->materiasProfesor(),
                default           => $this->error('Acción no válida')
            };
        } elseif ($this->method() === 'POST') {
            match($action) {
                'subir_material'       => $this->subirMaterial(),
                'eliminar_material'    => $this->eliminarMaterial(),
                'crear_actividad'      => $this->crearActividad(),
                'eliminar_actividad'   => $this->eliminarActividad(),
                'calificar_entrega'    => $this->calificarEntrega(),
                'guardar_nota'         => $this->guardarNota(),
                'crear_foro'           => $this->crearForo(),
                'publicar_mensaje'     => $this->publicarMensaje(),
                'registrar_asistencia' => $this->registrarAsistencia(),
                'marcar_notifs'        => $this->marcarNotifs(),
                'marcar_notif'         => $this->marcarNotifIndividual(),
                'actualizar_perfil'    => $this->actualizarPerfil(),
                'cambiar_password'     => $this->cambiarPassword(),
                default                => $this->error('Acción no válida')
            };
        } else {
            $this->error('Método no permitido', 405);
        }
    }

    // ── GET dashboard ──────────────────────────────────────────────────────────
    private function dashboard(): void {
        $db = db();

        // Materias con estadísticas
        $stmt = $db->prepare(
            "SELECT m.*, av.idAula,
                    (SELECT COUNT(*) FROM inscripciones WHERE idMateria = m.idMateria) AS total_est,
                    (SELECT COUNT(*) FROM materiales WHERE idAula = av.idAula) AS total_materiales,
                    (SELECT COUNT(*) FROM actividades WHERE idAula = av.idAula) AS total_actividades,
                    (SELECT COUNT(*) FROM entregas e
                     JOIN actividades a ON e.idActividad = a.idActividad
                     WHERE a.idAula = av.idAula AND e.nota IS NULL) AS sinCalificar
             FROM materias m
             JOIN aulas_virtuales av ON av.idMateria = m.idMateria
             WHERE m.idProfesor = ? AND m.activa = 1
             ORDER BY m.nombre"
        );
        $stmt->execute([$this->idProf]);
        $materias = $stmt->fetchAll();

        // Total sin calificar global
        $sinCalificar = $db->prepare(
            "SELECT COUNT(*) FROM entregas e
             JOIN actividades a ON e.idActividad = a.idActividad
             JOIN aulas_virtuales av ON a.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             WHERE m.idProfesor = ? AND e.nota IS NULL"
        );
        $sinCalificar->execute([$this->idProf]);

        // Entregas recientes
        $recientes = $db->prepare(
            "SELECT e.idEntrega, e.nota, e.entregado_en,
                    u.nombre AS estudiante,
                    act.titulo AS actividad,
                    m.nombre AS materia, m.color
             FROM entregas e
             JOIN actividades act ON e.idActividad = act.idActividad
             JOIN aulas_virtuales av ON act.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             JOIN estudiantes est ON e.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE m.idProfesor = ?
             ORDER BY e.entregado_en DESC
             LIMIT 8"
        );
        $recientes->execute([$this->idProf]);

        // Notificaciones no leídas
        $notifs = $db->prepare(
            "SELECT * FROM notificaciones
             WHERE idUsuario = ? AND leida = 0
             ORDER BY creado_en DESC LIMIT 5"
        );
        if (session_status() === PHP_SESSION_NONE) session_start();
        $notifs->execute([$_SESSION['idUsuario'] ?? 0]);

        $totalSinCalif = (int)$sinCalificar->fetchColumn();

        $this->ok([
            'materias'          => $materias,
            // Nombres que React espera en el panel
            'totalMaterias'     => count($materias),
            'totalSinCalificar' => $totalSinCalif,
            'totalEstudiantes'  => array_sum(array_column($materias, 'total_est')),
            'totalMateriales'   => array_sum(array_column($materias, 'total_materiales')),
            'entregasRecientes' => $recientes->fetchAll(),
            'notificaciones'    => $notifs->fetchAll(),
        ]);
    }

    // ── GET aula ───────────────────────────────────────────────────────────────
    private function aula(): void {
        $idAula = (int)($_GET['idAula'] ?? 0);
        if (!$idAula) $this->error('idAula requerido');

        $db = db();

        // Verificar que el aula pertenece al profesor
        $infoAula = $db->prepare(
            "SELECT av.*, m.nombre AS materia, m.color, m.codigo, m.idMateria
             FROM aulas_virtuales av
             JOIN materias m ON av.idMateria = m.idMateria
             WHERE av.idAula = ? AND m.idProfesor = ?"
        );
        $infoAula->execute([$idAula, $this->idProf]);
        $aula = $infoAula->fetch();
        if (!$aula) $this->error('Aula no encontrada o sin acceso', 403);

        // Materiales
        $mats = $db->prepare("SELECT * FROM materiales WHERE idAula = ? ORDER BY subido_en DESC");
        $mats->execute([$idAula]);

        // Actividades con conteo de entregas
        $acts = $db->prepare(
            "SELECT actividades.*,
                    (SELECT COUNT(*) FROM entregas WHERE idActividad = actividades.idActividad) AS entregas,
                    (SELECT COUNT(*) FROM entregas WHERE idActividad = actividades.idActividad AND nota IS NULL) AS sin_calificar
             FROM actividades WHERE idAula = ? ORDER BY fechaEntrega"
        );
        $acts->execute([$idAula]);

        // Foros con mensajes
        $foros = $db->prepare(
            "SELECT foros.*,
                    (SELECT COUNT(*) FROM mensajes WHERE idForo = foros.idForo) AS total_msgs
             FROM foros WHERE idAula = ? ORDER BY creado_en DESC"
        );
        $foros->execute([$idAula]);
        $forosData = $foros->fetchAll();

        // Mensajes de cada foro
        foreach ($forosData as &$foro) {
            $msgs = $db->prepare(
                "SELECT msg.*, u.nombre, u.rol
                 FROM mensajes msg
                 JOIN usuarios u ON msg.idUsuario = u.idUsuario
                 WHERE msg.idForo = ?
                 ORDER BY msg.publicado_en ASC"
            );
            $msgs->execute([$foro['idForo']]);
            $foro['mensajes'] = $msgs->fetchAll();
        }

        // Estudiantes con asistioHoy y pctAsistencia (que React usa en el tab asistencia)
        $hoy  = date('Y-m-d');
        $ests = $db->prepare(
            "SELECT u.nombre, est.codigoEst, est.idEstudiante,
                    i.idInscripcion, i.nota_parcial1, i.nota_parcial2,
                    i.nota_talleres, i.nota_final,
                    -- Asistencia de hoy
                    (SELECT asistio FROM asistencia
                     WHERE idEstudiante = est.idEstudiante
                       AND idMateria = ?
                       AND fecha = ?) AS asistioHoy,
                    -- Porcentaje global de asistencia del estudiante
                    ROUND(
                      (SELECT SUM(asistio) FROM asistencia
                       WHERE idEstudiante = est.idEstudiante AND idMateria = ?)
                      /
                      NULLIF(
                        (SELECT COUNT(*) FROM asistencia
                         WHERE idEstudiante = est.idEstudiante AND idMateria = ?), 0
                      ) * 100
                    , 0) AS pctAsistencia
             FROM inscripciones i
             JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE i.idMateria = ?
             ORDER BY u.nombre"
        );
        $ests->execute([
            $aula['idMateria'], $hoy,         // para asistioHoy
            $aula['idMateria'],                 // SUM asistio
            $aula['idMateria'],                 // COUNT total
            $aula['idMateria'],                 // WHERE inscripciones
        ]);

        // Asistencia global del aula en el día de hoy (para el banner)
        $sAsist = $db->prepare(
            "SELECT COUNT(*) AS total, SUM(asistio) AS asistidos
             FROM asistencia WHERE idMateria = ? AND fecha = ?"
        );
        $sAsist->execute([$aula['idMateria'], $hoy]);
        $asistHoy = $sAsist->fetch();

        $materialesArr  = $mats->fetchAll();
        $actividadesArr = $acts->fetchAll();
        $estudiantesArr = $ests->fetchAll();

        // Extraer mensajes como array plano para que React pueda filtrar por idForo
        $mensajesPlano = [];
        foreach ($forosData as $foro) {
            foreach ($foro['mensajes'] ?? [] as $msg) {
                $mensajesPlano[] = $msg;
            }
        }

        $this->ok([
            'aula'              => $aula,
            'materiales'        => $materialesArr,
            'actividades'       => $actividadesArr,
            'foros'             => $forosData,
            'mensajes'          => $mensajesPlano,   // array plano para filtrar en React
            'estudiantes'       => $estudiantesArr,
            // Conteos que el header del Aula usa
            'totalEstudiantes'  => count($estudiantesArr),
            'totalMateriales'   => count($materialesArr),
            'totalActividades'  => count($actividadesArr),
            'totalForos'        => count($forosData),
            'asistenciaHoy'     => [
                'total'     => (int)($asistHoy['total'] ?? 0),
                'asistidos' => (int)($asistHoy['asistidos'] ?? 0),
            ],
        ]);
    }

    // ── GET entregas ──────────────────────────────────────────────────────────
    private function entregas(): void {
        // React puede enviar idAula o idMateria — aceptamos ambos
        $idAula    = (int)($_GET['idAula']    ?? 0);
        $idMateria = (int)($_GET['idMateria'] ?? 0);

        $db = db();

        // Si viene idAula, resolver idMateria
        if ($idAula && !$idMateria) {
            $r = $db->prepare("SELECT idMateria FROM aulas_virtuales WHERE idAula = ?");
            $r->execute([$idAula]);
            $row = $r->fetch();
            if (!$row) $this->error('Aula no encontrada', 404);
            $idMateria = (int)$row['idMateria'];
        }

        if (!$idMateria) $this->error('idAula o idMateria requerido');

        // Verificar que la materia pertenece al profesor autenticado
        $check = $db->prepare("SELECT idMateria FROM materias WHERE idMateria = ? AND idProfesor = ?");
        $check->execute([$idMateria, $this->idProf]);
        if (!$check->fetch()) $this->error('Sin acceso', 403);

        $stmt = $db->prepare(
            "SELECT e.idEntrega, e.nota, e.feedback, e.entregado_en,
                    e.nombreOriginal, e.archivoEntrega, e.comentario,
                    u.nombre AS estudiante, est.codigoEst, est.idEstudiante,
                    act.titulo AS actividad, act.puntaje_max, act.idActividad
             FROM entregas e
             JOIN actividades act ON e.idActividad = act.idActividad
             JOIN aulas_virtuales av ON act.idAula = av.idAula
             JOIN estudiantes est ON e.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE av.idMateria = ?
             ORDER BY e.entregado_en DESC"
        );
        $stmt->execute([$idMateria]);
        $this->ok($stmt->fetchAll());
    }

    // ── GET asistencia ────────────────────────────────────────────────────────
    private function asistencia(): void {
        $idMateria = (int)($_GET['idMateria'] ?? 0);
        $fecha     = $_GET['fecha'] ?? date('Y-m-d');
        if (!$idMateria) $this->error('idMateria requerido');

        $db = db();

        // Estudiantes con estado de asistencia para esa fecha
        $stmt = $db->prepare(
            "SELECT est.idEstudiante, u.nombre, est.codigoEst,
                    (SELECT asistio FROM asistencia
                     WHERE idEstudiante = est.idEstudiante
                     AND idMateria = ? AND fecha = ?) AS asistio
             FROM inscripciones i
             JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE i.idMateria = ?
             ORDER BY u.nombre"
        );
        $stmt->execute([$idMateria, $fecha, $idMateria]);

        // Fechas previas con registro
        $fechas = $db->prepare(
            "SELECT DISTINCT fecha FROM asistencia WHERE idMateria = ? ORDER BY fecha DESC LIMIT 10"
        );
        $fechas->execute([$idMateria]);

        $this->ok([
            'estudiantes' => $stmt->fetchAll(),
            'fechas'      => array_column($fechas->fetchAll(), 'fecha'),
        ]);
    }

    // ── POST calificar_entrega ────────────────────────────────────────────────
    private function calificarEntrega(): void {
        $body      = $this->getBody();
        $idEntrega = (int)($body['idEntrega'] ?? 0);
        $nota      = (float)($body['nota'] ?? 0);
        $feedback  = trim($body['feedback'] ?? '');

        if (!$idEntrega) $this->error('idEntrega requerido');
        $nota = max(0, min(5, $nota));

        $db = db();
        $db->prepare("UPDATE entregas SET nota=?, feedback=? WHERE idEntrega=?")
           ->execute([$nota, $feedback, $idEntrega]);

        // Notificar estudiante
        $info = $db->prepare(
            "SELECT u.idUsuario, act.titulo
             FROM entregas e
             JOIN actividades act ON e.idActividad = act.idActividad
             JOIN estudiantes est ON e.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             WHERE e.idEntrega = ?"
        );
        $info->execute([$idEntrega]);
        $data = $info->fetch();
        if ($data) {
            notificar(
                $data['idUsuario'],
                'Entrega calificada',
                "Tu entrega \"{$data['titulo']}\" fue calificada: {$nota}/5",
                'nota',
                '/univirtual/pages/estudiante/tareas.php'
            );
        }

        $this->ok(null, 'Entrega calificada');
    }

    // ── POST guardar_nota ─────────────────────────────────────────────────────
    private function guardarNota(): void {
        $body        = $this->getBody();
        $idInsc      = (int)($body['idInscripcion'] ?? 0);
        $idEstudiante = (int)($body['idEstudiante'] ?? 0);
        $idMateria   = (int)($body['idMateria']    ?? 0);
        $campo       = $body['campo'] ?? '';
        $nota        = (float)($body['nota'] ?? 0);

        // Resolver idInscripcion desde idEstudiante+idMateria si no viene directo
        if (!$idInsc && $idEstudiante && $idMateria) {
            $r = db()->prepare(
                "SELECT idInscripcion FROM inscripciones
                 WHERE idEstudiante = ? AND idMateria = ?"
            );
            $r->execute([$idEstudiante, $idMateria]);
            $idInsc = (int)($r->fetchColumn() ?: 0);
        }
        $campo  = $body['campo'] ?? '';
        $nota   = (float)($body['nota'] ?? 0);

        $permitidos = ['nota_parcial1', 'nota_parcial2', 'nota_talleres', 'nota_final'];
        if (!$idInsc || !in_array($campo, $permitidos)) {
            $this->error('Datos inválidos');
        }

        $nota = max(0, min(5, $nota));
        $db   = db();
        $db->prepare("UPDATE inscripciones SET {$campo} = ? WHERE idInscripcion = ?")
           ->execute([$nota, $idInsc]);

        // Notificar estudiante
        $info = $db->prepare(
            "SELECT u.idUsuario, m.nombre AS materia
             FROM inscripciones i
             JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
             JOIN usuarios u ON est.idUsuario = u.idUsuario
             JOIN materias m ON i.idMateria = m.idMateria
             WHERE i.idInscripcion = ?"
        );
        $info->execute([$idInsc]);
        $data = $info->fetch();
        if ($data) {
            notificar(
                $data['idUsuario'],
                'Nota publicada',
                "Se publicó tu {$campo} en {$data['materia']}: {$nota}/5",
                'nota',
                '/univirtual/pages/estudiante/notas.php'
            );
        }

        $this->ok(null, 'Nota guardada');
    }

    // ── POST crear_foro ───────────────────────────────────────────────────────
    private function crearForo(): void {
        $body   = $this->getBody();
        $idAula = (int)($body['idAula'] ?? 0);
        $titulo = trim($body['titulo'] ?? '');
        $desc   = trim($body['descripcion'] ?? '');

        if (!$idAula || !$titulo) $this->error('idAula y titulo requeridos');

        $db = db();
        $db->prepare("INSERT INTO foros (idAula, titulo, descripcion) VALUES (?, ?, ?)")
           ->execute([$idAula, $titulo, $desc]);

        $this->ok(['idForo' => (int)$db->lastInsertId()], 'Foro creado');
    }

    // ── POST publicar_mensaje ─────────────────────────────────────────────────
    private function publicarMensaje(): void {
        $body      = $this->getBody();
        $idForo    = (int)($body['idForo'] ?? 0);
        $contenido = trim($body['contenido'] ?? '');
        $idPadre   = $body['idPadre'] ? (int)$body['idPadre'] : null;

        if (!$idForo || !$contenido) $this->error('idForo y contenido requeridos');

        if (session_status() === PHP_SESSION_NONE) session_start();
        $idUsuario = (int)$_SESSION['idUsuario'];

        $db = db();
        $db->prepare("INSERT INTO mensajes (idForo, idUsuario, idPadre, contenido) VALUES (?, ?, ?, ?)")
           ->execute([$idForo, $idUsuario, $idPadre, $contenido]);

        $idMensaje = (int)$db->lastInsertId();

        // Retornar el mensaje con nombre de usuario
        $msg = $db->prepare(
            "SELECT msg.*, u.nombre, u.rol
             FROM mensajes msg JOIN usuarios u ON msg.idUsuario = u.idUsuario
             WHERE msg.idMensaje = ?"
        );
        $msg->execute([$idMensaje]);

        $this->ok($msg->fetch(), 'Mensaje publicado');
    }

    // ── POST subir_material ───────────────────────────────────────────────────
    // Schema v3: materiales(idMaterial, idAula, idProfesor, titulo, descripcion,
    //            tipo ENUM('pdf','video','presentacion','otro'), urlArchivo,
    //            nombreOriginal, tamano, descargas, subido_en)
    private function subirMaterial(): void {
        $idAula      = (int)($_POST['idAula']      ?? 0);
        $titulo      = trim($_POST['titulo']       ?? '');
        $descripcion = trim($_POST['descripcion']  ?? '');
        $tipo        = $_POST['tipo']              ?? 'pdf';
        $enlace      = trim($_POST['enlace']       ?? '');

        if (!$idAula || !$titulo) $this->error('idAula y titulo son obligatorios');

        // Mapear tipo a ENUM válido del schema v3
        $tipoMap  = ['pdf' => 'pdf', 'video' => 'video',
                     'presentacion' => 'presentacion', 'enlace' => 'otro', 'otro' => 'otro'];
        $tipoEnum = $tipoMap[$tipo] ?? 'otro';

        $db = db();

        // Verificar que el aula pertenece a este profesor
        $check = $db->prepare(
            "SELECT av.idAula FROM aulas_virtuales av
             JOIN materias m ON av.idMateria = m.idMateria
             WHERE av.idAula = ? AND m.idProfesor = ?"
        );
        $check->execute([$idAula, $this->idProf]);
        if (!$check->fetch()) $this->error('Sin acceso a esta aula', 403);

        $urlArchivo     = '';
        $nombreOriginal = '';
        $tamano         = 0;

        if ($tipo === 'enlace') {
            if (!$enlace) $this->error('El enlace es requerido para tipo enlace');
            $urlArchivo = $enlace;
        } else {
            if (empty($_FILES['archivo']['name'])) $this->error('Archivo requerido');
            if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                $this->error('Error en la subida: código ' . $_FILES['archivo']['error']);
            }

            // Whitelist de extensiones por tipo
            $ext    = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $allowed = [
                'pdf'          => ['pdf'],
                'video'        => ['mp4', 'webm', 'mov', 'avi'],
                'presentacion' => ['ppt', 'pptx', 'odp'],
                'otro'         => ['pdf','doc','docx','xls','xlsx','zip','rar',
                                   'txt','mp4','webm','ppt','pptx'],
            ];
            if (!in_array($ext, $allowed[$tipoEnum] ?? $allowed['otro'])) {
                $this->error("Extensión .$ext no permitida para tipo $tipo");
            }

            // Límite 200 MB
            if ($_FILES['archivo']['size'] > 200 * 1024 * 1024) {
                $this->error('El archivo supera el límite de 200 MB');
            }

            $uploadDir = BASE_PATH . '/uploads/materiales/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Nombre único — preserva extensión original
            $nombreArchivo  = 'mat_' . $idAula . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $urlArchivo     = 'uploads/materiales/' . $nombreArchivo;
            $nombreOriginal = $_FILES['archivo']['name'];
            $tamano         = (int)$_FILES['archivo']['size'];

            if (!move_uploaded_file($_FILES['archivo']['tmp_name'], BASE_PATH . '/' . $urlArchivo)) {
                $this->error('No se pudo guardar el archivo en el servidor');
            }
        }

        $db->prepare(
            "INSERT INTO materiales
             (idAula, idProfesor, titulo, descripcion, tipo, urlArchivo, nombreOriginal, tamano)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([$idAula, $this->idProf, $titulo, $descripcion, $tipoEnum,
                    $urlArchivo, $nombreOriginal, $tamano]);

        $idMaterial = (int)$db->lastInsertId();

        // Notificar a estudiantes inscritos
        $idMateria = $db->prepare("SELECT idMateria FROM aulas_virtuales WHERE idAula=?");
        $idMateria->execute([$idAula]);
        $matRow = $idMateria->fetch();
        if ($matRow) {
            $ests = $db->prepare(
                "SELECT u.idUsuario FROM inscripciones i
                 JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
                 JOIN usuarios u ON est.idUsuario = u.idUsuario
                 WHERE i.idMateria = ?"
            );
            $ests->execute([$matRow['idMateria']]);
            foreach ($ests->fetchAll() as $est) {
                notificar(
                    $est['idUsuario'],
                    'Nuevo material publicado',
                    "Se publicó \"$titulo\" en tu aula virtual.",
                    'material',
                    ''
                );
            }
        }

        $mat = $db->prepare("SELECT * FROM materiales WHERE idMaterial = ?");
        $mat->execute([$idMaterial]);
        $this->ok($mat->fetch(), 'Material publicado correctamente');
    }

    // ── POST eliminar_material ────────────────────────────────────────────────
    private function eliminarMaterial(): void {
        $body       = $this->getBody();
        $idMaterial = (int)($body['idMaterial'] ?? 0);
        if (!$idMaterial) $this->error('idMaterial requerido');

        $db = db();
        $check = $db->prepare(
            "SELECT mat.urlArchivo FROM materiales mat
             JOIN aulas_virtuales av ON mat.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             WHERE mat.idMaterial = ? AND m.idProfesor = ?"
        );
        $check->execute([$idMaterial, $this->idProf]);
        $mat = $check->fetch();
        if (!$mat) $this->error('Material no encontrado o sin permiso', 403);

        // Eliminar archivo físico si no es un enlace externo
        if ($mat['urlArchivo'] && !str_starts_with($mat['urlArchivo'], 'http')) {
            $ruta = BASE_PATH . '/' . $mat['urlArchivo'];
            if (file_exists($ruta)) unlink($ruta);
        }

        $db->prepare("DELETE FROM materiales WHERE idMaterial = ?")->execute([$idMaterial]);
        $this->ok(null, 'Material eliminado');
    }

    // ── POST crear_actividad ──────────────────────────────────────────────────
    // Schema v3: actividades(idActividad, idAula, titulo, descripcion,
    //            fechaEntrega, puntaje_max, enunciado, creada_en)
    private function crearActividad(): void {
        $body        = $this->getBody();
        $idAula      = (int)($body['idAula']       ?? 0);
        $titulo      = trim($body['titulo']        ?? '');
        $descripcion = trim($body['descripcion']   ?? '');
        $fechaEnt    = trim($body['fechaEntrega']  ?? '');
        $puntajeMax  = (float)($body['puntaje_max'] ?? 5.0);

        if (!$idAula || !$titulo || !$fechaEnt) {
            $this->error('idAula, titulo y fechaEntrega son obligatorios');
        }

        $ts = strtotime($fechaEnt);
        if (!$ts) $this->error('Formato de fecha inválido. Use: YYYY-MM-DD HH:MM');
        $puntajeMax = max(0.1, min(10.0, $puntajeMax));

        $db = db();
        $check = $db->prepare(
            "SELECT av.idAula FROM aulas_virtuales av
             JOIN materias m ON av.idMateria = m.idMateria
             WHERE av.idAula = ? AND m.idProfesor = ?"
        );
        $check->execute([$idAula, $this->idProf]);
        if (!$check->fetch()) $this->error('Sin acceso a esta aula', 403);

        $db->prepare(
            "INSERT INTO actividades (idAula, titulo, descripcion, fechaEntrega, puntaje_max)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$idAula, $titulo, $descripcion, date('Y-m-d H:i:s', $ts), $puntajeMax]);

        $idActividad = (int)$db->lastInsertId();

        // Notificar estudiantes
        $idMateria = $db->prepare("SELECT idMateria FROM aulas_virtuales WHERE idAula=?");
        $idMateria->execute([$idAula]);
        $matRow = $idMateria->fetch();
        if ($matRow) {
            $ests = $db->prepare(
                "SELECT u.idUsuario FROM inscripciones i
                 JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
                 JOIN usuarios u ON est.idUsuario = u.idUsuario
                 WHERE i.idMateria = ?"
            );
            $ests->execute([$matRow['idMateria']]);
            $fechaFmt = date('d/m/Y H:i', $ts);
            foreach ($ests->fetchAll() as $est) {
                notificar(
                    $est['idUsuario'],
                    'Nueva actividad publicada',
                    "\"$titulo\" — Entrega: $fechaFmt",
                    'tarea',
                    ''
                );
            }
        }

        $act = $db->prepare("SELECT * FROM actividades WHERE idActividad = ?");
        $act->execute([$idActividad]);
        $this->ok($act->fetch(), 'Actividad creada correctamente');
    }

    // ── POST eliminar_actividad ───────────────────────────────────────────────
    private function eliminarActividad(): void {
        $body        = $this->getBody();
        $idActividad = (int)($body['idActividad'] ?? 0);
        if (!$idActividad) $this->error('idActividad requerido');

        $db = db();
        $check = $db->prepare(
            "SELECT act.idActividad FROM actividades act
             JOIN aulas_virtuales av ON act.idAula = av.idAula
             JOIN materias m ON av.idMateria = m.idMateria
             WHERE act.idActividad = ? AND m.idProfesor = ?"
        );
        $check->execute([$idActividad, $this->idProf]);
        if (!$check->fetch()) $this->error('Actividad no encontrada o sin permiso', 403);

        // Eliminar archivos de entregas asociadas
        $entregas = $db->prepare("SELECT archivoEntrega FROM entregas WHERE idActividad = ?");
        $entregas->execute([$idActividad]);
        foreach ($entregas->fetchAll() as $e) {
            if ($e['archivoEntrega']) {
                $ruta = BASE_PATH . '/' . $e['archivoEntrega'];
                if (file_exists($ruta)) unlink($ruta);
            }
        }

        $db->prepare("DELETE FROM entregas    WHERE idActividad = ?")->execute([$idActividad]);
        $db->prepare("DELETE FROM actividades WHERE idActividad = ?")->execute([$idActividad]);
        $this->ok(null, 'Actividad eliminada');
    }

    // ── POST registrar_asistencia ────────────────────────────────────────────
    private function registrarAsistencia(): void {
        $body        = $this->getBody();
        $idMateria   = (int)($body['idMateria'] ?? 0);
        $fecha       = $body['fecha'] ?? date('Y-m-d');
        $idEstudiante= (int)($body['idEstudiante'] ?? 0);
        $asistio     = isset($body['asistio']) ? (int)$body['asistio'] : 0;

        // Soporte para registro individual Y para lista bulk
        if ($idEstudiante) {
            if (!$idMateria) $this->error('idMateria requerido');
            db()->prepare(
                "INSERT INTO asistencia (idMateria,idEstudiante,fecha,asistio)
                 VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE asistio=VALUES(asistio)"
            )->execute([$idMateria, $idEstudiante, $fecha, $asistio]);
            $this->ok(null, 'Asistencia registrada');
        }

        // Modo bulk (lista de {idEstudiante, asistio})
        $lista = $body['lista'] ?? [];
        if (!$idMateria || empty($lista)) $this->error('idMateria y lista requeridos');
        $stmt = db()->prepare(
            "INSERT INTO asistencia (idMateria,idEstudiante,fecha,asistio)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE asistio=VALUES(asistio)"
        );
        foreach ($lista as $item) {
            $stmt->execute([$idMateria, (int)$item['idEstudiante'], $fecha, $item['asistio'] ? 1 : 0]);
        }
        $this->ok(null, 'Asistencia registrada');
    }

    // ── GET asistencia_fecha — asistencia de una clase específica ─────────────
    private function asistenciaFecha(): void {
        $idMateria = (int)($_GET['idMateria'] ?? 0);
        $fecha     = $_GET['fecha'] ?? date('Y-m-d');
        if (!$idMateria) $this->error('idMateria requerido');
        $s = db()->prepare(
            "SELECT a.idEstudiante, a.asistio, a.observacion
             FROM asistencia a
             WHERE a.idMateria = ? AND a.fecha = ?"
        );
        $s->execute([$idMateria, $fecha]);
        $this->ok($s->fetchAll());
    }

    // ── GET notificaciones ────────────────────────────────────────────────────
    private function notificaciones(): void {
        $s = db()->prepare(
            "SELECT * FROM notificaciones
             WHERE idUsuario = ?
             ORDER BY creado_en DESC LIMIT 50"
        );
        $s->execute([$this->idUsuario]);
        $this->ok($s->fetchAll());
    }

    // ── GET materias del profesor ─────────────────────────────────────────────
    private function materiasProfesor(): void {
        $s = db()->prepare(
            "SELECT m.*, COUNT(i.idInscripcion) AS total_inscritos
             FROM materias m
             LEFT JOIN inscripciones i ON m.idMateria = i.idMateria
             WHERE m.idProfesor = ?
             GROUP BY m.idMateria"
        );
        $s->execute([$this->idProf]);
        $this->ok($s->fetchAll());
    }

    // ── POST marcar_notifs ────────────────────────────────────────────────────
    private function marcarNotifs(): void {
        db()->prepare("UPDATE notificaciones SET leida=1 WHERE idUsuario=?")
            ->execute([$this->idUsuario]);
        $this->ok(null, 'Notificaciones marcadas');
    }

    // ── POST marcar_notif (individual) ────────────────────────────────────────
    private function marcarNotifIndividual(): void {
        $body    = $this->getBody();
        $idNotif = (int)($body['idNotif'] ?? 0);
        if (!$idNotif) $this->error('idNotif requerido');
        db()->prepare("UPDATE notificaciones SET leida=1 WHERE idNotif=? AND idUsuario=?")
            ->execute([$idNotif, $this->idUsuario]);
        $this->ok(null, 'Notificación marcada');
    }

    // ── POST actualizar_perfil ────────────────────────────────────────────────
    private function actualizarPerfil(): void {
        $body   = $this->getBody();
        $nombre = trim($body['nombre'] ?? '');
        if (!$nombre) $this->error('Nombre requerido');
        db()->prepare("UPDATE usuarios SET nombre=? WHERE idUsuario=?")
            ->execute([$nombre, $this->idUsuario]);
        $this->ok(null, 'Perfil actualizado');
    }

    // ── POST cambiar_password ─────────────────────────────────────────────────
    private function cambiarPassword(): void {
        $body   = $this->getBody();
        $actual = $body['actual'] ?? '';
        $nueva  = $body['nueva'] ?? '';
        if (strlen($nueva) < 6) $this->error('La nueva contraseña debe tener al menos 6 caracteres');
        $s = db()->prepare("SELECT password FROM usuarios WHERE idUsuario=?");
        $s->execute([$this->idUsuario]);
        $row = $s->fetch();
        if (!$row || !password_verify($actual, $row['password'])) {
            $this->error('La contraseña actual es incorrecta');
        }
        db()->prepare("UPDATE usuarios SET password=? WHERE idUsuario=?")
            ->execute([password_hash($nueva, PASSWORD_BCRYPT), $this->idUsuario]);
        $this->ok(null, 'Contraseña actualizada');
    }
}

(new ProfesorController())->handle();
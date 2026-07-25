<?php
/**
 * VideoLlamadaController — api/v1/videollamadas.php
 * 
 * SCRUM-119: Refactorización de script procedimental a clase POO.
 * Extiende Controller (Template Method pattern) igual que todos
 * los demás controladores del sistema.
 * 
 * ENDPOINTS:
 *   GET  ?action=lista[&idMateria=N]   → Lista videollamadas
 *   GET  ?action=activa&idMateria=N    → Videollamada activa de una materia
 *   POST (JSON) action=crear           → Programar nueva videollamada
 *   POST (JSON) action=finalizar       → Marcar videollamada como finalizada
 *   POST (JSON) action=eliminar        → Eliminar videollamada
 *   POST (multipart) action=subir_grabacion → Subir archivo de grabación (SCRUM-120)
 * 
 * FIX BUG-004 (mantenido): $action se lee también del JSON body
 * porque fetch() de React envía Content-Type: application/json
 * y PHP no popula $_POST con JSON body.
 */

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../../config/config.php';

class VideoLlamadaController extends Controller {

    private int $idUsuario;
    private string $rol;
    private ?int $idProfesor = null;

    public function __construct() {
        // Leer el JSON body UNA VEZ aquí antes de que Controller lo necesite.
        // php://input solo se puede leer una vez por request.
        if (empty($this->_jsonBody)) {
            $this->_jsonBody = json_decode(
                file_get_contents('php://input'), true
            ) ?? [];
        }
    }

    /**
     * Punto de entrada del controlador.
     * Template Method: CORS → auth → enrutar → respuesta JSON.
     */
    public function handle(): void {
        // FIX BUG-004: $action lee de GET, POST y del JSON body
        $action = $_GET['action']
               ?? $_POST['action']
               ?? ($this->_jsonBody['action'] ?? '');

        // Todos los endpoints requieren sesión activa
        $session = $this->requireAuth(null); // null = cualquier rol autenticado
        $this->idUsuario = (int)$session['idUsuario'];
        $this->rol       = $session['rol'];

        // Cargar idProfesor si el usuario tiene ese rol
        if ($this->rol === 'profesor') {
            $this->idProfesor = $this->resolverIdProfesor($this->idUsuario);
        }

        switch ($action) {
            case 'lista':
                $this->lista();
                break;
            case 'activa':
                $this->activa();
                break;
            case 'crear':
                $this->crear();
                break;
            case 'finalizar':
                $this->finalizar();
                break;
            case 'eliminar':
                $this->eliminar();
                break;
            case 'subir_grabacion':
                $this->subirGrabacion();
                break;
            default:
                $this->error('Acción no válida: ' . htmlspecialchars($action), 400);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  GET ?action=lista[&idMateria=N]
    //  Admin ve todas. Profesor y estudiante ven solo su materia.
    // ──────────────────────────────────────────────────────────────────────
    private function lista(): void {
        $idMateria = isset($_GET['idMateria']) ? (int)$_GET['idMateria'] : null;

        // Admin sin filtro → ve todo el sistema
        if ($this->rol === 'admin' && !$idMateria) {
            $stmt = db()->query(
                'SELECT vl.*,
                        m.nombre AS materia,
                        m.codigo AS codigoMateria,
                        CONCAT(u.nombre) AS profesor,
                        TIMESTAMPDIFF(MINUTE, NOW(), vl.programada_en) AS minutosParaInicio
                 FROM videollamadas vl
                 JOIN materias  m  ON m.idMateria  = vl.idMateria
                 JOIN profesores p ON p.idProfesor = vl.idProfesor
                 JOIN usuarios   u ON u.idUsuario  = p.idUsuario
                 ORDER BY vl.programada_en DESC'
            );
        } elseif ($idMateria) {
            $stmt = db()->prepare(
                'SELECT vl.*,
                        m.nombre AS materia,
                        m.codigo AS codigoMateria,
                        CONCAT(u.nombre) AS profesor,
                        TIMESTAMPDIFF(MINUTE, NOW(), vl.programada_en) AS minutosParaInicio
                 FROM videollamadas vl
                 JOIN materias  m  ON m.idMateria  = vl.idMateria
                 JOIN profesores p ON p.idProfesor = vl.idProfesor
                 JOIN usuarios   u ON u.idUsuario  = p.idUsuario
                 WHERE vl.idMateria = ?
                 ORDER BY vl.programada_en DESC'
            );
            $stmt->execute([$idMateria]);
        } else {
            $this->error('Debe especificar idMateria o ser admin', 400);
            return;
        }

        $videollamadas = $stmt->fetchAll();
        
        // Agregar grabaciones a cada videollamada
        foreach ($videollamadas as &$vl) {
            $stmtGrab = db()->prepare(
                'SELECT * FROM grabaciones_videollamadas
                 WHERE idVideoLlamada = ?
                 ORDER BY creada_en DESC'
            );
            $stmtGrab->execute([(int)$vl['idVideoLlamada']]);
            $vl['grabaciones'] = $stmtGrab->fetchAll();
            
            // Agregar jitsiUrl para compatibilidad
            $vl['jitsiUrl'] = 'https://meet.jit.si/' . $vl['roomName'];
        }

        $this->ok($videollamadas, 'Lista de videollamadas');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  GET ?action=activa&idMateria=N
    //  Retorna la videollamada en_curso de una materia (si existe)
    // ──────────────────────────────────────────────────────────────────────
    private function activa(): void {
        $idMateria = (int)($_GET['idMateria'] ?? 0);
        if (!$idMateria) {
            $this->error('idMateria requerido', 400);
            return;
        }

        $stmt = db()->prepare(
            'SELECT * FROM videollamadas
             WHERE idMateria = ? AND estado = "en_curso"
             LIMIT 1'
        );
        $stmt->execute([$idMateria]);
        $vl = $stmt->fetch() ?: null;

        $this->ok($vl, $vl ? 'Videollamada activa encontrada' : 'Sin videollamada activa');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  POST (JSON) action=crear
    //  Solo profesores y admin pueden crear videollamadas.
    // ──────────────────────────────────────────────────────────────────────
    private function crear(): void {
        if (!in_array($this->rol, ['profesor', 'admin'])) {
            $this->error('Solo profesores pueden programar videollamadas', 403);
            return;
        }

        $body       = $this->_jsonBody;
        $titulo     = trim($body['titulo']         ?? '');
        $idMateria  = (int)($body['idMateria']     ?? 0);
        $fecha      = trim($body['programada_en']  ?? '');
        $duracion   = (int)($body['duracion_min']  ?? 60);

        if (!$titulo || !$idMateria || !$fecha) {
            $this->error('Campos requeridos: titulo, idMateria, programada_en', 422);
            return;
        }

        // Validar rango de duración
        $duracion = max(15, min(480, $duracion));

        // Resolver idProfesor (admin crea en nombre de la materia)
        $idProfesor = $this->idProfesor;
        if ($this->rol === 'admin') {
            $stmt = db()->prepare(
                'SELECT idProfesor FROM materias WHERE idMateria = ?'
            );
            $stmt->execute([$idMateria]);
            $mat = $stmt->fetch();
            $idProfesor = $mat ? (int)$mat['idProfesor'] : null;
        }

        if (!$idProfesor) {
            $this->error('No se pudo determinar el profesor de la materia', 422);
            return;
        }

        // Generar roomName único: univirtual-{codigoMateria}-{fecha}-{hora}
        $stmt = db()->prepare('SELECT codigo FROM materias WHERE idMateria = ?');
        $stmt->execute([$idMateria]);
        $mat = $stmt->fetch();
        $codigo = $mat ? strtolower(preg_replace('/\s+/', '', $mat['codigo'])) : 'mat';
        $ts     = strtotime($fecha);
        $roomName = 'univirtual-' . $codigo . '-' . date('Ymd', $ts) . '-' . date('Hi', $ts);

        // Insertar videollamada
        $stmt = db()->prepare(
            'INSERT INTO videollamadas
                (idMateria, idProfesor, titulo, roomName, programada_en, duracion_min, estado, activa)
             VALUES (?, ?, ?, ?, ?, ?, "programada", 1)'
        );
        $stmt->execute([$idMateria, $idProfesor, $titulo, $roomName, $fecha, $duracion]);
        $idVL = (int)db()->lastInsertId();

        // Notificar a estudiantes inscritos (FIX 1)
        $inscritos = db()->prepare(
            'SELECT u.idUsuario FROM inscripciones i
             JOIN estudiantes e ON i.idEstudiante = e.idEstudiante
             JOIN usuarios u ON e.idUsuario = u.idUsuario
             WHERE i.idMateria = ?'
        );
        $inscritos->execute([$idMateria]);
        $stmtNotif = db()->prepare(
            'INSERT INTO notificaciones
             (idUsuario, tipo, titulo, mensaje, url, creada_en)
             VALUES (?, "videollamada", ?, ?, "#", NOW())'
        );
        foreach ($inscritos->fetchAll() as $est) {
            $stmtNotif->execute([
                $est['idUsuario'],
                'Nueva clase virtual: ' . $titulo,
                'El profesor programó una clase para el ' . $fecha
            ]);
        }

        $this->ok([
            'idVideoLlamada' => $idVL,
            'roomName'       => $roomName,
            'urlJitsi'       => "https://meet.jit.si/{$roomName}",
        ], 'Videollamada creada correctamente');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  POST (JSON) action=finalizar
    //  Marca la videollamada como finalizada.
    // ──────────────────────────────────────────────────────────────────────
    private function finalizar(): void {
        if (!in_array($this->rol, ['profesor', 'admin'])) {
            $this->error('Sin permisos para finalizar videollamadas', 403);
            return;
        }

        $idVL = (int)($this->_jsonBody['idVideoLlamada'] ?? 0);
        if (!$idVL) {
            $this->error('idVideoLlamada requerido', 422);
            return;
        }

        $stmt = db()->prepare(
            'UPDATE videollamadas
             SET estado = "finalizada", activa = 0
             WHERE idVideoLlamada = ?'
        );
        $stmt->execute([$idVL]);

        $this->ok(['idVideoLlamada' => $idVL], 'Videollamada finalizada');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  POST (JSON) action=eliminar
    //  Solo admin o el profesor dueño pueden eliminar.
    // ──────────────────────────────────────────────────────────────────────
    private function eliminar(): void {
        $idVL = (int)($this->_jsonBody['idVideoLlamada'] ?? 0);
        if (!$idVL) {
            $this->error('idVideoLlamada requerido', 422);
            return;
        }

        // Verificar propiedad si no es admin
        if ($this->rol !== 'admin' && $this->idProfesor) {
            $stmt = db()->prepare(
                'SELECT idProfesor FROM videollamadas WHERE idVideoLlamada = ?'
            );
            $stmt->execute([$idVL]);
            $vl = $stmt->fetch();
            if (!$vl || (int)$vl['idProfesor'] !== $this->idProfesor) {
                $this->error('No tienes permisos para eliminar esta videollamada', 403);
                return;
            }
        }

        $stmt = db()->prepare(
            'DELETE FROM videollamadas WHERE idVideoLlamada = ?'
        );
        $stmt->execute([$idVL]);

        $this->ok(['idVideoLlamada' => $idVL], 'Videollamada eliminada');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  POST (multipart) action=subir_grabacion  — SCRUM-120
    //  Sube el archivo MP4 de la grabación de una videollamada finalizada.
    // ──────────────────────────────────────────────────────────────────────
    private function subirGrabacion(): void {
        if (!in_array($this->rol, ['profesor', 'admin'])) {
            $this->error('Solo profesores pueden subir grabaciones', 403);
            return;
        }

        $idVL = (int)($_POST['idVideoLlamada'] ?? 0);
        if (!$idVL) {
            $this->error('idVideoLlamada requerido', 422);
            return;
        }

        if (empty($_FILES['grabacion'])) {
            $this->error('No se recibió ningún archivo (campo: grabacion)', 422);
            return;
        }

        $file   = $_FILES['grabacion'];
        $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $maxMB  = 500;

        if ($ext !== 'mp4') {
            $this->error('Solo se permiten archivos .mp4 para grabaciones', 422);
            return;
        }

        if ($file['size'] > $maxMB * 1024 * 1024) {
            $this->error("El archivo supera el límite de {$maxMB} MB", 422);
            return;
        }

        // Guardar con nombre único
        $nombreArchivo = 'grabacion_' . $idVL . '_' . time() . '.mp4';
        $destino = dirname(__DIR__, 2) . '/uploads/grabaciones/' . $nombreArchivo;

        if (!is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            $this->error('Error al guardar el archivo en el servidor', 500);
            return;
        }

        $urlGrabacion = BASE_URL . '/uploads/grabaciones/' . $nombreArchivo;
        $tamanoMB     = round($file['size'] / (1024 * 1024), 2);
        $nombreOriginal = $file['name'];

        // Insertar en la tabla grabaciones_videollamadas (SCRUM-120)
        $stmt = db()->prepare(
            'INSERT INTO grabaciones_videollamadas
                (idVideoLlamada, urlGrabacion, duracion_seg, tamano_mb, nombre, subida_por)
             VALUES (?, ?, 0, ?, ?, ?)'
        );
        $stmt->execute([$idVL, $urlGrabacion, $tamanoMB, $nombreOriginal, $this->idUsuario]);

        $this->ok([
            'idVideoLlamada' => $idVL,
            'urlGrabacion'   => $urlGrabacion,
            'tamano_mb'      => $tamanoMB,
        ], 'Grabación subida correctamente');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Helpers privados
    // ──────────────────────────────────────────────────────────────────────
    private function resolverIdProfesor(int $idUsuario): ?int {
        $stmt = db()->prepare(
            'SELECT idProfesor FROM profesores WHERE idUsuario = ?'
        );
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch();
        return $row ? (int)$row['idProfesor'] : null;
    }
}

// ── Punto de entrada ─────────────────────────────────────────────────────
(new VideoLlamadaController())->handle();
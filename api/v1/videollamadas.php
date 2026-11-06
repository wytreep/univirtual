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
    private array $_jsonBody = [];

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
                        TIMESTAMPDIFF(MINUTE, NOW(), vl.programada_en) AS minutosParaInicio,
                        CASE
                          WHEN vl.estado = "finalizada" THEN "finalizada"
                          WHEN NOW() < vl.programada_en THEN "programada"
                          WHEN NOW() <= DATE_ADD(vl.programada_en, INTERVAL vl.duracion_min MINUTE) THEN "en_curso"
                          ELSE "finalizada"
                        END AS estadoCalculado,
                        CASE WHEN vl.estado <> "finalizada"
                                  AND NOW() >= DATE_SUB(vl.programada_en, INTERVAL 10 MINUTE)
                                  AND NOW() <= DATE_ADD(vl.programada_en, INTERVAL vl.duracion_min MINUTE)
                             THEN 1 ELSE 0 END AS puedeUnirse
                 FROM videollamadas vl
                 JOIN materias  m  ON m.idMateria  = vl.idMateria
                 JOIN profesores p ON p.idProfesor = vl.idProfesor
                 JOIN usuarios   u ON u.idUsuario  = p.idUsuario
                 ORDER BY vl.programada_en DESC'
            );
        } elseif ($idMateria) {
            // Un profesor solo ve sus materias; un estudiante solo las inscritas.
            if ($this->rol === 'profesor') {
                $own = db()->prepare('SELECT 1 FROM materias WHERE idMateria = ? AND idProfesor = ?');
                $own->execute([$idMateria, $this->idProfesor]);
                if (!$own->fetch()) { $this->error('Sin acceso a esta materia', 403); return; }
            } elseif ($this->rol === 'estudiante') {
                $own = db()->prepare(
                    'SELECT 1 FROM inscripciones i
                     JOIN estudiantes e ON i.idEstudiante = e.idEstudiante
                     WHERE e.idUsuario = ? AND i.idMateria = ?'
                );
                $own->execute([$this->idUsuario, $idMateria]);
                if (!$own->fetch()) { $this->error('No estás inscrito en esta materia', 403); return; }
            }

            $stmt = db()->prepare(
                'SELECT vl.*,
                        m.nombre AS materia,
                        m.codigo AS codigoMateria,
                        CONCAT(u.nombre) AS profesor,
                        TIMESTAMPDIFF(MINUTE, NOW(), vl.programada_en) AS minutosParaInicio,
                        CASE
                          WHEN vl.estado = "finalizada" THEN "finalizada"
                          WHEN NOW() < vl.programada_en THEN "programada"
                          WHEN NOW() <= DATE_ADD(vl.programada_en, INTERVAL vl.duracion_min MINUTE) THEN "en_curso"
                          ELSE "finalizada"
                        END AS estadoCalculado,
                        CASE WHEN vl.estado <> "finalizada"
                                  AND NOW() >= DATE_SUB(vl.programada_en, INTERVAL 10 MINUTE)
                                  AND NOW() <= DATE_ADD(vl.programada_en, INTERVAL vl.duracion_min MINUTE)
                             THEN 1 ELSE 0 END AS puedeUnirse
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

        // La sala está disponible desde 10 minutos antes del inicio y hasta
        // que termina la duración programada. Se calcula con NOW() de MySQL,
        // que es la misma zona horaria usada al programar la clase.
        $stmt = db()->prepare(
            'SELECT *, "en_curso" AS estadoCalculado, 1 AS puedeUnirse
               FROM videollamadas
              WHERE idMateria = ?
                AND estado <> "finalizada"
                AND activa = 1
                AND NOW() >= DATE_SUB(programada_en, INTERVAL 10 MINUTE)
                AND NOW() <= DATE_ADD(programada_en, INTERVAL duracion_min MINUTE)
              ORDER BY programada_en ASC
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
        // Regla de negocio: la clase la programa quien la dicta.
        if ($this->rol !== 'profesor' || !$this->idProfesor) {
            $this->error('Solo los profesores pueden programar videollamadas', 403);
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

        // La materia debe estar asignada al profesor autenticado.
        $stmt = db()->prepare(
            'SELECT codigo FROM materias WHERE idMateria = ? AND idProfesor = ?'
        );
        $stmt->execute([$idMateria, $this->idProfesor]);
        $mat = $stmt->fetch();
        if (!$mat) {
            $this->error('La materia no existe o no está asignada a tu usuario', 403);
            return;
        }

        // Validar rango de duración
        $duracion = max(15, min(480, $duracion));

        // Fecha válida y futura (misma zona horaria que MySQL: America/Bogota).
        $ts = strtotime($fecha);
        if ($ts === false) {
            $this->error('La fecha y hora no tienen un formato válido', 422);
            return;
        }
        if ($ts <= time()) {
            $this->error('La videollamada debe programarse para una fecha y hora futuras', 422);
            return;
        }
        $fechaSql = date('Y-m-d H:i:s', $ts);

        // roomName único: si ya existe una sala para esa materia y hora,
        // se agrega un sufijo numérico en lugar de fallar por el índice UNIQUE.
        $codigo   = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)$mat['codigo']));
        $base     = 'univirtual-' . ($codigo !== '' ? $codigo : 'mat') . '-' . date('Ymd-Hi', $ts);
        $roomName = $base;
        $existe   = db()->prepare('SELECT 1 FROM videollamadas WHERE roomName = ?');
        for ($i = 2; $i <= 50; $i++) {
            $existe->execute([$roomName]);
            if (!$existe->fetch()) break;
            $roomName = $base . '-' . $i;
        }
        $existe->execute([$roomName]);
        if ($existe->fetch()) {
            $this->error('Ya existe una sala para esa fecha y hora. Elige otra hora.', 409);
            return;
        }

        $db = db();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                'INSERT INTO videollamadas
                    (idMateria, idProfesor, titulo, roomName, programada_en, duracion_min, estado, activa)
                 VALUES (?, ?, ?, ?, ?, ?, "programada", 1)'
            );
            $stmt->execute([$idMateria, $this->idProfesor, $titulo, $roomName, $fechaSql, $duracion]);
            $idVL = (int)$db->lastInsertId();

            // Notificar a los estudiantes inscritos.
            // La columna real es `creado_en` (no `creada_en`).
            $inscritos = $db->prepare(
                'SELECT u.idUsuario FROM inscripciones i
                 JOIN estudiantes e ON i.idEstudiante = e.idEstudiante
                 JOIN usuarios u ON e.idUsuario = u.idUsuario
                 WHERE i.idMateria = ? AND u.activo = 1'
            );
            $inscritos->execute([$idMateria]);
            $stmtNotif = $db->prepare(
                'INSERT INTO notificaciones
                    (idUsuario, tipo, titulo, mensaje, url, creado_en)
                 VALUES (?, "videollamada", ?, ?, ?, NOW())'
            );
            $fechaLegible = date('d/m/Y H:i', $ts);
            $urlAula      = BASE_URL . '/pages/estudiante/dashboard.php';
            foreach ($inscritos->fetchAll() as $est) {
                $stmtNotif->execute([
                    (int)$est['idUsuario'],
                    'Nueva clase virtual: ' . $titulo,
                    'Tu profesor programó una videollamada para el ' . $fechaLegible . '.',
                    $urlAula,
                ]);
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[videollamadas.crear] ' . $e->getMessage());
            $this->error('No se pudo crear la videollamada. Intenta de nuevo.', 500);
            return;
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

        // Un profesor solo puede finalizar sus propias clases.
        if ($this->rol === 'profesor') {
            $own = db()->prepare(
                'SELECT 1 FROM videollamadas WHERE idVideoLlamada = ? AND idProfesor = ?'
            );
            $own->execute([$idVL, $this->idProfesor]);
            if (!$own->fetch()) {
                $this->error('No tienes permisos para finalizar esta videollamada', 403);
                return;
            }
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
        if (!in_array($this->rol, ['profesor', 'admin'])) {
            $this->error('Sin permisos para eliminar videollamadas', 403);
            return;
        }

        $idVL = (int)($this->_jsonBody['idVideoLlamada'] ?? 0);
        if (!$idVL) {
            $this->error('idVideoLlamada requerido', 422);
            return;
        }

        // Verificar propiedad si no es admin
        if ($this->rol !== 'admin') {
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
        if ($this->rol !== 'profesor' || !$this->idProfesor) {
            $this->error('Solo los profesores pueden subir grabaciones', 403);
            return;
        }

        $idVL = (int)($_POST['idVideoLlamada'] ?? 0);
        if (!$idVL) {
            $this->error('idVideoLlamada requerido', 422);
            return;
        }

        // La grabación solo puede subirse a una clase propia.
        $own = db()->prepare(
            'SELECT 1 FROM videollamadas WHERE idVideoLlamada = ? AND idProfesor = ?'
        );
        $own->execute([$idVL, $this->idProfesor]);
        if (!$own->fetch()) {
            $this->error('No tienes permisos para subir grabaciones de esta videollamada', 403);
            return;
        }

        if (empty($_FILES['grabacion']) || ($_FILES['grabacion']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->error('No se recibió ningún archivo (campo: grabacion)', 422);
            return;
        }

        $file = $_FILES['grabacion'];
        $maxMB = 200;

        // Mensajes claros para los errores nativos de PHP.
        $errores = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite de subida del servidor (' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el tamaño permitido por el formulario.',
            UPLOAD_ERR_PARTIAL    => 'La subida se interrumpió. Intenta de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene directorio temporal configurado.',
            UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir el archivo. Revisa permisos de la carpeta uploads.',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP bloqueó la subida del archivo.',
        ];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->error($errores[$file['error']] ?? 'No se pudo recibir el archivo (código ' . $file['error'] . ').', 422);
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
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

        if (!is_dir(dirname($destino)) && !mkdir(dirname($destino), 0755, true)) {
            $this->error('No existe la carpeta uploads/grabaciones y no se pudo crear', 500);
            return;
        }

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            $this->error('Error al guardar el archivo en el servidor', 500);
            return;
        }

        $urlGrabacion = BASE_URL . '/uploads/grabaciones/' . $nombreArchivo;
        $tamanoMB     = round($file['size'] / (1024 * 1024), 2);

        // Insertar en la tabla grabaciones_videollamadas (SCRUM-120).
        // La tabla no tiene columna `nombre`; el nombre original se conserva
        // en el nombre del archivo y se registra el tamaño real.
        $stmt = db()->prepare(
            'INSERT INTO grabaciones_videollamadas
                (idVideoLlamada, urlGrabacion, duracion_seg, tamano_mb, subida_por)
             VALUES (?, ?, 0, ?, ?)'
        );
        $stmt->execute([$idVL, $urlGrabacion, $tamanoMB, $this->idUsuario]);

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
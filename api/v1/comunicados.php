<?php
require_once __DIR__.'/Controller.php';

/**
 * ComunicadosController — api/v1/comunicados.php
 *
 * UV-19: mensajería directa del directivo a estudiantes y profesores.
 * No usa la tabla `mensajes` (esa es del foro por aula): el aviso se
 * guarda en `comunicados` y se distribuye en `comunicado_destinatarios`.
 *
 * Acciones GET:
 *   ?action=bandeja  → estudiante/profesor: comunicados recibidos
 *   ?action=enviados → directivo: comunicados enviados con leídos/total
 *
 * Acciones POST (JSON):
 *   { action:'enviar', publico, asunto, contenido } → directivo
 *   { action:'marcar_leido', idComunicado }          → estudiante/profesor
 *
 * Reglas:
 *   - El directivo solo envía a la facultad a la que pertenece.
 *   - El público se resuelve con estudiantes (vía carreras) y profesores.
 *   - Cada destinatario recibe además una notificación (reutiliza UV-18).
 */
class ComunicadosController extends Controller {

    private const MAX_ASUNTO    = 150;
    private const MAX_CONTENIDO = 5000;

    public function handle(): void {
        $session = $this->requireAuth(['estudiante', 'profesor', 'directivo']);
        $rol     = $session['rol'];

        if ($this->method() === 'GET') {
            $action = $_GET['action'] ?? 'bandeja';

            if ($action === 'bandeja' && in_array($rol, ['estudiante', 'profesor'], true)) {
                $this->bandeja($session);
                return;
            }
            if ($action === 'enviados' && $rol === 'directivo') {
                $this->enviados($session);
                return;
            }

            $this->error('Acción no válida', 400);
            return;
        }

        if ($this->method() === 'POST') {
            $body   = $this->getBody();
            $action = $body['action'] ?? '';

            if ($action === 'enviar' && $rol === 'directivo') {
                $this->enviar($session, $body);
                return;
            }
            if ($action === 'marcar_leido' && in_array($rol, ['estudiante', 'profesor'], true)) {
                $this->marcarLeido($session, $body);
                return;
            }

            $this->error('Acción no válida', 400);
            return;
        }

        $this->error('Método no permitido', 405);
    }

    // ── GET ──────────────────────────────────────────────────────────

    private function bandeja(array $session): void {
        $stmt = db()->prepare(
            "SELECT c.idComunicado, c.asunto, c.contenido, c.publico,
                    c.creado_en, c.idFacultad,
                    u.nombre AS remitente,
                    f.nombre AS facultad,
                    d.leido, d.leido_en
               FROM comunicado_destinatarios d
               JOIN comunicados c ON c.idComunicado = d.idComunicado
               JOIN usuarios u    ON u.idUsuario = c.idRemitente
               JOIN facultades f  ON f.idFacultad = c.idFacultad
              WHERE d.idUsuario = ?
              ORDER BY c.creado_en DESC"
        );
        $stmt->execute([(int)$session['idUsuario']]);
        $mensajes = $stmt->fetchAll();

        $this->ok([
            'mensajes' => $mensajes,
            'total'    => count($mensajes),
            'noLeidos' => count(array_filter($mensajes, fn($m) => !(int)$m['leido'])),
        ]);
    }

    private function enviados(array $session): void {
        $stmt = db()->prepare(
            "SELECT c.idComunicado, c.asunto, c.contenido, c.publico, c.creado_en,
                    (SELECT COUNT(*) FROM comunicado_destinatarios d
                      WHERE d.idComunicado = c.idComunicado) AS destinatarios,
                    (SELECT COUNT(*) FROM comunicado_destinatarios d
                      WHERE d.idComunicado = c.idComunicado AND d.leido = 1) AS leidos
               FROM comunicados c
              WHERE c.idRemitente = ?
              ORDER BY c.creado_en DESC"
        );
        $stmt->execute([(int)$session['idUsuario']]);
        $this->ok($stmt->fetchAll());
    }

    // ── POST ─────────────────────────────────────────────────────────

    private function enviar(array $session, array $body): void {
        $asunto    = trim($body['asunto'] ?? '');
        $contenido = trim($body['contenido'] ?? '');
        $publico   = $body['publico'] ?? 'ambos';

        if ($asunto === '' || $contenido === '') {
            $this->error('El asunto y el mensaje son obligatorios', 422);
            return;
        }
        if (mb_strlen($asunto) > self::MAX_ASUNTO) {
            $this->error('El asunto no puede superar 150 caracteres', 422);
            return;
        }
        if (mb_strlen($contenido) > self::MAX_CONTENIDO) {
            $this->error('El mensaje no puede superar 5000 caracteres', 422);
            return;
        }
        if (!in_array($publico, ['estudiantes', 'profesores', 'ambos'], true)) {
            $this->error('Público no válido', 422);
            return;
        }

        $db = db();

        $dir = $db->prepare("SELECT idFacultad FROM directivos WHERE idUsuario = ?");
        $dir->execute([(int)$session['idUsuario']]);
        $idFacultad = (int)$dir->fetchColumn();

        if ($idFacultad <= 0) {
            $this->error('El directivo no tiene facultad asignada', 403);
            return;
        }

        $porRol = ['estudiante' => [], 'profesor' => []];

        if ($publico === 'estudiantes' || $publico === 'ambos') {
            $est = $db->prepare(
                "SELECT DISTINCT u.idUsuario
                   FROM estudiantes e
                   JOIN usuarios u  ON u.idUsuario = e.idUsuario
                   JOIN carreras c  ON c.idCarrera = e.idCarrera
                  WHERE c.idFacultad = ? AND u.activo = 1 AND u.rol = 'estudiante'"
            );
            $est->execute([$idFacultad]);
            $porRol['estudiante'] = array_map('intval', $est->fetchAll(PDO::FETCH_COLUMN));
        }

        if ($publico === 'profesores' || $publico === 'ambos') {
            $prof = $db->prepare(
                "SELECT DISTINCT u.idUsuario
                   FROM profesores p
                   JOIN usuarios u ON u.idUsuario = p.idUsuario
                  WHERE p.idFacultad = ? AND u.activo = 1 AND u.rol = 'profesor'"
            );
            $prof->execute([$idFacultad]);
            $porRol['profesor'] = array_map('intval', $prof->fetchAll(PDO::FETCH_COLUMN));
        }

        $destinatarios = array_values(array_unique(array_merge($porRol['estudiante'], $porRol['profesor'])));

        if (!$destinatarios) {
            $this->error('No hay destinatarios activos en tu facultad para ese público', 422);
            return;
        }

        try {
            $db->beginTransaction();

            $ins = $db->prepare(
                "INSERT INTO comunicados (idRemitente, idFacultad, publico, asunto, contenido)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $ins->execute([(int)$session['idUsuario'], $idFacultad, $publico, $asunto, $contenido]);
            $idComunicado = (int)$db->lastInsertId();

            $dest = $db->prepare(
                "INSERT INTO comunicado_destinatarios (idComunicado, idUsuario) VALUES (?, ?)"
            );
            foreach ($destinatarios as $idUsuario) {
                $dest->execute([$idComunicado, $idUsuario]);
            }

            $preview = mb_strlen($contenido) > 180
                ? mb_substr($contenido, 0, 177) . '...'
                : $contenido;

            foreach ($porRol as $rolDestino => $ids) {
                if (!$ids) {
                    continue;
                }
                notificar(
                    $ids,
                    'Comunicado: ' . $asunto,
                    $preview,
                    'info',
                    BASE_URL . '/pages/' . $rolDestino . '/dashboard.php?view=mensajes'
                );
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->error('No se pudo enviar el comunicado. Intenta de nuevo.', 500);
            return;
        }

        $this->ok([
            'idComunicado' => $idComunicado,
            'destinatarios' => count($destinatarios),
            'publico'      => $publico,
        ], 'Comunicado enviado a ' . count($destinatarios) . ' destinatario(s)');
    }

    private function marcarLeido(array $session, array $body): void {
        $idComunicado = (int)($body['idComunicado'] ?? 0);
        if ($idComunicado <= 0) {
            $this->error('Comunicado no válido', 422);
            return;
        }

        $stmt = db()->prepare(
            "UPDATE comunicado_destinatarios
                SET leido = 1, leido_en = NOW()
              WHERE idComunicado = ? AND idUsuario = ? AND leido = 0"
        );
        $stmt->execute([$idComunicado, (int)$session['idUsuario']]);

        $this->ok(['idComunicado' => $idComunicado], 'Comunicado marcado como leído');
    }
}

(new ComunicadosController())->handle();

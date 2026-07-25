<?php
require_once __DIR__.'/Controller.php';

class NotificacionesController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $session = $this->requireAuth();
        $idUsuario = (int)$session['idUsuario'];
        $body = $this->getBody();
        $action = $_GET['action'] ?? $_POST['action'] ?? ($body['action'] ?? '');

        if ($this->method() === 'GET') {
            $s = db()->prepare("SELECT * FROM notificaciones WHERE idUsuario = ? ORDER BY creado_en DESC LIMIT 50");
            $s->execute([$idUsuario]);
            $this->ok($s->fetchAll());
        } elseif ($this->method() === 'POST') {
            if ($action === 'marcar_todas') {
                db()->prepare("UPDATE notificaciones SET leida=1 WHERE idUsuario=?")->execute([$idUsuario]);
                $this->ok(null, 'Marcadas');
            } elseif ($action === 'marcar_una') {
                $idNotif = (int)($body['idNotif'] ?? 0);
                if ($idNotif) {
                    db()->prepare("UPDATE notificaciones SET leida=1 WHERE idNotif=? AND idUsuario=?")->execute([$idNotif, $idUsuario]);
                }
                $this->ok(null, 'Marcada');
            } else {
                $this->error('Acción no válida');
            }
        } else {
            $this->error('Método no permitido', 405);
        }
    }
}
(new NotificacionesController())->handle();

<?php
require_once __DIR__.'/Controller.php';

class ConfiguracionController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->requireAuth(['admin']);
        
        switch($this->method()) {
            case 'GET': $this->getAll(); break;
            case 'PUT': $this->updateAll(); break;
            default: $this->error('Método no permitido', 405);
        }
    }

    private function getAll(): void {
        try {
            $stmt = db()->query("SELECT clave, valor, tipo, descripcion FROM configuracion ORDER BY clave ASC");
            $this->ok($stmt->fetchAll());
        } catch(Exception $e) {
            $this->ok([]);
        }
    }

    private function updateAll(): void {
        $body = $this->getBody();
        if (!isset($body['configs']) || !is_array($body['configs'])) {
            $this->error('Formato inválido');
        }

        $db = db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
            foreach ($body['configs'] as $c) {
                if (isset($c['clave']) && isset($c['valor'])) {
                    $stmt->execute([$c['valor'], $c['clave']]);
                }
            }
            $db->commit();
            $this->ok(null, 'Configuración actualizada');
        } catch (Exception $e) {
            $db->rollBack();
            $this->error('Error al guardar configuración');
        }
    }
}

(new ConfiguracionController())->handle();

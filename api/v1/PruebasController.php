<?php
require_once __DIR__.'/Controller.php';
require_once dirname(__DIR__, 2).'/models/Prueba.php';

/**
 * PruebasController — SCRUM-129
 * SCRUM-130: hardening — require explícito del modelo (no depender solo del autoload).
 * Recibe y sirve resultados de la suite Selenium/pytest.
 *
 * GET  ?action=lista    → últimos 50 resultados
 * GET  ?action=resumen  → totales PASSED/FAILED/ERROR
 * POST action=registrar → inserta un resultado (llamado desde conftest.py)
 * POST action=limpiar   → limpia la tabla (solo admin)
 */
class PruebasController extends Controller {

    private Prueba $modelo;

    public function __construct() {
        parent::__construct();
        $this->modelo = new Prueba();
    }

    public function handle(): void {
        $body   = $this->getBody();
        $action = $_GET['action'] ?? $body['action'] ?? '';

        if ($this->method() === 'GET') {
            match($action) {
                'lista'   => $this->lista(),
                'resumen' => $this->resumen(),
                default   => $this->error('Acción GET no válida', 400)
            };
            return;
        }

        match($this->method()) {
            'POST'  => match($action) {
                'registrar' => $this->registrar($body),
                'limpiar'   => $this->limpiar(),
                default     => $this->error('Acción POST no válida', 400)
            },
            default => $this->error('Método no permitido', 405)
        };
    }

    private function lista(): void {
        $this->requireAuth(['admin']);
        $limite = intval($_GET['limite'] ?? 50);
        $this->ok($this->modelo->getLista($limite));
    }

    private function resumen(): void {
        $this->requireAuth(['admin']);
        $this->ok($this->modelo->getResumen());
    }

    private function registrar(array $body): void {
        // Sin requireAuth — lo llama conftest.py desde localhost
        $nombre = trim($body['nombre_test']  ?? '');
        $clase  = trim($body['clase_test']   ?? 'General');
        $estado = strtoupper(trim($body['estado'] ?? ''));
        $dur    = floatval($body['duracion_seg']  ?? 0);
        $error  = $body['mensaje_error'] ?? null;

        if (!$nombre || !in_array($estado, ['PASSED','FAILED','ERROR'])) {
            $this->error('nombre_test y estado son requeridos', 422);
            return;
        }

        $id = $this->modelo->insertar($nombre, $clase, $estado, $dur, $error);
        $id
            ? $this->ok(['idPrueba' => $id])
            : $this->error('No se pudo guardar', 500);
    }

    private function limpiar(): void {
        $this->requireAuth(['admin']);
        $this->modelo->limpiar();
        $this->ok(['limpiado' => true]);
    }
}

(new PruebasController())->handle();
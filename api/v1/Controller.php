<?php
/**
 * Clase base para todos los controladores de la API REST.
 * Maneja headers CORS, autenticación de sesión y respuestas JSON.
 */
abstract class Controller {

    public function __construct() {
        // Headers CORS para que React pueda consumir la API
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: http://localhost');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');

        // Preflight OPTIONS — React lo envía antes de cada request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        require_once __DIR__.'/../../config/config.php';
    }

    /**
     * Respuesta exitosa con datos.
     */
    protected function ok(mixed $data, string $mensaje = ''): void {
        echo json_encode([
            'status'  => 'ok',
            'mensaje' => $mensaje,
            'data'    => $data
        ]);
        exit;
    }

    /**
     * Respuesta de error.
     */
    protected function error(string $mensaje, int $codigo = 400): void {
        http_response_code($codigo);
        echo json_encode([
            'status'  => 'error',
            'mensaje' => $mensaje,
            'data'    => null
        ]);
        exit;
    }

    /**
     * Verifica que el usuario esté autenticado vía sesión PHP.
     * Retorna los datos de la sesión o lanza error 401.
     */
    protected function requireAuth(string|array $rol = null): array {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['idUsuario'])) {
            $this->error('No autenticado', 401);
        }
        if ($rol) {
            $roles = is_array($rol) ? $rol : [$rol];
            if (!in_array($_SESSION['rol'], $roles)) {
                $this->error('Sin permisos suficientes', 403);
            }
        }
        return $_SESSION;
    }

    /**
     * Lee el body JSON del request (para POST/PUT).
     */
private array $_body = [];

protected function getBody(): array {
    if (!empty($this->_body)) return $this->_body;
    $raw = file_get_contents('php://input');
    $this->_body = json_decode($raw, true) ?? [];
    return $this->_body;
}
    /**
     * Retorna el método HTTP del request.
     */
    protected function method(): string {
        return $_SERVER['REQUEST_METHOD'];
    }
}

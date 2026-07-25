<?php
/**
 * View — includes/View.php
 *
 * SCRUM-123: Clase abstracta base para todas las vistas PHP del sistema.
 * Aplica el patrón Template Method: define el flujo base (verificar sesión,
 * extraer datos, calcular initials) y deja render() a cada subclase.
 *
 * FIX SCRUM-128: $_SESSION['nombre'] no existe. El login guarda los datos
 * del usuario en $_SESSION['usuario']['nombre']. Se agregó fallback doble.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

abstract class View {

    // ── Propiedades accesibles en subclases ──────────────────────────
    protected string $nombre;
    protected string $initials;
    protected int    $idUsuario;
    protected string $rol;
    protected string $email;

    /**
     * Constructor del Template Method.
     * @param string $rolRequerido  Rol que debe tener el usuario.
     */
    public function __construct(string $rolRequerido) {

        // 1. Verificar sesión activa (redirige a login si no hay sesión)
        requireLogin($rolRequerido);

        // 2. Extraer datos de sesión PHP
        //    FIX: el login guarda los datos en $_SESSION['usuario'][...]
        //    Se mantiene fallback a $_SESSION['nombre'] por compatibilidad
        $usr = $_SESSION['usuario'] ?? [];

        $this->idUsuario = (int)($usr['idUsuario'] ?? $_SESSION['idUsuario'] ?? 0);
        $this->nombre    = $usr['nombre'] ?? $_SESSION['nombre']   ?? 'Usuario';
        $this->rol       = $usr['rol']    ?? $_SESSION['rol']      ?? $rolRequerido;
        $this->email     = $usr['email']  ?? $_SESSION['email']    ?? '';

        // 3. Calcular initials para el avatar (máximo 2 letras)
        $this->initials = $this->buildInitials($this->nombre);
    }

    /**
     * Genera las iniciales del nombre para el avatar del panel.
     * Ej: "Edwin Carabali" → "EC", "James" → "JA"
     */
    protected function buildInitials(string $nombre): string {
        $palabras = array_filter(explode(' ', trim($nombre)));
        if (count($palabras) >= 2) {
            return strtoupper(
                substr($palabras[0], 0, 1) .
                substr($palabras[1], 0, 1)
            );
        }
        return strtoupper(substr($nombre, 0, 2));
    }

    /**
     * Genera el objeto JSON de sesión que React SPA necesita.
     * Se inyecta como window.__S en el HTML de cada dashboard.
     */
    protected function sessionJS(): string {
        $data = [
            'idUsuario' => $this->idUsuario,
            'nombre'    => $this->nombre,
            'rol'       => $this->rol,
            'email'     => $this->email,
            'initials'  => $this->initials,
        ];

        $data = array_merge($data, $this->sessionExtra());

        return json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Hook para que las subclases agreguen datos extra a window.__S.
     */
    protected function sessionExtra(): array {
        return [];
    }

    /**
     * Genera el bloque HTML de los tags <script> del CDN de React.
     */
    protected function reactCDN(): string {
        return '
    <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>';
    }

    /**
     * Método abstracto: cada subclase implementa su propio HTML.
     */
    abstract public function render(): void;
}
<?php
/**
 * UNI-VIRTUAL — Plantilla de configuración
 *
 * Uso:
 *   1. Copiar este archivo a config/config.php
 *   2. Ajustar los valores del entorno local
 *   3. No subir config/config.php al repositorio (está en .gitignore)
 *
 * Requisitos: PHP 8.x, MySQL 8, Apache (XAMPP).
 */

// --- Zona horaria ---------------------------------------------------------
// Debe coincidir con la de MySQL (America/Bogota). Si no, las videollamadas
// y las notificaciones comparan fechas con horas distintas.
date_default_timezone_set('America/Bogota');

// --- Conexión MySQL -------------------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');        // XAMPP suele usar 3307; si tu MySQL usa 3306, cámbialo
define('DB_USER', 'root');
define('DB_PASS', '');            // contraseña local de MySQL (vacía en XAMPP por defecto)
define('DB_NAME', 'univirtual');

// --- Rutas ----------------------------------------------------------------
// Ajusta si el proyecto no está en htdocs/univirtual
define('BASE_URL', 'http://localhost/univirtual');
define('BASE_PATH', realpath(__DIR__ . '/..'));

// --- Conexión única (Singleton por request) -------------------------------
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // En producción: registrar el error y no exponer detalles.
            error_log('[UNI-VIRTUAL] Error de conexión: ' . $e->getMessage());
            http_response_code(500);
            die('No fue posible conectar con la base de datos. Verifica config/config.php.');
        }
    }

    return $pdo;
}

// --- Notificaciones -------------------------------------------------------
/**
 * Crea una notificación para uno o varios usuarios.
 *
 * @param int|int[] $idUsuario ID o lista de IDs
 */
function notificar(
    int|array $idUsuario,
    string $titulo,
    string $mensaje,
    string $tipo = 'info',
    string $url = ''
): void {
    $ids = is_array($idUsuario) ? $idUsuario : [$idUsuario];

    $stmt = db()->prepare(
        'INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo, url)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($ids as $id) {
        if ((int) $id > 0) {
            $stmt->execute([(int) $id, $titulo, $mensaje, $tipo, $url]);
        }
    }
}

// --- Consultas de apoyo ---------------------------------------------------
/**
 * Devuelve los idUsuario de los estudiantes inscritos en una materia.
 *
 * @return int[]
 */
function estudiantesDeMateria(int $idMateria): array
{
    $stmt = db()->prepare(
        'SELECT u.idUsuario
           FROM inscripciones i
           JOIN estudiantes est ON i.idEstudiante = est.idEstudiante
           JOIN usuarios u      ON est.idUsuario    = u.idUsuario
          WHERE i.idMateria = ?'
    );
    $stmt->execute([$idMateria]);

    return array_map('intval', array_column($stmt->fetchAll(), 'idUsuario'));
}

// --- Autoload de modelos --------------------------------------------------
// Nota: en el refactor se reemplaza por spl_autoload_register.
foreach (glob(BASE_PATH . '/models/*.php') as $modelo) {
    require_once $modelo;
}

<?php
require_once __DIR__.'/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Jerarquía de roles ────────────────────────────────────────────
// admin > directivo > profesor > estudiante
const ROL_NIVEL = ['estudiante' => 1, 'profesor' => 2, 'directivo' => 3, 'admin' => 4];

function estaLogueado(): bool  { return isset($_SESSION['idUsuario']); }
function rolActual(): string   { return $_SESSION['rol'] ?? ''; }
function usuario(): array      { return $_SESSION['usuario'] ?? []; }
function nivelRol(string $rol = ''): int {
    return ROL_NIVEL[$rol ?: rolActual()] ?? 0;
}

/**
 * Verifica autenticación y redirige si no cumple el rol mínimo.
 * El admin siempre tiene acceso a todo.
 */
function requireLogin(string $rol = '') {
    if (!estaLogueado()) {
        header('Location: '.BASE_URL.'/pages/auth/login.php'); exit;
    }
    if ($rol && rolActual() !== $rol && rolActual() !== 'admin') {
        header('Location: '.BASE_URL.'/pages/auth/login.php?error=acceso'); exit;
    }
}

/**
 * Requiere nivel mínimo de jerarquía.
 * requireNivel('directivo') permite a directivos Y admins.
 */
function requireNivel(string $nivelMinimo) {
    if (!estaLogueado()) {
        header('Location: '.BASE_URL.'/pages/auth/login.php'); exit;
    }
    if (nivelRol() < nivelRol($nivelMinimo)) {
        header('Location: '.BASE_URL.'/pages/auth/login.php?error=acceso'); exit;
    }
}

/**
 * Verifica si el usuario actual tiene al menos el nivel indicado.
 */
function tieneNivel(string $nivelMinimo): bool {
    return estaLogueado() && nivelRol() >= nivelRol($nivelMinimo);
}

function contarNotifs(): int {
    if (!estaLogueado()) return 0;
    $s = db()->prepare("SELECT COUNT(*) FROM notificaciones WHERE idUsuario=? AND leida=0");
    $s->execute([$_SESSION['idUsuario']]);
    return (int)$s->fetchColumn();
}

function timeAgo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Hace un momento';
    if ($diff < 3600)   return 'Hace '.floor($diff/60).' min';
    if ($diff < 86400)  return 'Hace '.floor($diff/3600).' h';
    if ($diff < 604800) return 'Hace '.floor($diff/86400).' días';
    return date('d/m/Y', strtotime($fecha));
}

function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes/1048576,1).' MB';
    if ($bytes >= 1024)    return round($bytes/1024,1).' KB';
    return $bytes.' B';
}

function iconoTipo(string $tipo): string {
    return match($tipo) {
        'pdf'          => '📄',
        'video'        => '▶️',
        'presentacion' => '📊',
        default        => '📎',
    };
}
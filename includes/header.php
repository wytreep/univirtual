<?php
require_once __DIR__.'/../includes/auth.php';
requireLogin();
$rol      = rolActual();
$usr      = usuario();
$notifCnt = contarNotifs();
$pag      = $pag ?? '';
$titulo   = $titulo ?? 'UNI-VIRTUAL';

$initials = '';
foreach (explode(' ', $usr['nombre']) as $w) $initials .= strtoupper($w[0] ?? '');
$initials = substr($initials, 0, 2);

$avClass = match($rol) { 'profesor' => 'av-p', 'admin' => 'av-a', default => 'av-e' };

if ($rol === 'estudiante') {
    $nav = [
        ['href'=>'dashboard.php', 'label'=>'Inicio',          'icon'=>'grid',     'key'=>'dashboard'],
        ['href'=>'materias.php',  'label'=>'Mis Materias',     'icon'=>'book',     'key'=>'materias'],
        ['href'=>'tareas.php',    'label'=>'Tareas',           'icon'=>'check',    'key'=>'tareas',   'badge'=>true],
        ['href'=>'notas.php',     'label'=>'Calificaciones',   'icon'=>'chart',    'key'=>'notas'],
        ['href'=>'notificaciones.php','label'=>'Notificaciones','icon'=>'bell',    'key'=>'notifs',   'badge'=>true],
    ];
    $basePath = BASE_URL.'/pages/estudiante/';
} elseif ($rol === 'profesor') {
    $nav = [
        ['href'=>'dashboard.php',   'label'=>'Inicio',          'icon'=>'grid',   'key'=>'dashboard'],
        ['href'=>'materias.php',    'label'=>'Mis Materias',     'icon'=>'book',   'key'=>'materias'],
        ['href'=>'materiales.php',  'label'=>'Materiales',       'icon'=>'upload', 'key'=>'materiales'],
        ['href'=>'actividades.php', 'label'=>'Actividades',      'icon'=>'check',  'key'=>'actividades'],
        ['href'=>'calificar.php',   'label'=>'Calificar',        'icon'=>'edit',   'key'=>'calificar'],
        ['href'=>'reportes.php',    'label'=>'Reportes',         'icon'=>'report', 'key'=>'reportes'],
        ['href'=>'notificaciones.php','label'=>'Notificaciones', 'icon'=>'bell',   'key'=>'notifs', 'badge'=>true],
    ];
    $basePath = BASE_URL.'/pages/profesor/';
}

function svgIcon(string $name): string {
    $icons = [
        'grid'   => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'book'   => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
        'check'  => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
        'chart'  => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
        'bell'   => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
        'upload' => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        'edit'   => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
        'report' => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        'logout' => '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($titulo) ?> — UNI-VIRTUAL</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body>
<div class="layout">
<aside class="sidebar">
  <div class="sb-brand">
    <div class="sb-logo">UNI<span>VIRTUAL</span></div>
    <div class="sb-tag">Plataforma Académica</div>
  </div>
  <div class="sb-user">
    <div class="av <?= $avClass ?>"><?= $initials ?></div>
    <div>
      <div class="sb-uname"><?= htmlspecialchars($usr['nombre']) ?></div>
      <div class="sb-role"><?= strtoupper($rol) ?></div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Principal</div>
    <?php foreach ($nav as $item): ?>
    <a href="<?= $basePath.$item['href'] ?>" class="<?= $pag===$item['key']?'active':'' ?>">
      <?= svgIcon($item['icon']) ?>
      <?= $item['label'] ?>
      <?php if (!empty($item['badge']) && $notifCnt > 0): ?>
        <span class="sb-bdg"><?= $notifCnt ?></span>
      <?php endif ?>
    </a>
    <?php endforeach ?>
  </nav>
  <div class="sb-foot">
    <a href="<?= BASE_URL ?>/pages/auth/logout.php" class="sb-out">
      <?= svgIcon('logout') ?> Cerrar sesión
    </a>
  </div>
</aside>
<div class="main">
  <div class="topbar">
    <div>
      <div class="tb-crumb">UNI-VIRTUAL › <a href="<?= $basePath ?>dashboard.php"><?= ucfirst($rol) ?></a></div>
      <div class="tb-ttl"><?= htmlspecialchars($titulo) ?></div>
    </div>
    <div class="tb-r">
      <span class="tb-chip">2026-I</span>
      <a href="<?= $basePath ?>notificaciones.php" class="tb-ic">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <?php if ($notifCnt > 0): ?><div class="ndot"></div><?php endif ?>
      </a>
    </div>
  </div>
  <div class="content">

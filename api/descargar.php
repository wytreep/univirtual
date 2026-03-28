<?php
/**
 * Servicio de descarga segura — UNI-VIRTUAL
 *
 * GET /api/descargar.php?id={id}&tipo=material|entrega|enunciado|video
 *
 * Con ?preview=1  → muestra página de descarga con diseño institucional
 * Sin ?preview=1  → sirve el archivo directamente (streaming)
 *
 * Los enlaces del panel usan preview=1 para dar una experiencia institucional.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id      = (int)($_GET['id']      ?? 0);
$tipo    = $_GET['tipo']   ?? '';
$preview = isset($_GET['preview']);
$db      = db();

if (!$id || !$tipo) { http_response_code(400); die('Parámetros requeridos: id y tipo.'); }

// ── Obtener metadata del archivo según tipo ───────────────────────────────────
$meta = null; // ['titulo','nombreOriginal','ruta','tamano','materia','tipo_archivo']

if ($tipo === 'material') {
    $s = $db->prepare(
        "SELECT mat.*, m.nombre AS nombreMateria, m.codigo, m.idProfesor,
                u.nombre AS nombreProfesor
         FROM materiales mat
         JOIN aulas_virtuales av ON mat.idAula = av.idAula
         JOIN materias m ON av.idMateria = m.idMateria
         JOIN profesores p ON m.idProfesor = p.idProfesor
         JOIN usuarios u ON p.idUsuario = u.idUsuario
         WHERE mat.idMaterial = ?"
    );
    $s->execute([$id]);
    $row = $s->fetch();
    if (!$row) { http_response_code(404); die('Material no encontrado.'); }

    // Control de acceso
    $rol = rolActual();
    if ($rol === 'estudiante') {
        $sA = $db->prepare(
            "SELECT 1 FROM inscripciones i
             JOIN aulas_virtuales av ON av.idMateria = i.idMateria
             WHERE av.idAula = ? AND i.idEstudiante = ?"
        );
        $sA->execute([$row['idAula'], $_SESSION['idEspecifico']]);
        if (!$sA->fetch()) { http_response_code(403); die('Sin acceso.'); }
    } elseif ($rol === 'profesor' && (int)$row['idProfesor'] !== (int)$_SESSION['idEspecifico']) {
        http_response_code(403); die('Sin acceso.');
    }

    // Enlace externo → redirigir directo, sin preview
    if (str_starts_with($row['urlArchivo'], 'http')) {
        header('Location: ' . $row['urlArchivo']);
        exit;
    }

    $ruta = BASE_PATH . '/' . ltrim($row['urlArchivo'], '/');
    $meta = [
        'titulo'          => $row['titulo'],
        'nombreOriginal'  => $row['nombreOriginal'] ?: basename($ruta),
        'ruta'            => $ruta,
        'tamano'          => (int)$row['tamano'],
        'materia'         => $row['nombreMateria'] . ' (' . $row['codigo'] . ')',
        'profesor'        => $row['nombreProfesor'],
        'tipo_archivo'    => $row['tipo'],
        'descargas'       => (int)$row['descargas'],
        'fecha'           => $row['subido_en'],
    ];
    // Incrementar contador
    $db->prepare("UPDATE materiales SET descargas = descargas + 1 WHERE idMaterial = ?")
       ->execute([$id]);
}

if ($tipo === 'entrega') {
    if (rolActual() !== 'profesor') { http_response_code(403); die('Solo profesores.'); }
    $s = $db->prepare(
        "SELECT e.*, act.titulo AS tituloActividad,
                m.nombre AS nombreMateria, m.codigo, m.idProfesor,
                u.nombre AS nombreEstudiante
         FROM entregas e
         JOIN actividades act ON e.idActividad = act.idActividad
         JOIN aulas_virtuales av ON act.idAula = av.idAula
         JOIN materias m ON av.idMateria = m.idMateria
         JOIN estudiantes est ON e.idEstudiante = est.idEstudiante
         JOIN usuarios u ON est.idUsuario = u.idUsuario
         WHERE e.idEntrega = ?"
    );
    $s->execute([$id]);
    $row = $s->fetch();
    if (!$row || (int)$row['idProfesor'] !== (int)$_SESSION['idEspecifico']) {
        http_response_code(403); die('Sin acceso.');
    }
    $ruta = BASE_PATH . '/' . ltrim($row['archivoEntrega'], '/');
    $meta = [
        'titulo'         => 'Entrega: ' . $row['tituloActividad'],
        'nombreOriginal' => $row['nombreOriginal'] ?: basename($ruta),
        'ruta'           => $ruta,
        'tamano'         => 0,
        'materia'        => $row['nombreMateria'] . ' (' . $row['codigo'] . ')',
        'profesor'       => $row['nombreEstudiante'],
        'tipo_archivo'   => 'entrega',
        'descargas'      => 0,
        'fecha'          => $row['entregado_en'],
    ];
}

if ($tipo === 'enunciado') {
    $s = $db->prepare(
        "SELECT a.*, m.nombre AS nombreMateria, m.codigo, m.idProfesor
         FROM actividades a
         JOIN aulas_virtuales av ON a.idAula = av.idAula
         JOIN materias m ON av.idMateria = m.idMateria
         WHERE a.idActividad = ?"
    );
    $s->execute([$id]);
    $row = $s->fetch();
    if (!$row || !$row['enunciado']) { http_response_code(404); die('Sin enunciado adjunto.'); }

    $ruta = BASE_PATH . '/uploads/enunciados/' . $row['enunciado'];
    $ext  = pathinfo($row['enunciado'], PATHINFO_EXTENSION);
    $meta = [
        'titulo'         => 'Enunciado: ' . $row['titulo'],
        'nombreOriginal' => 'Enunciado_' . preg_replace('/[^\w\-]/', '_', $row['titulo']) . '.' . $ext,
        'ruta'           => $ruta,
        'tamano'         => file_exists($ruta) ? filesize($ruta) : 0,
        'materia'        => $row['nombreMateria'] . ' (' . $row['codigo'] . ')',
        'profesor'       => '',
        'tipo_archivo'   => 'pdf',
        'descargas'      => 0,
        'fecha'          => $row['creada_en'],
    ];
}

if ($tipo === 'video') {
    // Videos siempre van a stream_video.php para Range requests
    header('Location: ' . BASE_URL . '/api/stream_video.php?id=' . $id);
    exit;
}

if (!$meta) { http_response_code(400); die('Tipo no válido.'); }
if (!file_exists($meta['ruta'])) { http_response_code(404); die('Archivo no encontrado en el servidor.'); }

// ── Sin preview → servir archivo directamente ─────────────────────────────────
if (!$preview) {
    $mime       = mime_content_type($meta['ruta']) ?: 'application/octet-stream';
    $ext        = strtolower(pathinfo($meta['ruta'], PATHINFO_EXTENSION));
    $inlineExts = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
    $disp       = in_array($ext, $inlineExts) ? 'inline' : 'attachment';
    $nombreSafe = rawurlencode($meta['nombreOriginal']);

    header('Content-Type: ' . $mime);
    header("Content-Disposition: $disp; filename*=UTF-8''$nombreSafe");
    header('Content-Length: ' . filesize($meta['ruta']));
    header('Cache-Control: no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    readfile($meta['ruta']);
    exit;
}

// ── Con preview → Página institucional de descarga ────────────────────────────
$icoMapa = [
    'pdf'          => ['ico' => '📄', 'color' => '#fdecea', 'label' => 'Documento PDF'],
    'video'        => ['ico' => '🎬', 'color' => '#e8f0fb', 'label' => 'Video'],
    'presentacion' => ['ico' => '📊', 'color' => '#e8f5ee', 'label' => 'Presentación'],
    'entrega'      => ['ico' => '📤', 'color' => '#f0ebff', 'label' => 'Entrega'],
    'otro'         => ['ico' => '📎', 'color' => '#f4f5f8', 'label' => 'Archivo'],
];
$ico   = $icoMapa[$meta['tipo_archivo']] ?? $icoMapa['otro'];
$tamMb = $meta['tamano'] > 0
    ? ($meta['tamano'] >= 1048576
        ? round($meta['tamano'] / 1048576, 1) . ' MB'
        : round($meta['tamano'] / 1024, 0) . ' KB')
    : null;
$fechaFmt = $meta['fecha'] ? date('d/m/Y H:i', strtotime($meta['fecha'])) : '';

// URL para descarga directa (sin preview)
$urlDirecta = BASE_URL . '/api/descargar.php?id=' . $id . '&tipo=' . $tipo;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Descarga — <?= htmlspecialchars($meta['titulo']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{
  font-family:'DM Sans',sans-serif;
  min-height:100vh;
  background:linear-gradient(135deg,#0d1f4e 0%,#1a3a6e 50%,#2462b0 100%);
  display:flex;align-items:center;justify-content:center;padding:24px;
}

/* Fondo decorativo */
body::before{
  content:'';position:fixed;inset:0;
  background:
    radial-gradient(circle at 20% 20%, rgba(212,168,67,.06) 0%,transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(36,98,176,.15) 0%,transparent 50%);
  pointer-events:none;
}

.card{
  background:#fff;border-radius:20px;
  box-shadow:0 24px 64px rgba(0,0,0,.22), 0 4px 16px rgba(0,0,0,.1);
  max-width:500px;width:100%;overflow:hidden;position:relative;
}

/* Franja superior dorada */
.card-top{
  height:5px;
  background:linear-gradient(90deg,#d4a843,#f0c96a,#d4a843);
}

/* Header */
.header{
  background:linear-gradient(135deg,#0d1f4e,#2462b0);
  padding:28px 32px 24px;color:#fff;
}
.uni-logo{
  font-family:'DM Serif Display',serif;font-size:20px;font-weight:400;
  margin-bottom:4px;letter-spacing:-.3px;
}
.uni-logo em{color:#d4a843;font-style:italic;}
.uni-sub{font-size:11px;color:rgba(255,255,255,.5);font-family:'JetBrains Mono',monospace;letter-spacing:.5px;}

/* Cuerpo */
.body{padding:28px 32px;}

/* Ícono del archivo */
.file-icon-wrap{
  display:flex;align-items:center;gap:16px;margin-bottom:24px;
  padding:16px 20px;
  background:var(--bg,#f8f9fc);border-radius:12px;
  border:1px solid #e8ecf4;
}
.file-icon{
  width:52px;height:52px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:26px;flex-shrink:0;
}
.file-title{font-size:16px;font-weight:700;color:#0d1f4e;line-height:1.3;margin-bottom:4px;}
.file-type{font-size:11px;color:#6b7a99;font-family:'JetBrains Mono',monospace;}

/* Metadata */
.meta-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px;
}
.meta-item{
  background:#f8f9fc;border:1px solid #e8ecf4;
  border-radius:8px;padding:10px 14px;
}
.meta-item label{
  font-size:10px;font-weight:700;color:#9aa5b8;
  letter-spacing:.6px;text-transform:uppercase;display:block;margin-bottom:3px;
}
.meta-item span{font-size:13px;font-weight:600;color:#1a1a2e;}
.meta-item.full{grid-column:1/-1;}

/* Botón descarga */
.btn-download{
  display:block;width:100%;padding:15px;
  background:linear-gradient(135deg,#0d1f4e,#2462b0);
  color:#fff;border:none;border-radius:10px;
  font-size:16px;font-weight:700;font-family:'DM Sans',sans-serif;
  text-decoration:none;text-align:center;cursor:pointer;
  transition:transform .15s,box-shadow .15s;
  box-shadow:0 4px 16px rgba(13,31,78,.3);
  letter-spacing:.2px;
}
.btn-download:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(13,31,78,.35);}
.btn-download:active{transform:translateY(0);}

.btn-back{
  display:block;text-align:center;margin-top:12px;
  font-size:13px;color:#6b7a99;text-decoration:none;
  font-family:'DM Sans',sans-serif;
}
.btn-back:hover{color:#2462b0;}

/* Footer */
.footer{
  background:#f8f9fc;border-top:1px solid #e8ecf4;
  padding:14px 32px;display:flex;justify-content:space-between;align-items:center;
}
.footer-left{font-size:11px;color:#9aa5b8;font-family:'JetBrains Mono',monospace;}
.footer-seal{
  background:#0d1f4e;color:rgba(255,255,255,.7);
  font-size:10px;padding:3px 10px;border-radius:20px;
  font-family:'JetBrains Mono',monospace;
}

/* Alerta de inicio de descarga */
#notif{
  position:fixed;bottom:24px;right:24px;
  background:#1a7a48;color:#fff;
  padding:12px 20px;border-radius:10px;
  font-size:13px;font-weight:600;
  box-shadow:0 4px 20px rgba(0,0,0,.2);
  transform:translateY(80px);opacity:0;
  transition:all .3s ease;z-index:100;
}
#notif.show{transform:translateY(0);opacity:1;}
</style>
</head>
<body>

<div class="card">
  <div class="card-top"></div>

  <div class="header">
    <div class="uni-logo">UNI<em>VIRTUAL</em></div>
    <div class="uni-sub">PLATAFORMA ACADÉMICA · UNIAJC · 2026-I</div>
  </div>

  <div class="body">

    <div class="file-icon-wrap">
      <div class="file-icon" style="background:<?= $ico['color'] ?>"><?= $ico['ico'] ?></div>
      <div>
        <div class="file-title"><?= htmlspecialchars($meta['titulo']) ?></div>
        <div class="file-type"><?= $ico['label'] ?></div>
      </div>
    </div>

    <div class="meta-grid">
      <div class="meta-item full">
        <label>Materia</label>
        <span><?= htmlspecialchars($meta['materia']) ?></span>
      </div>
      <?php if ($meta['profesor']): ?>
      <div class="meta-item">
        <label><?= $tipo === 'entrega' ? 'Estudiante' : 'Profesor' ?></label>
        <span><?= htmlspecialchars($meta['profesor']) ?></span>
      </div>
      <?php endif ?>
      <?php if ($fechaFmt): ?>
      <div class="meta-item">
        <label><?= $tipo === 'entrega' ? 'Entregado' : 'Publicado' ?></label>
        <span><?= $fechaFmt ?></span>
      </div>
      <?php endif ?>
      <?php if ($meta['nombreOriginal']): ?>
      <div class="meta-item">
        <label>Archivo</label>
        <span><?= htmlspecialchars($meta['nombreOriginal']) ?></span>
      </div>
      <?php endif ?>
      <?php if ($tamMb): ?>
      <div class="meta-item">
        <label>Tamaño</label>
        <span><?= $tamMb ?></span>
      </div>
      <?php endif ?>
    </div>

    <a href="<?= htmlspecialchars($urlDirecta) ?>" class="btn-download" id="btnDescargar"
       download="<?= htmlspecialchars($meta['nombreOriginal']) ?>">
      ⬇ Descargar <?= htmlspecialchars($meta['nombreOriginal']) ?>
    </a>

    <a href="javascript:history.back()" class="btn-back">← Volver al aula</a>

  </div>

  <div class="footer">
    <div class="footer-left">UNI-VIRTUAL · <?= date('d/m/Y H:i') ?></div>
    <div class="footer-seal">SEGURO · VERIFICADO</div>
  </div>
</div>

<div id="notif">✅ Descarga iniciada</div>

<script>
document.getElementById('btnDescargar').addEventListener('click', function() {
  const n = document.getElementById('notif');
  n.classList.add('show');
  setTimeout(() => n.classList.remove('show'), 3000);
});
</script>

</body>
</html>

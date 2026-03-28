<?php
/**
 * Streaming seguro de video con soporte de Range requests.
 * Permite que el navegador haga seek sin descargar el archivo completo.
 *
 * GET /api/stream_video.php?id={idMaterial}
 *
 * urlArchivo en DB contiene la ruta relativa desde la raíz del proyecto
 * Ejemplo: 'uploads/materiales/mat_1_1710000000_ab12cd34.mp4'
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); die('Parámetro id requerido.'); }

$db = db();
$s  = $db->prepare(
    "SELECT mat.*, av.idAula
     FROM materiales mat
     JOIN aulas_virtuales av ON mat.idAula = av.idAula
     WHERE mat.idMaterial = ? AND mat.tipo = 'video'"
);
$s->execute([$id]);
$row = $s->fetch();
if (!$row) { http_response_code(404); die('Video no encontrado.'); }

// Verificar acceso: estudiante debe estar inscrito, profesor propietario o admin
$rol = rolActual();
if ($rol === 'estudiante') {
    $sA = $db->prepare(
        "SELECT 1 FROM inscripciones i
         JOIN aulas_virtuales av ON av.idMateria = i.idMateria
         WHERE av.idAula = ? AND i.idEstudiante = ?"
    );
    $sA->execute([$row['idAula'], $_SESSION['idEspecifico']]);
    if (!$sA->fetch()) { http_response_code(403); die('No estás inscrito en esta materia.'); }
}

// urlArchivo contiene la ruta relativa desde la raíz (ej: uploads/materiales/video.mp4)
$ruta = BASE_PATH . '/' . ltrim($row['urlArchivo'], '/');

if (!file_exists($ruta)) {
    http_response_code(404);
    die('Archivo de video no encontrado en el servidor.');
}

$size  = filesize($ruta);
$start = 0;
$end   = $size - 1;

// Detectar MIME del video por extensión
$ext  = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
$mime = match($ext) {
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'ogg'  => 'video/ogg',
    'mov'  => 'video/quicktime',
    'avi'  => 'video/x-msvideo',
    'mkv'  => 'video/x-matroska',
    default => 'video/mp4',
};

header('Content-Type: ' . $mime);
header('Accept-Ranges: bytes');
header('Cache-Control: no-cache');

// Range requests para seeking en el player HTML5
if (isset($_SERVER['HTTP_RANGE'])) {
    if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        $start = (int)$m[1];
        $end   = ($m[2] !== '') ? (int)$m[2] : $size - 1;
    }
    http_response_code(206); // Partial Content
    header("Content-Range: bytes $start-$end/$size");
}

$length = $end - $start + 1;
header('Content-Length: ' . $length);

$fp = fopen($ruta, 'rb');
fseek($fp, $start);
$remaining = $length;
while (!feof($fp) && $remaining > 0) {
    $chunk     = fread($fp, min(65536, $remaining)); // 64 KB chunks
    echo $chunk;
    $remaining -= strlen($chunk);
    flush();
}
fclose($fp);

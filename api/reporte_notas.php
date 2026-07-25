<?php
/**
 * Reporte de Calificaciones — UNI-VIRTUAL
 * GET /api/reporte_notas.php?idMateria={id}&formato=xlsx|pdf|csv
 */
ob_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('profesor');

$idProf    = (int)$_SESSION['idEspecifico'];
$idMateria = (int)($_GET['idMateria'] ?? 0);
$formato   = strtolower($_GET['formato'] ?? 'xlsx');

if (!$idMateria) { http_response_code(400); die('Parámetro idMateria requerido.'); }

$db = db();
$sM = $db->prepare(
    "SELECT m.*, u.nombre AS nombreProf
     FROM materias m
     JOIN profesores p ON m.idProfesor = p.idProfesor
     JOIN usuarios u   ON p.idUsuario  = u.idUsuario
     WHERE m.idMateria = ? AND m.idProfesor = ?"
);
$sM->execute([$idMateria, $idProf]);
$materia = $sM->fetch();
if (!$materia) { http_response_code(403); die('Sin acceso.'); }

$sE = $db->prepare(
    "SELECT u.nombre, est.codigoEst, est.semestre,
            i.nota_parcial1, i.nota_parcial2, i.nota_talleres, i.nota_final,
            (SELECT ROUND(SUM(asistio)/NULLIF(COUNT(*),0)*100,0)
             FROM asistencia WHERE idMateria=? AND idEstudiante=est.idEstudiante) AS pct_asistencia,
            (SELECT COUNT(*) FROM entregas e
             JOIN actividades a ON e.idActividad=a.idActividad
             JOIN aulas_virtuales av ON a.idAula=av.idAula
             WHERE e.idEstudiante=est.idEstudiante AND av.idMateria=?) AS entregas_hechas,
            (SELECT COUNT(*) FROM actividades a
             JOIN aulas_virtuales av ON a.idAula=av.idAula
             WHERE av.idMateria=?) AS total_actividades
     FROM inscripciones i
     JOIN estudiantes est ON i.idEstudiante=est.idEstudiante
     JOIN usuarios u      ON est.idUsuario=u.idUsuario
     WHERE i.idMateria=? ORDER BY u.nombre"
);
$sE->execute([$idMateria, $idMateria, $idMateria, $idMateria]);
$estudiantes = $sE->fetchAll();

function calcProm(array $e): ?float {
    $n = array_filter([$e['nota_parcial1'],$e['nota_parcial2'],$e['nota_talleres']],
                      fn($x)=>$x!==null&&$x!=='');
    return count($n) ? round(array_sum($n)/count($n),2) : null;
}

$fechaGen = date('d/m/Y H:i');

// ════════════════════════════════════════════════════════════
//  XLSX — via Python/openpyxl (100% compatible con Excel)
// ════════════════════════════════════════════════════════════
if ($formato === 'xlsx') {
    // Construir payload JSON para el script Python
    $payload = [
        'tipo'    => 'notas',
        'materia' => $materia['nombre'],
        'codigo'  => $materia['codigo'],
        'prof'    => $materia['nombreProf'],
        'fecha'   => $fechaGen,
        'estudiantes' => [],
    ];

    foreach ($estudiantes as $e) {
        $prom  = calcProm($e);
        $final = $e['nota_final'] ?? $prom;
        $payload['estudiantes'][] = [
            'nombre'     => $e['nombre'],
            'codigo'     => $e['codigoEst'],
            'semestre'   => (int)$e['semestre'],
            'p1'         => $e['nota_parcial1'] !== null ? (float)$e['nota_parcial1'] : null,
            'p2'         => $e['nota_parcial2'] !== null ? (float)$e['nota_parcial2'] : null,
            'tall'       => $e['nota_talleres']  !== null ? (float)$e['nota_talleres']  : null,
            'final'      => $e['nota_final']     !== null ? (float)$e['nota_final']     : null,
            'asistencia' => (int)($e['pct_asistencia'] ?? 0),
            'entregas'   => ($e['entregas_hechas']??0) . '/' . ($e['total_actividades']??0),
        ];
    }

    $json  = json_encode($payload);
    $script = BASE_PATH . '/api/gen_xlsx.py';

    // Detectar Python disponible en el sistema
    $pythonCmds = ['python3', 'python', 'py'];
    $pythonBin  = null;
    foreach ($pythonCmds as $cmd) {
        $test = shell_exec("$cmd --version 2>&1");
        if ($test && stripos($test, 'python') !== false) {
            $pythonBin = $cmd;
            break;
        }
    }

    if ($pythonBin && file_exists($script)) {
        // Llamar al script Python via proc_open para pasar JSON por stdin
        $desc = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open("$pythonBin " . escapeshellarg($script), $desc, $pipes);

        if (is_resource($proc)) {
            fwrite($pipes[0], $json);
            fclose($pipes[0]);

            $xlsxData = stream_get_contents($pipes[1]);
            $stderr   = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($proc);

            if ($exitCode === 0 && strlen($xlsxData) > 100) {
                $archivo = 'Notas_' . $materia['codigo'] . '_' . date('Ymd_His') . '.xlsx';
                ob_end_clean();
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($archivo));
                header('Content-Length: ' . strlen($xlsxData));
                header('Cache-Control: no-cache');
                echo $xlsxData;
                exit;
            }
        }
    }

    // Fallback si Python no disponible: redirigir al HTML imprimible
    header('Location: ' . BASE_URL . '/api/reporte_notas.php?idMateria=' . $idMateria . '&formato=pdf');
    exit;
}

// ════════════════════════════════════════════════════════════
//  CSV
// ════════════════════════════════════════════════════════════
if ($formato === 'csv') {
    $archivo = 'notas_' . $materia['codigo'] . '_' . date('Ymd_His') . '.csv';
    ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($archivo));
    $out = fopen('php://output','w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out,['UNI-VIRTUAL — Reporte de Calificaciones'],';');
    fputcsv($out,['Materia',$materia['nombre'],'Código',$materia['codigo']],';');
    fputcsv($out,['Profesor',$materia['nombreProf'],'Generado',$fechaGen],';');
    fputcsv($out,[],';');
    fputcsv($out,['Nombre','Código','Sem.','Parcial 1','Parcial 2','Talleres','Promedio','Nota Final','Asistencia %','Estado'],';');
    foreach ($estudiantes as $e) {
        $prom=$e['nota_final']??calcProm($e);
        $estado=$prom===null?'En curso':((float)$prom>=3.0?'Aprobado':'Reprobado');
        $fmt=fn($v)=>$v!==null?number_format((float)$v,2,',',''):'—';
        fputcsv($out,[$e['nombre'],$e['codigoEst'],$e['semestre'],
            $fmt($e['nota_parcial1']),$fmt($e['nota_parcial2']),$fmt($e['nota_talleres']),
            $fmt(calcProm($e)),$fmt($e['nota_final']),
            ($e['pct_asistencia']??0).'%',$estado],';');
    }
    fclose($out); exit;
}

// ════════════════════════════════════════════════════════════
//  PDF — HTML imprimible institucional
// ════════════════════════════════════════════════════════════
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Calificaciones — <?= htmlspecialchars($materia['nombre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',Arial,sans-serif;font-size:13px;background:#f4f6fb;}
.controles{background:#fff;border-bottom:1px solid #dde3f0;padding:12px 24px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:10;}
.btn{background:#0d1f4e;color:#fff;border:none;padding:9px 20px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
.btn:hover{background:#2462b0;}
.btn-green{background:#1a7a48;}
.btn-green:hover{background:#156a3c;}
.tip{font-size:12px;color:#6b7a99;}
.doc{max-width:1100px;margin:24px auto;background:#fff;border-radius:10px;box-shadow:0 2px 20px rgba(13,31,78,.1);overflow:hidden;}
.header{background:linear-gradient(135deg,#0d1f4e,#2462b0);padding:24px 32px;color:#fff;}
.logo{font-family:'DM Serif Display',serif;font-size:26px;}
.logo em{color:#d4a843;font-style:italic;}
.header-info{display:flex;gap:28px;margin-top:12px;flex-wrap:wrap;}
.info-item label{font-size:10px;color:rgba(255,255,255,.5);display:block;}
.info-item span{font-size:13px;font-weight:600;}
.resumen{display:flex;gap:14px;padding:18px 24px;background:#f8f9fc;flex-wrap:wrap;}
.res-card{background:#fff;border:1px solid #dde3f0;border-radius:8px;padding:12px 18px;text-align:center;min-width:110px;}
.res-num{font-family:'DM Serif Display',serif;font-size:24px;color:#0d1f4e;line-height:1;}
.res-lbl{font-size:11px;color:#6b7a99;margin-top:3px;}
.tabla-wrap{overflow-x:auto;padding:0 20px 24px;}
table{width:100%;border-collapse:collapse;margin-top:20px;font-size:12px;}
thead tr{background:#0d1f4e;color:#fff;}
th{padding:10px;font-weight:600;font-size:11px;text-align:center;}
th:first-child{text-align:left;}
tbody tr:nth-child(even){background:#f5f7fb;}
td{padding:8px 10px;border-bottom:1px solid #e8ecf4;text-align:center;}
td:first-child{text-align:left;font-weight:500;}
.ok{color:#1a7a48;font-weight:700;}.mal{color:#c0392b;font-weight:700;}
.badge-ok{background:#e8f5ee;color:#1a7a48;padding:3px 10px;border-radius:20px;font-weight:700;font-size:11px;}
.badge-mal{background:#fdecea;color:#c0392b;padding:3px 10px;border-radius:20px;font-weight:700;font-size:11px;}
.badge-pend{background:#fdf3e4;color:#d68910;padding:3px 10px;border-radius:20px;font-size:11px;}
.footer-doc{padding:14px 24px;font-size:11px;color:#9aa5b8;border-top:1px solid #eee;}
@media print{.controles{display:none;}body{background:#fff;}.doc{margin:0;border-radius:0;box-shadow:none;}}
</style>
</head>
<body>
<div class="controles">
  <button class="btn" onclick="window.print()">🖨️ Imprimir / PDF</button>
  <a href="?idMateria=<?= $idMateria ?>&formato=xlsx" class="btn btn-green">📊 Descargar Excel</a>
  <span class="tip">En el diálogo de impresión elige "Guardar como PDF".</span>
</div>
<div class="doc">
  <div class="header">
    <div style="display:flex;justify-content:space-between;">
      <div class="logo">UNI<em>VIRTUAL</em></div>
      <div style="font-size:11px;opacity:.6;"><?= $fechaGen ?></div>
    </div>
    <div class="header-info">
      <div class="info-item"><label>REPORTE</label><span>Calificaciones</span></div>
      <div class="info-item"><label>MATERIA</label><span><?= htmlspecialchars($materia['nombre']) ?> (<?= htmlspecialchars($materia['codigo']) ?>)</span></div>
      <div class="info-item"><label>PROFESOR</label><span><?= htmlspecialchars($materia['nombreProf']) ?></span></div>
    </div>
  </div>
  <?php
    $proms=array_filter(array_map(fn($e)=>$e['nota_final']??calcProm($e),$estudiantes));
    $pg=count($proms)?round(array_sum($proms)/count($proms),2):0;
    $apr=count(array_filter($proms,fn($p)=>(float)$p>=3.0));
    $rep=count($proms)-$apr;
    $pctApr=count($proms)?round($apr/count($proms)*100):0;
  ?>
  <div class="resumen">
    <div class="res-card"><div class="res-num"><?= count($estudiantes) ?></div><div class="res-lbl">Estudiantes</div></div>
    <div class="res-card"><div class="res-num" style="color:#1a7a48"><?= $apr ?></div><div class="res-lbl">Aprobados</div></div>
    <div class="res-card"><div class="res-num" style="color:#c0392b"><?= $rep ?></div><div class="res-lbl">Reprobados</div></div>
    <div class="res-card"><div class="res-num" style="color:#2462b0"><?= number_format($pg,2) ?></div><div class="res-lbl">Promedio</div></div>
    <div class="res-card"><div class="res-num" style="color:#d4a843"><?= $pctApr ?>%</div><div class="res-lbl">Aprobación</div></div>
  </div>
  <div class="tabla-wrap">
    <table>
      <thead><tr><th>Nombre</th><th>Código</th><th>P1</th><th>P2</th><th>Talleres</th><th>Promedio</th><th>Final</th><th>Asistencia</th><th>Estado</th></tr></thead>
      <tbody>
      <?php foreach ($estudiantes as $e):
        $prom=calcProm($e); $final=$e['nota_final']??$prom;
        $ok=$final!==null&&(float)$final>=3.0;
        $fmt=fn($v)=>$v!==null?number_format((float)$v,2):'—';
      ?>
      <tr>
        <td><?= htmlspecialchars($e['nombre']) ?></td>
        <td><?= htmlspecialchars($e['codigoEst']) ?></td>
        <td class="<?= $e['nota_parcial1']!==null?((float)$e['nota_parcial1']>=3?'ok':'mal'):'' ?>"><?= $fmt($e['nota_parcial1']) ?></td>
        <td class="<?= $e['nota_parcial2']!==null?((float)$e['nota_parcial2']>=3?'ok':'mal'):'' ?>"><?= $fmt($e['nota_parcial2']) ?></td>
        <td class="<?= $e['nota_talleres']!==null?((float)$e['nota_talleres']>=3?'ok':'mal'):'' ?>"><?= $fmt($e['nota_talleres']) ?></td>
        <td class="<?= $prom!==null?($ok?'ok':'mal'):'' ?>"><?= $fmt($prom) ?></td>
        <td class="<?= $final!==null?($ok?'ok':'mal'):'' ?>"><?= $fmt($e['nota_final']) ?></td>
        <td class="<?= ($e['pct_asistencia']??0)<75?'mal':'ok' ?>"><?= $e['pct_asistencia']??0 ?>%</td>
        <td><?php if($final===null):?><span class="badge-pend">En curso</span>
            <?php elseif($ok):?><span class="badge-ok">✓ Aprobado</span>
            <?php else:?><span class="badge-mal">✗ Reprobado</span><?php endif?></td>
      </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="footer-doc">UNI-VIRTUAL · <?= $fechaGen ?> · <?= count($estudiantes) ?> estudiantes</div>
</div>
</body></html>
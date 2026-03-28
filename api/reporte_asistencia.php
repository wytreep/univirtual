<?php
/**
 * Reporte de Asistencia — UNI-VIRTUAL
 * GET /api/reporte_asistencia.php?idAula={id}&formato=xlsx|pdf|csv
 */
ob_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('profesor');

$idProf  = (int)$_SESSION['idEspecifico'];
$idAula  = (int)($_GET['idAula']  ?? 0);
$desde   = $_GET['desde'] ?? date('Y-m-01');
$hasta   = $_GET['hasta'] ?? date('Y-m-d');
$formato = strtolower($_GET['formato'] ?? 'xlsx');

if (!$idAula) { http_response_code(400); die('Parámetro idAula requerido.'); }

$db = db();
$sV = $db->prepare(
    "SELECT av.*, m.nombre AS materia, m.codigo, m.idMateria, u.nombre AS nombreProf
     FROM aulas_virtuales av
     JOIN materias m ON av.idMateria=m.idMateria
     JOIN profesores p ON m.idProfesor=p.idProfesor
     JOIN usuarios u ON p.idUsuario=u.idUsuario
     WHERE av.idAula=? AND m.idProfesor=?"
);
$sV->execute([$idAula,$idProf]);
$aula = $sV->fetch();
if (!$aula) { http_response_code(403); die('Sin acceso.'); }

$sEsts = $db->prepare(
    "SELECT u.nombre, est.codigoEst, est.idEstudiante
     FROM inscripciones i
     JOIN estudiantes est ON i.idEstudiante=est.idEstudiante
     JOIN usuarios u ON est.idUsuario=u.idUsuario
     WHERE i.idMateria=? ORDER BY u.nombre"
);
$sEsts->execute([$aula['idMateria']]);
$estudiantes = $sEsts->fetchAll();

$sFechas = $db->prepare(
    "SELECT DISTINCT fecha FROM asistencia
     WHERE idMateria=? AND fecha BETWEEN ? AND ? ORDER BY fecha"
);
$sFechas->execute([$aula['idMateria'],$desde,$hasta]);
$fechas = array_column($sFechas->fetchAll(),'fecha');

$sA = $db->prepare(
    "SELECT idEstudiante, fecha, asistio FROM asistencia
     WHERE idMateria=? AND fecha BETWEEN ? AND ?"
);
$sA->execute([$aula['idMateria'],$desde,$hasta]);
$asistMap = [];
foreach ($sA->fetchAll() as $r) {
    $asistMap[$r['idEstudiante']][$r['fecha']] = (bool)$r['asistio'];
}

$fechaGen = date('d/m/Y H:i');

// ════════════════════════════════════════════════════════════
//  XLSX — via Python/openpyxl
// ════════════════════════════════════════════════════════════
if ($formato === 'xlsx') {
    $payload = [
        'tipo'     => 'asistencia',
        'materia'  => $aula['materia'],
        'codigo'   => $aula['codigo'],
        'prof'     => $aula['nombreProf'],
        'fecha'    => $fechaGen,
        'desde'    => $desde,
        'hasta'    => $hasta,
        'fechas'   => array_map(fn($f)=>['fecha'=>$f,'label'=>date('d/m',strtotime($f))], $fechas),
        'estudiantes' => [],
    ];

    foreach ($estudiantes as $e) {
        $asistData = [];
        foreach ($fechas as $f) {
            $v = $asistMap[$e['idEstudiante']][$f] ?? null;
            $asistData[$f] = $v;
        }
        $payload['estudiantes'][] = [
            'nombre'    => $e['nombre'],
            'codigo'    => $e['codigoEst'],
            'asistencia'=> $asistData,
        ];
    }

    $json   = json_encode($payload);
    $script = BASE_PATH . '/api/gen_xlsx.py';

    $pythonCmds = ['python3','python','py'];
    $pythonBin  = null;
    foreach ($pythonCmds as $cmd) {
        $test = shell_exec("$cmd --version 2>&1");
        if ($test && stripos($test,'python')!==false) { $pythonBin=$cmd; break; }
    }

    if ($pythonBin && file_exists($script)) {
        $desc = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
        $proc = proc_open("$pythonBin ".escapeshellarg($script),$desc,$pipes);
        if (is_resource($proc)) {
            fwrite($pipes[0],$json); fclose($pipes[0]);
            $xlsxData = stream_get_contents($pipes[1]);
            fclose($pipes[1]); fclose($pipes[2]);
            $exitCode = proc_close($proc);
            if ($exitCode===0 && strlen($xlsxData)>100) {
                $archivo = 'Asistencia_'.$aula['codigo'].'_'.date('Ymd_His').'.xlsx';
                ob_end_clean();
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($archivo));
                header('Content-Length: '.strlen($xlsxData));
                header('Cache-Control: no-cache');
                echo $xlsxData; exit;
            }
        }
    }
    header('Location: '.BASE_URL.'/api/reporte_asistencia.php?idAula='.$idAula.'&desde='.$desde.'&hasta='.$hasta.'&formato=pdf');
    exit;
}

// CSV
if ($formato === 'csv') {
    $archivo = 'asistencia_'.$aula['codigo'].'_'.date('Ymd_His').'.csv';
    ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($archivo));
    $out = fopen('php://output','w');
    fprintf($out,chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out,['UNI-VIRTUAL — Reporte de Asistencia'],';');
    $hdr=['Nombre','Código'];
    foreach($fechas as $f) $hdr[]=date('d/m/Y',strtotime($f));
    $hdr[]='Asistidas'; $hdr[]='%';
    fputcsv($out,$hdr,';');
    foreach($estudiantes as $e){
        $row=[$e['nombre'],$e['codigoEst']]; $asi=0; $tot=count($fechas);
        foreach($fechas as $f){$v=$asistMap[$e['idEstudiante']][$f]??null;$row[]=$v===null?'—':($v?'Sí':'No');if($v===true)$asi++;}
        $row[]="$asi/$tot"; $row[]=$tot>0?round($asi/$tot*100).'%':'—';
        fputcsv($out,$row,';');
    }
    fclose($out); exit;
}

// PDF — HTML imprimible
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Asistencia — <?= htmlspecialchars($aula['materia']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',Arial,sans-serif;font-size:12px;background:#f4f6fb;}
.controles{background:#fff;border-bottom:1px solid #dde3f0;padding:12px 24px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:10;}
.btn{background:#0d1f4e;color:#fff;border:none;padding:9px 20px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
.btn:hover{background:#2462b0;}.btn-green{background:#1a7a48;}.btn-green:hover{background:#156a3c;}
.doc{max-width:1200px;margin:24px auto;background:#fff;border-radius:10px;box-shadow:0 2px 20px rgba(13,31,78,.1);overflow:hidden;}
.header{background:linear-gradient(135deg,#0d1f4e,#2462b0);padding:24px 32px;color:#fff;}
.logo{font-family:'DM Serif Display',serif;font-size:26px;}.logo em{color:#d4a843;font-style:italic;}
.header-info{display:flex;gap:28px;margin-top:12px;flex-wrap:wrap;}
.info-item label{font-size:10px;color:rgba(255,255,255,.5);display:block;}
.info-item span{font-size:13px;font-weight:600;}
.tabla-wrap{overflow-x:auto;padding:0 20px 24px;}
table{width:100%;border-collapse:collapse;margin-top:20px;font-size:12px;}
thead tr{background:#0d1f4e;color:#fff;}
th{padding:9px 8px;font-weight:600;font-size:11px;text-align:center;}
th:first-child{text-align:left;}
tbody tr:nth-child(even){background:#f5f7fb;}
td{padding:7px 8px;border-bottom:1px solid #e8ecf4;text-align:center;}
td:first-child{text-align:left;font-weight:500;}
.si{color:#1a7a48;font-weight:700;}.no{color:#c0392b;font-weight:700;}
.pct-ok{color:#1a7a48;font-weight:700;}.pct-mal{color:#c0392b;font-weight:700;}
.leyenda{padding:14px 20px;background:#f8f9fc;border-top:1px solid #eee;font-size:11px;color:#6b7a99;}
@media print{.controles{display:none;}body{background:#fff;}.doc{margin:0;border-radius:0;box-shadow:none;}}
</style>
</head>
<body>
<div class="controles">
  <button class="btn" onclick="window.print()">🖨️ Imprimir / PDF</button>
  <a href="?idAula=<?= $idAula ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>&formato=xlsx" class="btn btn-green">📊 Descargar Excel</a>
</div>
<div class="doc">
  <div class="header">
    <div style="display:flex;justify-content:space-between;">
      <div class="logo">UNI<em>VIRTUAL</em></div>
      <div style="font-size:11px;opacity:.6;"><?= $fechaGen ?></div>
    </div>
    <div class="header-info">
      <div class="info-item"><label>REPORTE</label><span>Asistencia</span></div>
      <div class="info-item"><label>MATERIA</label><span><?= htmlspecialchars($aula['materia']) ?></span></div>
      <div class="info-item"><label>PERÍODO</label><span><?= $desde ?> — <?= $hasta ?></span></div>
    </div>
  </div>
  <div class="tabla-wrap">
    <table>
      <thead><tr>
        <th style="text-align:left">Estudiante</th><th>Código</th>
        <?php foreach($fechas as $f):?><th><?= date('d/m',strtotime($f)) ?></th><?php endforeach?>
        <th>Total</th><th>%</th>
      </tr></thead>
      <tbody>
      <?php foreach($estudiantes as $e): $asi=0; $tot=count($fechas); ?>
      <tr>
        <td><?= htmlspecialchars($e['nombre']) ?></td>
        <td><?= htmlspecialchars($e['codigoEst']) ?></td>
        <?php foreach($fechas as $f):
          $v=$asistMap[$e['idEstudiante']][$f]??null;
          if($v===true)$asi++;
        ?><td class="<?= $v===null?'':($v?'si':'no') ?>"><?= $v===null?'—':($v?'✓':'✗') ?></td><?php endforeach?>
        <?php $pct=$tot>0?round($asi/$tot*100):0;?>
        <td style="font-weight:600"><?= $asi ?>/<?= $tot ?></td>
        <td class="<?= $pct>=75?'pct-ok':'pct-mal' ?>"><?= $pct ?>%</td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
  <div class="leyenda">✓ = Asistió | ✗ = No asistió | — = Sin registro | Mínimo: <strong>75%</strong></div>
</div>
</body></html>
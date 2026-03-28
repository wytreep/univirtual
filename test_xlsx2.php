<?php
ob_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/XlsxWriter.php';

$w = new XlsxWriter();
$w->writeSheetHeader('Hoja1', [
    'Nombre'  => 'string',
    'Nota'    => 'price',
    'Estado'  => 'string',
]);
$w->writeSheetRow('Hoja1', ['Edwin Carabali', 4.2, 'Aprobado']);
$w->writeSheetRow('Hoja1', ['Laura Córdoba',  2.8, 'Reprobado']);

$path = __DIR__ . '/test_output.xlsx';
$w->writeToFile($path);

// Mostrar contenido interno del ZIP para diagnóstico
echo "<h3>Archivos dentro del XLSX:</h3>";
$zip = new ZipArchive();
$zip->open($path);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    $content = $zip->getFromIndex($i);
    echo "<details><summary>{$name} (" . strlen($content) . " bytes)</summary>";
    echo "<pre>" . htmlspecialchars($content) . "</pre></details><br>";
}
$zip->close();
?>
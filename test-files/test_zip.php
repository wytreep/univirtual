<?php
ob_start();

// Test básico de ZipArchive
$tmp = sys_get_temp_dir() . '/test_' . time() . '.xlsx';

$zip = new ZipArchive();
$r = $zip->open($tmp, ZipArchive::CREATE);
echo "ZipArchive::open result: " . var_export($r, true) . "<br>";

$zip->addFromString('test.txt', 'hola mundo');
$zip->close();

$size = filesize($tmp);
echo "Archivo creado: {$size} bytes<br>";

// Verificar que es un ZIP válido
$zip2 = new ZipArchive();
$r2 = $zip2->open($tmp);
echo "Verificación apertura: " . var_export($r2, true) . "<br>";
if ($r2 === true) {
    echo "Archivos dentro: " . $zip2->numFiles . "<br>";
    $zip2->close();
    echo "<strong style='color:green'>✅ ZipArchive funciona correctamente</strong><br>";
} else {
    echo "<strong style='color:red'>❌ ZipArchive está roto</strong><br>";
}

// Test: ¿PHP puede escribir en temp?
echo "sys_get_temp_dir: " . sys_get_temp_dir() . "<br>";
echo "PHP version: " . PHP_VERSION . "<br>";
echo "ZipArchive disponible: " . (class_exists('ZipArchive') ? 'Sí' : 'No') . "<br>";

unlink($tmp);
?>
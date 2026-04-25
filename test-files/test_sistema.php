<?php
/**
 * test_sistema.php — Auditoría completa de UNI-VIRTUAL
 * Prueba todos los endpoints, funcionalidades y módulos del sistema.
 * USO: http://localhost/univirtual/test_sistema.php
 */
require_once __DIR__ . '/config/config.php';

$results = [];
$startTime = microtime(true);

// ─── Helper ───────────────────────────────────────────────────────────────────
function test(string $grupo, string $nombre, callable $fn): array {
    $t0 = microtime(true);
    try {
        $result = $fn();
        $ok     = $result['ok'] ?? true;
        $msg    = $result['msg'] ?? 'OK';
        $detail = $result['detail'] ?? '';
    } catch (Throwable $e) {
        $ok     = false;
        $msg    = $e->getMessage();
        $detail = get_class($e) . ' en línea ' . $e->getLine();
    }
    return [
        'grupo'  => $grupo,
        'nombre' => $nombre,
        'ok'     => $ok,
        'msg'    => $msg,
        'detail' => $detail,
        'ms'     => round((microtime(true) - $t0) * 1000, 1),
    ];
}

$db = db();

// ════════════════════════════════════════════════════════════════════════════
// 1. INFRAESTRUCTURA
// ════════════════════════════════════════════════════════════════════════════
$results[] = test('Infraestructura', 'Conexión MySQL', function() use ($db) {
    $v = $db->query("SELECT VERSION() AS v")->fetchColumn();
    return ['ok' => true, 'msg' => "MySQL $v"];
});

$results[] = test('Infraestructura', 'PHP Version', function() {
    $v = PHP_VERSION;
    $ok = version_compare($v, '8.0', '>=');
    return ['ok' => $ok, 'msg' => "PHP $v" . ($ok ? '' : ' ← Requiere PHP 8+')];
});

$results[] = test('Infraestructura', 'ZipArchive disponible', function() {
    $ok = class_exists('ZipArchive');
    return ['ok' => $ok, 'msg' => $ok ? 'Disponible' : 'No disponible — XLSX no funcionará'];
});

$results[] = test('Infraestructura', 'Python disponible (para XLSX)', function() {
    foreach (['python3','python','py'] as $cmd) {
        $v = @shell_exec("$cmd --version 2>&1");
        if ($v && stripos($v, 'python') !== false) {
            return ['ok' => true, 'msg' => trim($v) . " ($cmd)"];
        }
    }
    return ['ok' => false, 'msg' => 'Python no encontrado — XLSX usa HTML fallback'];
});

$results[] = test('Infraestructura', 'openpyxl instalado', function() {
    foreach (['python3','python','py'] as $cmd) {
        $v = @shell_exec("$cmd --version 2>&1");
        if ($v && stripos($v, 'python') !== false) {
            $r = @shell_exec("$cmd -c \"import openpyxl; print(openpyxl.__version__)\" 2>&1");
            if ($r && !str_contains($r, 'Error')) {
                return ['ok' => true, 'msg' => 'openpyxl ' . trim($r)];
            }
            return ['ok' => false, 'msg' => 'openpyxl no instalado — ejecuta: pip install openpyxl'];
        }
    }
    return ['ok' => false, 'msg' => 'Python no disponible'];
});

$results[] = test('Infraestructura', 'Directorio uploads existe', function() {
    $path = BASE_PATH . '/uploads';
    $ok   = is_dir($path);
    return ['ok' => $ok, 'msg' => $ok ? $path : "No existe: $path"];
});

$results[] = test('Infraestructura', 'Directorio uploads escribible', function() {
    $path = BASE_PATH . '/uploads';
    if (!is_dir($path)) return ['ok' => false, 'msg' => 'Directorio no existe'];
    $ok = is_writable($path);
    return ['ok' => $ok, 'msg' => $ok ? 'Escribible' : 'Sin permisos de escritura'];
});

$results[] = test('Infraestructura', 'gen_xlsx.py existe', function() {
    $path = BASE_PATH . '/api/gen_xlsx.py';
    $ok   = file_exists($path);
    return ['ok' => $ok, 'msg' => $ok ? 'Presente' : 'No encontrado en ' . $path];
});

// ════════════════════════════════════════════════════════════════════════════
// 2. BASE DE DATOS — TABLAS
// ════════════════════════════════════════════════════════════════════════════
$tablas = ['usuarios','estudiantes','profesores','materias','inscripciones',
           'aulas_virtuales','materiales','actividades','entregas',
           'foros','mensajes','asistencia','notificaciones','videollamadas'];

foreach ($tablas as $tabla) {
    $results[] = test('Base de Datos', "Tabla: $tabla", function() use ($db, $tabla) {
        $count = $db->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
        return ['ok' => true, 'msg' => "$count registros"];
    });
}

// ════════════════════════════════════════════════════════════════════════════
// 3. AUTENTICACIÓN — USUARIOS
// ════════════════════════════════════════════════════════════════════════════
$cuentas = [
    ['admin@univirtual.edu.co',   '123456', 'admin'],
    ['james@univirtual.edu.co',   '123456', 'profesor'],
    ['maria@univirtual.edu.co',   '123456', 'profesor'],
    ['edwin@univirtual.edu.co',   '123456', 'estudiante'],
    ['angel@univirtual.edu.co',   '123456', 'estudiante'],
];

foreach ($cuentas as [$email, $pass, $rol]) {
    $results[] = test('Autenticación', "Login: $email ($rol)", function() use ($db, $email, $pass, $rol) {
        $u = $db->prepare("SELECT * FROM usuarios WHERE email = ?")->execute([$email])
            ? null : null;
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u) return ['ok' => false, 'msg' => 'Usuario no encontrado en DB'];
        if (!password_verify($pass, $u['password'])) return ['ok' => false, 'msg' => 'Contraseña no coincide'];
        if ($u['rol'] !== $rol) return ['ok' => false, 'msg' => "Rol incorrecto: {$u['rol']} ≠ $rol"];
        if (!$u['activo']) return ['ok' => false, 'msg' => 'Usuario inactivo'];
        return ['ok' => true, 'msg' => "OK — rol: {$u['rol']}, activo: {$u['activo']}"];
    });
}

$results[] = test('Autenticación', 'Rechaza contraseña incorrecta', function() use ($db) {
    $stmt = $db->prepare("SELECT password FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@univirtual.edu.co']);
    $hash = $stmt->fetchColumn();
    $ok   = !password_verify('wrongpassword', $hash);
    return ['ok' => $ok, 'msg' => $ok ? 'Correcto — rechaza contraseña inválida' : 'FALLO — acepta contraseña incorrecta'];
});

// ════════════════════════════════════════════════════════════════════════════
// 4. API REST — ENDPOINTS
// ════════════════════════════════════════════════════════════════════════════
$base = BASE_URL;

function callApi(string $url, string $method = 'GET', array $body = []): array {
    $ctx = stream_context_create([
        'http' => [
            'method'  => $method,
            'header'  => "Content-Type: application/json\r\nCookie: " . session_name() . '=' . session_id(),
            'content' => $method !== 'GET' ? json_encode($body) : null,
            'timeout' => 5,
            'ignore_errors' => true,
        ]
    ]);
    $raw  = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\S+ (\d+)/', $http_response_header[0], $m);
        $code = (int)($m[1] ?? 0);
    }
    $json = $raw ? json_decode($raw, true) : null;
    return ['code' => $code, 'raw' => $raw, 'json' => $json];
}

// Iniciar sesión como admin para pruebas de API
session_start();
$_SESSION['idUsuario']    = 1;
$_SESSION['rol']          = 'admin';
$_SESSION['idEspecifico'] = 1;

// Stats
$results[] = test('API REST', 'GET stats/dashboard (admin)', function() use ($base) {
    $r = callApi("$base/api/v1/stats.php?action=dashboard");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}"];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    return ['ok' => $ok, 'msg' => $ok ? 'JSON OK' : 'Respuesta: ' . substr($r['raw'], 0, 80)];
});

// Usuarios
$results[] = test('API REST', 'GET usuarios (admin)', function() use ($base) {
    $r = callApi("$base/api/v1/usuarios.php?action=listar");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}"];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    $cnt = count($r['json']['data'] ?? []);
    return ['ok' => $ok, 'msg' => $ok ? "$cnt usuarios" : substr($r['raw'], 0, 80)];
});

// Materias
$results[] = test('API REST', 'GET materias (admin)', function() use ($base) {
    $r = callApi("$base/api/v1/materias.php?action=listar");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}"];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    $cnt = count($r['json']['data'] ?? []);
    return ['ok' => $ok, 'msg' => $ok ? "$cnt materias" : substr($r['raw'], 0, 80)];
});

// Videollamadas
$results[] = test('API REST', 'GET videollamadas/lista', function() use ($base) {
    $r = callApi("$base/api/v1/videollamadas.php?action=lista&idMateria=1");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}"];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    return ['ok' => $ok, 'msg' => $ok ? 'JSON OK' : substr($r['raw'], 0, 80)];
});

// Sesión como profesor
$_SESSION['idUsuario']    = 2;
$_SESSION['rol']          = 'profesor';
$_SESSION['idEspecifico'] = 1;

$results[] = test('API REST', 'GET profesor/dashboard', function() use ($base) {
    $r = callApi("$base/api/v1/profesor.php?action=dashboard");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}: " . substr($r['raw'],0,100)];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    return ['ok' => $ok, 'msg' => $ok ? 'JSON OK' : substr($r['raw'], 0, 100)];
});

$results[] = test('API REST', 'GET profesor/aula (idAula=1)', function() use ($base) {
    $r = callApi("$base/api/v1/profesor.php?action=aula&idAula=1");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}: " . substr($r['raw'],0,100)];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    return ['ok' => $ok, 'msg' => $ok ? 'JSON OK' : substr($r['raw'], 0, 100)];
});

$results[] = test('API REST', 'GET profesor/entregas (idAula=1)', function() use ($base) {
    $r = callApi("$base/api/v1/profesor.php?action=entregas&idAula=1");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}: " . substr($r['raw'],0,100)];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    return ['ok' => $ok, 'msg' => $ok ? 'JSON OK' : substr($r['raw'], 0, 100)];
});

// Sesión como estudiante
$_SESSION['idUsuario']    = 3;
$_SESSION['rol']          = 'estudiante';
$_SESSION['idEspecifico'] = 1;

$results[] = test('API REST', 'GET estudiante/dashboard', function() use ($base) {
    $r = callApi("$base/api/v1/estudiante.php?action=dashboard");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}: " . substr($r['raw'],0,100)];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    return ['ok' => $ok, 'msg' => $ok ? 'JSON OK' : substr($r['raw'], 0, 100)];
});

$results[] = test('API REST', 'GET estudiante/notas', function() use ($base) {
    $r = callApi("$base/api/v1/estudiante.php?action=notas");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}: " . substr($r['raw'],0,100)];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    return ['ok' => $ok, 'msg' => $ok ? 'JSON OK' : substr($r['raw'], 0, 100)];
});

$results[] = test('API REST', 'GET estudiante/aula (idAula=1)', function() use ($base) {
    $r = callApi("$base/api/v1/estudiante.php?action=aula&idAula=1");
    if ($r['code'] !== 200) return ['ok' => false, 'msg' => "HTTP {$r['code']}: " . substr($r['raw'],0,100)];
    $ok = isset($r['json']['status']) && $r['json']['status'] === 'ok';
    return ['ok' => $ok, 'msg' => $ok ? 'JSON OK' : substr($r['raw'], 0, 100)];
});

// Sin sesión — debe retornar 401
session_destroy();
session_start();
$results[] = test('API REST', 'Sin sesión retorna 401', function() use ($base) {
    $r = callApi("$base/api/v1/profesor.php?action=dashboard");
    $ok = $r['code'] === 401;
    return ['ok' => $ok, 'msg' => $ok ? 'HTTP 401 correcto' : "HTTP {$r['code']} — debería ser 401"];
});

// Volver a activar sesión admin
session_destroy();
session_start();
$_SESSION['idUsuario']    = 1;
$_SESSION['rol']          = 'admin';
$_SESSION['idEspecifico'] = 1;

// ════════════════════════════════════════════════════════════════════════════
// 5. MÓDULOS FUNCIONALES
// ════════════════════════════════════════════════════════════════════════════

// Materiales
$results[] = test('Módulos', 'Materiales — consulta BD', function() use ($db) {
    $stmt = $db->prepare(
        "SELECT m.idMaterial, m.titulo, m.tipo, m.urlArchivo, av.idAula
         FROM materiales m JOIN aulas_virtuales av ON m.idAula = av.idAula
         LIMIT 5"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();
    if (empty($rows)) return ['ok' => false, 'msg' => 'No hay materiales en BD'];
    return ['ok' => true, 'msg' => count($rows) . ' materiales encontrados',
            'detail' => implode(', ', array_column($rows, 'titulo'))];
});

// Actividades
$results[] = test('Módulos', 'Actividades — consulta BD', function() use ($db) {
    $stmt = $db->query("SELECT COUNT(*) AS c, MIN(fechaEntrega) AS prox FROM actividades");
    $r = $stmt->fetch();
    return ['ok' => (int)$r['c'] > 0, 'msg' => "{$r['c']} actividades, próxima: {$r['prox']}"];
});

// Entregas
$results[] = test('Módulos', 'Entregas — consulta BD', function() use ($db) {
    $cnt = $db->query("SELECT COUNT(*) FROM entregas")->fetchColumn();
    return ['ok' => true, 'msg' => "$cnt entregas registradas"];
});

// Foros y Mensajes
$results[] = test('Módulos', 'Foros y Mensajes', function() use ($db) {
    $foros = $db->query("SELECT COUNT(*) FROM foros")->fetchColumn();
    $msgs  = $db->query("SELECT COUNT(*) FROM mensajes")->fetchColumn();
    return ['ok' => true, 'msg' => "$foros foros, $msgs mensajes"];
});

// Asistencia
$results[] = test('Módulos', 'Asistencia — registros', function() use ($db) {
    $cnt = $db->query("SELECT COUNT(*) FROM asistencia")->fetchColumn();
    $pct = $db->query("SELECT ROUND(AVG(asistio)*100,0) FROM asistencia")->fetchColumn();
    return ['ok' => true, 'msg' => "$cnt registros, promedio $pct%"];
});

// Notificaciones
$results[] = test('Módulos', 'Notificaciones — registros', function() use ($db) {
    $cnt = $db->query("SELECT COUNT(*) FROM notificaciones")->fetchColumn();
    $noLeidas = $db->query("SELECT COUNT(*) FROM notificaciones WHERE leida=0")->fetchColumn();
    return ['ok' => true, 'msg' => "$cnt total, $noLeidas no leídas"];
});

// Videollamadas
$results[] = test('Módulos', 'Videollamadas — tabla y datos', function() use ($db) {
    $cnt = $db->query("SELECT COUNT(*) FROM videollamadas")->fetchColumn();
    return ['ok' => true, 'msg' => "$cnt videollamadas registradas"];
});

// ════════════════════════════════════════════════════════════════════════════
// 6. ARCHIVOS Y DESCARGA
// ════════════════════════════════════════════════════════════════════════════
$results[] = test('Archivos', 'descargar.php existe', function() {
    $ok = file_exists(BASE_PATH . '/api/descargar.php');
    return ['ok' => $ok, 'msg' => $ok ? 'Presente' : 'No encontrado'];
});

$results[] = test('Archivos', 'stream_video.php existe', function() {
    $ok = file_exists(BASE_PATH . '/api/stream_video.php');
    return ['ok' => $ok, 'msg' => $ok ? 'Presente' : 'No encontrado'];
});

$results[] = test('Archivos', 'Materiales físicos en uploads/', function() {
    $dir   = BASE_PATH . '/uploads/materiales';
    if (!is_dir($dir)) return ['ok' => false, 'msg' => 'Directorio no existe: ' . $dir];
    $files = glob($dir . '/*');
    return ['ok' => true, 'msg' => count($files) . ' archivos en uploads/materiales'];
});

$results[] = test('Archivos', 'Entregas físicas en uploads/', function() {
    $dir   = BASE_PATH . '/uploads/entregas';
    if (!is_dir($dir)) return ['ok' => false, 'msg' => 'Directorio no existe: ' . $dir];
    $files = glob($dir . '/*');
    return ['ok' => true, 'msg' => count($files) . ' archivos en uploads/entregas'];
});

// ════════════════════════════════════════════════════════════════════════════
// 7. PÁGINAS PRINCIPALES (HTTP 200)
// ════════════════════════════════════════════════════════════════════════════
$paginas = [
    'Login'              => '/pages/auth/login.php',
    'Dashboard Admin'    => '/pages/admin/dashboard.php',
    'Dashboard Profesor' => '/pages/profesor/dashboard.php',
    'Dashboard Estudiante'=> '/pages/estudiante/dashboard.php',
];

foreach ($paginas as $nombre => $ruta) {
    $results[] = test('Páginas', $nombre, function() use ($base, $ruta) {
        $ctx = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true,
            'header' => "Cookie: " . session_name() . '=' . session_id(),
        ]]);
        $raw  = @file_get_contents($base . $ruta, false, $ctx);
        $code = 0;
        if (isset($http_response_header)) {
            preg_match('/HTTP\/\S+ (\d+)/', $http_response_header[0], $m);
            $code = (int)($m[1] ?? 0);
        }
        $ok = in_array($code, [200, 302]);
        return ['ok' => $ok, 'msg' => "HTTP $code" . ($raw ? ' ('.round(strlen($raw)/1024,1).' KB)' : '')];
    });
}

// ════════════════════════════════════════════════════════════════════════════
// 8. REPORTES
// ════════════════════════════════════════════════════════════════════════════
$_SESSION['idUsuario']    = 2;
$_SESSION['rol']          = 'profesor';
$_SESSION['idEspecifico'] = 1;

$results[] = test('Reportes', 'Reporte notas — HTML (formato=pdf)', function() use ($base) {
    $ctx = stream_context_create(['http' => [
        'timeout' => 8, 'ignore_errors' => true,
        'header' => "Cookie: " . session_name() . '=' . session_id(),
    ]]);
    $raw  = @file_get_contents("$base/api/reporte_notas.php?idMateria=1&formato=pdf", false, $ctx);
    $code = 0;
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\S+ (\d+)/', $http_response_header[0], $m);
        $code = (int)($m[1] ?? 0);
    }
    $ok = $code === 200 && $raw && str_contains($raw, 'UNI');
    return ['ok' => $ok, 'msg' => "HTTP $code, " . round(strlen($raw??'')/1024,1) . ' KB'];
});

$results[] = test('Reportes', 'Reporte asistencia — HTML (formato=pdf)', function() use ($base) {
    $ctx = stream_context_create(['http' => [
        'timeout' => 8, 'ignore_errors' => true,
        'header' => "Cookie: " . session_name() . '=' . session_id(),
    ]]);
    $raw  = @file_get_contents("$base/api/reporte_asistencia.php?idAula=1&formato=pdf", false, $ctx);
    $code = 0;
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\S+ (\d+)/', $http_response_header[0], $m);
        $code = (int)($m[1] ?? 0);
    }
    $ok = $code === 200 && $raw && str_contains($raw, 'UNI');
    return ['ok' => $ok, 'msg' => "HTTP $code, " . round(strlen($raw??'')/1024,1) . ' KB'];
});

$results[] = test('Reportes', 'Reporte notas — XLSX (genera archivo)', function() use ($base) {
    $ctx = stream_context_create(['http' => [
        'timeout' => 15, 'ignore_errors' => true,
        'header' => "Cookie: " . session_name() . '=' . session_id(),
        'follow_location' => false,
    ]]);
    $raw  = @file_get_contents("$base/api/reporte_notas.php?idMateria=1&formato=xlsx", false, $ctx);
    $code = 0; $ct = '';
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\S+ (\d+)/', $http_response_header[0], $m);
        $code = (int)($m[1] ?? 0);
        foreach ($http_response_header as $h) {
            if (stripos($h, 'content-type') !== false) $ct = $h;
        }
    }
    // Verificar que es un ZIP (XLSX = ZIP)
    $isXlsx = $raw && substr($raw, 0, 2) === 'PK';
    if ($code === 200 && $isXlsx) {
        return ['ok' => true, 'msg' => 'XLSX generado — ' . round(strlen($raw)/1024,1) . ' KB, formato ZIP válido'];
    }
    if ($code === 302) {
        return ['ok' => false, 'msg' => 'Redirige a PDF — Python/openpyxl no disponible'];
    }
    return ['ok' => false, 'msg' => "HTTP $code, CT: $ct, " . round(strlen($raw??'')/1024,1) . ' KB, PK: ' . (str_starts_with($raw??'','PK')?'SÍ':'NO')];
});

// ════════════════════════════════════════════════════════════════════════════
// 9. INTEGRIDAD DE DATOS
// ════════════════════════════════════════════════════════════════════════════
$results[] = test('Integridad', 'Cada materia tiene aula virtual', function() use ($db) {
    $sin = $db->query(
        "SELECT COUNT(*) FROM materias m LEFT JOIN aulas_virtuales av ON m.idMateria=av.idMateria WHERE av.idAula IS NULL"
    )->fetchColumn();
    return ['ok' => (int)$sin === 0, 'msg' => (int)$sin === 0 ? 'OK' : "$sin materias sin aula virtual"];
});

$results[] = test('Integridad', 'Cada inscripción tiene materia y estudiante', function() use ($db) {
    $sin = $db->query(
        "SELECT COUNT(*) FROM inscripciones i
         LEFT JOIN materias m ON i.idMateria=m.idMateria
         LEFT JOIN estudiantes e ON i.idEstudiante=e.idEstudiante
         WHERE m.idMateria IS NULL OR e.idEstudiante IS NULL"
    )->fetchColumn();
    return ['ok' => (int)$sin === 0, 'msg' => (int)$sin === 0 ? 'OK' : "$sin inscripciones huérfanas"];
});

$results[] = test('Integridad', 'Contraseñas hasheadas con bcrypt', function() use ($db) {
    $bad = $db->query(
        "SELECT COUNT(*) FROM usuarios WHERE password NOT LIKE '\$2y\$%'"
    )->fetchColumn();
    return ['ok' => (int)$bad === 0, 'msg' => (int)$bad === 0 ? 'Todas con bcrypt' : "$bad sin hash bcrypt"];
});

$results[] = test('Integridad', 'Usuarios activos', function() use ($db) {
    $activos   = $db->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetchColumn();
    $inactivos = $db->query("SELECT COUNT(*) FROM usuarios WHERE activo=0")->fetchColumn();
    return ['ok' => (int)$activos > 0, 'msg' => "$activos activos, $inactivos inactivos"];
});

$results[] = test('Integridad', 'Notas en rango 0-5', function() use ($db) {
    $bad = $db->query(
        "SELECT COUNT(*) FROM inscripciones WHERE
         (nota_parcial1 IS NOT NULL AND (nota_parcial1 < 0 OR nota_parcial1 > 5)) OR
         (nota_parcial2 IS NOT NULL AND (nota_parcial2 < 0 OR nota_parcial2 > 5)) OR
         (nota_final    IS NOT NULL AND (nota_final    < 0 OR nota_final    > 5))"
    )->fetchColumn();
    return ['ok' => (int)$bad === 0, 'msg' => (int)$bad === 0 ? 'Todas en rango válido' : "$bad notas fuera de rango"];
});

// ════════════════════════════════════════════════════════════════════════════
// RESULTADOS
// ════════════════════════════════════════════════════════════════════════════
$totalMs   = round((microtime(true) - $startTime) * 1000);
$totalOk   = count(array_filter($results, fn($r) => $r['ok']));
$totalFail = count($results) - $totalOk;
$grupos    = array_unique(array_column($results, 'grupo'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Auditoría — UNI-VIRTUAL</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:#f0f2f8;color:#1a1a2e;font-size:13px}
.top{background:linear-gradient(135deg,#0d1f4e,#2462b0);padding:28px 36px;color:#fff}
.top h1{font-family:'DM Serif Display',serif;font-size:28px}
.top h1 em{color:#d4a843;font-style:italic}
.top-meta{display:flex;gap:24px;margin-top:12px;flex-wrap:wrap}
.top-stat{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);
  border-radius:8px;padding:10px 18px;text-align:center}
.top-num{font-family:'DM Serif Display',serif;font-size:26px;line-height:1}
.top-lbl{font-size:11px;opacity:.6;margin-top:2px}
.ok-num{color:#6ee7b7}
.fail-num{color:#fca5a5}
.body{max-width:1100px;margin:24px auto;padding:0 16px}
.grupo{background:#fff;border-radius:10px;box-shadow:0 1px 10px rgba(13,31,78,.07);
  margin-bottom:20px;overflow:hidden}
.grupo-header{background:#0d1f4e;color:#fff;padding:12px 20px;
  display:flex;justify-content:space-between;align-items:center}
.grupo-title{font-weight:700;font-size:13px;letter-spacing:.5px}
.grupo-badge{font-size:11px;background:rgba(255,255,255,.15);padding:3px 10px;border-radius:20px}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:8px 16px;font-size:11px;color:#6b7a99;
   border-bottom:2px solid #e8ecf4;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
td{padding:9px 16px;border-bottom:1px solid #f0f2f8;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:nth-child(even){background:#fafbfd}
tr:hover{background:#f0f4ff}
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;
  border-radius:20px;font-size:11px;font-weight:600}
.badge-ok{background:#e8f5ee;color:#1a7a48}
.badge-fail{background:#fdecea;color:#c0392b}
.test-name{font-weight:500}
.test-msg{color:#4a5568}
.test-ms{font-family:'JetBrains Mono',monospace;font-size:11px;color:#9aa5b8}
.test-detail{font-family:'JetBrains Mono',monospace;font-size:10px;color:#9aa5b8;
  margin-top:3px;word-break:break-all}
.footer{text-align:center;padding:20px;color:#9aa5b8;font-size:12px}
.progress{height:6px;background:#e8ecf4;border-radius:3px;margin:8px 0;overflow:hidden}
.progress-bar{height:100%;border-radius:3px;transition:width .3s;
  background:linear-gradient(90deg,#1a7a48,#2ecc71)}
.progress-bar.has-fail{background:linear-gradient(90deg,#c0392b,#e74c3c)}
</style>
</head>
<body>
<div class="top">
  <h1>UNI<em>VIRTUAL</em> — Auditoría Completa</h1>
  <div class="top-meta">
    <div class="top-stat">
      <div class="top-num"><?= count($results) ?></div>
      <div class="top-lbl">Pruebas totales</div>
    </div>
    <div class="top-stat">
      <div class="top-num ok-num"><?= $totalOk ?></div>
      <div class="top-lbl">Pasaron ✓</div>
    </div>
    <div class="top-stat">
      <div class="top-num fail-num"><?= $totalFail ?></div>
      <div class="top-lbl">Fallaron ✗</div>
    </div>
    <div class="top-stat">
      <div class="top-num"><?= $totalMs ?><span style="font-size:14px">ms</span></div>
      <div class="top-lbl">Tiempo total</div>
    </div>
    <div class="top-stat">
      <div class="top-num"><?= round($totalOk/count($results)*100) ?>%</div>
      <div class="top-lbl">Tasa de éxito</div>
    </div>
  </div>
</div>

<div class="body">
<?php foreach ($grupos as $grupo):
  $gr = array_filter($results, fn($r) => $r['grupo'] === $grupo);
  $gOk   = count(array_filter($gr, fn($r) => $r['ok']));
  $gFail = count($gr) - $gOk;
  $pct   = round($gOk/count($gr)*100);
?>
<div class="grupo">
  <div class="grupo-header">
    <span class="grupo-title"><?= $grupo ?></span>
    <span class="grupo-badge"><?= $gOk ?>/<?= count($gr) ?> — <?= $pct ?>%</span>
  </div>
  <div class="progress">
    <div class="progress-bar <?= $gFail>0?'has-fail':'' ?>" style="width:<?= $pct ?>%"></div>
  </div>
  <table>
    <thead><tr>
      <th>Prueba</th><th>Estado</th><th>Resultado</th><th>ms</th>
    </tr></thead>
    <tbody>
    <?php foreach ($gr as $r): ?>
    <tr>
      <td class="test-name"><?= htmlspecialchars($r['nombre']) ?></td>
      <td>
        <?php if ($r['ok']): ?>
          <span class="badge badge-ok">✓ OK</span>
        <?php else: ?>
          <span class="badge badge-fail">✗ FALLO</span>
        <?php endif ?>
      </td>
      <td>
        <div class="test-msg"><?= htmlspecialchars($r['msg']) ?></div>
        <?php if ($r['detail']): ?>
          <div class="test-detail"><?= htmlspecialchars($r['detail']) ?></div>
        <?php endif ?>
      </td>
      <td class="test-ms"><?= $r['ms'] ?>ms</td>
    </tr>
    <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php endforeach ?>

<div class="footer">
  UNI-VIRTUAL · Auditoría ejecutada <?= date('d/m/Y H:i:s') ?> · <?= $totalMs ?>ms total
</div>
</div>
</body>
</html>

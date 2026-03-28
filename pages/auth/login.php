<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Si viene ?error=acceso, destruir sesión y mostrar mensaje
$errorAcceso = isset($_GET['error']) && $_GET['error'] === 'acceso';
if ($errorAcceso && estaLogueado()) {
  session_destroy();
  session_start();
}
// Si ya está logueado (y sin error de acceso), redirigir a su dashboard
if (estaLogueado() && !$errorAcceso) {
  header('Location: ' . BASE_URL . '/pages/' . rolActual() . '/dashboard.php');
  exit;
}

$error = $errorAcceso ? 'No tienes permisos para acceder a esa sección.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';

  // Anti timing-attack: siempre tardamos ~250ms
  $inicio = microtime(true);

  if ($email && $pass) {
    // Buscar usuario SIN filtrar por activo (para dar mensaje correcto)
    $stmt = db()->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch() ?: null;

    // Usar hash dummy si no existe el usuario
    $hashReal = $u ? $u['password'] : '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    $ok = password_verify($pass, $hashReal);

    if ($u && $ok) {
      if (!$u['activo']) {
        $error = 'Tu cuenta está desactivada. Contacta al administrador.';
      } else {
        // Login exitoso
        session_regenerate_id(true);
        $_SESSION['idUsuario'] = $u['idUsuario'];
        $_SESSION['rol'] = $u['rol'];
        $_SESSION['usuario'] = $u;

        // Obtener ID específico según rol
        $db = db();
        $idEspecifico = 0;
        if ($u['rol'] === 'estudiante') {
          $s = $db->prepare("SELECT idEstudiante FROM estudiantes WHERE idUsuario=?");
          $s->execute([$u['idUsuario']]);
          $idEspecifico = (int) $s->fetchColumn();
        } elseif ($u['rol'] === 'profesor') {
          $s = $db->prepare("SELECT idProfesor FROM profesores WHERE idUsuario=?");
          $s->execute([$u['idUsuario']]);
          $idEspecifico = (int) $s->fetchColumn();
        } elseif ($u['rol'] === 'directivo') {
          $s = $db->prepare("SELECT idDirectivo FROM directivos WHERE idUsuario=?");
          $s->execute([$u['idUsuario']]);
          $idEspecifico = (int) $s->fetchColumn();
        }
        $_SESSION['idEspecifico'] = $idEspecifico;

        // Tiempo constante
        $elapsed = (microtime(true) - $inicio) * 1000;
        if ($elapsed < 250)
          usleep((int) ((250 - $elapsed) * 1000));

        header('Location: ' . BASE_URL . '/pages/' . $u['rol'] . '/dashboard.php');
        exit;
      }
    } else {
      $error = 'Correo o contraseña incorrectos.';
    }
  } else {
    $error = 'Completa todos los campos.';
  }

  // Tiempo constante también en errores
  $elapsed = (microtime(true) - $inicio) * 1000;
  if ($elapsed < 250)
    usleep((int) ((250 - $elapsed) * 1000));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Iniciar Sesión — UNI-VIRTUAL</title>
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap"
    rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      display: flex;
      min-height: 100vh;
      font-family: 'DM Sans', Arial, sans-serif;
    }

    .ll {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 64px;
      background: linear-gradient(140deg, #0d1f4e 0%, #2462b0 100%);
      position: relative;
      overflow: hidden;
    }

    .ll::before {
      content: '';
      position: absolute;
      top: -80px;
      right: -80px;
      width: 380px;
      height: 380px;
      background: radial-gradient(circle, rgba(58, 123, 213, .16) 0%, transparent 70%);
    }

    .ll-inner {
      position: relative;
      z-index: 1;
      max-width: 460px;
    }

    .ll-badge {
      display: inline-block;
      background: rgba(255, 255, 255, .07);
      border: 1px solid rgba(255, 255, 255, .14);
      color: #d4a843;
      font-family: 'JetBrains Mono', monospace;
      font-size: 11px;
      padding: 5px 14px;
      border-radius: 20px;
      letter-spacing: 1.5px;
      margin-bottom: 26px;
    }

    .ll-title {
      font-family: 'DM Serif Display', serif;
      font-size: 52px;
      font-weight: 700;
      color: #fff;
      line-height: 1;
      letter-spacing: -1.5px;
    }

    .ll-title em {
      color: #d4a843;
      font-style: italic;
    }

    .ll-sub {
      font-size: 15px;
      color: rgba(255, 255, 255, .5);
      margin-top: 14px;
      line-height: 1.7;
    }

    .ll-stats {
      display: flex;
      gap: 14px;
      margin-top: 36px;
      flex-wrap: wrap;
    }

    .ll-s {
      padding: 12px 16px;
      background: rgba(255, 255, 255, .07);
      border: 1px solid rgba(255, 255, 255, .1);
      border-radius: 8px;
      text-align: center;
    }

    .ll-sn {
      font-family: 'DM Serif Display', serif;
      font-size: 22px;
      font-weight: 700;
      color: #d4a843;
      line-height: 1;
    }

    .ll-sl {
      font-size: 10px;
      color: rgba(255, 255, 255, .38);
      font-family: 'JetBrains Mono', monospace;
      margin-top: 2px;
    }

    .ll-chips {
      display: flex;
      gap: 7px;
      flex-wrap: wrap;
      margin-top: 24px;
    }

    .ll-chip {
      background: rgba(255, 255, 255, .06);
      border: 1px solid rgba(255, 255, 255, .1);
      color: rgba(255, 255, 255, .45);
      font-family: 'JetBrains Mono', monospace;
      font-size: 10px;
      padding: 3px 9px;
      border-radius: 4px;
    }

    .lr {
      width: 460px;
      min-width: 340px;
      background: #f8f9fc;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 56px 48px;
    }

    .lr-logo {
      font-family: 'DM Serif Display', serif;
      font-size: 24px;
      font-weight: 700;
      color: #0d1f4e;
      margin-bottom: 5px;
    }

    .lr-logo em {
      color: #2462b0;
      font-style: italic;
    }

    .lr-sub {
      font-size: 13px;
      color: #6b7a99;
      margin-bottom: 28px;
    }

    .lbl {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: #4a5568;
      margin-bottom: 6px;
      margin-top: 16px;
    }

    .inp {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid #dde3f0;
      border-radius: 7px;
      font-size: 14px;
      outline: none;
      transition: border-color .2s;
      background: #fff;
    }

    .inp:focus {
      border-color: #2462b0;
      box-shadow: 0 0 0 3px rgba(36, 98, 176, .08);
    }

    .lr-btn {
      width: 100%;
      padding: 13px;
      background: #0d1f4e;
      color: #fff;
      border: none;
      border-radius: 7px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: all .2s;
      margin-top: 20px;
      letter-spacing: .3px;
    }

    .lr-btn:hover {
      background: #2462b0;
      transform: translateY(-1px);
      box-shadow: 0 4px 16px rgba(36, 98, 176, .3);
    }

    .lr-btn:active {
      transform: translateY(0);
    }

    .lr-hint {
      background: #eef3fb;
      border: 1px solid #b8d0f0;
      color: #2462b0;
      padding: 10px 14px;
      border-radius: 6px;
      font-size: 12px;
      margin-bottom: 20px;
      line-height: 1.6;
    }

    .lr-hint strong {
      font-weight: 700;
    }

    .alert-err {
      background: #fdecea;
      color: #c0392b;
      padding: 10px 14px;
      border-radius: 6px;
      font-size: 13px;
      margin-bottom: 16px;
      border: 1px solid #f5a7a7;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .lr-foot {
      margin-top: 20px;
      font-size: 11px;
      color: #9aa5b8;
      text-align: center;
      font-family: 'JetBrains Mono', monospace;
      line-height: 1.8;
    }

    @media(max-width:768px) {
      .ll {
        display: none;
      }

      .lr {
        width: 100%;
        padding: 40px 28px;
      }
    }
  </style>
</head>

<body>
  <div class="ll">
    <div class="ll-inner">
      <div class="ll-badge">PLATAFORMA ACADÉMICA · 2026-I</div>
      <div class="ll-title">UNI<em>CAMACHO</em></div>
      <div class="ll-sub">
        Sistema web de educación virtual de la Institución Universitaria Antonio José Camacho.
      </div>
      <div class="ll-stats">
        <div class="ll-s">
          <div class="ll-sn">4</div>
          <div class="ll-sl">Roles</div>
        </div>
        <div class="ll-s">
          <div class="ll-sn">4</div>
          <div class="ll-sl">Facultades</div>
        </div>
        <div class="ll-s">
          <div class="ll-sn">13</div>
          <div class="ll-sl">Carreras</div>
        </div>
        <div class="ll-s">
          <div class="ll-sn">5</div>
          <div class="ll-sl">Materias</div>
        </div>
      </div>
      <div class="ll-chips">
        <span class="ll-chip">PHP 8</span>
        <span class="ll-chip">MySQL 8</span>
        <span class="ll-chip">React 18</span>
        <span class="ll-chip">Jitsi Meet</span>
        <span class="ll-chip">XAMPP · Apache</span>
      </div>
    </div>
  </div>

  <div class="lr">
    <div class="lr-logo">UNI<em>VIRTUAL</em></div>
    <div class="lr-sub">Ingresa con tus credenciales institucionales</div>

    <div class="lr-hint">
      <strong>Cuentas de prueba</strong> — contraseña: <strong>123456</strong><br>
      Admin: admin@univirtual.edu.co<br>
      Directivo: diana@univirtual.edu.co<br>
      Profesor: james@univirtual.edu.co · maria@univirtual.edu.co<br>
      Estudiante: edwin@univirtual.edu.co
    </div>

    <?php if ($error): ?>
      <div class="alert-err">
        <span>⚠</span>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif ?>

    <form method="POST" autocomplete="on">
      <label class="lbl" for="email">Correo institucional</label>
      <input class="inp" type="email" id="email" name="email"
        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus
        autocomplete="email" placeholder="usuario@univirtual.edu.co">

      <label class="lbl" for="password">Contraseña</label>
      <input class="inp" type="password" id="password" name="password" required autocomplete="current-password"
        placeholder="••••••••">

      <button type="submit" class="lr-btn">Iniciar Sesión →</button>
    </form>

    <div class="lr-foot">
      bcrypt · Sesiones PHP seguras · Tiempo constante<br>
      UNIAJC © 2026 — UNI-VIRTUAL v8
    </div>
  </div>
</body>

</html>
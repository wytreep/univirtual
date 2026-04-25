"""
============================================================
  UNIVIRTUAL — Suite de Tests Selenium v3.0
  Institución Universitaria Antonio José Camacho

  Cambios v3.0 respecto a v2.0:
    - API: usa execute_async_script + fetch() desde el browser
      Resuelve el HTTP 401 causado por cookies incompatibles de requests
    - Módulos: rutas corregidas — el proyecto es SPA, solo existe
      dashboard.php por panel. Los módulos se testean via API.
    - E2E: rutas reales, datos verificados via API
    - Reportes BD-301: usa maria (idProfesor=2), no james
    - do_login: sleep(2.5) + reintento para timing race
    - wait_react: espera contenido React en #root

  REQUISITO PREVIO: ejecutar fix_usuarios.sql en phpMyAdmin
  para activar Maria, Angel y Carlos (activo=1)

  Ejecutar:
    pip install selenium webdriver-manager requests
    python tests_selenium_univirtual.py
============================================================
"""

import time, sys, json, requests
from datetime import datetime

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import (
    NoSuchElementException, WebDriverException, TimeoutException
)
from webdriver_manager.chrome import ChromeDriverManager

BASE_URL = "http://localhost/univirtual"
TIMEOUT  = 10
HEADLESS = False
PASS     = "123456"

PAGES = {
    "login":                "/pages/auth/login.php",
    "logout":               "/pages/auth/logout.php",
    "admin_dashboard":      "/pages/admin/dashboard.php",
    "profesor_dashboard":   "/pages/profesor/dashboard.php",
    "estudiante_dashboard": "/pages/estudiante/dashboard.php",
    "estudiante_notas":     "/pages/estudiante/notas.php",
    "estudiante_aula":      "/pages/estudiante/aula.php",
    "estudiante_foro":      "/pages/estudiante/foro.php",
}

API = {
    "stats":         "/api/v1/stats.php",
    "usuarios":      "/api/v1/usuarios.php",
    "materias":      "/api/v1/materias.php",
    "videollamadas": "/api/v1/videollamadas.php",
    "estudiante":    "/api/v1/estudiante.php",
    "profesor":      "/api/v1/profesor.php",
    "inscripciones": "/api/v1/inscripciones.php",
    "reporte_notas": "/api/reporte_notas.php",
    "reporte_asist": "/api/reporte_asistencia.php",
}

USUARIOS = {
    "admin":  {"email": "admin@univirtual.edu.co",  "password": PASS},
    "james":  {"email": "james@univirtual.edu.co",  "password": PASS},
    "maria":  {"email": "maria@univirtual.edu.co",  "password": PASS},
    "edwin":  {"email": "edwin@univirtual.edu.co",  "password": PASS},
    "angel":  {"email": "angel@univirtual.edu.co",  "password": PASS},
    "laura":  {"email": "laura@univirtual.edu.co",  "password": PASS},
    "carlos": {"email": "carlos@univirtual.edu.co", "password": PASS},
}

results = []

def log(category, name, passed, detail="", elapsed=0):
    icon  = "✓ PASS" if passed else "✗ FAIL"
    color = "\033[92m" if passed else "\033[91m"
    print(f"  {color}{icon}\033[0m  [{category}] {name}")
    if detail:
        print(f"           → {detail}  ({elapsed:.0f}ms)")
    results.append({"category": category, "name": name,
                    "passed": passed, "detail": detail, "ms": elapsed})

def section(title):
    print(f"\n{'─'*64}\n  {title}\n{'─'*64}")

def now_ms():
    return time.perf_counter() * 1000

def url(path):
    return BASE_URL + path

# ── Driver ─────────────────────────────────────────────────────────────────
def make_driver():
    opts = Options()
    if HEADLESS:
        opts.add_argument("--headless=new")
    opts.add_argument("--no-sandbox")
    opts.add_argument("--disable-dev-shm-usage")
    opts.add_argument("--window-size=1366,768")
    d = webdriver.Chrome(
        service=Service(ChromeDriverManager().install()),
        options=opts
    )
    d.set_script_timeout(15)
    return d

def wait_react(driver, timeout=12):
    try:
        WebDriverWait(driver, timeout).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "#root > div, .page"))
        )
        return True
    except TimeoutException:
        return False

# ── fetch() desde el browser — resuelve el problema de cookies ─────────────
def browser_fetch(driver, path, params=None):
    """
    Ejecuta fetch() con credentials:'include' desde dentro del browser.
    Las cookies de sesión PHP se envían automáticamente — sin problemas
    de dominio o PHPSESSID que tiene requests.Session.
    """
    query = ""
    if params:
        query = "?" + "&".join(f"{k}={v}" for k, v in params.items())
    full_url = url(path) + query

    script = """
    var done = arguments[arguments.length - 1];
    fetch(arguments[0], {credentials: 'include'})
      .then(function(r) {
        var s = r.status;
        var ct = r.headers.get('content-type') || '';
        return r.text().then(function(b) {
          var j = null;
          try { j = JSON.parse(b); } catch(e) {}
          done({status: s, json: j, bytes: b.length, ct: ct});
        });
      })
      .catch(function(e) { done({status: 0, json: null, bytes: 0, error: e.message}); });
    """
    try:
        return driver.execute_async_script(script, full_url)
    except Exception as e:
        return {"status": 0, "json": None, "bytes": 0, "error": str(e)}

# ── Login / Logout ──────────────────────────────────────────────────────────
def do_login(driver, email, password, retries=2):
    for attempt in range(retries):
        driver.get(url(PAGES["login"]))
        try:
            WebDriverWait(driver, 6).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "form"))
            )
        except TimeoutException:
            if attempt < retries - 1:
                time.sleep(1.5)
                continue
            return False
        try:
            campo_email = None
            for attr in ["email", "correo", "usuario", "user"]:
                try:
                    campo_email = driver.find_element(By.NAME, attr)
                    break
                except NoSuchElementException:
                    pass
            if not campo_email:
                campo_email = driver.find_element(
                    By.CSS_SELECTOR, "input[type='email'], input[type='text']")
            campo_pass = driver.find_element(By.CSS_SELECTOR, "input[type='password']")
            campo_email.clear(); campo_email.send_keys(email)
            campo_pass.clear();  campo_pass.send_keys(password)
            driver.find_element(
                By.CSS_SELECTOR, "button[type='submit'], input[type='submit']"
            ).click()
            time.sleep(2.5)
            return PAGES["login"] not in driver.current_url
        except Exception:
            if attempt < retries - 1:
                time.sleep(1.5)
                continue
            return False
    return False

def do_logout(driver):
    try:
        driver.get(url(PAGES["logout"]))
        time.sleep(2.0)
        driver.delete_all_cookies()
        driver.get("about:blank")
        time.sleep(0.5)
    except Exception:
        pass

# ═══════════════════════════════════════════════════════════
#  1 — PÁGINAS PÚBLICAS
# ═══════════════════════════════════════════════════════════
def test_paginas_publicas(driver):
    section("1 — PÁGINAS PÚBLICAS")
    t = now_ms()
    try:
        driver.get(url(PAGES["login"]))
        WebDriverWait(driver, 8).until(EC.presence_of_element_located((By.CSS_SELECTOR, "form")))
        log("Páginas", "Login — carga correctamente (HTTP 200)", True,
            f"{len(driver.page_source)} bytes", now_ms()-t)
    except Exception as e:
        log("Páginas", "Login — carga correctamente (HTTP 200)", False, str(e), now_ms()-t)

    for key in ["admin_dashboard", "profesor_dashboard", "estudiante_dashboard"]:
        t = now_ms()
        driver.get(url(PAGES[key]))
        time.sleep(1.2)
        log("Páginas", f"Sin sesión → {key} redirige a login",
            "login" in driver.current_url, driver.current_url, now_ms()-t)

# ═══════════════════════════════════════════════════════════
#  2 — AUTENTICACIÓN
# ═══════════════════════════════════════════════════════════
def test_autenticacion(driver):
    section("2 — AUTENTICACIÓN  (password de todos: 123456)")

    for email, pwd, desc in [
        ("admin@univirtual.edu.co",  PASS, "Admin Sistema  (rol: admin)"),
        ("james@univirtual.edu.co",  PASS, "James Medina   (rol: profesor)"),
        ("maria@univirtual.edu.co",  PASS, "Maria Angulo   (rol: profesor)"),
        ("edwin@univirtual.edu.co",  PASS, "Edwin Carabali (rol: estudiante)"),
        ("angel@univirtual.edu.co",  PASS, "Angel Angulo   (rol: estudiante)"),
        ("laura@univirtual.edu.co",  PASS, "Laura Córdoba  (rol: estudiante)"),
        ("carlos@univirtual.edu.co", PASS, "Carlos Pino    (rol: estudiante)"),
    ]:
        t = now_ms()
        entro = do_login(driver, email, pwd)
        log("Auth", f"✔ Login válido — {desc}", entro,
            f"→ {driver.current_url}" if entro else "No redirigió — verificar activo=1 en BD",
            now_ms()-t)
        if entro: do_logout(driver)

    for email, pwd, desc in [
        ("noexiste@univirtual.edu.co", PASS,       "usuario no registrado"),
        ("admin@univirtual.edu.co",    "wrongpass", "contraseña incorrecta"),
        ("",                           "",          "campos vacíos"),
    ]:
        t = now_ms()
        entro = do_login(driver, email, pwd)
        log("Auth", f"✖ Login inválido — {desc}", not entro,
            "Bloqueado ✓" if not entro else f"¡Entró! → {driver.current_url}",
            now_ms()-t)
        if entro: do_logout(driver)

# ═══════════════════════════════════════════════════════════
#  3 — API REST  (fetch desde el browser — cookies automáticas)
# ═══════════════════════════════════════════════════════════
def test_api_rest(driver):
    section("3 — API REST  (fetch desde browser con credentials:'include')")

    # Admin
    if not do_login(driver, USUARIOS["admin"]["email"], USUARIOS["admin"]["password"]):
        log("API", "Login admin", False, "No se pudo hacer login"); return

    for path, params, nombre in [
        (API["stats"],        {"action": "dashboard"}, "stats.php         — dashboard admin"),
        (API["usuarios"],     {"action": "list"},       "usuarios.php      — lista usuarios"),
        (API["materias"],     {"action": "list"},       "materias.php      — lista materias"),
        (API["videollamadas"],{"action": "lista"},      "videollamadas.php — lista (admin)"),
        (API["inscripciones"],{"idMateria": 1},         "inscripciones.php — lista IS-201"),
    ]:
        t = now_ms()
        r = browser_fetch(driver, path, params)
        ok = r.get("status") == 200 and r.get("json") is not None
        log("API", nombre, ok,
            f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes" +
            (" — JSON ✓" if r.get("json") else " — ⚠ no JSON"),
            now_ms()-t)

    do_logout(driver)

    # Estudiante
    if do_login(driver, USUARIOS["edwin"]["email"], USUARIOS["edwin"]["password"]):
        t = now_ms()
        r = browser_fetch(driver, API["estudiante"], {"action": "dashboard"})
        ok = r.get("status") == 200 and r.get("json") is not None
        log("API", "estudiante.php    — dashboard (sesión estudiante)", ok,
            f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes" +
            (" — JSON ✓" if r.get("json") else ""), now_ms()-t)
        do_logout(driver)

    # Profesor
    if do_login(driver, USUARIOS["james"]["email"], USUARIOS["james"]["password"]):
        t = now_ms()
        r = browser_fetch(driver, API["profesor"], {"action": "dashboard"})
        ok = r.get("status") == 200 and r.get("json") is not None
        log("API", "profesor.php      — dashboard (sesión profesor)", ok,
            f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes" +
            (" — JSON ✓" if r.get("json") else ""), now_ms()-t)
        do_logout(driver)

    # Sin sesión → 401
    t = now_ms()
    try:
        r = requests.get(url(API["usuarios"]), params={"action": "list"}, timeout=8)
        log("API", "Sin sesión → retorna 401", r.status_code == 401,
            f"HTTP {r.status_code}", now_ms()-t)
    except Exception as e:
        log("API", "Sin sesión → retorna 401", False, str(e), now_ms()-t)

# ═══════════════════════════════════════════════════════════
#  4 — REPORTES
# ═══════════════════════════════════════════════════════════
def test_reportes(driver):
    section("4 — REPORTES")

    # James → IS-201
    if not do_login(driver, USUARIOS["james"]["email"], USUARIOS["james"]["password"]):
        log("Reportes", "Login james", False, "No se pudo hacer login"); return

    for path, params, nombre in [
        (API["reporte_notas"], {"idMateria": 1, "formato": "pdf"},  "Notas IS-201 — PDF"),
        (API["reporte_notas"], {"idMateria": 1, "formato": "csv"},  "Notas IS-201 — CSV"),
        (API["reporte_asist"], {"idAula": 1,    "formato": "pdf"},  "Asistencia IS-201 — PDF"),
    ]:
        t = now_ms()
        r = browser_fetch(driver, path, params)
        ok = r.get("status") == 200 and r.get("bytes", 0) > 100
        log("Reportes", nombre, ok,
            f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes", now_ms()-t)

    # XLSX — depende de Python en el servidor
    for path, params, nombre in [
        (API["reporte_notas"], {"idMateria": 1, "formato": "xlsx"}, "Notas IS-201 — XLSX"),
        (API["reporte_asist"], {"idAula": 1,    "formato": "xlsx"}, "Asistencia IS-201 — XLSX"),
    ]:
        t = now_ms()
        r = browser_fetch(driver, path, params)
        st, byt = r.get("status"), r.get("bytes", 0)
        if st == 200 and byt > 100:
            log("Reportes", nombre, True, f"HTTP 200 — {byt} bytes ✓", now_ms()-t)
        else:
            log("Reportes", nombre, False,
                f"HTTP {st} — Python/openpyxl no disponible → pip install openpyxl",
                now_ms()-t)

    do_logout(driver)

    # Maria → BD-301
    maria_ok = do_login(driver, USUARIOS["maria"]["email"], USUARIOS["maria"]["password"])
    for path, params, nombre in [
        (API["reporte_notas"], {"idMateria": 2, "formato": "pdf"}, "Notas BD-301 — PDF (Maria)"),
        (API["reporte_asist"], {"idAula": 2,    "formato": "pdf"}, "Asistencia BD-301 — PDF (Maria)"),
    ]:
        if not maria_ok:
            log("Reportes", nombre, False, "Maria inactiva — ejecutar fix_usuarios.sql"); continue
        t = now_ms()
        r = browser_fetch(driver, path, params)
        ok = r.get("status") == 200 and r.get("bytes", 0) > 100
        log("Reportes", nombre, ok,
            f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes", now_ms()-t)
    if maria_ok: do_logout(driver)

# ═══════════════════════════════════════════════════════════
#  5 — DASHBOARD ADMIN
# ═══════════════════════════════════════════════════════════
def test_dashboard_admin(driver):
    section("5 — DASHBOARD ADMIN")

    if not do_login(driver, USUARIOS["admin"]["email"], USUARIOS["admin"]["password"]):
        log("Admin", "Login admin", False, "No se pudo hacer login"); return

    driver.get(url(PAGES["admin_dashboard"]))
    t = now_ms()
    wait_react(driver, timeout=12)
    ok = "login" not in driver.current_url
    log("Admin", "Dashboard — carga sin redirigir", ok, driver.current_url, now_ms()-t)

    t = now_ms()
    elems = driver.find_elements(By.CSS_SELECTOR,
        ".card, .stat, [class*='stat'], [class*='card'], .badge")
    log("Admin", "Dashboard — contiene tarjetas/estadísticas",
        len(elems) > 0, f"{len(elems)} elementos", now_ms()-t)

    # Módulos via API (SPA — no hay URLs separadas)
    for path, params, nombre in [
        (API["usuarios"],    {"action": "list"},    "Usuarios      — API (7 en BD)"),
        (API["materias"],    {"action": "list"},    "Materias      — API (2 en BD)"),
        (API["videollamadas"],{"action": "lista"},  "Videollamadas — API (2 en BD)"),
        (API["stats"],       {"action": "dashboard"},"Stats general — API"),
    ]:
        t = now_ms()
        r = browser_fetch(driver, path, params)
        ok = r.get("status") == 200
        cnt = ""
        if ok and r.get("json"):
            d = r["json"].get("data")
            if isinstance(d, list): cnt = f" — {len(d)} registros"
        log("Admin", nombre, ok,
            f"HTTP {r.get('status')}{cnt}", now_ms()-t)

    do_logout(driver)

# ═══════════════════════════════════════════════════════════
#  6 — DASHBOARD PROFESOR
# ═══════════════════════════════════════════════════════════
def test_dashboard_profesor(driver):
    section("6 — DASHBOARD PROFESOR")

    for user_key, aula_id, materia in [("james", 1, "IS-201"), ("maria", 2, "BD-301")]:
        if not do_login(driver, USUARIOS[user_key]["email"], USUARIOS[user_key]["password"]):
            log("Profesor", f"Login {user_key}", False,
                "No se pudo hacer login — verificar activo=1 en BD")
            continue

        t = now_ms()
        driver.get(url(PAGES["profesor_dashboard"]))
        wait_react(driver, timeout=10)
        ok = "login" not in driver.current_url
        log("Profesor", f"{user_key} — Dashboard", ok, driver.current_url, now_ms()-t)

        t = now_ms()
        r = browser_fetch(driver, API["profesor"], {"action": "aula", "idAula": aula_id})
        ok = r.get("status") == 200 and r.get("json") is not None
        log("Profesor", f"{user_key} — API aula {materia}", ok,
            f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes", now_ms()-t)

        t = now_ms()
        r = browser_fetch(driver, API["profesor"], {"action": "entregas", "idAula": aula_id})
        ok = r.get("status") == 200
        log("Profesor", f"{user_key} — API entregas {materia}", ok,
            f"HTTP {r.get('status')}", now_ms()-t)

        t = now_ms()
        driver.get(url(PAGES["admin_dashboard"]))
        time.sleep(1.5)
        log("Profesor", f"{user_key} — NO accede a /admin/dashboard",
            "login" in driver.current_url, driver.current_url, now_ms()-t)

        do_logout(driver)

# ═══════════════════════════════════════════════════════════
#  7 — DASHBOARD ESTUDIANTE
# ═══════════════════════════════════════════════════════════
def test_dashboard_estudiante(driver):
    section("7 — DASHBOARD ESTUDIANTE")

    for user_key in ["edwin", "angel", "laura", "carlos"]:
        if not do_login(driver, USUARIOS[user_key]["email"], USUARIOS[user_key]["password"]):
            log("Estudiante", f"Login {user_key}", False,
                "No se pudo hacer login — verificar activo=1 en BD")
            continue

        for path, nombre in [
            (PAGES["estudiante_dashboard"],          f"{user_key} — Dashboard"),
            (PAGES["estudiante_notas"],              f"{user_key} — Notas"),
            (PAGES["estudiante_aula"] + "?idAula=1", f"{user_key} — Aula IS-201"),
            (PAGES["estudiante_aula"] + "?idAula=2", f"{user_key} — Aula BD-301"),
            (PAGES["estudiante_foro"] + "?idAula=1", f"{user_key} — Foro IS-201"),
        ]:
            t = now_ms()
            driver.get(url(path))
            WebDriverWait(driver, 5).until(EC.presence_of_element_located((By.TAG_NAME, "body")))
            ok = "login" not in driver.current_url
            log("Estudiante", nombre, ok, driver.current_url, now_ms()-t)

        for ruta, desc in [
            (PAGES["admin_dashboard"],    "admin/dashboard"),
            (PAGES["profesor_dashboard"], "profesor/dashboard"),
        ]:
            t = now_ms()
            driver.get(url(ruta))
            time.sleep(1.5)
            log("Estudiante", f"{user_key} — NO accede a {desc}",
                "login" in driver.current_url, driver.current_url, now_ms()-t)

        do_logout(driver)

# ═══════════════════════════════════════════════════════════
#  8 — SESIONES
# ═══════════════════════════════════════════════════════════
def test_sesiones(driver):
    section("8 — SESIONES")

    t = now_ms()
    do_login(driver, USUARIOS["admin"]["email"], USUARIOS["admin"]["password"])
    do_logout(driver)
    driver.get(url(PAGES["admin_dashboard"]))
    time.sleep(1)
    log("Sesiones", "Logout limpia sesión → redirige a login",
        "login" in driver.current_url, driver.current_url, now_ms()-t)

    for user_key, desc in [("edwin", "estudiante"), ("james", "profesor")]:
        t = now_ms()
        do_login(driver, USUARIOS[user_key]["email"], USUARIOS[user_key]["password"])
        driver.get(url(PAGES["admin_dashboard"]))
        time.sleep(1.5)
        log("Sesiones", f"Sesión {desc} bloqueada en /admin/",
            "login" in driver.current_url, driver.current_url, now_ms()-t)
        do_logout(driver)

# ═══════════════════════════════════════════════════════════
#  9 — MÓDULOS FUNCIONALES  (via API desde browser)
# ═══════════════════════════════════════════════════════════
def test_modulos(driver):
    section("9 — MÓDULOS FUNCIONALES  (SPA — testeo via API interna)")

    if not do_login(driver, USUARIOS["admin"]["email"], USUARIOS["admin"]["password"]):
        log("Módulos", "Login admin", False, "No se pudo hacer login"); return

    driver.get(url(PAGES["admin_dashboard"]))
    wait_react(driver, timeout=12)

    for path, params, nombre in [
        (API["usuarios"],     {"action": "list"},    "Usuarios        — módulo lista"),
        (API["materias"],     {"action": "list"},    "Materias        — módulo lista"),
        (API["inscripciones"],{"idMateria": 1},      "Inscripciones   — módulo (IS-201)"),
        (API["videollamadas"],{"action": "lista"},   "Videollamadas   — módulo lista"),
        (API["stats"],        {"action": "dashboard"},"Estadísticas   — módulo dashboard"),
    ]:
        t = now_ms()
        r = browser_fetch(driver, path, params)
        ok  = r.get("status") == 200
        det = f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes"
        log("Módulos", nombre, ok, det, now_ms()-t)

    do_logout(driver)

    # Módulos de profesor
    if do_login(driver, USUARIOS["james"]["email"], USUARIOS["james"]["password"]):
        driver.get(url(PAGES["profesor_dashboard"]))
        wait_react(driver, timeout=10)
        for action, nombre in [
            ("aula",     "Aula virtual IS-201 — módulo"),
            ("entregas", "Entregas IS-201     — módulo"),
            ("materias", "Materias profesor   — módulo"),
        ]:
            t = now_ms()
            r = browser_fetch(driver, API["profesor"],
                              {"action": action, "idAula": 1} if action != "materias"
                              else {"action": action})
            ok = r.get("status") == 200
            log("Módulos", nombre, ok,
                f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes", now_ms()-t)
        do_logout(driver)

# ═══════════════════════════════════════════════════════════
#  10 — FLUJO E2E
# ═══════════════════════════════════════════════════════════
def test_e2e(driver):
    section("10 — FLUJO E2E  (James → Aula IS-201 → Edwin)")

    if not do_login(driver, USUARIOS["james"]["email"], USUARIOS["james"]["password"]):
        log("E2E", "Login James", False, "No se pudo hacer login"); return

    t = now_ms()
    driver.get(url(PAGES["profesor_dashboard"]))
    wait_react(driver, timeout=12)
    log("E2E", "James — dashboard carga con React",
        "login" not in driver.current_url, driver.current_url, now_ms()-t)

    t = now_ms()
    r = browser_fetch(driver, API["profesor"], {"action": "aula", "idAula": 1})
    data = r.get("json", {}).get("data", {}) if r.get("json") else {}
    mats = data.get("totalMateriales", "?") if isinstance(data, dict) else "?"
    acts = data.get("totalActividades", "?") if isinstance(data, dict) else "?"
    log("E2E", f"James — aula IS-201 ({mats} materiales, {acts} actividades)",
        r.get("status") == 200,
        f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes", now_ms()-t)

    t = now_ms()
    r = browser_fetch(driver, API["profesor"], {"action": "entregas", "idAula": 1})
    n = len((r.get("json") or {}).get("data", []))
    log("E2E", f"James — entregas IS-201 ({n} registros)",
        r.get("status") == 200, f"HTTP {r.get('status')}", now_ms()-t)

    do_logout(driver)

    if not do_login(driver, USUARIOS["edwin"]["email"], USUARIOS["edwin"]["password"]):
        log("E2E", "Login Edwin", False, "No se pudo hacer login"); return

    t = now_ms()
    r = browser_fetch(driver, API["estudiante"], {"action": "aula", "idAula": 1})
    log("E2E", "Edwin — API aula IS-201 retorna datos",
        r.get("status") == 200 and r.get("json") is not None,
        f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes", now_ms()-t)

    t = now_ms()
    r = browser_fetch(driver, API["estudiante"], {"action": "notas"})
    notas = (r.get("json") or {}).get("data", {})
    prom  = notas.get("promedio") if isinstance(notas, dict) else None
    log("E2E", f"Edwin — notas (promedio: {prom})",
        r.get("status") == 200,
        f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes", now_ms()-t)

    t = now_ms()
    driver.get(url(PAGES["estudiante_notas"]))
    wait_react(driver, timeout=10)
    log("E2E", "Edwin — página notas carga correctamente",
        "login" not in driver.current_url, driver.current_url, now_ms()-t)

    do_logout(driver)

# ── Resumen ────────────────────────────────────────────────────────────────
def print_summary(total_ms):
    print(f"\n{'═'*64}")
    print("  RESUMEN — UNIVIRTUAL Selenium Audit v3.0")
    print(f"{'═'*64}")
    cats = {}
    for r in results:
        c = r["category"]
        cats.setdefault(c, {"pass": 0, "fail": 0})
        cats[c]["pass" if r["passed"] else "fail"] += 1
    total_pass = sum(c["pass"] for c in cats.values())
    total_fail = sum(c["fail"] for c in cats.values())
    total = total_pass + total_fail
    pct   = round(total_pass / total * 100) if total else 0
    print(f"\n  {'Categoría':<22} {'✓':>6} {'✗':>6}  {'%':>6}")
    print(f"  {'─'*42}")
    for cat, c in cats.items():
        tot  = c["pass"] + c["fail"]
        rate = f"{round(c['pass']/tot*100)}%" if tot else "—"
        print(f"  {cat:<22} {c['pass']:>6} {c['fail']:>6}  {rate:>6}")
    print(f"\n  Total    : {total}")
    print(f"  Pasaron  : {total_pass}  ({pct}%)")
    print(f"  Fallaron : {total_fail}")
    print(f"  Tiempo   : {total_ms:.0f}ms")
    print(f"  Fecha    : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"{'═'*64}")
    fallos = [r for r in results if not r["passed"]]
    if fallos:
        print(f"\n  FALLOS ({len(fallos)}):\n  {'─'*60}")
        for r in fallos:
            print(f"  ✗ [{r['category']}] {r['name']}")
            if r['detail']: print(f"      {r['detail']}\n")

def main():
    print("=" * 64)
    print("  UNIVIRTUAL — Auditoría Selenium v3.0")
    print(f"  Base URL : {BASE_URL}")
    print(f"  Inicio   : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 64)
    print("\n  ⚠ REQUISITO: ejecutar fix_usuarios.sql en phpMyAdmin\n")

    start  = now_ms()
    driver = None
    try:
        driver = make_driver()
        test_paginas_publicas(driver)
        test_autenticacion(driver)
        test_api_rest(driver)
        test_reportes(driver)
        test_dashboard_admin(driver)
        test_dashboard_profesor(driver)
        test_dashboard_estudiante(driver)
        test_sesiones(driver)
        test_modulos(driver)
        test_e2e(driver)
    except WebDriverException as e:
        print(f"\n\033[91m[ERROR] ChromeDriver: {e}\033[0m")
        sys.exit(1)
    finally:
        if driver: driver.quit()
    print_summary(now_ms() - start)

if __name__ == "__main__":
    main()
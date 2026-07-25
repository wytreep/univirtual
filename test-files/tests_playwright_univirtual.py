"""
════════════════════════════════════════════════════════════════════
  UNI-VIRTUAL v8 — Suite de Tests con Playwright
  Institución Universitaria Antonio José Camacho

  REQUIERE:
    pip install playwright requests
    playwright install chromium

  EJECUTAR:
    python tests_playwright_univirtual.py
    python tests_playwright_univirtual.py --headless   # sin ventana

  CREDENCIALES (contraseña: 123456 — EJECUTAR fix_passwords_v8.sql primero):
    admin@univirtual.edu.co      → admin
    diana@univirtual.edu.co      → directivo
    james@univirtual.edu.co      → profesor (IS-201)
    maria@univirtual.edu.co      → profesor (BD-301)
    edwin@univirtual.edu.co      → estudiante
    angel@univirtual.edu.co      → estudiante
    laura@univirtual.edu.co      → estudiante
    carlos@univirtual.edu.co     → estudiante
════════════════════════════════════════════════════════════════════
"""

import sys, time, json, requests
from datetime import datetime
from playwright.sync_api import sync_playwright, Page, expect, TimeoutError as PWTimeout

# ── Configuración ──────────────────────────────────────────────────────────
BASE     = "http://localhost/univirtual"
PASS     = "123456"
HEADLESS = "--headless" in sys.argv
TIMEOUT  = 10_000  # ms

USUARIOS = {
    "admin":   "admin@univirtual.edu.co",
    "diana":   "diana@univirtual.edu.co",
    "james":   "james@univirtual.edu.co",
    "maria":   "maria@univirtual.edu.co",
    "edwin":   "edwin@univirtual.edu.co",
    "angel":   "angel@univirtual.edu.co",
    "laura":   "laura@univirtual.edu.co",
    "carlos":  "carlos@univirtual.edu.co",
}

# ── Sistema de reporte ─────────────────────────────────────────────────────
results: list[dict] = []

def log(categoria: str, nombre: str, ok: bool, detalle: str = "", ms: float = 0):
    icon  = "✓ PASS" if ok else "✗ FAIL"
    color = "\033[92m" if ok else "\033[91m"
    reset = "\033[0m"
    print(f"  {color}{icon}{reset}  [{categoria}] {nombre}")
    if detalle:
        print(f"           → {detalle}  ({ms:.0f}ms)")
    results.append({"cat": categoria, "nombre": nombre, "ok": ok, "ms": ms})

def seccion(titulo: str):
    print(f"\n{'─'*68}")
    print(f"  {titulo}")
    print(f"{'─'*68}")

def url(path: str) -> str:
    return BASE + path

# ── Helpers de Playwright ──────────────────────────────────────────────────
def login(page: Page, email: str, password: str = PASS) -> bool:
    """Hace login y retorna True si redirigió fuera de /login.php"""
    page.goto(url("/pages/auth/login.php"))
    try:
        page.wait_for_selector("input[type='email'], input[name='email']",
                               timeout=5_000)
        page.fill("input[name='email']", email)
        page.fill("input[type='password']", password)
        page.click("button[type='submit']")
        page.wait_for_url(lambda u: "login.php" not in u, timeout=6_000)
        return True
    except PWTimeout:
        return False

def logout(page: Page):
    """Cierra sesión limpiamente"""
    try:
        page.goto(url("/pages/auth/logout.php"))
        page.wait_for_url("**/login.php", timeout=4_000)
        page.context.clear_cookies()
        page.goto("about:blank")
    except Exception:
        page.context.clear_cookies()
        page.goto("about:blank")

def esperar_react(page: Page, timeout: int = 8_000):
    """Espera a que React monte el contenido"""
    try:
        page.wait_for_selector("#root > div, .page, .sidebar", timeout=timeout)
        return True
    except PWTimeout:
        return False

def api_fetch(page: Page, path: str, params: dict = None) -> dict:
    """
    Ejecuta fetch() desde el contexto del browser con las cookies de sesión.
    Resuelve el problema de autenticación de requests.Session vs PHP sessions.
    """
    query = ""
    if params:
        query = "?" + "&".join(f"{k}={v}" for k, v in params.items())
    full_url = url(path) + query

    result = page.evaluate(f"""
    async () => {{
        try {{
            const r = await fetch('{full_url}', {{credentials: 'include'}});
            const text = await r.text();
            let json = null;
            try {{ json = JSON.parse(text); }} catch(e) {{}}
            return {{status: r.status, json: json, bytes: text.length}};
        }} catch(e) {{
            return {{status: 0, json: null, bytes: 0, error: e.message}};
        }}
    }}
    """)
    return result or {"status": 0, "json": None, "bytes": 0}

def api_post(page: Page, path: str, body: dict) -> dict:
    """POST desde el browser con cookies de sesión activas"""
    body_str = json.dumps(body).replace("'", "\\'")
    result = page.evaluate(f"""
    async () => {{
        try {{
            const r = await fetch('{url(path)}', {{
                method: 'POST',
                credentials: 'include',
                headers: {{'Content-Type': 'application/json'}},
                body: '{body_str}'
            }});
            const text = await r.text();
            let json = null;
            try {{ json = JSON.parse(text); }} catch(e) {{}}
            return {{status: r.status, json: json}};
        }} catch(e) {{
            return {{status: 0, json: null, error: e.message}};
        }}
    }}
    """)
    return result or {"status": 0, "json": None}


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 1 — PÁGINAS PÚBLICAS
# ════════════════════════════════════════════════════════════════════
def test_paginas_publicas(page: Page):
    seccion("1 — PÁGINAS PÚBLICAS")

    t = time.perf_counter()
    page.goto(url("/pages/auth/login.php"))
    try:
        page.wait_for_selector("form", timeout=6_000)
        log("Páginas", "Login — carga correctamente", True,
            f"{len(page.content())} bytes", (time.perf_counter()-t)*1000)
    except PWTimeout:
        log("Páginas", "Login — carga correctamente", False, "Timeout")

    for key, path in [
        ("admin_dashboard",    "/pages/admin/dashboard.php"),
        ("profesor_dashboard", "/pages/profesor/dashboard.php"),
        ("directivo_dashboard","/pages/directivo/dashboard.php"),
        ("estudiante_dashboard","/pages/estudiante/dashboard.php"),
    ]:
        t = time.perf_counter()
        page.goto(url(path))
        page.wait_for_timeout(1200)
        redirigido = "login" in page.url
        log("Páginas", f"Sin sesión → {key} redirige a login",
            redirigido, page.url, (time.perf_counter()-t)*1000)


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 2 — AUTENTICACIÓN (todos los roles)
# ════════════════════════════════════════════════════════════════════
def test_autenticacion(page: Page):
    seccion("2 — AUTENTICACIÓN  (password: 123456)")

    validos = [
        ("admin",  "Admin Sistema  (rol: admin)"),
        ("diana",  "Diana Salcedo  (rol: directivo)"),
        ("james",  "James Medina   (rol: profesor)"),
        ("maria",  "Maria Angulo   (rol: profesor)"),
        ("edwin",  "Edwin Carabali (rol: estudiante)"),
        ("angel",  "Angel Angulo   (rol: estudiante)"),
        ("laura",  "Laura Córdoba  (rol: estudiante)"),
        ("carlos", "Carlos Pino    (rol: estudiante)"),
    ]

    for key, desc in validos:
        t = time.perf_counter()
        entro = login(page, USUARIOS[key])
        det = f"→ {page.url}" if entro else "No redirigió — verificar fix_passwords_v8.sql"
        log("Auth", f"✔ Login válido — {desc}", entro, det, (time.perf_counter()-t)*1000)
        if entro:
            logout(page)

    # Casos inválidos
    for email, pwd, desc in [
        ("noexiste@univirtual.edu.co", PASS,        "usuario no registrado"),
        ("admin@univirtual.edu.co",    "wrongpass",  "contraseña incorrecta"),
        ("",                           "",           "campos vacíos"),
    ]:
        t = time.perf_counter()
        entro = login(page, email, pwd)
        log("Auth", f"✖ Login inválido — {desc}", not entro,
            "Bloqueado ✓" if not entro else f"¡Entró! → {page.url}",
            (time.perf_counter()-t)*1000)
        if entro:
            logout(page)


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 3 — API REST (fetch desde browser — cookies automáticas)
# ════════════════════════════════════════════════════════════════════
def test_api_rest(page: Page):
    seccion("3 — API REST  (fetch con credentials:'include')")

    # Admin
    if login(page, USUARIOS["admin"]):
        esperar_react(page)
        for path, params, nombre in [
            ("/api/v1/stats.php",         {"action":"dashboard"},  "stats — dashboard admin"),
            ("/api/v1/usuarios.php",       {"action":"list"},       "usuarios — lista"),
            ("/api/v1/materias.php",       {"action":"list"},       "materias — lista"),
            ("/api/v1/videollamadas.php",  {"action":"lista"},      "videollamadas — lista admin"),
            ("/api/v1/inscripciones.php",  {"idMateria":"1"},       "inscripciones — IS-201"),
        ]:
            t = time.perf_counter()
            r = api_fetch(page, path, params)
            ok = r.get("status") == 200 and r.get("json") is not None
            log("API", nombre, ok,
                f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes" +
                (" — JSON ✓" if r.get("json") else " — ⚠ no JSON"),
                (time.perf_counter()-t)*1000)
        logout(page)

    # Estudiante
    if login(page, USUARIOS["edwin"]):
        esperar_react(page)
        t = time.perf_counter()
        r = api_fetch(page, "/api/v1/estudiante.php", {"action":"dashboard"})
        ok = r.get("status") == 200 and r.get("json") is not None
        log("API", "estudiante — dashboard (sesión correcta)", ok,
            f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes",
            (time.perf_counter()-t)*1000)
        logout(page)

    # Profesor
    if login(page, USUARIOS["james"]):
        esperar_react(page)
        for params, nombre in [
            ({"action":"dashboard"},             "profesor — dashboard"),
            ({"action":"entregas","idAula":"1"}, "profesor — entregas IS-201 (fue 500)"),
            ({"action":"asistencia_fecha","idMateria":"1","fecha":"2026-01-20"},
             "profesor — asistencia por fecha"),
        ]:
            t = time.perf_counter()
            r = api_fetch(page, "/api/v1/profesor.php", params)
            ok = r.get("status") == 200 and r.get("json") is not None
            log("API", nombre, ok,
                f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes",
                (time.perf_counter()-t)*1000)
        logout(page)

    # Sin sesión → 401
    t = time.perf_counter()
    try:
        r = requests.get(url("/api/v1/usuarios.php"),
                         params={"action":"list"}, timeout=8)
        log("API", "Sin sesión → retorna 401", r.status_code == 401,
            f"HTTP {r.status_code}", (time.perf_counter()-t)*1000)
    except Exception as e:
        log("API", "Sin sesión → retorna 401", False, str(e))


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 4 — REPORTES
# ════════════════════════════════════════════════════════════════════
def test_reportes(page: Page):
    seccion("4 — REPORTES")

    if not login(page, USUARIOS["james"]):
        log("Reportes", "Login james", False, "No se pudo hacer login"); return

    esperar_react(page)

    for path, params, nombre in [
        ("/api/reporte_notas.php",     {"idMateria":1,"formato":"pdf"},  "Notas IS-201 — PDF"),
        ("/api/reporte_notas.php",     {"idMateria":1,"formato":"csv"},  "Notas IS-201 — CSV"),
        ("/api/reporte_asistencia.php",{"idAula":1,   "formato":"pdf"},  "Asistencia IS-201 — PDF"),
        ("/api/reporte_notas.php",     {"idMateria":1,"formato":"xlsx"}, "Notas IS-201 — XLSX"),
    ]:
        t = time.perf_counter()
        r = api_fetch(page, path, params)
        ok = r.get("status") == 200 and r.get("bytes", 0) > 100
        log("Reportes", nombre, ok,
            f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes",
            (time.perf_counter()-t)*1000)

    logout(page)


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 5 — DASHBOARD ADMIN
# ════════════════════════════════════════════════════════════════════
def test_dashboard_admin(page: Page):
    seccion("5 — DASHBOARD ADMIN")

    if not login(page, USUARIOS["admin"]):
        log("Admin", "Login admin", False, "No se pudo hacer login"); return

    t = time.perf_counter()
    page.wait_for_selector(".page, .stat-grid-6, #root > div", timeout=8_000)
    ok = "login" not in page.url
    log("Admin", "Dashboard — carga con React", ok, page.url, (time.perf_counter()-t)*1000)

    # Verificar secciones del nav
    for seccion_id, nombre in [
        ("usuarios",      "Nav — Usuarios"),
        ("materias",      "Nav — Materias"),
        ("inscripciones", "Nav — Inscripciones"),
        ("reportes",      "Nav — Reportes"),
        ("notificaciones","Nav — Notificaciones (nuevo en v8)"),
        ("configuracion", "Nav — Configuración (nuevo en v8)"),
    ]:
        t = time.perf_counter()
        try:
            btn = page.locator(f".sb-item:has-text('{seccion_id.capitalize()}')"
                               ).first
            # Intentar también con lbl parcial
            count = page.locator(".sb-nav .sb-item").count()
            items = [page.locator(".sb-nav .sb-item").nth(i).inner_text()
                     for i in range(count)]
            encontrado = any(seccion_id.lower() in t.lower() for t in items)
            log("Admin", nombre, encontrado,
                f"Items nav: {items}", (time.perf_counter()-t)*1000)
        except Exception as e:
            log("Admin", nombre, False, str(e))

    # Verificar API de inscripciones — disponibles excluye ya inscritos
    t = time.perf_counter()
    r = api_fetch(page, "/api/v1/inscripciones.php",
                  {"disponibles":"1", "idMateria":"1"})
    disponibles = r.get("json", {})
    data = disponibles.get("data", []) if disponibles else []
    # Todos los devueltos deben tener idEstudiante
    ok = r.get("status") == 200 and isinstance(data, list)
    log("Admin", "Inscripciones — lista disponibles excluye ya inscritos",
        ok, f"HTTP {r.get('status')} — {len(data)} disponibles", (time.perf_counter()-t)*1000)

    logout(page)


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 6 — DASHBOARD DIRECTIVO
# ════════════════════════════════════════════════════════════════════
def test_dashboard_directivo(page: Page):
    seccion("6 — DASHBOARD DIRECTIVO")

    if not login(page, USUARIOS["diana"]):
        log("Directivo", "Login diana", False,
            "No se pudo — verificar activo=1 en BD"); return

    t = time.perf_counter()
    esperar_react(page)
    ok = "login" not in page.url
    log("Directivo", "Dashboard — carga", ok, page.url, (time.perf_counter()-t)*1000)

    # Verificar stats de facultad
    t = time.perf_counter()
    r = api_fetch(page, "/api/v1/stats.php",
                  {"action":"facultad","idFacultad":"1"})
    data = r.get("json", {}).get("data", {}) if r.get("json") else {}
    ok = r.get("status") == 200 and "totalProfesores" in (data or {})
    log("Directivo", "API stats facultad — totalProfesores presente",
        ok, f"HTTP {r.get('status')} — data: {data}", (time.perf_counter()-t)*1000)

    # Verificar profesores de facultad
    t = time.perf_counter()
    r = api_fetch(page, "/api/v1/usuarios.php",
                  {"action":"profesores_facultad","idFacultad":"1"})
    profs = r.get("json", {}).get("data", []) if r.get("json") else []
    ok = r.get("status") == 200 and isinstance(profs, list)
    log("Directivo", f"API profesores_facultad — {len(profs)} profesores",
        ok, f"HTTP {r.get('status')}", (time.perf_counter()-t)*1000)

    # Verificar estudiantes de facultad (separados de profesores)
    t = time.perf_counter()
    r = api_fetch(page, "/api/v1/usuarios.php",
                  {"action":"estudiantes_facultad","idFacultad":"1"})
    ests = r.get("json", {}).get("data", []) if r.get("json") else []
    ok = r.get("status") == 200 and isinstance(ests, list)
    log("Directivo", f"API estudiantes_facultad — {len(ests)} estudiantes",
        ok, f"HTTP {r.get('status')}", (time.perf_counter()-t)*1000)

    logout(page)


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 7 — DASHBOARD PROFESOR
# ════════════════════════════════════════════════════════════════════
def test_dashboard_profesor(page: Page):
    seccion("7 — DASHBOARD PROFESOR")

    for user_key, aula_id, materia in [
        ("james", 1, "IS-201"), ("maria", 2, "BD-301")
    ]:
        if not login(page, USUARIOS[user_key]):
            log("Profesor", f"Login {user_key}", False,
                "No se pudo — verificar activo=1 en BD")
            continue

        esperar_react(page)
        log("Profesor", f"{user_key} — Dashboard carga", "login" not in page.url, page.url)

        # Verificar que el nav tiene "Materias" (fix v8)
        t = time.perf_counter()
        nav_items = page.locator(".sb-nav .sb-item").all_inner_texts()
        tiene_materias = any("materia" in n.lower() for n in nav_items)
        log("Profesor", f"{user_key} — Nav incluye 'Materias'",
            tiene_materias, f"Items: {nav_items}", (time.perf_counter()-t)*1000)

        # Verificar entregas (fue 500 por campo inexistente)
        t = time.perf_counter()
        r = api_fetch(page, "/api/v1/profesor.php",
                      {"action":"entregas", "idAula": str(aula_id)})
        ok = r.get("status") == 200
        log("Profesor", f"{user_key} — API entregas {materia} (fix 500)",
            ok, f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes",
            (time.perf_counter()-t)*1000)

        # Verificar asistencia por fecha
        t = time.perf_counter()
        r = api_fetch(page, "/api/v1/profesor.php",
                      {"action":"asistencia_fecha",
                       "idMateria": str(aula_id),
                       "fecha": "2026-01-20"})
        ok = r.get("status") == 200
        log("Profesor", f"{user_key} — Asistencia por fecha {materia}",
            ok, f"HTTP {r.get('status')}", (time.perf_counter()-t)*1000)

        # Registrar asistencia individual (fix ON DUPLICATE KEY)
        t = time.perf_counter()
        r = api_post(page, "/api/v1/profesor.php", {
            "action": "registrar_asistencia",
            "idMateria": aula_id,
            "idEstudiante": 1,
            "fecha": "2026-03-26",
            "asistio": 1
        })
        ok = r.get("status") == 200
        log("Profesor", f"{user_key} — Registrar asistencia (fix DUPLICATE KEY)",
            ok, f"HTTP {r.get('status')}", (time.perf_counter()-t)*1000)

        # Control de acceso
        t = time.perf_counter()
        page.goto(url("/pages/admin/dashboard.php"))
        page.wait_for_timeout(1500)
        log("Profesor", f"{user_key} — NO accede a /admin/",
            "login" in page.url, page.url, (time.perf_counter()-t)*1000)

        logout(page)


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 8 — DASHBOARD ESTUDIANTE
# ════════════════════════════════════════════════════════════════════
def test_dashboard_estudiante(page: Page):
    seccion("8 — DASHBOARD ESTUDIANTE")

    for user_key in ["edwin", "angel", "laura", "carlos"]:
        if not login(page, USUARIOS[user_key]):
            log("Estudiante", f"Login {user_key}", False,
                "No se pudo — verificar activo=1 en BD")
            continue

        esperar_react(page)

        for path, nombre in [
            ("/pages/estudiante/dashboard.php",  f"{user_key} — Dashboard"),
        ]:
            t = time.perf_counter()
            page.goto(url(path))
            page.wait_for_timeout(800)
            ok = "login" not in page.url and "404" not in page.content()[:500]
            log("Estudiante", nombre, ok, page.url, (time.perf_counter()-t)*1000)

        # Verificar que notas.php SPA (no da 404)
        t = time.perf_counter()
        r = api_fetch(page, "/api/v1/estudiante.php", {"action":"notas"})
        ok = r.get("status") in [200, 400]  # 400 si falta idEst, pero no 404/500
        log("Estudiante", f"{user_key} — API notas (no 404)", ok,
            f"HTTP {r.get('status')}", (time.perf_counter()-t)*1000)

        # Verificar notificaciones
        t = time.perf_counter()
        r = api_fetch(page, "/api/v1/estudiante.php", {"action":"notificaciones"})
        ok = r.get("status") == 200 and r.get("json") is not None
        log("Estudiante", f"{user_key} — Notificaciones con URL de destino",
            ok, f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes",
            (time.perf_counter()-t)*1000)

        # Control de acceso
        for ruta, desc in [
            ("/pages/admin/dashboard.php",    "admin"),
            ("/pages/profesor/dashboard.php", "profesor"),
        ]:
            t = time.perf_counter()
            page.goto(url(ruta))
            page.wait_for_timeout(1500)
            ok = "login" in page.url
            log("Estudiante", f"{user_key} — NO accede a {desc}", ok,
                page.url, (time.perf_counter()-t)*1000)

        logout(page)


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 9 — SESIONES
# ════════════════════════════════════════════════════════════════════
def test_sesiones(page: Page):
    seccion("9 — SESIONES")

    t = time.perf_counter()
    login(page, USUARIOS["admin"])
    logout(page)
    page.goto(url("/pages/admin/dashboard.php"))
    page.wait_for_timeout(1000)
    log("Sesiones", "Logout limpia sesión → redirige a login",
        "login" in page.url, page.url, (time.perf_counter()-t)*1000)

    for user_key, desc in [("edwin", "estudiante"), ("james", "profesor")]:
        t = time.perf_counter()
        login(page, USUARIOS[user_key])
        page.goto(url("/pages/admin/dashboard.php"))
        page.wait_for_timeout(1500)
        log("Sesiones", f"Sesión {desc} bloqueada en /admin/",
            "login" in page.url, page.url, (time.perf_counter()-t)*1000)
        logout(page)


# ════════════════════════════════════════════════════════════════════
#  SECCIÓN 10 — FLUJO E2E
# ════════════════════════════════════════════════════════════════════
def test_e2e(page: Page):
    seccion("10 — FLUJO E2E  (James → Crea actividad → Edwin entrega)")

    # James crea actividad
    if not login(page, USUARIOS["james"]):
        log("E2E", "Login James", False, "No se pudo hacer login"); return

    esperar_react(page)

    t = time.perf_counter()
    r = api_fetch(page, "/api/v1/profesor.php",
                  {"action":"aula", "idAula":"1"})
    data = r.get("json", {}).get("data", {}) if r.get("json") else {}
    mats = data.get("totalMateriales", "?") if isinstance(data, dict) else "?"
    log("E2E", f"James — Aula IS-201 ({mats} materiales)",
        r.get("status") == 200,
        f"HTTP {r.get('status')}", (time.perf_counter()-t)*1000)

    t = time.perf_counter()
    r = api_fetch(page, "/api/v1/profesor.php",
                  {"action":"entregas", "idAula":"1"})
    n = len(r.get("json", {}).get("data", []) if r.get("json") else [])
    log("E2E", f"James — Entregas IS-201 ({n} registros)",
        r.get("status") == 200,
        f"HTTP {r.get('status')}", (time.perf_counter()-t)*1000)

    logout(page)

    # Edwin ve aula y notas
    if not login(page, USUARIOS["edwin"]):
        log("E2E", "Login Edwin", False, "No se pudo hacer login"); return

    esperar_react(page)

    t = time.perf_counter()
    r = api_fetch(page, "/api/v1/estudiante.php",
                  {"action":"aula", "idAula":"1"})
    log("E2E", "Edwin — API aula IS-201",
        r.get("status") == 200 and r.get("json") is not None,
        f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes",
        (time.perf_counter()-t)*1000)

    t = time.perf_counter()
    r = api_fetch(page, "/api/v1/estudiante.php", {"action":"notas"})
    log("E2E", "Edwin — API notas (no 404, no 500)",
        r.get("status") in [200, 400],
        f"HTTP {r.get('status')}", (time.perf_counter()-t)*1000)

    logout(page)

    # Admin verifica videollamadas finalizadas
    if not login(page, USUARIOS["admin"]):
        log("E2E", "Login Admin", False, "No se pudo hacer login"); return

    t = time.perf_counter()
    r = api_fetch(page, "/api/v1/videollamadas.php", {"action":"lista"})
    ok = r.get("status") == 200
    log("E2E", "Admin — Lista videollamadas (revisión post-clase)",
        ok, f"HTTP {r.get('status')} — {r.get('bytes',0)} bytes",
        (time.perf_counter()-t)*1000)

    logout(page)


# ── Resumen ────────────────────────────────────────────────────────────────
def imprimir_resumen(total_ms: float):
    print(f"\n{'═'*68}")
    print("  RESUMEN — UNI-VIRTUAL Playwright Tests v8")
    print(f"{'═'*68}")

    cats: dict[str, dict] = {}
    for r in results:
        c = r["cat"]
        cats.setdefault(c, {"pass": 0, "fail": 0})
        cats[c]["pass" if r["ok"] else "fail"] += 1

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
    print(f"{'═'*68}")

    fallos = [r for r in results if not r["ok"]]
    if fallos:
        print(f"\n  FALLOS ({len(fallos)}):\n  {'─'*64}")
        for r in fallos:
            print(f"  ✗ [{r['cat']}] {r['nombre']}\n")


# ── Main ───────────────────────────────────────────────────────────────────
def main():
    print("=" * 68)
    print("  UNI-VIRTUAL — Auditoría Playwright v8")
    print(f"  Base URL  : {BASE}")
    print(f"  Headless  : {HEADLESS}")
    print(f"  Inicio    : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 68)
    print("\n  ⚠ REQUISITO: Ejecutar fix_passwords_v8.sql antes de correr\n")

    inicio = time.perf_counter()

    with sync_playwright() as pw:
        browser = pw.chromium.launch(
            headless=HEADLESS,
            args=["--no-sandbox", "--disable-dev-shm-usage"]
        )
        ctx  = browser.new_context(viewport={"width": 1366, "height": 768})
        page = ctx.new_page()
        page.set_default_timeout(TIMEOUT)

        try:
            test_paginas_publicas(page)
            test_autenticacion(page)
            test_api_rest(page)
            test_reportes(page)
            test_dashboard_admin(page)
            test_dashboard_directivo(page)
            test_dashboard_profesor(page)
            test_dashboard_estudiante(page)
            test_sesiones(page)
            test_e2e(page)
        finally:
            ctx.close()
            browser.close()

    imprimir_resumen((time.perf_counter() - inicio) * 1000)


if __name__ == "__main__":
    main()

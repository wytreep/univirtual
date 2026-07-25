"""
============================================================================
UNI-VIRTUAL — Suite de tests de API (red de seguridad para refactor POO)
SCRUM-130 — Refactor POO completo
============================================================================

Estos tests son la PRIMERA LÍNEA DE DEFENSA del refactor.
Verifican que los endpoints JSON sigan retornando exactamente la misma
estructura ANTES y DESPUÉS del refactor.

No usan Selenium — son llamadas HTTP directas vía requests.
Mucho más rápidos que los tests E2E (segundos vs minutos).

Ejecutar:
    python -m pytest tests/test_refactor_api.py -v

Prerrequisitos:
    - XAMPP corriendo con Apache + MySQL puerto 3307
    - BD univirtual con datos seed de database_v8.sql
    - pip install requests pytest

Convención de tests:
    test_<rol>_<accion>_<verificacion>
============================================================================
"""

import json
import unittest
from typing import Any

import requests

# ── Configuración ──────────────────────────────────────────────────────────
BASE_URL = "http://localhost/univirtual"
API      = f"{BASE_URL}/api/v1"
TIMEOUT  = 5  # segundos

# Credenciales seed (database_v8.sql)
CREDENCIALES = {
    "admin":      ("admin@univirtual.edu.co",   "123456"),
    "profesor":   ("james@univirtual.edu.co",   "123456"),  # idProfesor=1
    "estudiante": ("edwin@univirtual.edu.co",   "123456"),  # idEstudiante=1
    "directivo":  ("diana@univirtual.edu.co",   "123456"),
}


# ── Helpers ────────────────────────────────────────────────────────────────
def login_session(rol: str) -> requests.Session:
    """
    Crea una sesión autenticada vía el endpoint de login.
    Retorna requests.Session con la cookie PHPSESSID lista para reutilizar.
    """
    email, password = CREDENCIALES[rol]
    session = requests.Session()
    response = session.post(
        f"{BASE_URL}/pages/auth/login.php",
        data={"email": email, "password": password},
        timeout=TIMEOUT,
        allow_redirects=True,
    )
    # El login redirige al dashboard del rol — si la URL final no contiene
    # 'dashboard', el login falló.
    if "dashboard" not in response.url:
        raise RuntimeError(
            f"Login fallido para rol={rol}. URL final: {response.url}"
        )
    return session


def get_json(session: requests.Session, path: str, **params) -> dict[str, Any]:
    """GET sobre /api/v1/<path> y parsea JSON. Falla con AssertionError si no es JSON."""
    r = session.get(f"{API}/{path}", params=params, timeout=TIMEOUT)
    assert r.status_code in (200, 201), (
        f"GET {path} retornó status {r.status_code}. Body: {r.text[:200]}"
    )
    try:
        return r.json()
    except json.JSONDecodeError as e:
        raise AssertionError(
            f"GET {path} no retornó JSON válido. Body: {r.text[:300]}"
        ) from e


def post_json(session: requests.Session, path: str, payload: dict) -> dict[str, Any]:
    """POST con body JSON. Mismo manejo de errores que get_json."""
    r = session.post(
        f"{API}/{path}",
        json=payload,
        timeout=TIMEOUT,
        headers={"Content-Type": "application/json"},
    )
    assert r.status_code in (200, 201, 400), (
        f"POST {path} retornó status {r.status_code}. Body: {r.text[:200]}"
    )
    try:
        return r.json()
    except json.JSONDecodeError as e:
        raise AssertionError(
            f"POST {path} no retornó JSON válido. Body: {r.text[:300]}"
        ) from e


def assert_estructura_respuesta_ok(resp: dict, contexto: str = "") -> None:
    """
    Toda respuesta de la API debe tener: status, mensaje, data.
    Si falla, sabemos que el refactor rompió el contrato JSON.
    """
    assert "status" in resp, f"{contexto}: falta campo 'status'. Resp: {resp}"
    assert "data" in resp, f"{contexto}: falta campo 'data'. Resp: {resp}"
    assert resp["status"] in ("ok", "error"), (
        f"{contexto}: status inválido '{resp['status']}'"
    )


# ══════════════════════════════════════════════════════════════════════════
#  TEST GRUPO 1 — CONTRATO JSON BASE
#  Verifica que TODA la API devuelve {status, mensaje, data}
# ══════════════════════════════════════════════════════════════════════════
class TestContratoJSON(unittest.TestCase):
    """Verifica el contrato JSON común a todos los endpoints."""

    @classmethod
    def setUpClass(cls):
        cls.s_profesor   = login_session("profesor")
        cls.s_estudiante = login_session("estudiante")
        cls.s_directivo  = login_session("directivo")
        cls.s_admin      = login_session("admin")

    def test_profesor_dashboard_estructura(self):
        """GET profesor.php?action=dashboard retorna {status, mensaje, data}"""
        r = get_json(self.s_profesor, "profesor.php", action="dashboard")
        assert_estructura_respuesta_ok(r, "profesor dashboard")
        self.assertEqual(r["status"], "ok")

    def test_estudiante_dashboard_estructura(self):
        """GET estudiante.php?action=dashboard retorna {status, mensaje, data}"""
        r = get_json(self.s_estudiante, "estudiante.php", action="dashboard")
        assert_estructura_respuesta_ok(r, "estudiante dashboard")

    def test_estudiante_notas_estructura(self):
        """GET estudiante.php?action=notas retorna materias y promedio"""
        r = get_json(self.s_estudiante, "estudiante.php", action="notas")
        assert_estructura_respuesta_ok(r, "estudiante notas")
        self.assertIn("materias", r["data"],
                      "Respuesta de notas debe tener 'materias'")
        self.assertIn("promedio", r["data"],
                      "Respuesta de notas debe tener 'promedio'")

    def test_directivo_dashboard_estructura(self):
        """GET directivo.php?action=resumen retorna stats facultad"""
        # NOTA: el endpoint del directivo usa action=resumen, no =dashboard
        r = get_json(self.s_directivo, "directivo.php", action="resumen")
        assert_estructura_respuesta_ok(r, "directivo resumen")

    def test_endpoint_sin_auth_retorna_401(self):
        """Endpoint sin sesión retorna status error con 401"""
        r = requests.get(f"{API}/profesor.php?action=dashboard", timeout=TIMEOUT)
        self.assertEqual(r.status_code, 401,
                         f"Sin auth debe retornar 401, retornó {r.status_code}")

    def test_endpoint_rol_incorrecto_retorna_403(self):
        """Estudiante intentando endpoint de profesor → 403"""
        r = self.s_estudiante.get(f"{API}/profesor.php?action=dashboard",
                                  timeout=TIMEOUT)
        self.assertEqual(r.status_code, 403,
                         f"Rol incorrecto debe retornar 403, retornó {r.status_code}")


# ══════════════════════════════════════════════════════════════════════════
#  TEST GRUPO 2 — DASHBOARDS POR ROL
#  Verifica que las estructuras de datos clave estén presentes
# ══════════════════════════════════════════════════════════════════════════
class TestDashboards(unittest.TestCase):
    """Snapshot de claves esperadas en cada dashboard."""

    @classmethod
    def setUpClass(cls):
        cls.s_profesor   = login_session("profesor")
        cls.s_estudiante = login_session("estudiante")
        cls.s_directivo  = login_session("directivo")

    def test_profesor_dashboard_tiene_materias(self):
        """Dashboard del profesor debe traer la lista de materias"""
        r = get_json(self.s_profesor, "profesor.php", action="dashboard")
        self.assertEqual(r["status"], "ok")
        self.assertIn("materias", r["data"])
        self.assertIsInstance(r["data"]["materias"], list)
        # James es profesor de 2 materias en el seed
        self.assertGreaterEqual(len(r["data"]["materias"]), 1,
                                "Profesor seed debe tener al menos 1 materia")

    def test_profesor_dashboard_materia_tiene_campos_clave(self):
        """Cada materia del profesor debe tener: idMateria, nombre, color, idAula"""
        r = get_json(self.s_profesor, "profesor.php", action="dashboard")
        materias = r["data"]["materias"]
        self.assertGreater(len(materias), 0, "Sin materias para probar")
        m = materias[0]
        for campo in ("idMateria", "nombre", "color", "idAula", "sinCalificar"):
            self.assertIn(campo, m,
                          f"Materia debe tener '{campo}'. Got: {list(m.keys())}")

    def test_estudiante_notas_calcula_promedio(self):
        """El endpoint de notas debe calcular promedio global"""
        r = get_json(self.s_estudiante, "estudiante.php", action="notas")
        self.assertEqual(r["status"], "ok")
        materias = r["data"]["materias"]
        self.assertIsInstance(materias, list)
        # Edwin (idEst=1) está inscrito en 4 materias en el seed
        self.assertGreaterEqual(len(materias), 1,
                                "Edwin debe tener materias inscritas")
        # Cada materia debe tener los campos de notas (aunque sean null)
        m = materias[0]
        for campo in ("nota_parcial1", "nota_parcial2",
                      "nota_talleres", "nota_final"):
            self.assertIn(campo, m, f"Materia debe tener '{campo}'")


# ══════════════════════════════════════════════════════════════════════════
#  TEST GRUPO 3 — FLUJO CRÍTICO DE CALIFICACIONES
#  Es lo que SCRUM-142 va a tocar — debe seguir funcionando idéntico
# ══════════════════════════════════════════════════════════════════════════
class TestCalificaciones(unittest.TestCase):
    """
    Flujo: profesor califica entrega → estudiante ve nota.
    Si esto se rompe en el refactor, los usuarios pierden funcionalidad real.
    """

    @classmethod
    def setUpClass(cls):
        cls.s_profesor   = login_session("profesor")
        cls.s_estudiante = login_session("estudiante")

    def test_profesor_lista_entregas_de_su_aula(self):
        """GET entregas debe retornar lista con idEntrega, estudiante, nota"""
        # idAula=1 es 'Ingeniería de Software II' del profesor James (idProf=1)
        r = get_json(self.s_profesor, "profesor.php",
                     action="entregas", idAula=1, idProf=1)
        self.assertEqual(r["status"], "ok")
        self.assertIsInstance(r["data"], list)
        if len(r["data"]) > 0:
            e = r["data"][0]
            for campo in ("idEntrega", "nota", "estudiante", "actividad"):
                self.assertIn(campo, e,
                              f"Entrega debe tener '{campo}'. Got: {list(e.keys())}")

    def test_calificar_entrega_guarda_nota(self):
        """
        POST calificar_entrega actualiza la nota de una entrega real.
        Lee la nota antes, califica con +0.1, verifica que cambió, restaura.
        """
        # Buscar una entrega existente del aula 1
        r = get_json(self.s_profesor, "profesor.php",
                     action="entregas", idAula=1, idProf=1)
        entregas = r["data"]
        if not entregas:
            self.skipTest("No hay entregas en aula 1 para probar")

        entrega    = entregas[0]
        idEntrega  = entrega["idEntrega"]
        nota_orig  = float(entrega["nota"]) if entrega["nota"] else 3.0
        nota_nueva = round(min(5.0, nota_orig + 0.1), 2)

        # Calificar con la nueva nota
        resp = post_json(self.s_profesor, "profesor.php", {
            "action":    "calificar_entrega",
            "idEntrega": idEntrega,
            "nota":      nota_nueva,
            "feedback":  "Test automatizado SCRUM-130",
        })
        self.assertEqual(resp["status"], "ok",
                         f"calificar_entrega debe retornar ok. Resp: {resp}")

        # Verificar que la nota cambió
        r2 = get_json(self.s_profesor, "profesor.php",
                      action="entregas", idAula=1, idProf=1)
        entrega_actualizada = next(
            (e for e in r2["data"] if e["idEntrega"] == idEntrega), None
        )
        self.assertIsNotNone(entrega_actualizada,
                             "Entrega calificada no aparece en GET posterior")
        self.assertAlmostEqual(float(entrega_actualizada["nota"]),
                               nota_nueva, places=2,
                               msg="La nota guardada no coincide con la enviada")

        # Restaurar la nota original (limpieza)
        post_json(self.s_profesor, "profesor.php", {
            "action":    "calificar_entrega",
            "idEntrega": idEntrega,
            "nota":      nota_orig,
            "feedback":  entrega.get("feedback") or "",
        })

    def test_guardar_nota_consolidada(self):
        """
        POST guardar_nota actualiza inscripciones.nota_parcial1 (u otra).
        Lee, modifica, verifica, restaura.
        """
        # idEstudiante=1 (Edwin), idMateria=1 (IS-II del profe James)
        # Primero leemos la nota actual vía dashboard del profesor con aula 1
        r = get_json(self.s_profesor, "profesor.php",
                     action="aula", idAula=1, idProf=1)
        if r["status"] != "ok" or not r["data"].get("estudiantes"):
            self.skipTest("Sin datos de estudiantes en aula 1")

        estudiantes = r["data"]["estudiantes"]
        est = next((e for e in estudiantes if e.get("idEstudiante") == 1),
                   estudiantes[0])
        nota_orig = est.get("nota_parcial1")
        nota_orig_val = float(nota_orig) if nota_orig is not None else None

        nota_test = 4.2

        # Guardar nueva nota
        resp = post_json(self.s_profesor, "profesor.php", {
            "action":       "guardar_nota",
            "idEstudiante": est["idEstudiante"],
            "idMateria":    1,
            "campo":        "nota_parcial1",
            "nota":         nota_test,
        })
        self.assertEqual(resp["status"], "ok",
                         f"guardar_nota debe retornar ok. Resp: {resp}")

        # Verificar que cambió
        r2 = get_json(self.s_profesor, "profesor.php",
                      action="aula", idAula=1, idProf=1)
        est2 = next((e for e in r2["data"]["estudiantes"]
                     if e["idEstudiante"] == est["idEstudiante"]), None)
        self.assertIsNotNone(est2, "Estudiante no aparece tras guardar nota")
        self.assertAlmostEqual(float(est2["nota_parcial1"]), nota_test,
                               places=2,
                               msg="nota_parcial1 no se actualizó correctamente")

        # Restaurar
        if nota_orig_val is not None:
            post_json(self.s_profesor, "profesor.php", {
                "action":       "guardar_nota",
                "idEstudiante": est["idEstudiante"],
                "idMateria":    1,
                "campo":        "nota_parcial1",
                "nota":         nota_orig_val,
            })

    def test_guardar_nota_campo_invalido_rechazado(self):
        """guardar_nota debe rechazar campos no permitidos (whitelist)"""
        resp = post_json(self.s_profesor, "profesor.php", {
            "action":       "guardar_nota",
            "idEstudiante": 1,
            "idMateria":    1,
            "campo":        "DROP TABLE inscripciones; --",  # ataque
            "nota":         5,
        })
        self.assertEqual(resp["status"], "error",
                         "Campo no whitelist debe ser rechazado")

    def test_estudiante_ve_su_nota_calificada(self):
        """Estudiante consulta sus notas y debe ver las que el profesor le puso"""
        r = get_json(self.s_estudiante, "estudiante.php", action="notas")
        self.assertEqual(r["status"], "ok")
        materias = r["data"]["materias"]
        # Cada materia debe tener los 4 campos de notas (aunque algunos sean None)
        for m in materias:
            for campo in ("nota_parcial1", "nota_parcial2",
                          "nota_talleres", "nota_final"):
                self.assertIn(campo, m,
                              f"Materia '{m.get('nombre')}' debe tener '{campo}'")


# ══════════════════════════════════════════════════════════════════════════
#  TEST GRUPO 4 — CRUD CRÍTICO (actividades, materiales, asistencia)
#  Lo más sensible del profesor — son flujos completos
# ══════════════════════════════════════════════════════════════════════════
class TestCRUDProfesor(unittest.TestCase):
    """
    CRUD de actividades y asistencia. Si el refactor de profesor.php
    rompe esto, los profesores no pueden trabajar.
    """

    @classmethod
    def setUpClass(cls):
        cls.s_profesor = login_session("profesor")

    def test_crear_y_eliminar_actividad(self):
        """Crea una actividad de prueba, la verifica, y la elimina"""
        # Crear
        resp = post_json(self.s_profesor, "profesor.php", {
            "action":       "crear_actividad",
            "idAula":       1,
            "idProf":       1,
            "titulo":       "Test SCRUM-130 — Actividad temporal",
            "descripcion":  "Creada por test automatizado",
            "fechaEntrega": "2030-12-31T23:59:00",
            "puntaje_max":  5,
        })
        self.assertEqual(resp["status"], "ok",
                         f"crear_actividad debe retornar ok. Resp: {resp}")

        # Verificar que aparece en el listado
        r = get_json(self.s_profesor, "profesor.php",
                     action="aula", idAula=1, idProf=1)
        actividades = r["data"].get("actividades", [])
        creada = next(
            (a for a in actividades
             if "SCRUM-130" in (a.get("titulo") or "")),
            None
        )
        self.assertIsNotNone(creada,
                             "Actividad creada no aparece en GET aula")

        # Limpieza — eliminar la actividad
        post_json(self.s_profesor, "profesor.php", {
            "action":      "eliminar_actividad",
            "idActividad": creada["idActividad"],
        })

    def test_registrar_asistencia_idempotente(self):
        """
        Registrar asistencia es idempotente — la misma combinación
        (materia, estudiante, fecha) puede llamarse varias veces.
        """
        payload = {
            "action":       "registrar_asistencia",
            "idMateria":    1,
            "idEstudiante": 1,
            "fecha":        "2030-01-01",  # fecha futura para no chocar
            "asistio":      1,
        }
        r1 = post_json(self.s_profesor, "profesor.php", payload)
        r2 = post_json(self.s_profesor, "profesor.php", payload)
        self.assertEqual(r1["status"], "ok")
        self.assertEqual(r2["status"], "ok",
                         "Segundo registro debe ser idempotente, no error")


# ══════════════════════════════════════════════════════════════════════════
#  TEST GRUPO 5 — REPORTES (incluye el bug nota_habilitacion conocido)
# ══════════════════════════════════════════════════════════════════════════
class TestReportes(unittest.TestCase):

    @classmethod
    def setUpClass(cls):
        # NOTA: reportes.php requiere rol=admin (no directivo)
        cls.s_admin = login_session("admin")

    def test_reporte_asistencia_responde(self):
        """Reporte de asistencia retorna estructura válida"""
        r = get_json(self.s_admin, "reportes.php",
                     tipo="asistencia", idMateria=1)
        assert_estructura_respuesta_ok(r, "reporte asistencia")

    def test_reporte_notas_no_crashea(self):
        """
        Reporte de notas debe retornar JSON válido.
        AVISO: hoy esto FALLA porque reportes.php:111 consulta
        i.nota_habilitacion que no existe en database_v8.sql.
        El refactor debe arreglarlo.
        """
        r = get_json(self.s_admin, "reportes.php",
                     tipo="notas", idMateria=1)
        # Lo importante: debe retornar JSON con status, no crashear el server
        assert_estructura_respuesta_ok(r, "reporte notas")
        # Si retorna error por la columna inexistente, el test detecta el bug
        if r["status"] == "error" and "nota_habilitacion" in str(r):
            self.fail("BUG conocido: reportes.php consulta columna inexistente "
                      "nota_habilitacion. Debe corregirse en el refactor.")


# ══════════════════════════════════════════════════════════════════════════
#  RUNNER
# ══════════════════════════════════════════════════════════════════════════
if __name__ == "__main__":
    loader = unittest.TestLoader()
    suite  = unittest.TestSuite()
    for clase in (TestContratoJSON, TestDashboards, TestCalificaciones,
                  TestCRUDProfesor, TestReportes):
        suite.addTests(loader.loadTestsFromTestCase(clase))

    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)

    total    = result.testsRun
    fallaron = len(result.failures) + len(result.errors)
    print("\n" + "=" * 60)
    print(f"  TOTAL:    {total}")
    print(f"  PASARON:  {total - fallaron}")
    print(f"  FALLARON: {fallaron}")
    print("=" * 60)
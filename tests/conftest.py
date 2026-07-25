"""
conftest.py — SCRUM-129
Plugin de pytest que guarda cada resultado de test en la tabla `pruebas`
de UNI-VIRTUAL a través de PruebasController.

Instalación: colocar este archivo en la raíz de /tests/
Se activa automáticamente — no requiere ningún import adicional.

Uso:
    python -m pytest tests/ -v
    # Cada test que corra (pase o falle) se guarda en BD automáticamente.
"""

import pytest
import requests

# ── Configuración ─────────────────────────────────────────────────────
API_URL = "http://localhost/univirtual/api/v1/PruebasController.php"


def _enviar(nombre: str, clase: str, estado: str,
            duracion: float, error: str | None) -> None:
    """Envía el resultado al endpoint PHP. Silencia errores de red."""
    try:
        requests.post(
            API_URL,
            json={
                "action":        "registrar",
                "nombre_test":   nombre,
                "clase_test":    clase,
                "estado":        estado,
                "duracion_seg":  round(duracion, 3),
                "mensaje_error": error,
            },
            timeout=3,
        )
    except Exception:
        # No interrumpir la suite si la BD no está disponible
        pass


# ── Hook principal ────────────────────────────────────────────────────
@pytest.hookimpl(hookwrapper=True)
def pytest_runtest_makereport(item, call):
    """
    Se ejecuta después de cada fase del test (setup / call / teardown).
    Solo registramos la fase 'call' — el test en sí.
    """
    outcome = yield
    reporte = outcome.get_result()

    if reporte.when != "call":
        return

    # Determinar estado
    if reporte.passed:
        estado = "PASSED"
        mensaje_error = None
    elif reporte.failed:
        estado = "FAILED"
        mensaje_error = str(reporte.longrepr)[:2000]  # máx 2000 chars
    else:
        estado = "ERROR"
        mensaje_error = str(reporte.longrepr)[:2000]

    # Clase del test (nombre de la clase TestXxx)
    clase = item.cls.__name__ if item.cls else "General"

    # Duración
    duracion = reporte.duration if hasattr(reporte, "duration") else 0.0

    _enviar(item.name, clase, estado, duracion, mensaje_error)
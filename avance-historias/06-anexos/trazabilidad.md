# Matriz de trazabilidad — Historias → Criterios → Casos → Evidencia

**Corte:** 31 de octubre de 2026

> **Nota de alcance:** esta matriz cubre las 13 historias vencidas (sprints 1 y 2) y registra la evidencia del avance anticipado de las 4 historias del Sprint 3 (UV-16, UV-18, UV-19, UV-31), que aún no tienen casos CP formales — se cierran al iniciar el sprint (2 nov). El porcentaje de avance de cada historia está en `01-gestion/matriz-historias.md` y en su ficha de `03-historias/`.

---

## 1. Trazabilidad completa

| HU | Criterio de aceptación | Casos | Evidencia de implementación | Evidencia de prueba | Estado |
|---|---|---|---|---|---|
| UV-8 | Cada rol llega a su panel. | CP-01, CP-02, CP-03, CP-04 | `pages/auth/login.php`, `index.php` | `test_univirtual.py::TestLogin` | ✅ |
| UV-8 | Un rol no accede al panel de otro. | CP-05 | `includes/auth.php::requireLogin` | `TestLogin` (bloqueo cruzado) | ✅ |
| UV-9 | Admin crea usuarios de los cuatro roles. | CP-06, CP-07 | `api/v1/usuarios.php`, `pages/admin/dashboard.php` | Inspección de contrato | 🔴 |
| UV-9 | Admin edita usuario. | CP-08 | `api/v1/usuarios.php` | Inspección | ✅ |
| UV-9 | Admin activa/desactiva. | CP-09 | `api/v1/usuarios.php` | Inspección | ✅ |
| UV-9 | Admin elimina (soft delete). | CP-10 | `api/v1/usuarios.php` | Inspección | ✅ |
| UV-10 | Estudiante ve materias, promedio, asistencia, tareas y notificaciones. | CP-12 | `api/v1/estudiante.php`, `pages/estudiante/dashboard.php` | Inspección + `TestEstudiante` | 🟡 |
| UV-11 | Profesor ve materias, estudiantes y entregas. | CP-13 | `api/v1/profesor.php`, `pages/profesor/dashboard.php` | Inspección + `TestProfesor` | 🟡 |
| UV-11 | Profesor abre tab de videollamadas. | CP-16 | `api/v1/videollamadas.php` | `TestVideollamadas` | ✅ |
| UV-12 | Admin inscribe estudiante. | CP-17 | `api/v1/inscripciones.php` | Inspección | ✅ |
| UV-12 | Admin desinscribe. | CP-18 | `api/v1/inscripciones.php` | Inspección | ✅ |
| UV-12 | Se bloquea si no cumple créditos. | CP-19 | `api/v1/inscripciones.php` | Rama `feature/UV-104` | ✅ |
| UV-12 | Se notifica al estudiante. | CP-20 | `config/config.php::notificar` | Inspección | ✅ |
| UV-13 | Profesor registra nota por parcial. | CP-21 | `api/v1/profesor.php::guardarNota` | `test_refactor_api.py` | ✅ |
| UV-13 | Estudiante ve su nota. | CP-22 | `api/v1/estudiante.php::notas` | `test_refactor_api.py` | ✅ |
| UV-13 | Se calcula el promedio. | CP-23 | `api/v1/estudiante.php` | `test_refactor_api.py` | ✅ |
| UV-13 | Se rechaza nota inválida. | CP-24 | `api/v1/profesor.php` | `test_refactor_api.py` | ✅ |
| UV-13 | Profesor califica entrega. | CP-25 | `api/v1/profesor.php` | Bloqueado por `archivoEntrega` | ⛔ |
| UV-25 | Existen PRD y SRS IEEE 830. | — | `02-producto/PRD.md`, `02-producto/SRS-IEEE830.md` | Revisión documental | ✅ |
| UV-26 | Existen arquitectura y ADRs. | — | `02-producto/arquitectura.md`, `02-producto/ADRs.md` | Revisión documental | ✅ |
| UV-27 | Un tercero clona y ejecuta. | CP-30 | `README.md`, `docs/GIT_WORKFLOW.md` | Inspección | 🟡 |
| UV-28 | Esquema 3FN con datos semilla. | — | `config/database_v8.sql`, `02-producto/modelo-datos.md` | Conteo de 19 tablas | 🟡 |
| UV-29 | Autoload, PDO, clase base y JSON. | CP-26, CP-27, CP-28, CP-29 | `config/config.php`, `api/v1/Controller.php`, `models/Model.php` | `php -l` 61/61 | ✅ |
| UV-30 | Layout React y consumo de API. | CP-11, CP-14, CP-15 | `pages/*/dashboard.php`, `includes/View.php` | Inspección + historial | 🟡 |
| UV-32 | Plan y casos de aceptación. | CP-01 – CP-30 | `04-pruebas/*` | Este documento | ✅ |
| UV-16 | Crear, listar, finalizar videollamada Jitsi. | (Sprint 3, sin CP aún) | `api/v1/videollamadas.php`, dashboards | Inspección de código | 🔵 85% |
| UV-18 | Notificar 5 eventos + polling 30s. | (Sprint 3, sin CP aún) | `notificar()` en `inscripciones.php` y `profesor.php` | Inspección de código | 🔵 85% |
| UV-19 | Mensajes del directivo por facultad. | (Sprint 3, sin endpoint) | tabla `mensajes`, `notificar()` | Inspección de código | 🔵 20% |
| UV-31 | Resumen, listados, perfil y contraseña del directivo. | (Sprint 3, sin CP aún) | `api/v1/directivo.php`, `api/v1/stats.php`, `pages/directivo/dashboard.php` | Inspección de código | 🔵 75% |

---

## 2. Cobertura por caso

| Caso | HU asociada |
|---|---|
| CP-01 – CP-05 | UV-8 |
| CP-06 – CP-10 | UV-9 |
| CP-11 | UV-30 |
| CP-12 | UV-10, UV-30 |
| CP-13 | UV-11, UV-30 |
| CP-14, CP-15 | UV-30 |
| CP-16 | UV-11 |
| CP-17 – CP-20 | UV-12 |
| CP-21 – CP-25 | UV-13 |
| CP-26 – CP-28 | UV-29 |
| CP-29 | UV-29 |
| CP-30 | UV-27 |

Todos los casos tienen al menos una historia; todas las historias vencidas tienen al menos un caso, salvo UV-25, UV-26 y UV-28, que se verifican por revisión documental. Las historias del Sprint 3 (UV-16, UV-18, UV-19, UV-31) se trazan por inspección de código hasta que el sprint defina sus CP.

---

## 3. Cadena de custodia de la evidencia

| Nivel | Qué se conserva | Dónde |
|---|---|---|
| Código | Archivos fuente versionados | Repositorio Git, rama `main` |
| Datos | Script de creación y semilla | `config/database_v8.sql` |
| Pruebas | Suites y reporte histórico | `tests/`, `docs/reporte_tests_scrum124.md` |
| Documentación | Entregables del avance | `avance-historias/` |
| Ejecución | Resultados del corte | `04-pruebas/resultados.md` |

---

## 4. Brechas de trazabilidad

| # | Brecha | Acción |
|---|---|---|
| 1 | UV-9 no tiene prueba automatizada de creación. | Añadir casos por rol al propagar el parche al original (ya verificado en la copia ejecutable). |
| 2 | UV-28 no tiene caso de ejecución del script. | Añadir CP de importación limpia. |
| 3 | UV-25 y UV-26 no tienen caso de aceptación formal. | Agregar revisión por checklist documental. |
| 4 | El reporte de pruebas es de abril, no del corte. | Re-ejecutar y actualizar. |
| 5 | UV-16, UV-18, UV-19 y UV-31 sin casos CP. | Definir al iniciar el Sprint 3 (2 nov). |

---

## 5. Regla de mantenimiento

Toda historia que cambie de estado actualiza, en el mismo commit:

1. `01-gestion/matriz-historias.md`.
2. Su ficha en `03-historias/`.
3. Esta matriz.
4. `04-pruebas/resultados.md`.

Así la trazabilidad no se convierte en un documento decorativo.

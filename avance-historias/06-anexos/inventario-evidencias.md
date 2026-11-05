# Inventario de evidencias

**Corte:** 31 de octubre de 2026  
**Convención:** `D` = documento del avance, `C` = código del repositorio, `P` = prueba, `S` = script/datos.

---

## 1. Documentos del avance

| ID | Evidencia | Tipo | Ruta | Estado |
|---|---|---|---|---|
| D-01 | Índice del entregable | D | `avance-historias/README.md` | ✅ |
| D-02 | Resumen ejecutivo | D | `00-resumen-ejecutivo.md` | ✅ |
| D-03 | Matriz de historias | D | `01-gestion/matriz-historias.md` | ✅ |
| D-04 | Acta de entrega | D | `01-gestion/acta-entrega-avance.md` | ✅ |
| D-05 | Cronograma | D | `01-gestion/cronograma.md` | ✅ |
| D-06 | PRD | D | `02-producto/PRD.md` | ✅ |
| D-07 | SRS IEEE 830 | D | `02-producto/SRS-IEEE830.md` | ✅ |
| D-08 | Arquitectura | D | `02-producto/arquitectura.md` | ✅ |
| D-09 | ADRs | D | `02-producto/ADRs.md` | ✅ |
| D-10 | Modelo de datos | D | `02-producto/modelo-datos.md` | ✅ |
| D-11 | Manual de instalación | D | `02-producto/manual-instalacion.md` | ✅ |
| D-12 | Plan de pruebas | D | `04-pruebas/plan-pruebas.md` | ✅ |
| D-13 | Casos de aceptación | D | `04-pruebas/casos-aceptacion.md` | ✅ |
| D-14 | Resultados | D | `04-pruebas/resultados.md` | ✅ |
| D-15 | Checklist de entrega | D | `05-entrega/checklist-entrega.md` | ✅ |
| D-16 | Guion de demo | D | `05-entrega/guion-demo.md` | ✅ |
| D-17 | Riesgos y deuda | D | `05-entrega/riesgos-y-deuda.md` | ✅ |
| D-18 | Trazabilidad | D | `06-anexos/trazabilidad.md` | ✅ |
| D-19 | Inventario | D | `06-anexos/inventario-evidencias.md` | ✅ |
| D-20 | Glosario | D | `06-anexos/glosario.md` | ✅ |

---

## 2. Fichas de historia

| ID | Historia | Ruta | Estado |
|---|---|---|---|
| H-01 | UV-8 Login | `03-historias/UV-08-login.md` | ✅ |
| H-02 | UV-9 CRUD usuarios | `03-historias/UV-09-crud-usuarios.md` | 🔴 |
| H-03 | UV-10 Dashboard estudiante | `03-historias/UV-10-dashboard-estudiante.md` | ✅ |
| H-04 | UV-11 Dashboard profesor | `03-historias/UV-11-dashboard-profesor.md` | 🟡 |
| H-05 | UV-12 Inscripciones | `03-historias/UV-12-inscripciones.md` | ✅ |
| H-06 | UV-13 Notas | `03-historias/UV-13-notas.md` | 🟡 |
| H-07 | UV-25 Documentación | `03-historias/UV-25-documentacion.md` | ✅ |
| H-08 | UV-26 Arquitectura | `03-historias/UV-26-arquitectura.md` | ✅ |
| H-09 | UV-27 Repositorio | `03-historias/UV-27-repositorio.md` | 🟡 |
| H-10 | UV-28 Base de datos | `03-historias/UV-28-base-datos.md` | 🟡 |
| H-11 | UV-29 Backend | `03-historias/UV-29-backend.md` | ✅ |
| H-12 | UV-30 Frontend | `03-historias/UV-30-frontend.md` | 🟡 |
| H-13 | UV-32 Pruebas | `03-historias/UV-32-pruebas.md` | ✅ |
| H-14 | UV-16 Videollamadas (Sprint 3, avance anticipado) | `03-historias/UV-16-videollamadas.md` | 🔵 85% |
| H-15 | UV-18 Notificaciones (Sprint 3, avance anticipado) | `03-historias/UV-18-notificaciones.md` | 🔵 85% |
| H-16 | UV-19 Mensajes directivo (Sprint 3, iniciada) | `03-historias/UV-19-mensajes-directivo.md` | 🔵 20% |
| H-17 | UV-31 Dashboard directivo (Sprint 3, avance anticipado) | `03-historias/UV-31-dashboard-directivo.md` | 🔵 75% |

---

## 3. Código de respaldo

| ID | Evidencia | Tipo | Ruta |
|---|---|---|---|
| C-01 | Conexión y autoload | C | `config/config.php` |
| C-02 | Esquema v8 | S | `config/database_v8.sql` |
| C-03 | Clase base API | C | `api/v1/Controller.php` |
| C-04 | CRUD base | C | `models/Model.php` |
| C-05 | CRUD usuarios | C | `api/v1/usuarios.php` |
| C-06 | API estudiante | C | `api/v1/estudiante.php` |
| C-07 | API profesor | C | `api/v1/profesor.php` |
| C-08 | API inscripciones | C | `api/v1/inscripciones.php` |
| C-09 | Template Method | C | `includes/View.php` |
| C-10 | Autenticación | C | `includes/auth.php` |
| C-11 | Login | C | `pages/auth/login.php` |
| C-12 | Dashboard admin | C | `pages/admin/dashboard.php` |
| C-13 | Dashboard estudiante | C | `pages/estudiante/dashboard.php` |
| C-14 | Dashboard profesor | C | `pages/profesor/dashboard.php` |
| C-15 | Dashboard directivo | C | `pages/directivo/dashboard.php` |
| C-16 | Estilos del panel | C | `assets/css/panel.css` |
| C-17 | Modelo de entregas | C | `models/Entrega.php` |
| C-18 | API videollamadas (UV-16) | C | `api/v1/videollamadas.php` |
| C-19 | API directivo (UV-31) | C | `api/v1/directivo.php` |
| C-20 | API estadísticas (UV-31) | C | `api/v1/stats.php` |
| C-21 | Emisor de notificaciones (UV-18) | C | `config/config.php::notificar()` |

---

## 4. Pruebas

| ID | Evidencia | Tipo | Ruta | Estado |
|---|---|---|---|---|
| P-01 | Suite E2E por rol | P | `tests/test_univirtual.py` | Histórico 23/23 |
| P-02 | Suite API refactor | P | `tests/test_refactor_api.py` | Pendiente |
| P-03 | Suite E2E refactor | P | `tests/test_refactor_e2e.py` | Pendiente |
| P-04 | Fixtures | P | `tests/conftest.py` | — |
| P-05 | Reporte SCRUM-124 | P | `docs/reporte_tests_scrum124.md` | ✅ |
| P-06 | Lint PHP | P | `php -l` | 61/61 |
| P-07 | Plan de pruebas | D | `04-pruebas/plan-pruebas.md` | ✅ |
| P-08 | Casos CP-01..CP-30 | D | `04-pruebas/casos-aceptacion.md` | ✅ |
| P-09 | Resultados del corte | D | `04-pruebas/resultados.md` | ✅ |

---

## 5. Plantillas de entrega

| ID | Evidencia | Tipo | Ruta | Estado |
|---|---|---|---|---|
| S-01 | Configuración de ejemplo | S | `05-entrega/plantillas/config.example.php` | ✅ |
| S-02 | Reparación de hashes | S | `05-entrega/plantillas/fix_passwords_v8.sql` | ✅ |
| S-03 | Esquema de base | S | `config/database_v8.sql` | ✅ |
| S-04 | Diagrama de dominio | D | `diagrams/diagrama_v8.puml` | ✅ |
| S-05 | Secuencia de login | D | `diagrams/secuencia_login_v8.puml` | ✅ |

---

## 6. Documentación previa reutilizada

| ID | Documento | Ruta | Uso |
|---|---|---|---|
| X-01 | Flujo Git | `docs/GIT_WORKFLOW.md` | UV-27 |
| X-02 | Tablero Jira | `docs/JIRA_BOARD.md` | Contexto de sprints |
| X-03 | README anterior | `docs/README.md` | Referencia histórica |
| X-04 | Reporte de tests | `docs/reporte_tests_scrum124.md` | UV-32 |
| X-05 | Memoria de decisiones | `docs/memoria/decisiones.md` | ADRs |

---

## 7. Evidencias faltantes o pendientes

| # | Evidencia | Motivo | Acción |
|---|---|---|---|
| 1 | Capturas de la demo | Apache/MySQL detenidos | Tomar al re-ejecutar. |
| 2 | Salida de `pytest` del corte | Entorno detenido | Adjuntar log. |
| 3 | Cobertura | No medida | Añadir `pytest-cov`. |
| 4 | Prueba de creación de usuario | UV-9 con parche aplicado solo en la copia ejecutable | Añadir al original tras propagar el parche. |
| 5 | Importación limpia de la base | No ejecutada | Añadir caso CP. |
| 6 | Casos CP de UV-16, UV-18, UV-31 | Avance anticipado sin suite formal | Definir al iniciar el Sprint 3. |

---

## 8. Cómo verificar este inventario

```powershell
# Existencia de documentos
Get-ChildItem avance-historias -Recurse -File

# Conteo de tablas
Select-String config\database_v8.sql -Pattern "CREATE TABLE"

# Sintaxis PHP
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Cada ruta de este inventario debe existir al momento de la entrega; si alguna falta, se actualiza el inventario antes de firmar.

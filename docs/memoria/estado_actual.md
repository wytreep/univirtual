# Estado Actual — UNI-VIRTUAL
> Última actualización: 2026-04-24

---

## Rama activa: `develop` | Tag: `v8.0.0`

---

## ✅ Completado y funcionando

| Issue | Descripción | Notas |
|---|---|---|
| SCRUM-97 | Login con bcrypt, 4 roles, session_regenerate_id | Estable |
| SCRUM-98 | CRUD usuarios con transacciones BD | Estable |
| SCRUM-101 | Controller.php abstracto — Template Method pattern | En `api/v1/Controller.php` |
| SCRUM-102 | Aula virtual SPA con 5 tabs | Estudiante + Profesor |
| SCRUM-103 | Materiales — subida, descarga, contador | Campo: `urlArchivo` |
| SCRUM-105 | React SPA embebida con fetch + credentials | Todos los dashboards |
| SCRUM-106 | Jerarquía de roles admin > directivo > profesor > estudiante | `requireNivel()` en auth.php |
| SCRUM-111 | Config perfil — cambio nombre y password todos los roles | Estable |
| SCRUM-118 | Jitsi Meet — link directo, notifica estudiantes | `meet.jit.si/{roomName}`, NO iframe |
| SCRUM-119 | VideoLlamadaController refactorizado a POO | En `api/v1/videollamadas.php` |
| SCRUM-120 | Grabaciones .mp4 — 500MB, `uploads/grabaciones/` | Estable |
| SCRUM-121 | Config perfil Estudiante y Directivo | Estable |
| SCRUM-122 | Git — primer commit en develop | Tag v8.0.0 |
| SCRUM-123 | View.php abstracta — dashboards la extienden | En `includes/View.php` |
| SCRUM-126 | Refactor POO — vistas heredan View, controllers usan modelos | Todos los dashboards |
| SCRUM-127 | Documentación + diagramas UML validados contra SOLID | En `docs/` y `diagrams/` |
| SCRUM-128 | BUG panel Directivo resuelto | Fix sesión en View.php |

---

## 🔴 Pendiente

| Issue | Descripción | Prioridad | Notas |
|---|---|---|---|
| ~~SCRUM-124~~ | Tests Selenium — ✅ 23/23 PASSED (2026-04-24) | ~~High~~ | Fix typo contraseña james, ver reporte_tests_scrum124.md |
| SCRUM-125 | Model.php — agregar CRUD base (find, findAll, save, delete) | High | Ver sección Models abajo |

---

## Estado de los Dashboards

| Rol | Completitud | Observaciones |
|---|---|---|
| Admin | ✅ 100% | SPA con gráficos Chart.js, CRUD usuarios/materias, reportes |
| Profesor | ✅ 100% | Aula virtual con 6 tabs, calificaciones, grabaciones, videollamadas |
| Estudiante | ✅ 100% | 7 secciones, notificaciones polling 30s, Jitsi integrado |
| Directivo | ⚠️ 60% | Solo lectura — sin gráficos ni operaciones complejas |

---

## SCRUM-124 — Tests Selenium: Estado detallado

**Archivos existentes:**

| Archivo | Framework | Tests | Estado |
|---|---|---|---|
| `tests/test_univirtual.py` | unittest + Selenium | 25 tests, 6 clases | Escrito, sin ejecutar |
| `test-files/tests_selenium_univirtual.py` | Selenium v3.0 | 10 funciones, más robusto | Escrito, sin ejecutar |
| `test-files/tests_playwright_univirtual.py` | Playwright v8 | 10 secciones, E2E completo | Escrito, sin ejecutar |

**Falta:**
- No existe `requirements.txt`
- No existe `pytest.ini` ni `conftest.py`
- No se ha ejecutado ninguna suite contra XAMPP local
- No hay reporte generado

**Para ejecutar:**
```bash
pip install selenium webdriver-manager requests
python -m unittest tests/test_univirtual.py -v
```

---

## SCRUM-125 — Model.php: Estado detallado

**Estado actual de `models/Model.php`:**
- Solo tiene constructor con conexión PDO (`db()` singleton)
- **NO tiene** métodos CRUD genéricos
- Cada modelo implementa sus propios métodos (11 modelos, cada uno con lógica propia)

**Modelos existentes:** Actividad, Asistencia, Entrega, Estudiante, Foro, Materia, Material, Mensaje, Notificacion, Profesor, Usuario

**Métodos que faltan agregar a Model.php:**
- `find(int $id): ?array`
- `findAll(): array`
- `save(array $data): int`
- `delete(int $id): void`

---

## Controladores API existentes (`api/v1/`)

| Archivo | Clase | Endpoints clave |
|---|---|---|
| Controller.php | Controller (base abstracta) | ok(), error(), requireAuth(), getBody() |
| estudiante.php | EstudianteController | dashboard, materias, tareas, aula, notificaciones |
| profesor.php | ProfesorController | dashboard, aula, calificar, asistencia, videollamadas |
| usuarios.php | UsuarioController | CRUD usuarios, profesores_facultad, estudiantes_facultad |
| directivo.php | DirectivoController | estadísticas académicas |
| materias.php | MateriaController | CRUD materias |
| inscripciones.php | InscripcionesController | inscribir/desinscribir |
| notificaciones.php | NotificacionController | obtener, marcar leídas |
| reportes.php | ReportesController | notas, asistencia, entregas |
| stats.php | StatsController | métricas dashboard |
| configuracion.php | ConfiguracionController | ajustes sistema |
| videollamadas.php | VideoLlamadaController | Jitsi integration |

# CLAUDE.md — UNI-VIRTUAL Project Brief
> Lee este archivo completo antes de tocar cualquier cosa. Es la memoria del proyecto.

---

## INSTRUCCIONES PARA CLAUDE CODE

### Uso de subagentes
- **Siempre usa subagentes en paralelo** para tareas independientes — nunca hagas secuencial lo que puede ser paralelo.
- Ejemplos de paralelización:
  - Leer múltiples archivos al mismo tiempo antes de editar
  - Ejecutar tests mientras generas código
  - Escribir modelo + controller al mismo tiempo
  - Buscar bugs en varios archivos simultáneamente
- Si una tarea tiene más de 2 pasos independientes → subagentes.

### Memoria del proyecto
- **Toda la memoria va en `/docs/memoria/`** dentro del proyecto.
- Al final de cada sesión de trabajo actualiza el archivo correspondiente.
- Nunca guardes memoria solo en el contexto — si se cierra la sesión se pierde.
- Estructura de la carpeta:

```
docs/
└── memoria/
    ├── CLAUDE.md          ← este archivo (briefing principal)
    ├── estado_actual.md   ← qué está hecho, qué falta, bugs conocidos
    ├── decisiones.md      ← decisiones de arquitectura y por qué
    ├── bugs.md            ← bugs encontrados y sus fixes
    └── sesiones/
        └── YYYY-MM-DD.md  ← resumen de cada sesión de trabajo
```

---

## EQUIPO

| Persona | Rol Scrum | Contacto |
|---|---|---|
| James Medina | Product Owner | medinajames666 (Jira owner) |
| Angel Angulo | Scrum Master | — |
| Edwin Carabali (Yonkers) | Developer | edwinacarabali@gmail.com |

---

## STACK TÉCNICO

| Capa | Tecnología |
|---|---|
| Backend | PHP 8 vanilla |
| Base de datos | MySQL 8 — **puerto 3307** (no 3306) |
| Frontend | React 18 CDN + Babel in-browser |
| Estilos | CSS puro — `assets/css/panel.css` |
| Servidor local | XAMPP |
| Video | Jitsi Meet — link directo `meet.jit.si/{roomName}`, NO iframe |
| Documentos | FPDF con fallback `window.print()` |
| Tipografía | DM Sans, DM Serif Display |
| Repo | github.com/wytreep/univirtual — rama `develop` |
| Gestión | Jira: medinajames666.atlassian.net |
| Tests | Selenium 4 + Python — `tests/test_univirtual.py` |

---

## ARQUITECTURA — REGLAS CRÍTICAS

### PHP — Patrón MVC
```
includes/
  Controller.php   ← abstracto, todos los controllers lo extienden
  View.php         ← abstracto, todos los dashboards lo extienden
  Model.php        ← Singleton PDO con CRUD base

models/
  Usuario.php, Materia.php, VideoLlamada.php, Actividad.php,
  Entrega.php, Asistencia.php, Notificacion.php, Material.php,
  Prueba.php       ← NUEVO SCRUM-129

api/v1/
  EstudianteController.php     extends Controller
  ProfesorController.php       extends Controller
  VideoLlamadaController.php   extends Controller  ← refactorizado SCRUM-119
  UsuarioController.php        extends Controller
  DirectivoController.php      extends Controller
  StatsController.php          extends Controller
  MateriaController.php        extends Controller
  ReportesController.php       extends Controller
  PruebasController.php        extends Controller  ← NUEVO SCRUM-129

pages/
  admin/dashboard.php          extends View
  profesor/dashboard.php       extends View
  estudiante/dashboard.php     extends View
  directivo/dashboard.php      extends View
```

### Sesión PHP — MUY IMPORTANTE
```php
// login.php guarda la fila COMPLETA del usuario:
$_SESSION['usuario'] = $fila_completa_de_bd;

// Para acceder al nombre — SIEMPRE así:
$_SESSION['usuario']['nombre']   // ✅ correcto
$_SESSION['nombre']              // ❌ incorrecto — da "Usuario"
```

### React — Convenciones
```js
// Renderizado condicional — SIEMPRE así:
{vista === 'x' && <Componente />}   // ✅
// NUNCA switch/case para vistas

// fetch — SIEMPRE con credentials:
fetch(url, { credentials: 'include' })

// API helper ya definido en todos los dashboards:
const api = {
  get: (u) => fetch(API+u, {credentials:'include'}).then(r=>r.json()),
  post: (u,b) => fetch(API+u, {method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'}, body:JSON.stringify(b)}).then(r=>r.json()),
  put: (u,b) => fetch(API+u, {method:'PUT', credentials:'include',
    headers:{'Content-Type':'application/json'}, body:JSON.stringify(b)}).then(r=>r.json()),
  del: (u) => fetch(API+u, {method:'DELETE', credentials:'include'}).then(r=>r.json()),
};
```

### Agregar vista al dashboard admin
Dos líneas — en el array `nav` y en el objeto `vistas`:
```js
// nav:
{id:'nueva_vista', ico:'🆕', lbl:'Nueva Vista'}
// vistas:
nueva_vista: <NuevoComponente/>
```

### API — Formato de respuesta SIEMPRE así:
```json
{ "status": "ok", "data": { ... } }
{ "status": "error", "mensaje": "..." }
```

### Modelos — usar db() directamente
```php
// ✅ CORRECTO — igual que el resto del proyecto
$stmt = db()->prepare("SELECT ...");

// ❌ INCORRECTO — no hay $this->db en los modelos
$stmt = $this->db->prepare("SELECT ...");
```

### MySQL — Campos exactos (no inventar nombres)
| Campo incorrecto | Campo correcto |
|---|---|
| `programa` | `creditos_aprobados` (tabla estudiantes) |
| `idNotificacion` | `idNotif` (tabla notificaciones) |
| `fecha_creacion` | `creada_en` (tabla notificaciones) |
| `archivoEntrega` | `urlArchivo` (tabla entregas y materiales) |
| `password` sin backticks | `` `password` `` — palabra reservada MySQL |

### codigoProf — generación obligatoria
```sql
SET codigoProf = CONCAT('PROF-', LPAD(idUsuario, 4, '0'))
```

### Bcrypt
- SIEMPRE generar hashes con `password_hash()` del propio PHP del proyecto
- NUNCA usar hashes de herramientas externas

---

## ESTADO ACTUAL (Mayo 2026)

### ✅ Completado
| Issue | Descripción |
|---|---|
| SCRUM-97 | Login con bcrypt, 4 roles, session_regenerate_id |
| SCRUM-98 | CRUD usuarios con transacciones BD |
| SCRUM-101 | Controller.php abstracto — Template Method |
| SCRUM-102 | Aula virtual SPA con 5 tabs |
| SCRUM-103 | Materiales — subida, descarga, contador |
| SCRUM-105 | React SPA embebida con fetch + credentials |
| SCRUM-106 | Jerarquía de roles admin > directivo > profesor > estudiante |
| SCRUM-111 | Config perfil — cambio nombre y password todos los roles |
| SCRUM-118 | Jitsi Meet — link directo, notifica estudiantes |
| SCRUM-119 | VideoLlamadaController refactorizado a POO ✅ |
| SCRUM-120 | Grabaciones .mp4 — 500MB, `uploads/grabaciones/` |
| SCRUM-121 | Config perfil Estudiante y Directivo |
| SCRUM-122 | Git — rama develop, tag v8.0.0 ✅ |
| SCRUM-123 | View.php abstracta — dashboards la extienden |
| SCRUM-124 | Tests Selenium 23/23 pasando ✅ |
| SCRUM-125 | Model.php CRUD base (find, findAll, save, delete) ✅ |
| SCRUM-126 | Refactor POO — vistas heredan View, controllers usan modelos |
| SCRUM-127 | Documentación + diagramas UML validados SOLID |
| SCRUM-128 | BUG panel Directivo resuelto |
| SCRUM-129 | Pruebas en BD — conftest.py + PruebasController + PanelPruebas ✅ |

### 🔴 Sprint actual — pendiente
| Issue | Descripción | Prioridad |
|---|---|---|
| SCRUM-142 | Sistema de calificaciones completo | 🔴 Alta — **SIGUIENTE** |
| SCRUM-143 | Foro por materia | 🔴 Alta |
| SCRUM-144 | Notificaciones en tiempo real (polling) | 🔴 Alta |
| SCRUM-145 | Dashboard estudiante mejorado con tarjetas | 🟡 Media |
| SCRUM-146 | Exportar reportes a PDF (FPDF) | 🟡 Media |
| SCRUM-147 | Recuperación de contraseña por email | 🟡 Media |
| SCRUM-148 | Historial de actividad por usuario | 🟢 Valor agregado |
| SCRUM-149 | Chat entre profesor y estudiante | 🟢 Valor agregado |

---

## BUGS CONOCIDOS Y FIXES

### BUG-001 — Hash incorrecto en BD
- **Causa:** hash de `'password'` en vez de `'123456'`
- **Fix:** ejecutar `fix_usuarios.sql` en phpMyAdmin

### BUG-004 — videollamadas.php retornaba 400
- **Causa:** PHP no populaba `$_POST` con JSON body
- **Fix ya aplicado en VideoLlamadaController:**
```php
$_jsonBody = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $_POST['action'] ?? $_jsonBody['action'] ?? '';
```

### BUG — View.php lee sesión mal
- **Fix ya aplicado:**
```php
$this->nombre = $_SESSION['usuario']['nombre']
             ?? $_SESSION['nombre']
             ?? 'Usuario';
```

---

## TABLA PRUEBAS — SCRUM-129

```sql
CREATE TABLE IF NOT EXISTS pruebas (
    idPrueba        INT AUTO_INCREMENT PRIMARY KEY,
    nombre_test     VARCHAR(200)  NOT NULL,
    clase_test      VARCHAR(100)  NOT NULL,
    estado          ENUM('PASSED','FAILED','ERROR') NOT NULL,
    duracion_seg    DECIMAL(6,3)  DEFAULT 0.000,
    mensaje_error   TEXT          DEFAULT NULL,
    ejecutado_en    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado    (estado),
    INDEX idx_clase     (clase_test),
    INDEX idx_ejecutado (ejecutado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Flujo: `python -m pytest tests/ -v` → `conftest.py` captura cada test → POST a `PruebasController` → guarda en tabla → visible en panel admin pestaña "Pruebas".

---

## JIRA

- **URL:** medinajames666.atlassian.net
- **cloudId:** `29b89eed-ba29-4eab-b6b1-7b9f2458e1ab`
- **Workflow:** solo existe "En revisión" — cerrar issues con comentario
- **Tono de comentarios:** casual, tipo Slack — sin checkboxes ni emojis de estado

---

## GIT — CONVENCIÓN DE COMMITS

```
feat(SCRUM-142): sistema de calificaciones completo
fix(SCRUM-xxx): descripción del fix
docs(SCRUM-xxx): actualizar documentación
chore(SCRUM-xxx): tarea de configuración
test(SCRUM-xxx): agregar o corregir tests
```

Rama activa: `develop` | Tag actual: `v8.0.0`

---

## PRINCIPIOS DE TRABAJO

1. **Auditar antes de agregar** — leer el código existente antes de escribir
2. **No agregar endpoints innecesarios** — solo los que pide el ticket
3. **Respetar panel.css** — no agregar estilos inline si existe la clase
4. **db() siempre** — los modelos usan `db()`, no `$this->db`
5. **Subagentes siempre** — paralelizar todo lo que se pueda
6. **Paths PHP en Windows** — usar `__DIR__.'/Controller.php'` (sin `../`)

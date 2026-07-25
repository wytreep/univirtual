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

---

## ARQUITECTURA — REGLAS CRÍTICAS

### PHP — Patrón MVC
```
includes/
  Controller.php   ← abstracto, todos los controllers lo extienden
  View.php         ← abstracto, todos los dashboards lo extienden
  Model.php        ← Singleton PDO

models/
  Usuario.php, Materia.php, VideoLlamada.php, Actividad.php,
  Entrega.php, Asistencia.php, Notificacion.php, Material.php...

api/v1/
  EstudianteController.php   extends Controller
  ProfesorController.php     extends Controller
  VideoLlamadaController.php extends Controller  ← recién refactorizado
  UsuarioController.php      extends Controller
  DirectivoController.php    extends Controller
  StatsController.php        extends Controller
  MateriaController.php      extends Controller
  ReportesController.php     extends Controller

pages/
  admin/dashboard.php        extends View
  profesor/dashboard.php     extends View
  estudiante/dashboard.php   extends View
  directivo/dashboard.php    extends View
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
```

### API — Formato de respuesta SIEMPRE así:
```json
{ "status": "ok", "data": { ... } }
{ "status": "error", "mensaje": "..." }
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
- NUNCA usar hashes de herramientas externas — no son compatibles

---

## ESTADO ACTUAL DEL PROYECTO (Abril 2026)

### ✅ Completado y funcionando
| Issue | Descripción |
|---|---|
| SCRUM-97 | Login con bcrypt, 4 roles, session_regenerate_id |
| SCRUM-98 | CRUD usuarios con transacciones BD |
| SCRUM-101 | Controller.php abstracto — Template Method pattern |
| SCRUM-102 | Aula virtual SPA con 5 tabs |
| SCRUM-103 | Materiales — subida, descarga, contador |
| SCRUM-105 | React SPA embebida con fetch + credentials |
| SCRUM-106 | Jerarquía de roles admin > directivo > profesor > estudiante |
| SCRUM-111 | Config perfil — cambio nombre y password todos los roles |
| SCRUM-118 | Jitsi Meet — link directo, notifica estudiantes |
| SCRUM-119 | VideoLlamadaController refactorizado a POO ✅ RECIÉN |
| SCRUM-120 | Grabaciones .mp4 — 500MB, `uploads/grabaciones/` |
| SCRUM-121 | Config perfil Estudiante y Directivo |
| SCRUM-123 | View.php abstracta — dashboards la extienden |
| SCRUM-126 | Refactor POO — vistas heredan View, controllers usan modelos |
| SCRUM-127 | Documentación + diagramas UML validados contra SOLID |
| SCRUM-128 | BUG panel Directivo resuelto |
| SCRUM-122 | Git — primer commit en develop ✅ RECIÉN |

### 🔴 Pendiente
| Issue | Descripción | Prioridad |
|---|---|---|
| SCRUM-124 | Tests Selenium — escritos, falta ejecutar y reportar | High |
| SCRUM-125 | Model.php — agregar CRUD base (find, findAll, save, delete) | High |

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
- **Causa:** `$_SESSION['nombre']` no existe
- **Fix ya aplicado:**
```php
$this->nombre = $_SESSION['usuario']['nombre']
             ?? $_SESSION['nombre']
             ?? 'Usuario';
```

---

## JIRA

- **URL:** medinajames666.atlassian.net
- **cloudId:** `29b89eed-ba29-4eab-b6b1-7b9f2458e1ab`
- **Workflow:** solo existe el estado "En revisión" — cerrar issues con comentario, no con transición
- **Tono de comentarios:** casual, tipo Slack — sin checkboxes ni emojis de estado

---

## GIT — CONVENCIÓN DE COMMITS

```
feat(SCRUM-120): agregar subida de grabaciones
fix(SCRUM-119): refactorizar videollamadas.php a POO
docs(SCRUM-127): actualizar diagramas UML
chore(SCRUM-122): configurar .gitignore y primer commit
test(SCRUM-124): agregar suite Selenium Sprint 6
```

Rama activa: `develop`
Tag actual: `v8.0.0`

---

## TESTS SELENIUM — SCRUM-124

Suite de 25 tests en Python + Selenium 4, distribuidos en 6 clases:
1. Login por rol (admin, profesor, estudiante, directivo)
2. Bloqueo de acceso cross-rol
3. Verificación diseño sidebar
4. Navegación configuración perfil (SCRUM-121)
5. Validación link Jitsi — confirmar que `JitsiMeetExternalAPI` NO existe en el HTML
6. Ejecución con un solo comando: `python -m pytest tests/ -v`

**Estado:** escritos, pendiente ejecutar contra XAMPP local y generar reporte.

---

## PRINCIPIOS DE TRABAJO

1. **Auditar antes de agregar** — siempre leer el código existente antes de escribir
2. **No agregar endpoints innecesarios** — solo los que pide el ticket
3. **Respetar panel.css** — no agregar estilos inline si existe la clase
4. **Qwen ejecuta, Claude decide** — si usas Qwen Code para ejecutar, dale instrucciones explícitas de alcance
5. **Subagentes siempre** — paralelizar todo lo que se pueda

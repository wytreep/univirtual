# UNI-VIRTUAL
**Plataforma de Educación Virtual — Institución Universitaria Antonio José Camacho**

> Proyecto de Ingeniería de Software · Semestre 2026-I
> Stack: PHP 8 POO · MySQL 8 · React 18 SPA · Chart.js 4.4 · FPDF · Jitsi Meet · Apache/XAMPP

---

## Equipo

| Nombre | Rol Scrum | GitHub |
|--------|-----------|--------|
| James Medina | Product Owner | @james-medina |
| Edwin Carabali | Developer | @edwin-carabali |
| Angel Angulo | Scrum Master | @angel-angulo |

---

## Stack tecnológico

```
Backend   PHP 8 POO — Arquitectura MVC de tres capas
          Patrón Singleton (PDO), Template Method, Composite
Database  MySQL 8 — 13 tablas, FK constraints, índices
Frontend  React 18 SPA embebido en PHP (sin build step)
          Chart.js 4.4 para gráficas
Reports   FPDF 1.86 para PDF nativos / HTML imprimible como fallback
Video     Streaming HTTP Range requests (mp4, webm)
Meetings  Jitsi Meet (meet.jit.si) — sin cuenta ni instalación
Server    Apache + XAMPP (Windows) / LAMP (Linux)
```

---

## Instalación local

### Requisitos previos

- [XAMPP](https://www.apachefriends.org/) con Apache + MySQL + PHP 8.x
- MySQL Workbench o phpMyAdmin
- Git
- Navegador moderno (Chrome, Firefox, Edge)

### Paso 1 — Clonar el repositorio

```bash
git clone https://github.com/USUARIO/univirtual.git
cd univirtual
```

O si ya tienes el ZIP: descomprime en `C:\xampp\htdocs\univirtual\`

### Paso 2 — Configurar entorno local

```bash
# Copiar plantilla de configuración
cp config/config.example.php config/config.php
```

Editar `config/config.php` con tus datos:

```php
define('DB_HOST', '127.0.0.1');   // o 'localhost'
define('DB_PORT', '3307');         // XAMPP suele usar 3307
define('DB_USER', 'root');
define('DB_PASS', '');             // tu contraseña MySQL
define('DB_NAME', 'univirtual');
define('BASE_URL',  'http://localhost/univirtual');
define('BASE_PATH', __DIR__ . '/..');
```

> ⚠️ `config/config.php` está en `.gitignore` — **nunca** se sube al repositorio.

### Paso 3 — Crear la base de datos

**Opción A — MySQL Workbench:**
1. Conexión → `localhost:3307` usuario `root`
2. File → Open SQL Script → selecciona `config/database.sql`
3. `Ctrl + Shift + Enter` para ejecutar todo

**Opción B — phpMyAdmin:**
1. `http://localhost/phpmyadmin`
2. Importar → selecciona `config/database.sql` → Ejecutar

### Paso 4 — Instalar FPDF (opcional pero recomendado)

```
1. Descargar fpdf186.zip de http://www.fpdf.org/
2. Extraer fpdf.php
3. Copiarlo a: univirtual/includes/fpdf.php
```

Sin FPDF los reportes funcionan como HTML imprimible (igualmente funcional).

### Paso 5 — Permisos de uploads

**Windows/XAMPP:** Funciona sin cambios adicionales.

**Linux/Mac:**
```bash
chmod -R 755 uploads/
chmod -R 777 uploads/materiales/ uploads/entregas/ uploads/enunciados/
```

### Paso 6 — Iniciar y probar

1. Abre XAMPP → Inicia **Apache** y **MySQL**
2. Navega a: `http://localhost/univirtual`
3. Inicia sesión con cualquiera de las cuentas de prueba

### Cuentas de prueba — contraseña `123456`

| Rol | Email |
|-----|-------|
| Admin | admin@univirtual.edu.co |
| Profesor | james@univirtual.edu.co |
| Profesor | maria@univirtual.edu.co |
| Estudiante | edwin@univirtual.edu.co |
| Estudiante | angel@univirtual.edu.co |
| Estudiante | laura@univirtual.edu.co |
| Estudiante | carlos@univirtual.edu.co |

---

## Estructura del proyecto

```
univirtual/
├── .gitignore
├── .htaccess                    ← URL rewriting Apache
├── index.php                    ← Punto de entrada → redirect login
│
├── config/
│   ├── config.php               ← Configuración local (en .gitignore)
│   ├── config.example.php       ← Plantilla sin credenciales ✅
│   └── database.sql             ← Schema + datos de prueba ✅
│
├── includes/
│   ├── auth.php                 ← Funciones de sesión y roles
│   ├── fpdf.php                 ← Librería PDF (instalar manualmente)
│   ├── header.php / footer.php
│
├── models/                      ← Capa de datos (PHP 8 POO)
│   ├── Model.php                ← Abstracto, Singleton PDO
│   ├── Usuario.php, Estudiante.php, Profesor.php
│   ├── Materia.php, Material.php, Actividad.php
│   ├── Entrega.php, Foro.php, Mensaje.php
│   ├── Asistencia.php, Notificacion.php
│
├── api/
│   ├── descargar.php            ← Descarga segura de archivos
│   ├── stream_video.php         ← Streaming de video (Range requests)
│   ├── reporte_notas.php        ← PDF/CSV calificaciones
│   ├── reporte_asistencia.php   ← PDF/CSV asistencia
│   └── v1/
│       ├── Controller.php       ← Clase base abstracta (CORS, auth, JSON)
│       ├── usuarios.php         ← CRUD usuarios (solo admin)
│       ├── materias.php         ← CRUD materias
│       ├── profesor.php         ← Acciones del profesor (12 endpoints)
│       ├── estudiante.php       ← Acciones del estudiante (9 endpoints)
│       ├── videollamadas.php    ← Módulo Jitsi Meet ✅ NEW
│       ├── inscripciones.php
│       ├── reportes.php
│       └── stats.php
│
├── pages/
│   ├── auth/login.php           ← Login institucional con DM Sans/Serif
│   ├── auth/logout.php
│   ├── admin/dashboard.php      ← Panel admin React SPA
│   ├── profesor/dashboard.php   ← Panel profesor React SPA
│   └── estudiante/dashboard.php ← Panel estudiante React SPA
│
├── assets/
│   ├── css/main.css             ← Estilos globales
│   ├── css/panel.css            ← Design system compartido (613 líneas)
│   └── js/main.js
│
└── uploads/                     ← Archivos subidos (en .gitignore)
    ├── materiales/              ← PDFs, videos, PPTs
    ├── entregas/                ← Archivos de estudiantes
    ├── enunciados/              ← Enunciados de actividades
    └── avatares/
```

---

## Funcionalidades

### Panel Profesor
- ✅ Dashboard con estadísticas (materias, entregas sin calificar, actividad reciente)
- ✅ Aula virtual con tabs: Materiales · Actividades · Foros · Estudiantes · Asistencia · **Videollamadas**
- ✅ Subir materiales (PDF, video, PPT, enlace externo) — validación de extensión, 200 MB
- ✅ Crear/eliminar actividades con fecha de entrega y notificación automática
- ✅ Ver y calificar entregas con retroalimentación
- ✅ Registrar notas por parciales (1, 2, talleres, final)
- ✅ Registrar asistencia por sesión (toggle por estudiante)
- ✅ Crear foros y responder mensajes (Patrón Composite)
- ✅ Reportes PDF/CSV de calificaciones y asistencia
- ✅ **Programar clases virtuales con Jitsi Meet** — genera sala automáticamente, notifica estudiantes
- ✅ Finalizar / eliminar videollamadas

### Panel Estudiante
- ✅ Dashboard con promedio general, asistencia, tareas pendientes
- ✅ Aula virtual con tabs: Materiales · Actividades · Foros · Asistencia · **Videollamadas**
- ✅ Descargar materiales (PDF inline en browser, otros como attachment)
- ✅ Ver videos con streaming y seeking (Range HTTP)
- ✅ Entregar actividades con archivo + comentario
- ✅ Ver calificaciones y retroalimentación del profesor
- ✅ Ver porcentaje de asistencia con alerta si < 75%
- ✅ Participar en foros con respuestas anidadas
- ✅ **Ver clases virtuales y unirse a Jitsi Meet con un clic**
- ✅ Notificaciones automáticas (nuevos materiales, tareas, calificaciones)

### Panel Admin
- ✅ Dashboard con estadísticas globales y gráficas (Chart.js)
- ✅ CRUD de usuarios con cambio de rol y estado activo/inactivo
- ✅ Gestión de materias e inscripciones

---

## Módulo Videollamadas — Jitsi Meet

No requiere cuenta, servidor propio ni instalación de software.

**Flujo:**
1. Profesor crea videollamada en el tab `🎥 Videollamadas` del aula
2. El sistema genera un `roomName` único: `univirtual-IS201-20260320-0800`
3. Los estudiantes inscritos reciben notificación automática
4. Cualquier participante abre `https://meet.jit.si/univirtual-IS201-20260320-0800`
5. La clase inicia en el navegador sin plugins ni cuentas

**Estados de una videollamada:**
- 🔵 **Programada** — con cuenta regresiva en tiempo real
- 🟢 **En curso** — se activa 15 min antes del horario y dura `duracion_min`
- ⚫ **Finalizada** — marcada por el profesor o pasado el tiempo

---

## Git & GitHub Workflow

Ver [`GIT_WORKFLOW.md`](GIT_WORKFLOW.md) para instrucciones completas.

**Resumen rápido:**
```
main ← Solo via PR desde develop (aprobado por PO)
develop ← Integración de features
feature/UV-XX-descripcion ← Una rama por tarea Jira
```

**Commits:**
```bash
feat(jitsi): agregar módulo videollamadas con Jitsi Meet
fix(reportes): restaurar parámetro idAula en reporte_asistencia
docs(readme): actualizar instrucciones de instalación
```

---

## Jira

Ver [`JIRA_BOARD.md`](JIRA_BOARD.md) para épicas, historias, criterios de aceptación y velocidad del equipo.

**Sprint actual:** Sprint 4 — Videollamadas + DevOps workflow

---

## Solución de problemas frecuentes

**"Connection refused" o "Error de conexión a MySQL"**
→ Verificar que MySQL esté corriendo en XAMPP
→ Cambiar `DB_HOST` a `127.0.0.1` en lugar de `localhost`
→ Verificar el puerto: XAMPP suele usar `3307`, no `3306`

**"Archivo no encontrado en el servidor"**
→ El archivo fue referenciado en BD pero no existe físicamente
→ Verificar que `uploads/materiales/` tiene permisos de escritura
→ En Linux: `chmod 777 uploads/materiales/`

**"Error al subir archivos grandes"**
→ Editar `C:\xampp\php\php.ini`:
```ini
upload_max_filesize = 200M
post_max_size = 200M
max_execution_time = 300
```
→ Reiniciar Apache en XAMPP

**Los reportes no generan PDF nativo**
→ Instalar FPDF: descargar de http://www.fpdf.org/ y copiar `fpdf.php` a `includes/`
→ Sin FPDF los reportes abren como HTML imprimible (usar Ctrl+P → Guardar como PDF)

**El video no hace streaming / no permite seeking**
→ Verificar que `stream_video.php` está accesible
→ El servidor debe enviar headers `Accept-Ranges: bytes`
→ Apache con XAMPP lo soporta por defecto

---

## Configuración php.ini recomendada

`C:\xampp\php\php.ini`:
```ini
upload_max_filesize = 200M
post_max_size       = 200M
max_execution_time  = 300
max_input_time      = 300
memory_limit        = 256M
display_errors      = On    ; solo en desarrollo
```

---

**UNI-VIRTUAL · Ingeniería de Software · 2026 · UNIAJC**

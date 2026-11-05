# SRS — UNI-VIRTUAL
## Especificación de Requisitos de Software (IEEE 830)

| Campo | Valor |
|---|---|
| Producto | UNI-VIRTUAL |
| Versión | 1.0 |
| Fecha | 29 de octubre de 2026 |
| Estándar | IEEE 830-1998 (adaptado) |
| Alcance | Historias vencidas (sprints 1–2) + avance anticipado del Sprint 3 |
| Documento padre | `PRD.md` |

---

## 1. Introducción

### 1.1 Propósito

Especificar de forma verificable los requisitos funcionales y no funcionales de UNI-VIRTUAL para las historias vencidas de los sprints 1 y 2 y el avance anticipado del Sprint 3, de modo que sirvan como contrato entre el equipo de desarrollo, el Product Owner y la evaluación académica.

### 1.2 Alcance del sistema

UNI-VIRTUAL es una aplicación web monolítica en PHP 8 con API JSON y frontend React embebido. Cubre autenticación por roles, dashboards, gestión de usuarios, materias e inscripciones, registro de notas, materiales, actividades, entregas, asistencia, foros, notificaciones y videollamadas.

Este SRS se limita a las 13 historias del avance. Las funcionalidades de los sprints 3 y 4 se mencionan solo como contexto.

### 1.3 Definiciones, acrónimos y abreviaturas

| Término | Significado |
|---|---|
| HU | Historia de usuario. |
| RF | Requisito funcional. |
| RNF | Requisito no funcional. |
| API | Interfaz de programación de aplicaciones. |
| JSON | Formato de intercambio de datos. |
| PDO | PHP Data Objects, capa de acceso a datos. |
| MVC | Modelo–Vista–Controlador. |
| SPA | Single Page Application. |
| CDN | Content Delivery Network. |
| 3FN | Tercera forma normal. |
| ADR | Architecture Decision Record. |
| JWT | JSON Web Token (no usado; la sesión es PHP nativa). |

### 1.4 Referencias

- `PRD.md` — visión y alcance.
- `arquitectura.md` — arquitectura y patrones.
- `modelo-datos.md` — modelo ER y diccionario.
- `config/database_v8.sql` — esquema de base de datos.
- `README.md` — instalación y credenciales.
- IEEE 830-1998 — guía para especificaciones de requisitos.

### 1.5 Visión general del documento

La sección 2 describe el producto y su contexto. La sección 3 contiene los requisitos específicos. La sección 4 presenta la trazabilidad y los apéndices.

---

## 2. Descripción general

### 2.1 Perspectiva del producto

UNI-VIRTUAL es un sistema nuevo que reutiliza una base técnica existente (v8) y la ordena para el semestre 2026-II. No es un módulo de otro sistema: es la plataforma central.

```text
Navegador (React 18 CDN)
        │  fetch + credentials
        ▼
Apache + PHP 8 (API JSON + vistas)
        │  PDO preparado
        ▼
MySQL 8 (19 tablas, 3FN)
        │
        ├── uploads/ (materiales, entregas, avatares)
        └── Jitsi Meet (link externo, Sprint 3)
```

### 2.2 Funciones del producto

1. Autenticación y autorización por roles.
2. Dashboards diferenciados.
3. Gestión de usuarios.
4. Gestión de materias e inscripciones.
5. Registro y consulta de notas.
6. Aulas virtuales con materiales y actividades.
7. Entregas y retroalimentación.
8. Asistencia.
9. Foros anidados.
10. Notificaciones.
11. Videollamadas (contexto Sprint 3).
12. Reportes (contexto Sprint 4).

### 2.3 Características de los usuarios

| Usuario | Conocimiento técnico | Frecuencia de uso | Necesidad |
|---|---|---|---|
| Estudiante | Básico | Diaria/semanal | Ver pendientes y entregar. |
| Profesor | Medio | Semanal | Publicar, calificar, comunicar. |
| Directivo | Medio | Semanal | Consultar indicadores. |
| Admin | Alto | Semanal | Gestionar datos maestros. |

### 2.4 Restricciones

- PHP 8.0 o superior; no se usa Composer ni framework.
- MySQL 8 en puerto 3307.
- Apache con XAMPP.
- React 18 y Babel por CDN; sin build step.
- Sesión PHP nativa con cookies; no JWT.
- Presupuesto de infraestructura cero.

### 2.5 Supuestos y dependencias

- El servidor tiene salida a Internet para los CDN de React y Google Fonts.
- La base `univirtual` se crea con `config/database_v8.sql`.
- Los archivos subidos se almacenan en `uploads/` con permisos de escritura.
- El usuario conoce su correo institucional.
- El navegador soporta `fetch`, `Promise` y ES2017.

---

## 3. Requisitos específicos

### 3.1 Interfaces externas

#### 3.1.1 Interfaces de usuario

- Pantalla de login con correo, contraseña y mensajes de error.
- Dashboards con barra lateral, encabezado, tarjetas y tablas.
- Modales para crear/editar entidades.
- Mensajes de alerta tipo `ok` / `err`.
- Diseño responsive básico (mínimo 768 px).

#### 3.1.2 Interfaces de hardware

- No requiere hardware especial.
- Servidor con al menos 2 GB de RAM y 1 GB libre en disco.

#### 3.1.3 Interfaces de software

- Apache 2.4+, PHP 8.0+, MySQL 8.0+.
- Navegador Chrome, Edge o Firefox actualizado.
- FPDF opcional para PDF nativo.
- Jitsi Meet por URL externa (Sprint 3).

#### 3.1.4 Interfaces de comunicación

- HTTP/HTTPS.
- JSON `application/json; charset=utf-8`.
- Multipart/form-data para subida de archivos.
- Cookies de sesión PHP (`PHPSESSID`).

---

### 3.2 Requisitos funcionales

#### RF-01 — Autenticación

**Prioridad:** Must  
**HU:** UV-8

El sistema debe permitir que un usuario registrado y activo inicie sesión con correo y contraseña.

**Entradas:** `email`, `password`.  
**Proceso:** buscar por email, verificar `password_verify`, comprobar `activo`, regenerar sesión.  
**Salida:** redirección a `/pages/{rol}/dashboard.php`.  
**Errores:** credenciales inválidas, cuenta inactiva, campos vacíos.

**Criterios de aceptación**

```gherkin
Escenario: Login exitoso de estudiante
  Dado que existe una cuenta activa con rol estudiante
  Cuando ingreso el correo y la contraseña correctos
  Entonces el sistema crea la sesión
  Y me redirige a /pages/estudiante/dashboard.php
```

#### RF-02 — Control de acceso por rol

**Prioridad:** Must  
**HU:** UV-8

El sistema debe impedir que un usuario acceda a un dashboard o endpoint de otro rol.

**Criterios de aceptación**

```gherkin
Escenario: Acceso cruzado bloqueado
  Dado que estoy autenticado como estudiante
  Cuando intento abrir /pages/admin/dashboard.php
  Entonces el sistema me redirige al login con error de acceso
```

#### RF-03 — Gestión de usuarios

**Prioridad:** Must  
**HU:** UV-9

El administrador debe poder listar, crear, editar, activar/desactivar y eliminar lógicamente usuarios de los roles estudiante, profesor, directivo y admin.

**Reglas**

- El email debe ser único.
- La contraseña se almacena con bcrypt.
- Al crear un estudiante se crea su registro en `estudiantes` con `codigoEst`, `semestre` e `idCarrera`.
- Al crear un profesor se crea su registro en `profesores` con `codigoProf`, `departamento` e `idFacultad`.
- La eliminación es lógica (`activo = 0`), no física.

**Criterios de aceptación**

```gherkin
Escenario: Crear estudiante
  Dado que soy administrador
  Y estoy en la sección Usuarios
  Cuando registro un estudiante con nombre, email, contraseña, código, semestre y carrera
  Entonces el sistema crea el usuario y su perfil académico
  Y el nuevo estudiante aparece en el listado
```

> **Estado actual:** RF-03 no cumple. El formulario envía `codigoEst` y el controlador espera `codigo`; además intenta insertar `programa`, columna ausente en v8. Ver `03-historias/UV-09-crud-usuarios.md`.

#### RF-04 — Dashboard del estudiante

**Prioridad:** Must  
**HU:** UV-10

El estudiante debe ver, al ingresar:

- Materias inscritas con profesor, notas y pendientes.
- Promedio general.
- Porcentaje de asistencia.
- Próximas tareas sin entregar.
- Notificaciones recientes y no leídas.

**Criterios de aceptación**

```gherkin
Escenario: Dashboard con datos
  Dado que soy estudiante con materias inscritas
  Cuando abro mi dashboard
  Entonces veo mis materias con su profesor
  Y veo mi promedio y porcentaje de asistencia
  Y veo las tareas próximas a vencer
```

#### RF-05 — Dashboard del profesor

**Prioridad:** Must  
**HU:** UV-11

El profesor debe ver:

- Materias asignadas con número de estudiantes, materiales y actividades.
- Entregas sin calificar.
- Entregas recientes.
- Notificaciones no leídas.

**Criterios de aceptación**

```gherkin
Escenario: Dashboard del profesor
  Dado que soy profesor con materias asignadas
  Cuando abro mi dashboard
  Entonces veo el total de estudiantes y materiales
  Y veo las entregas pendientes de calificar
```

#### RF-06 — Inscripción de estudiantes

**Prioridad:** Must  
**HU:** UV-12

El administrador debe poder inscribir y desinscribir estudiantes en materias.

**Reglas**

- No se permite duplicar la inscripción (`uq_est_mat`).
- Se valida `creditos_aprobados >= creditos_minimos` de la materia.
- Si no cumple, se rechaza con mensaje que indica créditos requeridos y disponibles.
- Al inscribir, se notifica al estudiante.

**Criterios de aceptación**

```gherkin
Escenario: Inscripción bloqueada por créditos
  Dado un estudiante con 40 créditos aprobados
  Y una materia que exige 48
  Cuando el admin intenta inscribirlo
  Entonces el sistema bloquea la inscripción
  Y muestra "necesita 48 créditos aprobados, tiene 40"
```

```gherkin
Escenario: Inscripción exitosa
  Dado un estudiante con 72 créditos aprobados
  Y una materia que exige 48
  Cuando el admin lo inscribe
  Entonces la inscripción se guarda
  Y el estudiante recibe una notificación
```

#### RF-07 — Registro y actualización de notas

**Prioridad:** Must  
**HU:** UV-13

El profesor debe poder registrar o actualizar `nota_parcial1`, `nota_parcial2`, `nota_talleres` y `nota_final` de un estudiante en una materia.

**Reglas**

- Solo se aceptan los cuatro campos permitidos.
- La nota se normaliza al rango 0.0–5.0.
- La actualización se hace sobre `inscripciones`.
- Se notifica al estudiante.

**Criterios de aceptación**

```gherkin
Escenario: Registrar nota
  Dado que soy profesor de una materia
  Cuando ingreso una nota de 4.5 en nota_parcial1
  Entonces el sistema guarda la nota
  Y el estudiante recibe una notificación
```

#### RF-08 — Consulta de notas del estudiante

**Prioridad:** Must  
**HU:** UV-13

El estudiante debe ver sus notas por materia, el promedio calculado y el estado (`aprobado`, `reprobando`, `en_curso`).

**Criterios de aceptación**

```gherkin
Escenario: Consultar notas
  Dado que tengo notas registradas
  Cuando abro Calificaciones
  Entonces veo cada materia con sus notas
  Y veo el promedio y el estado
```

#### RF-09 — API JSON consistente

**Prioridad:** Must  
**HU:** UV-29

Todas las respuestas de la API deben usar el contrato:

```json
{ "status": "ok", "mensaje": "", "data": {} }
{ "status": "error", "mensaje": "descripción", "data": null }
```

**Criterios de aceptación**

- Todo controlador extiende `Controller`.
- Los errores usan `http_response_code` coherente (400, 401, 403, 404, 405).
- No se filtran trazas ni contraseñas.

#### RF-10 — Consumo de API con sesión

**Prioridad:** Must  
**HU:** UV-30

El frontend debe consumir la API con `credentials: 'include'` para enviar la cookie de sesión.

**Criterios de aceptación**

- Un endpoint protegido responde 401 sin sesión.
- Con sesión válida responde 200 y datos.
- El dashboard renderiza sin recargar la página.

#### RF-11 — Base de datos normalizada

**Prioridad:** Must  
**HU:** UV-28

El sistema debe operar sobre un esquema MySQL 8 en 3FN, con integridad referencial y datos semilla.

**Criterios de aceptación**

- 19 tablas creadas sin errores.
- Claves foráneas activas.
- Datos semilla suficientes para iniciar sesión y demostrar el flujo.
- Script idempotente (`DROP TABLE IF EXISTS`).

#### RF-12 — Instalación reproducible

**Prioridad:** Must  
**HU:** UV-27

Un tercero debe poder clonar el repositorio, configurar la BD y ejecutar la aplicación siguiendo el manual.

**Criterios de aceptación**

- Existe `config.example.php`.
- Existe el script de hashes.
- El manual indica el orden de importación.
- El login responde en `http://localhost/univirtual`.

> **Estado actual:** parcial. Faltan `config.example.php` y `fix_passwords_v8.sql`; se incluyen como plantillas en `05-entrega/plantillas/`.

#### RF-13 — Plan y casos de prueba

**Prioridad:** Should  
**HU:** UV-32

El proyecto debe tener un plan de pruebas y casos de aceptación ejecutables.

**Criterios de aceptación**

- Plan con alcance, estrategia, entorno y criterios.
- Al menos un caso por historia.
- Trazabilidad HU → caso.
- Resultados registrados.

---

### 3.3 Requisitos no funcionales

| ID | Categoría | Requisito | Criterio verificable |
|---|---|---|---|
| RNF-01 | Seguridad | Contraseñas con bcrypt. | `password_hash` / `password_verify`. |
| RNF-02 | Seguridad | Sesión regenerada al autenticar. | `session_regenerate_id(true)`. |
| RNF-03 | Seguridad | Consultas preparadas en todo acceso a BD. | Revisión de `prepare`/`execute`. |
| RNF-04 | Seguridad | Control de acceso por rol en vistas y API. | `requireLogin`, `requireNivel`, `requireAuth`. |
| RNF-05 | Seguridad | Archivos subidos fuera de ejecución PHP. | `.htaccess` en `uploads/`. |
| RNF-06 | Rendimiento | Dashboard en menos de 2 s en local. | Medición manual. |
| RNF-07 | Rendimiento | Consultas con índices en columnas de filtro. | `idx_*` en el esquema. |
| RNF-08 | Usabilidad | Interfaz en español, mensajes claros. | Revisión de textos. |
| RNF-09 | Usabilidad | Navegación consistente entre dashboards. | Barra lateral y breadcrumb. |
| RNF-10 | Compatibilidad | Chrome, Edge y Firefox actuales. | Prueba manual. |
| RNF-11 | Compatibilidad | PHP 8.0+ y MySQL 8.0+. | `php -v`, versión del servidor. |
| RNF-12 | Mantenibilidad | Separación MVC y nombres consistentes. | Revisión de estructura. |
| RNF-13 | Mantenibilidad | Documentación de arquitectura y ADRs. | Documentos de esta carpeta. |
| RNF-14 | Disponibilidad | Operación local sin servicios de pago. | XAMPP. |
| RNF-15 | Escalabilidad | Soporte de al menos 100 usuarios concurrentes en local. | Prueba de carga (futuro). |
| RNF-16 | Auditabilidad | Notificaciones y registros de acciones clave. | Tabla `notificaciones`. |
| RNF-17 | Portabilidad | Configuración por constantes, no rutas absolutas. | `config.php` (con deuda de `BASE_URL`). |
| RNF-18 | Calidad | 0 errores de sintaxis PHP. | `php -l` sobre 61 archivos. |

---

### 3.4 Requisitos de datos

| Entidad | Regla |
|---|---|
| `usuarios` | Email único; rol en enum; `activo` por defecto 1. |
| `estudiantes` | Un registro por usuario; `codigoEst` único. |
| `profesores` | Un registro por usuario; `codigoProf` único. |
| `directivos` | Un registro por usuario; facultad obligatoria. |
| `materias` | Código único; profesor obligatorio; `creditos_minimos >= 0`. |
| `inscripciones` | Única por (estudiante, materia); notas entre 0 y 5. |
| `aulas_virtuales` | Una por materia. |
| `materiales` | Pertenece a un aula y a un profesor. |
| `actividades` | Pertenece a un aula; fecha de entrega obligatoria. |
| `entregas` | Única por (actividad, estudiante); archivo obligatorio. |
| `foros` | Pertenece a un aula. |
| `mensajes` | Puede tener padre (Composite); usuario obligatorio. |
| `asistencia` | Única por (materia, estudiante, fecha). |
| `notificaciones` | Pertenece a un usuario; `leida` por defecto 0. |
| `videollamadas` | `roomName` único; estado programada/en curso/finalizada. |
| `grabaciones` | Pertenece a una videollamada. |
| `configuracion` | Clave primaria textual; valor textual. |

**Regla transversal:** toda fecha se almacena en `DATETIME` o `DATE`; la zona horaria se asume local del servidor.

---

### 3.5 Requisitos de interfaz

- El login debe mostrar un mensaje claro ante credenciales inválidas o cuenta inactiva.
- El dashboard debe cargar sin recargar la página.
- Las tablas deben permitir búsqueda y filtro por rol/estado.
- Los formularios deben validar campos obligatorios en cliente y servidor.
- Las acciones destructivas deben pedir confirmación.
- Los mensajes de error de la API deben mostrarse en la UI.

---

## 4. Apéndices

### 4.1 Trazabilidad resumida

| HU | RF | Endpoint / archivo | Prueba |
|---|---|---|---|
| UV-8 | RF-01, RF-02 | `pages/auth/login.php`, `includes/auth.php` | CP-01 a CP-05 |
| UV-9 | RF-03 | `api/v1/usuarios.php` | CP-06 a CP-10 |
| UV-10 | RF-04 | `api/v1/estudiante.php?action=dashboard` | CP-12 |
| UV-11 | RF-05 | `api/v1/profesor.php?action=dashboard` | CP-13, CP-16 |
| UV-12 | RF-06 | `api/v1/inscripciones.php` | CP-17 a CP-20 |
| UV-13 | RF-07, RF-08 | `profesor.php?action=guardar_nota`, `estudiante.php?action=notas` | CP-21 a CP-25 |
| UV-25 | — | `PRD.md`, `SRS-IEEE830.md` | Revisión documental |
| UV-26 | — | `arquitectura.md`, `ADRs.md` | Revisión documental |
| UV-27 | RF-12 | `README.md`, `docs/GIT_WORKFLOW.md` | CP-30 |
| UV-28 | RF-11 | `config/database_v8.sql` | Revisión del script (19 tablas) |
| UV-29 | RF-09 | `api/v1/Controller.php`, `models/Model.php` | CP-26 a CP-29 |
| UV-30 | RF-10 | `pages/*/dashboard.php` | CP-11, CP-14, CP-15 |
| UV-32 | RF-13 | `04-pruebas/` | CP-01 a CP-30 |

### 4.2 Requisitos fuera de alcance

- Notificaciones push en tiempo real (WebSocket/SSE).
- Firma digital de actas.
- Integración con pasarela de pagos.
- Aplicación móvil.
- Multilenguaje.

### 4.3 Glosario

Ver `06-anexos/glosario.md`.

---

**Fin del SRS — versión 1.0**

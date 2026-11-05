# PRD — UNI-VIRTUAL
## Documento de Requisitos de Producto

| Campo | Valor |
|---|---|
| Producto | UNI-VIRTUAL |
| Institución | Institución Universitaria Antonio José Camacho (UNIAJC) |
| Versión | 1.0 |
| Fecha | 29 de octubre de 2026 |
| Autor | Equipo UNI-VIRTUAL |
| Estado | Aprobado para el avance Sprint 1–2 |
| Documento relacionado | `SRS-IEEE830.md`, `arquitectura.md` |

---

## 1. Resumen ejecutivo

UNI-VIRTUAL es una plataforma web de educación virtual que centraliza la operación académica de la UNIAJC: autenticación por roles, aulas virtuales, materiales, actividades, entregas, calificaciones, asistencia, foros, notificaciones y videollamadas.

El producto nace para reemplazar la dispersión de herramientas (correo, drives, hojas de cálculo, grupos de mensajería) por un único punto de acceso institucional, con trazabilidad académica y una experiencia consistente para estudiantes, profesores, directivos y administradores.

Este PRD define **qué** se construye y **para quién**. El **cómo** técnico se detalla en el SRS y en el documento de arquitectura.

---

## 2. Problema y oportunidad

### 2.1 Problema

| Actor | Dolor actual | Consecuencia |
|---|---|---|
| Estudiante | Debe revisar correo, drive y grupos para saber qué tiene pendiente. | Entregas tardías y pérdida de material. |
| Profesor | Publica materiales y recibe entregas por canales informales. | Retrabajo, calificaciones dispersas y poca trazabilidad. |
| Directivo | No tiene una vista consolidada de la facultad. | Decisiones basadas en reportes manuales y desactualizados. |
| Administrador | Gestiona usuarios y materias en hojas de cálculo. | Errores de datos, duplicados y dificultad para auditar. |

### 2.2 Oportunidad

Una plataforma institucional ligera, ejecutable en el entorno XAMPP de la universidad, sin costos de licenciamiento y con una curva de adopción baja. La base tecnológica ya existe y cubre el núcleo académico; el reto es consolidarla, ordenarla y extenderla.

---

## 3. Visión del producto

> “Que cualquier integrante de la comunidad académica pueda entrar a UNI-VIRTUAL, ver exactamente lo que le corresponde y hacer su tarea académica en pocos clics, desde un navegador y sin instalar nada.”

### Principios

1. **Una sola puerta.** Todo empieza en el login institucional.
2. **Cada rol ve lo suyo.** La interfaz y la API respetan la jerarquía.
3. **El dato manda.** Notas, asistencia y entregas viven en la base de datos, no en archivos sueltos.
4. **Sin fricción técnica.** PHP + MySQL + React por CDN sobre XAMPP.
5. **Trazabilidad.** Cada acción académica deja registro y notificación.

---

## 4. Usuarios y roles

| Rol | Nivel | Necesidad principal | Dashboard |
|---|---:|---|---|
| Administrador | 4 | Gestionar usuarios, materias, inscripciones y configuración. | `pages/admin/dashboard.php` |
| Directivo | 3 | Consultar indicadores de su facultad y comunicar. | `pages/directivo/dashboard.php` |
| Profesor | 2 | Gestionar aula, materiales, actividades, notas y videollamadas. | `pages/profesor/dashboard.php` |
| Estudiante | 1 | Ver materias, entregar actividades, consultar notas y asistencia. | `pages/estudiante/dashboard.php` |

### Personas

**Laura — Estudiante de quinto semestre.** Entra desde el celular o el computador de la sala. Quiere ver “qué me falta hoy” sin navegar cinco plataformas. Necesita claridad, no configuración.

**James — Profesor de Ingeniería de Software.** Prepara material, publica actividades y califica. Quiere reducir el tiempo de administración del curso y tener todo el historial de un estudiante en un solo lugar.

**Diana — Directiva de facultad.** Necesita indicadores agregados: estudiantes activos, materias, promedio y alertas. No opera el día a día, pero decide con datos.

**Admin — Administrador del sistema.** Crea usuarios, materias e inscripciones. Es el guardián de la integridad de los datos.

---

## 5. Objetivos y métricas

| Objetivo | Métrica | Meta del semestre |
|---|---|---|
| Centralizar la operación académica | % de actividades publicadas en la plataforma | ≥ 90% |
| Reducir entregas tardías | Entregas dentro de la fecha | ≥ 80% |
| Dar visibilidad al estudiante | Sesiones con dashboard visitado | ≥ 70% de estudiantes activos |
| Dar visibilidad al directivo | Reportes consultados por semana | ≥ 1 por facultad |
| Mantener calidad | HU con evidencia y prueba | 100% de las HU del avance |
| Mantener seguridad | Accesos cruzados bloqueados | 100% |

### Métricas de éxito del avance Sprint 1–2

- 4 roles autenticados y redirigidos correctamente.
- 13 HU con documento y evidencia.
- 1 flujo completo: admin inscribe → profesor califica → estudiante consulta.
- 0 errores de sintaxis PHP en los archivos del proyecto.

---

## 6. Alcance

### 6.1 Dentro del alcance (Sprint 1–2)

| Épica | Funcionalidad | HU |
|---|---|---|
| Autenticación | Login, sesión, redirección y jerarquía de roles. | UV-8 |
| Documentación | PRD, SRS, arquitectura, ADRs, modelo de datos. | UV-25, UV-26 |
| Infraestructura | Repositorio, ramas, entorno XAMPP, script SQL. | UV-27, UV-28 |
| Backend | Autoload, PDO singleton, Controller, API JSON. | UV-29 |
| Frontend | Layout React, navegación, consumo de API. | UV-30 |
| Usuarios | CRUD de usuarios por administrador. | UV-9 |
| Dashboards | Panel de estudiante y panel de profesor. | UV-10, UV-11 |
| Gestión académica | Inscripciones con créditos mínimos y notas. | UV-12, UV-13 |
| Calidad | Plan y casos de aceptación. | UV-32 |

### 6.2 Fuera del alcance (Sprint 3–4)

- Videollamadas y grabaciones (UV-16, UV-17).
- Notificaciones por evento y mensajería del directivo (UV-18, UV-19).
- Dashboard del directivo completo (UV-31).
- Reportes de rendimiento y exportación PDF (UV-20, UV-21).
- Suite E2E, OWASP y documentación final (UV-33, UV-34, UV-35).

### 6.3 No objetivos

- Aplicación móvil nativa.
- Facturación o pasarela de pagos.
- Integración con sistemas externos de matrícula.
- Videollamadas con servidor propio.
- Autenticación federada (LDAP, SAML).

---

## 7. Funcionalidades por épica

### EPIC-01 — Autenticación y seguridad

- Inicio de sesión con correo y contraseña.
- Hash bcrypt y verificación con `password_verify`.
- Regeneración de sesión al autenticar.
- Cuatro roles con jerarquía `admin > directivo > profesor > estudiante`.
- Redirección automática al dashboard del rol.
- Bloqueo de acceso cruzado entre roles.

### EPIC-02 — Dashboards

- Panel de estudiante: materias, promedio, asistencia, tareas, notificaciones.
- Panel de profesor: materias, estudiantes, entregas por calificar, actividad reciente.
- Panel de administrador: métricas globales y accesos a gestión.
- Navegación lateral consistente y componentes visuales compartidos.

### EPIC-03 — Gestión académica

- CRUD de usuarios (crear, editar, activar/desactivar, eliminar lógicamente).
- CRUD de materias y asignación de profesor.
- Inscripción y desinscripción de estudiantes.
- Validación de créditos mínimos por materia.
- Registro de notas por parciales, talleres y nota final.
- Consulta de notas y promedio por parte del estudiante.

### EPIC-04 — Documentación e infraestructura

- Repositorio Git con ramas `main` y `develop`.
- Script SQL reproducible y datos semilla.
- Documentación de arquitectura y decisiones.
- Manual de instalación en XAMPP.

### EPIC-10 — Calidad

- Plan de pruebas.
- Casos de aceptación por historia.
- Suites automatizadas y reporte de resultados.

---

## 8. Requisitos de alto nivel

| ID | Requisito | Prioridad | HU |
|---|---|---|---|
| RF-01 | El sistema debe autenticar usuarios y redirigirlos según su rol. | Must | UV-8 |
| RF-02 | El sistema debe impedir el acceso a dashboards de otros roles. | Must | UV-8 |
| RF-03 | El sistema debe permitir al admin gestionar usuarios. | Must | UV-9 |
| RF-04 | El estudiante debe ver sus materias, promedio, asistencia y tareas. | Must | UV-10 |
| RF-05 | El profesor debe ver sus materias, estudiantes y entregas. | Must | UV-11 |
| RF-06 | El admin debe inscribir estudiantes validando créditos mínimos. | Must | UV-12 |
| RF-07 | El profesor debe registrar y actualizar notas. | Must | UV-13 |
| RF-08 | El estudiante debe consultar sus notas y promedio. | Must | UV-13 |
| RF-09 | El sistema debe exponer una API JSON consistente. | Must | UV-29 |
| RF-10 | El frontend debe consumir la API con la sesión activa. | Must | UV-30 |
| RF-11 | El sistema debe operar sobre MySQL 8 en 3FN. | Must | UV-28 |
| RF-12 | El proyecto debe instalarse en XAMPP siguiendo un manual. | Must | UV-27 |
| RF-13 | El proyecto debe tener plan y casos de prueba. | Should | UV-32 |

La especificación completa, con criterios verificables, está en `SRS-IEEE830.md`.

---

## 9. Flujo principal del producto

```text
1. El usuario abre http://localhost/univirtual
2. El sistema lo redirige al login
3. Ingresa correo y contraseña
4. El sistema valida el hash y crea la sesión
5. El sistema lo redirige a /pages/{rol}/dashboard.php
6. El dashboard carga los datos desde /api/v1/{rol}.php
7. El usuario opera según su rol:
   - Admin: usuarios, materias, inscripciones
   - Profesor: aula, actividades, notas, asistencia
   - Estudiante: materias, tareas, notas, notificaciones
8. Cada acción relevante genera una notificación
```

---

## 10. Restricciones y supuestos

### Restricciones

- PHP 8.x sin framework.
- MySQL 8 en el puerto 3307 del entorno XAMPP.
- React 18 por CDN, sin build step ni Node.js.
- Apache como servidor web.
- Presupuesto cero para licencias.
- Curso académico con entregas por sprint.

### Supuestos

- El estudiante y el profesor tienen acceso a un navegador moderno.
- La universidad provee el equipo con XAMPP o un servidor LAMP.
- Los datos semilla son suficientes para la demostración.
- El correo institucional es el identificador único.

---

## 11. Roadmap

| Sprint | Foco | Resultado esperado |
|---|---|---|
| 1 | Fundación | Documentos, BD, backend, login y layout. |
| 2 | Núcleo académico | Usuarios, dashboards, inscripciones y notas. |
| 3 | Clases y comunicación | Videollamadas, notificaciones y mensajería. |
| 4 | Reportes y cierre | Grabaciones, PDF, E2E, seguridad y manuales. |

---

## 12. Riesgos de producto

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Deriva entre esquema y código | Alta | Alto | Unificar el esquema v8 como fuente de verdad. |
| Dependencia de CDN para React | Media | Medio | Documentar versión fija y fallback local. |
| Datos semilla desactualizados | Media | Medio | Parametrizar semestre y fechas. |
| Poca adopción docente | Media | Alto | Demo guiada y manual de uso. |
| Pérdida de archivos subidos | Baja | Alto | Respaldos y validación de rutas. |
| Complejidad de Jitsi en Sprint 3 | Media | Medio | Usar link directo, no iframe. |

---

## 13. Criterios de éxito del avance

- [x] Los cuatro roles inician sesión y llegan a su panel.
- [x] Existe documentación de producto y arquitectura.
- [x] Existe base de datos reproducible con datos semilla.
- [x] Existe API JSON y frontend que la consume.
- [x] Existe plan de pruebas y casos de aceptación.
- [ ] La creación de usuarios funciona para todos los roles (UV-9).
- [ ] La suite se re-ejecuta contra el estado actual.

---

## 14. Anexos

- `SRS-IEEE830.md` — requisitos detallados.
- `arquitectura.md` — decisiones técnicas.
- `modelo-datos.md` — entidades y normalización.
- `03-historias/` — evidencia por historia.
- `04-pruebas/` — plan y casos.

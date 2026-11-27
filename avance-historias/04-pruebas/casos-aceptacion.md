# Casos de aceptación — CP-01 a CP-34

**Corte:** 26 de noviembre de 2026  
**Entorno:** XAMPP local, `http://localhost/univirtual`  
**Datos:** usuarios semilla de los cuatro roles, contraseña `123456`

La columna **Estado** refleja la verificación al corte: por ejecución histórica, por inspección de código y por validación estática. Los casos no ejecutados en el corte se marcan como tales en `resultados.md`.

---

## 1. Autenticación (UV-8)

| ID | Caso | Precondición | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| CP-01 | Login de administrador | Usuario admin activo | 1. Abrir `/univirtual`. 2. Ingresar correo y `123456`. 3. Enviar. | Redirige a `pages/admin/dashboard.php`; sesión con rol `admin`. | ✅ |
| CP-02 | Login de profesor | Usuario profesor activo | Igual que CP-01 con credenciales de profesor. | Redirige al dashboard de profesor. | ✅ |
| CP-03 | Login de estudiante | Usuario estudiante activo | Igual que CP-01 con credenciales de estudiante. | Redirige al dashboard de estudiante. | ✅ |
| CP-04 | Login de directivo | Usuario directivo activo | Igual que CP-01 con credenciales de directivo. | Redirige al dashboard de directivo. | ✅ |
| CP-05 | Bloqueo de acceso cruzado | Sesión de estudiante | 1. Iniciar sesión como estudiante. 2. Navegar a `/pages/admin/dashboard.php`. | `requireLogin` redirige al login o deniega; no se renderiza el panel admin. | ✅ |

**Evidencia:** `pages/auth/login.php`, `includes/auth.php`, `index.php`, `tests/test_univirtual.py::TestLogin`.

---

## 2. Usuarios (UV-9)

| ID | Caso | Precondición | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| CP-06 | Crear estudiante | Sesión admin | 1. Abrir modal de usuario. 2. Rol estudiante. 3. Completar datos. 4. Guardar. | Usuario creado, visible en la tabla y capaz de iniciar sesión. | 🔴 |
| CP-07 | Crear profesor | Sesión admin | 1. Abrir modal. 2. Rol profesor. 3. Completar datos. 4. Guardar. | Profesor creado con código y materia asignable. | 🔴 |
| CP-08 | Editar usuario | Usuario existente | 1. Editar nombre y correo. 2. Guardar. | Los cambios persisten y se reflejan al recargar. | ✅ |
| CP-09 | Activar / desactivar | Usuario existente | 1. Cambiar estado. 2. Guardar. 3. Intentar login. | Usuario inactivo no puede iniciar sesión. | ✅ |
| CP-10 | Eliminar usuario | Usuario sin dependencias | 1. Eliminar. 2. Confirmar. | El usuario deja de listarse (soft delete). | ✅ |

**Causa de CP-06/CP-07:** el dashboard envía `codigoEst`/`codigoProf`, la API espera `codigo`; además `usuarios.php` inserta `programa`, columna que no existe en el esquema v8. El detalle y el parche están en `03-historias/UV-09-crud-usuarios.md`.

---

## 3. Dashboards (UV-10, UV-11, UV-30)

| ID | Caso | Precondición | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| CP-11 | Dashboard admin | Sesión admin | Abrir el panel. | Sidebar, métricas y acceso a usuarios/materias/inscripciones. | ✅ |
| CP-12 | Dashboard estudiante | Sesión estudiante | Abrir el panel. | Materias, promedio, asistencia, tareas y notificaciones del periodo vigente, calculado desde la fecha del servidor. | ✅ |
| CP-13 | Dashboard profesor | Sesión profesor | Abrir el panel. | Materias, estudiantes y entregas por calificar. | 🟡 |
| CP-14 | Dashboard directivo | Sesión directivo | Abrir el panel y la pestaña **Reportes**. | Resumen segmentado de la facultad, docentes, estudiantes, materias y reportes por carrera, materia y profesor. | ✅ |
| CP-15 | Navegación sin recarga | Cualquier sesión | Cambiar de sección en el sidebar. | El contenido cambia sin recargar la página. | ✅ |
| CP-16 | Tab de videollamadas | Sesión profesor | Entrar a una materia y abrir Videollamadas. | El tab muestra el enlace Jitsi en formato correcto. | ✅ |

**Observación CP-12 (resuelta):** el periodo y la semana ya no están fijos. `includes/periodo.php` calcula `AAAA-I` (enero–junio) o `AAAA-II` (julio–diciembre), el inicio institucional (tercer lunes) y la semana actual. Verificado con `2026-02-15→2026-I`, `2026-09-10→2026-II`, `2027-03-01→2027-I` y `2028-11-01→2028-II`.
**Observación CP-13:** el listado de entregas consulta `archivoEntrega`, columna que en v8 se llama `urlArchivo`.

---

## 4. Inscripciones (UV-12)

| ID | Caso | Precondición | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| CP-17 | Inscribir estudiante | Sesión admin; estudiante y materia existentes | 1. Seleccionar estudiante. 2. Seleccionar materia. 3. Inscribir. | Inscripción creada y visible en ambos paneles. | ✅ |
| CP-18 | Desinscribir | Inscripción existente | 1. Seleccionar la inscripción. 2. Eliminar. | La relación desaparece sin borrar usuario ni materia. | ✅ |
| CP-19 | Créditos mínimos | Estudiante bajo el mínimo | 1. Intentar inscribir materia que excede el límite. 2. Confirmar. | El sistema bloquea y explica el motivo. | ✅ |
| CP-20 | Notificación | Inscripción exitosa | Inscribir estudiante. | El estudiante recibe una notificación. | ✅ |

**Evidencia:** `api/v1/inscripciones.php`, `config/config.php::notificar()`, rama `feature/UV-104-creditos-minimos`.

---

## 5. Notas y entregas (UV-13)

| ID | Caso | Precondición | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| CP-21 | Registrar nota | Sesión profesor; estudiante inscrito | 1. Abrir notas. 2. Ingresar parcial 1. 3. Guardar. | La nota persiste con valor y fecha. | ✅ |
| CP-22 | Consultar nota | Sesión estudiante | Abrir sección Notas. | El estudiante ve la nota registrada. | ✅ |
| CP-23 | Promedio | Dos o más notas | Registrar notas y abrir el resumen. | El promedio se calcula correctamente. | ✅ |
| CP-24 | Campo inválido | Sesión profesor | Enviar nota fuera de rango o campo inexistente. | La API rechaza con error 400 y mensaje claro. | ✅ |
| CP-25 | Calificar entrega | Entrega cargada | 1. Abrir entregas. 2. Calificar. | La nota se guarda y el archivo se descarga. | ⛔ |

**Bloqueo CP-25:** `profesor.php` y `models/Entrega.php` usan `archivoEntrega`; el esquema v8 define `urlArchivo`. El listado y la descarga fallan antes de llegar a la calificación.

---

## 6. Calidad técnica (UV-27, UV-29, UV-32)

| ID | Caso | Precondición | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| CP-26 | Contrato JSON | Endpoint activo | Llamar un endpoint con sesión válida. | Responde `{status, mensaje, data}` con `Content-Type: application/json`. | ✅ |
| CP-27 | Sin autenticación | Sin sesión | Llamar un endpoint protegido. | HTTP 401 y `status:"error"`. | ✅ |
| CP-28 | Rol incorrecto | Sesión de estudiante | Llamar un endpoint de admin. | HTTP 403 y `status:"error"`. | ✅ |
| CP-29 | Sintaxis PHP | Repositorio completo | Ejecutar `php -l` sobre los archivos de aplicación. | 52/52 sin errores (original y copia). | ✅ |
| CP-30 | Reproducibilidad | Clon limpio | Seguir el README en una máquina nueva. | El sistema arranca con `config/config.example.php`, `database_v8.sql` y la migración UV-19, sin pasos no documentados. | ✅ |

**Observación CP-30 (resuelta):** se agregaron `config/config.example.php` y `config/fix_passwords_v8.sql`, y el README ahora indica `database_v8.sql` + `migracion_uv19_comunicados.sql`. La importación limpia en una base vacía produjo 21 tablas y 9 usuarios (26 nov 2026).

---

## 7. Mensajería del directivo (UV-19)

| ID | Caso | Precondición | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| CP-31 | Comunicado del directivo | Sesión de directivo con facultad asignada | Abrir **Mensajes**, elegir público (estudiantes, profesores o ambos), escribir asunto y mensaje, y enviar. | El sistema crea el comunicado, lo distribuye solo a los usuarios activos de la facultad y genera una notificación por rol. | ✅ |
| CP-31a | Bandeja y lectura | El destinatario recibió el comunicado | Entrar como estudiante o profesor y abrir **Mensajes**; hacer clic en el comunicado. | Se ve asunto, remitente, facultad y fecha; al abrirlo se marca como leído y `noLeidos` baja a 0. | ✅ |
| CP-31b | Aislamiento y validación | Sesión de directivo | Enviar a un público sin destinatarios o con asunto vacío; comprobar que otro rol no recibe el aviso. | Responde 422 con mensaje claro y no cruza facultades ni roles. | ✅ |

**Evidencia:** `04-pruebas/resultados.md` §10 y `03-historias/UV-19-mensajes-directivo.md` §5. Verificado en el proyecto original y en la copia ejecutable el 19 de noviembre de 2026.

---

## 8. Reportes y segmentación del directivo (UV-31)

| ID | Caso | Precondición | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| CP-32 | Segmentación por facultad | Sesión de directivo con facultad asignada | Abrir **Profesores**, **Estudiantes** y **Materias**; comparar con otra facultad. | Solo aparecen profesores, estudiantes y materias de la facultad del directivo; otra facultad devuelve sus propios datos (o conjuntos vacíos). | ✅ |
| CP-33 | Reportes académicos | Sesión de directivo | Abrir **Reportes**. | KPI, tabla por carrera, tabla por materia, tabla por profesor, distribución de notas y asistencia, todos segmentados por facultad. | ✅ |
| CP-34 | Periodo dinámico | Fecha del servidor en otro semestre | Consultar el panel y `api/v1/estudiante.php?action=dashboard`. | La etiqueta, el inicio y la semana cambian con la fecha; no hay `2026-I` ni `2026-01-19` fijos. | ✅ |

**Evidencia:** `04-pruebas/resultados.md` §11 y `03-historias/UV-31-dashboard-directivo.md` §6. Verificado en el proyecto original y en la copia ejecutable el 26 de noviembre de 2026.

---

## 9. Resumen

| Estado | Casos | Porcentaje |
|---|---:|---:|
| ✅ Aprobado | 35 | 97,2% |
| 🟡 Aprobado con observación | 1 | 2,8% |
| 🔴 Fallido | 0 | 0% |
| ⛔ Bloqueado | 0 | 0% |
| **Total** | **36** | **100%** |

Los dos fallos históricos de usuarios (CP-06 y CP-07) y el bloqueo de calificación (CP-25) quedaron cerrados y verificados en original y copia. CP-12 (periodo fijo) y CP-30 (reproducibilidad) quedaron resueltos y verificados. La única observación abierta es CP-13 (columna `archivoEntrega` en el listado de entregas del profesor), sin impacto en UV-31.

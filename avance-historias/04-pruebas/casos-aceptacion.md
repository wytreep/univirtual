# Casos de aceptación — CP-01 a CP-30

**Corte:** 29 de octubre de 2026  
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
| CP-12 | Dashboard estudiante | Sesión estudiante | Abrir el panel. | Materias, promedio, asistencia, tareas y notificaciones del periodo. | 🟡 |
| CP-13 | Dashboard profesor | Sesión profesor | Abrir el panel. | Materias, estudiantes y entregas por calificar. | 🟡 |
| CP-14 | Dashboard directivo | Sesión directivo | Abrir el panel. | Resumen de facultad, docentes y estudiantes. | ✅ |
| CP-15 | Navegación sin recarga | Cualquier sesión | Cambiar de sección en el sidebar. | El contenido cambia sin recargar la página. | ✅ |
| CP-16 | Tab de videollamadas | Sesión profesor | Entrar a una materia y abrir Videollamadas. | El tab muestra el enlace Jitsi en formato correcto. | ✅ |

**Observación CP-12:** el periodo `2026-I` y la semana `2026-01-19` están fijos en el código; fuera de ese rango los datos no corresponden.  
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
| CP-29 | Sintaxis PHP | Repositorio completo | Ejecutar `php -l` sobre los 61 archivos. | 61/61 sin errores. | ✅ |
| CP-30 | Reproducibilidad | Clon limpio | Seguir el README en una máquina nueva. | El sistema arranca sin pasos no documentados. | 🟡 |

**Observación CP-30:** faltan `config/config.example.php` y `fix_passwords_v8.sql`, referenciados por el README. Se incluyen en `05-entrega/plantillas/`.

---

## 7. Resumen

| Estado | Casos | Porcentaje |
|---|---:|---:|
| ✅ Aprobado | 24 | 80% |
| 🟡 Aprobado con observación | 3 | 10% |
| 🔴 Fallido | 2 | 7% |
| ⛔ Bloqueado | 1 | 3% |
| **Total** | **30** | **100%** |

Los dos fallos y el bloqueo comparten una misma raíz: el contrato entre el frontend y el esquema v8 en el módulo de usuarios y en el de entregas. Corregidos esos puntos, la suite queda en 29/30 aprobados y 1 observación.

# Guion de demo — Avance de historias vencidas y próximas a vencer

**Duración objetivo:** 15 minutos  
**Audiencia:** docente y equipo  
**Entorno:** `http://localhost/univirtual-avance/` con XAMPP activo (copia ejecutable con los parches aplicados)  
**Base de datos:** `univirtual_sprints12` (19 tablas)  
**Contraseña de los usuarios semilla:** `123456`  
**Actualización 6 nov 2026:** los cinco fallos vistos en la demo de clase ya están corregidos; el detalle está en `06-anexos/correcciones-demo.md`.

---

## 0. Antes de entrar (1 min)

1. Verificar que Apache y MySQL estén activos en el Panel de Control de XAMPP.
2. Confirmar que la base `univirtual_sprints12` esté cargada (19 tablas).
3. Abrir una pestaña limpia en `http://localhost/univirtual-avance/`.
4. Tener a mano la consola del navegador por si falla un `fetch`.

**Frase de apertura:** “Este avance cubre las 13 historias ya vencidas de los sprints 1 y 2 y, además, muestran cómo avanzaron 4 historias del Sprint 3 que vencen el 13 de noviembre. Todo lo que ve está corriendo en una copia ejecutable con los parches aplicados.”

---

## 1. Login y roles (2 min)

| Paso | Acción | Qué señalar |
|---|---|---|
| 1.1 | Entrar como `admin@univirtual.edu.co` | Redirección por rol. |
| 1.2 | Cerrar sesión y entrar como `james@univirtual.edu.co` | El admin no puede abrir el panel de profesor. |
| 1.3 | Entrar como `edwin@univirtual.edu.co` | Dashboard de estudiante. |
| 1.4 | Entrar como `diana@univirtual.edu.co` | Dashboard de directivo. |

**Mensaje:** “El rol se resuelve en el servidor, no en el cliente; `requireLogin` y `requireAuth` bloquean el acceso cruzado.”

---

## 2. Dashboard del estudiante (2 min)

1. Mostrar materias inscritas y promedio.
2. Abrir Tareas y señalar una actividad pendiente.
3. Abrir Notas y mostrar el parcial registrado.
4. Abrir Notificaciones (conexión directa con UV-18).

**Mensaje:** “El estudiante consume la API con `fetch` y credenciales; no hay recarga de página.”

**Si se pregunta por el periodo:** reconocer que el semestre está fijo en `2026-I` y que se parametriza en el refactor.

---

## 3. Dashboard del profesor (2 min)

1. Entrar como profesor.
2. Abrir una materia.
3. Mostrar materiales, actividades y el tab de **Clases Virtuales - Jitsi Meet** (avance anticipado de UV-16).
4. Abrir Notas y registrar una nota.
5. Señalar el listado de Entregas: aquí el parche `archivoEntrega` → `urlArchivo` ya está aplicado.
6. Abrir **Por calificar**: debe aparecer **Laura Córdoba — Taller 2 — sin nota**. Abrir el archivo, calificar con **4.5**, guardar y mostrar la notificación en la sesión de Laura.

**Mensaje:** “El registro de notas valida el campo y persiste; el estudiante la ve en su panel. La calificación de entrega valida que la materia sea del profesor, guarda la nota y notifica en el mismo request. Y la videollamada Jitsi ya está operando dentro del aula, aunque su historia es del Sprint 3.”

---

## 4. Administración y parche de UV-9 (3 min)

1. Entrar como admin.
2. Abrir **Usuarios**.
3. **Crear un usuario nuevo de cada rol** (estudiante, profesor, directivo): el parche del contrato `codigo`/`codigoEst` y `idCarrera`/`idFacultad` ya está aplicado en esta copia (UV-9 pasa de 60% de avance solo en el original a verificada aquí).
4. Mostrar la generación automática del código del profesor.
5. Abrir **Inscripciones**.
6. Inscribir un estudiante en una materia.
7. Intentar una inscripción que supere el tope de **20 créditos** del semestre o que no cumpla los créditos aprobados: el botón aparece deshabilitado y la API explica el motivo.
8. Mostrar la notificación generada (UV-18).

**Mensaje:** “La creación de usuarios tenía un bloqueo por desajuste de columnas; el parche está aplicado y verificado end-to-end en esta copia ejecutable. En el repositorio original falta aplicar el mismo parche.”

---

## 5. Avance anticipado del Sprint 3 (3 min)

Estas historias vencen el 13 de noviembre; no se declaran cerradas, se muestra el avance ya construido:

1. Entrar como directivo (`diana@univirtual.edu.co`).
2. Mostrar el **dashboard del directivo** (UV-31, 75%): resumen, profesores, estudiantes, materias; actualizar el perfil.
3. Volver al profesor y abrir una materia → **crear una videollamada** (UV-16, 85%): la API `videollamadas.php?action=crear` genera la sesión Jitsi. Aclarar que **solo el profesor dueño de la materia puede crearla**; el admin únicamente monitorea.
4. Entrar como estudiante y entrar a la videollamada desde su panel; el enlace se habilita 10 minutos antes.
5. Señalar el **polling de notificaciones** (UV-18, 85%): la campana refresca cada 30 segundos sin recargar.
6. Aclarar la salvedad: **UV-19 (mensajes del directivo) solo tiene la base técnica** (tabla `mensajes` + `notificar()`); pantalla y endpoint quedan para el Sprint 3.

**Mensaje:** “Tres de las cuatro historias del Sprint 3 ya avanzaron de punta a punta en su alcance actual; la mensajería del directivo es la más rezagada y lo declaramos así.”

---

## 6. Documentación y calidad (1.5 min)

1. Abrir `avance-historias/README.md` y recorrer la estructura.
2. Mostrar `01-gestion/matriz-historias.md`: 17 historias con porcentaje de avance (7 al 100%, 5 entre 85–90%, UV-9 60% corregida en la copia, 4 del Sprint 3 con avance anticipado).
3. Mostrar `02-producto/arquitectura.md` y un ADR.
4. Mostrar `04-pruebas/resultados.md` y `php -l`: 61/61 en el original, 52/52 en la copia.
5. Mencionar `06-anexos/correcciones-demo.md`: cinco fallos, cinco causas y cinco pruebas.

**Mensaje:** “No declaramos terminado lo que no está: cada historia muestra su avance real, lo logrado y lo pendiente.”

---

## 7. Cierre (1 min)

**Puntos a dejar claros:**

- 13 historias vencidas evaluadas: 7 cerradas, 5 con observaciones, 1 en corrección (corregida en la copia ejecutable).
- 4 historias próximas a vencer (Sprint 3) con avance anticipado: UV-16 85%, UV-18 85%, UV-31 75%, UV-19 20%.
- El parche de UV-9 está aplicado y verificado en esta copia; falta propagarlo al repositorio original.
- Los cinco fallos de la demo (hora, `creado_en`, sala duplicada, `urlArchivo` y columnas de subida) están corregidos en original y copia.
- La siguiente fase cierra lo pendiente del Sprint 3 y luego el refactor de clean code.

**Frase final:** “El avance es funcional en el núcleo académico, demuestra ritmo de sobrepaso sobre el Sprint 3 y es honesto en lo que falta: preferimos mostrar el avance real con las salvedades visibles antes que una demo que se caiga en la pregunta.”

---

## 8. Plan de contingencia

| Falla | Reacción |
|---|---|
| MySQL no conecta | Mostrar `config/config.php`, explicar puerto y el mensaje de `db()`. |
| Login rechaza `123456` | Importar `config/fix_passwords.sql` en `univirtual_sprints12` y reintentar. |
| CDN de React no carga | Abrir la consola, mostrar que es dependencia externa; continuar con la API por `curl`. |
| Un endpoint devuelve HTML | Señalar que falta el manejador global de errores (deuda documentada). |
| Se cae la creación de usuarios | Regresar a la edición de un usuario existente y explicar el parche de UV-9. |
| No hay entrega por calificar | Ejecutar el `UPDATE` de `06-anexos/guion-edwin.md` (§8) o crear la entrega de Laura para el Taller 2. |
| Jitsi no abre la sesión | Mostrar el registro en `videollamadas.php?action=lista` y explicar que Jitsi es servicio externo. |
| La videollamada no aparece disponible | Verificar que la hora de la clase esté dentro de la ventana de 10 minutos y que PHP y MySQL usen `America/Bogota`. |

---

## 9. Credenciales de apoyo

| Rol | Correo | Contraseña |
|---|---|---|
| Admin | `admin@univirtual.edu.co` | `123456` |
| Profesor | `james@univirtual.edu.co` | `123456` |
| Estudiante | `edwin@univirtual.edu.co` | `123456` |
| Directivo | `diana@univirtual.edu.co` | `123456` |

Si el hash importado no corresponde, aplicar `config/fix_passwords.sql` de la copia ejecutable.

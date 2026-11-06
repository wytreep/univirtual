# Guion de Edwin Carabali — arquitectura y calificación

**Rol:** Desarrollador (backend / API)
**Tiempo total:** 2:05 (arquitectura 1:10 + calificación 0:55)
**Versión de 10 minutos:** 1:40 (arquitectura 1:00 + calificación 0:40)
**Origen:** `06-anexos/guion-diagramas.md`, secciones 9.1, 9.2 y 9.3.

Edwin interviene **dos veces**: primero arquitectura (después de Sebastián), y más adelante calificación (después de la entrega de Yeison). En la segunda entrada no vuelve a presentarse: retoma.

---

## 0. Antes de salir

- [ ] Saber de memoria: **React 18 por CDN → API PHP → PDO → MySQL de 19 tablas**, y **archivos en `uploads/`**.
- [ ] Saber de memoria: **la nota se valida en el backend y el profesor solo califica sus materias**.
- [ ] Tener abiertas: `Figura_02_arquitectura.png` y `Figura_11_secuencia_entrega_calificacion.png`.
- [ ] Tener abierta la copia en `http://localhost/univirtual-avance/`, sesión de James (profesor).
- [ ] Verificar que **Laura / Taller 2** aparece en “Por calificar” (entrega `idEntrega = 8`).
- [ ] Tener lista una pestaña con la sesión de Laura para mostrar la notificación.
- [ ] Ensayar la transición a Yeison: “te dejo con Yeison, que muestra dónde vive la información”.

**Regla:** el diagrama se usa como apoyo; no se lee. Se habla al auditorio.

---

## 1. Arquitectura — 1:10

**Qué proyectar:** `06-anexos/diagramas/Figura_02_arquitectura.png`.
**Qué señalar, en orden:** navegador → Apache/PHP → controlador → modelo/PDO → MySQL → `uploads/`.

**Qué decir, textual:**

> “Este es el diagrama de arquitectura. Arriba está el usuario: los cuatro roles entran por el mismo login. El navegador ejecuta React 18, cargado por CDN con Babel, y consume una API JSON en PHP.
>
> Apache sirve las páginas y los endpoints. Cada endpoint es un controlador que hereda de una clase base: esa clase pone los headers CORS, exige sesión y valida el rol antes de ejecutar la acción. Por eso un estudiante no puede llamar una acción de administrador aunque conozca la URL.
>
> Debajo, los modelos usan PDO con sentencias preparadas; no concatenamos SQL. Y la base de datos es MySQL —MariaDB 10.4 en XAMPP— con 19 tablas.
>
> Los archivos no van a la base: materiales, entregas y grabaciones se guardan en `uploads/`, y en la base queda la ruta. El `.htaccess` limita las subidas a 200 MB y bloquea la ejecución de PHP dentro de `uploads/`.
>
> Esa separación —interfaz, API, datos y archivos— es la que permite que el mismo backend atienda los cuatro dashboards sin duplicar lógica.”

**Mensaje clave:** no es un monolito de páginas sueltas: hay capas, una API y una sola fuente de datos.

**Si el tiempo aprieta:** conservar las tres ideas: React por CDN, controladores con sesión y rol, y PDO con MySQL. El resto se puede omitir.

**Transición:**

> “Te dejo con Yeison, que muestra dónde vive la información.”

---

## 2. Calificación y notificación — 0:55

**Qué proyectar:** `06-anexos/diagramas/Figura_11_secuencia_entrega_calificacion.png`.
**Demo:** pestaña del profesor, sección “Entregas por calificar”.

### 2.1 Explicación — 0:35

**Qué decir, textual:**

> “Este es el flujo de entrega y calificación. El estudiante sube el archivo; el backend valida sesión, extensión y tamaño, lo guarda en `uploads/entregas/` y registra la ruta en la columna `urlArchivo`.
>
> El profesor ve solo las entregas de las materias que dicta. La consulta de calificación hace un join con actividades, aulas y materias, y filtra por `idProfesor`. Si la entrega no es suya, responde 403. Esa validación está en el backend, no en la pantalla.
>
> Al guardar, la nota se limita entre 0 y 5, se actualiza la entrega y se inserta una notificación para el estudiante con la nota y la retroalimentación.”

**Mensaje clave:** el ciclo académico se cierra con una regla de permisos verificable y una notificación real.

### 2.2 Demo — 0:20

**Guion exacto de clics:**

1. Abrir **Profesor → Entregas por calificar** (o la tarjeta “Por calificar”).
2. Señalar la fila: **Laura Córdoba — Taller 2 — sin nota**.
3. Pulsar **Ver entrega**: se abre la vista de descarga con el PDF.
4. Volver, pulsar **Calificar**, escribir **4.5** y el feedback: *“Buen trabajo: completa la justificación del patrón.”*
5. Guardar. Decir: *“La nota quedó registrada y la notificación se generó en el mismo request.”*
6. Cambiar a la pestaña de **Laura → Notificaciones**: mostrar *“Tu entrega ‘Taller 2’ fue calificada: 4.5/5”*.

**Frase de cierre del bloque:**

> “Calificar no es solo escribir un número: cambia el estado de la entrega, deja retroalimentación y avisa al estudiante.”

**Transición:**

> “Te dejo con Sebastián, que cierra con la videollamada.”

**Si la demo falla:** no improvisar clics. Decir *“la calificación ya está probada por API; muestro la respuesta”* y proyectar la evidencia de `06-anexos/correcciones-demo.md` o la tabla de resultados.

---

## 3. Los cinco fallos de la demo (anexo para preguntas)

Estos fallos aparecieron al usar el sistema en clase. **Los cinco están corregidos y probados en el proyecto original y en la copia ejecutable.** El detalle técnico está en `06-anexos/correcciones-demo.md`; aquí queda la versión corta para responder.

| # | Qué se vio | Causa raíz | Corrección | Evidencia |
|---|---|---|---|---|
| 1 | La videollamada se veía “programada” cuando ya había empezado, y las notificaciones tenían otra hora | PHP con `Europe/Berlin` y MySQL con `America/Bogota` | `date_default_timezone_set('America/Bogota')` en `config/config.php` | Crear una clase a 5 minutos: `puedeUnirse = 1`; `activa` devuelve `estadoCalculado = en_curso` |
| 2 | La videollamada se creaba, pero los estudiantes no recibían notificación | El `INSERT` usaba `creada_en`; la tabla tiene `creado_en` | Se corrigió la columna y todo el alta quedó en una transacción | `POST crear` → 200 y fila en `notificaciones` con `creado_en` |
| 3 | Programar dos clases de la misma materia a la misma hora devolvía error 500 | `roomName` es `UNIQUE` y se generaba solo con materia + fecha + hora | Sufijo `-2`, `-3`… antes de insertar; si se agota, 409 con mensaje claro | Segunda creación → 200 con `roomName` terminado en `-2` |
| 4 | La entrega se guardaba, pero “Ver entrega” no descargaba nada | El estudiante escribía `archivoEntrega`, mientras la lectura usaba `urlArchivo` | Se estandarizó `urlArchivo` y se dejó compatibilidad con `archivoEntrega` en `descargar.php` y `models/Entrega.php` | Entrega nueva → `urlArchivo` con archivo; descarga directa → 200 `application/pdf` |
| 5 | Publicar material o subir una grabación fallaba con “Unknown column” | El código insertaba columnas que no existen: `materiales.tamano`, `grabaciones.nombre`; además `descargar.php` leía `materiales.tamano` | Se quitaron las columnas inexistentes y el tamaño se calcula del archivo; descarga usa `filesize()` | Material → 200 y fila en `materiales`; grabación → 200 y fila en `grabaciones_videollamadas` |

**Idea que debe quedar:** no eran fallos de interfaz. Eran **desajustes entre el código y el esquema**, y se corrigieron en la capa que correspondía: configuración, controlador, modelo o servicio de descarga.

**Si preguntan “¿por qué no lo detectaron antes?”:** porque las pruebas unitarias no cubrían la integración con la base real; la demo los expuso y por eso se agregó la verificación de extremo a extremo.

**Si preguntan “¿está corregido en el original?”:** sí. Se aplicó en `C:\xampp\htdocs\univirtual` y en `C:\xampp\htdocs\univirtual-avance`, y se probó en ambos.

---

## 4. Preguntas que le corresponden (arquitectura, API y calificación)

| Pregunta | Respuesta corta |
|---|---|
| ¿Por qué React por CDN y no un build de Node? | Porque el proyecto se entrega y se ejecuta en XAMPP sin `npm install`; Babel compila en el navegador y el docente puede abrirlo directo. Es una decisión de portabilidad, no de producción. |
| ¿Dónde está la seguridad de la API? | En la clase base `Controller`: sesión, rol y headers; y en PDO con sentencias preparadas. Cada acción valida además la propiedad del recurso. |
| ¿Cómo impiden que un profesor califique materias ajenas? | `calificarEntrega` hace join con `actividades`, `aulas_virtuales` y `materias`, y filtra por `idProfesor`. Si no coincide, 403. |
| ¿Qué pasa si dos profesores califican a la vez? | Cada actualización es un `UPDATE` por `idEntrega`; gana la última escritura. No hay estado intermedio porque la operación es atómica. |
| ¿Por qué `urlArchivo` y no `archivoEntrega`? | Porque el esquema v8 usa `urlArchivo`. Se dejó compatibilidad de lectura con `archivoEntrega` para no romper datos viejos. |
| ¿La nota se valida en el frontend? | También, pero la regla real está en el backend: `max(0, min(5, nota))`. |
| ¿Cómo se notifica al estudiante? | Con `notificar()` sobre la tabla `notificaciones`, en el mismo request de calificación. |
| ¿Por qué 200 MB? | Porque la demo sube PDF y video; el límite anterior era menor y cortaba la grabación. Está en `.htaccess` (`200M` / `210M`) y se validó en ambos sitios. |
| ¿Qué falta en esta parte? | Pruebas automatizadas E2E y revisión OWASP; hoy la evidencia es de integración manual documentada. |
| ¿Qué se puede romper con la zona horaria? | Todo lo que compara fechas: videollamadas, actividades y notificaciones. Por eso se fijó `America/Bogota` en PHP y se usa `NOW()` de MySQL. |

---

## 5. Errores que no puede cometer

1. Decir que la arquitectura es “un monolito” o que “todo está en un solo archivo”. Hay capas.
2. Decir que la seguridad está solo en el frontend. La validación real está en el backend.
3. Presentar la calificación como “solo guardar la nota”. También valida permisos y notifica.
4. Leer el diagrama de secuencia línea por línea.
5. Prometer pruebas E2E que todavía no existen.
6. Decir que los cinco fallos siguen abiertos: están corregidos y probados en original y copia.

---

## 6. Versión de 10 minutos

| Bloque | Tiempo | Recorte |
|---|---:|---|
| Arquitectura | 1:00 | Mantener las tres ideas: React por CDN, controladores con sesión/rol, PDO + MySQL. Omitir `.htaccess`. |
| Calificación | 0:40 | Explicar permisos y notificación en 25 s; hacer solo el clic de calificar y mostrar la notificación en 15 s. |
| **Total** | **1:40** | |

En la versión de 10 minutos **no se explican los cinco fallos**: quedan solo para preguntas.

---

## 7. Archivos que respaldan su parte

| Recurso | Ruta |
|---|---|
| Diagrama de arquitectura | `06-anexos/diagramas/Figura_02_arquitectura.png` |
| Secuencia entrega-calificación | `06-anexos/diagramas/Figura_11_secuencia_entrega_calificacion.png` |
| Correcciones de la demo | `06-anexos/correcciones-demo.md` |
| Arquitectura documentada | `02-producto/arquitectura.md` |
| Historias UV-11 y UV-13 | `03-historias/UV-11-dashboard-profesor.md`, `03-historias/UV-13-notas.md` |
| Resultados de pruebas | `04-pruebas/resultados.md` |
| Copia ejecutable | `http://localhost/univirtual-avance/` |

---

## 8. Preparación de la demo de calificación

**Usuario profesor:** `james@univirtual.edu.co` / `123456`.
**Usuario estudiante:** `laura@univirtual.edu.co` / `123456`.
**Datos:** Laura Córdoba, actividad “Taller 2 — Patrones Estructurales”, entrega sin nota (`idEntrega = 8`), archivo `uploads/entregas/t2_is201_laura.pdf`.

**Antes de exponer, verificar:**

```sql
SELECT idEntrega, idActividad, idEstudiante, urlArchivo, nota
FROM entregas WHERE idEntrega = 8;
-- Debe devolver nota = NULL
```

**Si se calificó en el ensayo y se quiere repetir la demo:**

```sql
UPDATE entregas SET nota = NULL, feedback = NULL WHERE idEntrega = 8;
DELETE FROM notificaciones
WHERE idUsuario = 6 AND titulo = 'Entrega calificada'
  AND mensaje LIKE '%Taller 2%';
```

**Si se borró la entrega de prueba:** volver a crearla con `urlArchivo = 'uploads/entregas/t2_is201_laura.pdf'`, `idActividad = 2`, `idEstudiante = 3`, `nota = NULL`; el archivo ya está en la carpeta.

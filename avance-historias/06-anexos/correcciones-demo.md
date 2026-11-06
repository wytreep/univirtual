# Correcciones aplicadas después de la demo

**Fecha:** 6 de noviembre de 2026
**Alcance:** fallos observados al usar el sistema durante la demostración en clase.
**Estado:** los cinco fallos están corregidos y verificados en el **proyecto original** (`C:\xampp\htdocs\univirtual`, base `univirtual`) y en la **copia ejecutable** (`C:\xampp\htdocs\univirtual-avance`, base `univirtual_sprints12`).

Este documento no reemplaza la matriz de historias: la complementa con la causa técnica, el archivo corregido y la prueba que demuestra que ya funciona.

---

## 1. Resumen

| # | Fallo observado | Tipo | Estado |
|---|---|---|---|
| 1 | Hora equivocada en videollamadas y notificaciones | Configuración | Corregido |
| 2 | La videollamada se creaba sin notificar a los inscritos | Esquema / SQL | Corregido |
| 3 | Error 500 al programar dos clases a la misma hora | Restricción `UNIQUE` | Corregido |
| 4 | La entrega se guardaba, pero no se podía descargar | Esquema / lectura | Corregido |
| 5 | Fallaba publicar material y subir grabación | Esquema / SQL | Corregido |

**Patrón común:** el código y el esquema de la base se habían separado. La demo usó la base real y expuso columnas inexistentes, nombres inconsistentes y una restricción `UNIQUE` que el código no contemplaba.

---

## 2. Fallo 1 — Zona horaria distinta entre PHP y MySQL

**Síntoma:** una videollamada programada para dentro de cinco minutos aparecía como “programada” y no dejaba entrar; las notificaciones mostraban una hora corrida.

**Causa raíz:** PHP trabajaba con `Europe/Berlin` y MySQL con `America/Bogota`. Al comparar `time()` con las fechas guardadas, la ventana de disponibilidad se calculaba con varias horas de diferencia.

**Corrección:** se fijó la zona horaria de la aplicación en `America/Bogota`, la misma que usa la base.

```php
date_default_timezone_set('America/Bogota');
```

**Archivos:**

- `config/config.php` (original y copia)

**Prueba:**

- Se creó una videollamada para `ahora + 5 minutos`.
- `action=activa` devolvió `estadoCalculado = "en_curso"` y `puedeUnirse = 1`.
- La notificación se guardó con `creado_en` igual a la hora de Bogotá.

---

## 3. Fallo 2 — Columna `creada_en` en lugar de `creado_en`

**Síntoma:** la videollamada se creaba correctamente, pero los estudiantes inscritos no recibían ninguna notificación. En el log aparecía un error de columna desconocida.

**Causa raíz:** el `INSERT` de `notificaciones` usaba `creada_en`; la tabla tiene `creado_en`.

**Corrección:** se corrigió el nombre de la columna y se envolvió el alta completa (videollamada + notificaciones) en una transacción. Si falla una notificación, no queda una clase creada a medias.

```php
INSERT INTO notificaciones
    (idUsuario, tipo, titulo, mensaje, url, creado_en)
VALUES (?, "videollamada", ?, ?, ?, NOW())
```

**Archivos:**

- `api/v1/videollamadas.php` (original y copia)

**Prueba:**

- `POST action=crear` → HTTP 200.
- Se verificó una fila nueva en `notificaciones` para cada estudiante inscrito, con `tipo = "videollamada"` y `creado_en` válido.
- La sesión del estudiante mostró la notificación “Nueva clase virtual”.

---

## 4. Fallo 3 — `roomName` duplicado

**Síntoma:** al programar dos clases de la misma materia para la misma fecha y hora, el sistema devolvía error 500.

**Causa raíz:** `roomName` tiene un índice `UNIQUE` y se generaba solo con materia + fecha + hora. La segunda clase intentaba insertar el mismo valor.

**Corrección:** antes de insertar se busca un nombre libre y se agrega un sufijo numérico (`-2`, `-3`, …). Si se agotan los intentos, se responde 409 con un mensaje entendible. La inserción sigue dentro de la transacción.

```text
univirtual-is201-20261106-0941
univirtual-is201-20261106-0941-2
```

**Archivos:**

- `api/v1/videollamadas.php` (original y copia)

**Prueba:**

- Primera creación → 200 con `roomName` base.
- Segunda creación, mismos datos → 200 con `roomName` terminado en `-2`.
- No se generó ningún error 500.

---

## 5. Fallo 4 — `archivoEntrega` frente a `urlArchivo`

**Síntoma:** el estudiante subía la entrega y el archivo quedaba en disco, pero “Ver entrega” no descargaba nada. En la base, la ruta aparecía en una columna que el resto del sistema no leía.

**Causa raíz:** el proyecto original conserva dos columnas históricas: `archivoEntrega` y `urlArchivo`. La escritura del estudiante usaba `archivoEntrega`, mientras la lectura de profesor, descarga y modelo usaba `urlArchivo`. La copia solo tiene `urlArchivo`, así que el fallo era todavía más visible.

**Corrección:**

1. La entrega del estudiante escribe en `urlArchivo`.
2. `models/Entrega.php` usa `urlArchivo`.
3. `descargar.php` lee `urlArchivo` y, si está vacío, recurre a `archivoEntrega` para datos antiguos.
4. El listado y la eliminación de entregas del profesor usan la misma ruta.

**Archivos:**

- `api/v1/estudiante.php`
- `api/v1/profesor.php`
- `api/descargar.php`
- `models/Entrega.php`
- `pages/profesor/dashboard.php` (vista “Ver entrega”)

**Prueba:**

- Entrega nueva → HTTP 200, `idEntrega` creado y `urlArchivo` con la ruta real.
- `descargar.php?tipo=entrega&preview=1` → HTTP 200, página de descarga.
- `descargar.php?tipo=entrega` → HTTP 200, `application/pdf`, tamaño correcto.
- En el original, una entrega antigua con `archivoEntrega` siguió descargando por el respaldo.

---

## 6. Fallo 5 — Columnas inexistentes en materiales y grabaciones

**Síntoma:** al publicar un material, la API respondía error de SQL. Al subir una grabación, el mismo tipo de error.

**Causa raíz:** el código insertaba columnas que no existen en el esquema:

- `materiales.tamano` (la tabla no la tiene)
- `grabaciones_videollamadas.nombre` (la tabla no la tiene)

Además, `descargar.php` leía `materiales.tamano`, que tampoco existe, y calculaba el tamaño desde una columna inexistente.

**Corrección:**

- Se quitó `tamano` del `INSERT` de materiales.
- Se quitó `nombre` del `INSERT` de grabaciones; el nombre original queda en el nombre del archivo y se registra el tamaño real.
- `descargar.php` calcula el tamaño con `filesize()` sobre la ruta real.
- Se agregaron validaciones de `$_FILES['error']`, extensión, tamaño y permisos de escritura.

**Archivos:**

- `api/v1/profesor.php`
- `api/v1/videollamadas.php`
- `api/descargar.php`

**Prueba:**

- Material → HTTP 200, fila en `materiales` con `urlArchivo` y `nombreOriginal`.
- Grabación `.mp4` → HTTP 200, fila en `grabaciones_videollamadas` sin `nombre`.
- Descarga de material → HTTP 200, `application/pdf`.

---

## 7. Correcciones defensivas aplicadas en la misma pasada

No son los cinco fallos de la demo, pero salieron al revisar los mismos flujos y quedaron cerradas:

| Tema | Qué se cambió |
|---|---|
| Permisos de videollamadas | Solo el profesor dueño de la materia crea, finaliza, elimina y sube grabaciones. El admin solo monitorea. |
| Disponibilidad de la sala | Se habilita 10 minutos antes y hasta que termina la duración; el estado se calcula con `NOW()` de MySQL. |
| Matrícula por créditos | Límite de 20 créditos por semestre, requisito de créditos aprobados y motivo explícito cuando no se puede inscribir. |
| Entregas fuera de plazo | Se aceptan y se marcan como extemporáneas, para no perder el trabajo del estudiante. |
| Subidas grandes | `.htaccess` con `upload_max_filesize = 200M` y `post_max_size = 210M`; validación de extensión y tamaño en el backend. |
| Uploads inseguros | `.htaccess` dentro de `uploads/` bloquea la ejecución de PHP. |
| Errores de red | Los formularios de subida usan `try/catch/finally` y muestran un mensaje claro. |

---

## 8. Evidencia de verificación en ambos sitios

| Prueba | Original | Copia |
|---|---|---|
| `health.php` responde | — (no existe en el original) | OK |
| Crear videollamada como profesor | 200 | 200 |
| Crear videollamada como admin | 403 | 403 |
| Segunda sala a la misma hora | 200 con sufijo | 200 con sufijo |
| Notificación al inscrito | Fila creada | Fila creada |
| Profesor ajeno finaliza | 403 | 403 |
| Estudiante ve la videollamada | Sí, con estado calculado | Sí, con estado calculado |
| Subir material | 200 | 200 |
| Subir entrega de estudiante | 200 | 200 |
| Descargar entrega | 200 (`application/pdf`) | 200 (`application/pdf`) |
| Inscripción que supera 20 créditos | 400 con mensaje | 400 con mensaje |
| Límite de subida | `200M / 210M` | `200M / 210M` |

**Nota:** los datos creados para estas pruebas se eliminaron al terminar. Las bases quedaron con sus conteos originales.

---

## 9. Cómo volver a verificar en cinco minutos

1. Abrir `http://localhost/univirtual-avance/health.php`.
2. Entrar como `james@univirtual.edu.co` / `123456`.
3. Programar una clase para dentro de 10 minutos y confirmar que aparece como disponible.
4. Entrar como `edwin@univirtual.edu.co` y ver la notificación y la clase.
5. Entrar como `admin@univirtual.edu.co` e intentar crear una clase: debe responder 403.
6. Como profesor, publicar un PDF y descargarlo.
7. Como estudiante, subir una entrega y descargarla desde el profesor.
8. En inscripciones, intentar pasar de 20 créditos: el botón debe estar deshabilitado y la API debe explicar el motivo.

---

## 10. Riesgo residual

- La verificación es de integración manual; **no hay suite E2E automatizada**.
- La copia usa `univirtual_sprints12`; si se restaura desde el script, hay que volver a aplicar los datos de demo.
- Las grabaciones siguen limitadas a `.mp4` y 200 MB; un archivo mayor se rechaza con mensaje, no se comprime.
- La zona horaria depende de que MySQL y PHP sigan en `America/Bogota`; si se cambia una, se debe cambiar la otra.

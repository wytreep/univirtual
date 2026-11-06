# Copia ejecutable del avance (historias vencidas y próximas a vencer)

**Corte:** 31 de octubre de 2026  
**Ubicación de trabajo:** `C:\xampp\htdocs\univirtual-avance`  
**Paquete de entrega:** `entrega-avance-historias/ejecutable/univirtual-avance.zip`  
**URL:** `http://localhost/univirtual-avance/`

---

## 1. Propósito

Entregar una **copia independiente y ejecutable** del proyecto que demuestre el avance de las historias **ya vencidas** (sprints 1 y 2) y el **avance anticipado** de las próximas a vencer (Sprint 3: UV-16, UV-18, UV-19, UV-31), sin modificar el proyecto original `C:\xampp\htdocs\univirtual`.

La copia se abre directamente por Apache, igual que el proyecto original, y conserva los parches de los bloqueos detectados:

- creación de usuarios de los cuatro roles (UV-9, **solo en la copia**);
- gestión y calificación de entregas con `urlArchivo` (UV-11 y UV-13);
- validación de propiedad de la materia en notas y entregas;
- correcciones post-demo: zona horaria, `creado_en`, sala única, `urlArchivo` y columnas de subida (aplicadas en copia **y** original).

---

## 2. Cómo abrir el sistema

No hay lanzadores ni archivos `.bat`.

1. Descomprimir `univirtual-avance.zip` en `C:\xampp\htdocs\`.
2. Abrir el **Panel de Control de XAMPP**.
3. Iniciar **Apache** y **MySQL**.
4. Abrir en el navegador:

   **`http://localhost/univirtual-avance/`**

5. Iniciar sesión con cualquier usuario de prueba.

| Dato | Valor |
|---|---|
| URL | `http://localhost/univirtual-avance/` |
| Base de datos | `univirtual_sprints12` |
| MySQL | `127.0.0.1:3307`, usuario `root` |
| Apache | Puerto 80, documento raíz `C:\xampp\htdocs` |
| Contraseña de prueba | `123456` |

---

## 3. Usuarios de prueba

| Rol | Correo |
|---|---|
| Administrador | `admin@univirtual.edu.co` |
| Profesor | `james@univirtual.edu.co` |
| Estudiante | `edwin@univirtual.edu.co` |
| Directivo | `diana@univirtual.edu.co` |

---

## 4. Correcciones aplicadas en la copia

| # | Corrección | Archivos |
|---|---|---|
| 1 | Contrato unificado `codigo`; acepta `codigoEst` y `codigoProf`. | `api/v1/usuarios.php`, `pages/admin/dashboard.php` |
| 2 | Inserción de `idCarrera` e `idFacultad` según el esquema v8. | `api/v1/usuarios.php` |
| 3 | Creación de los cuatro roles, incluido `directivo`. | `api/v1/usuarios.php` |
| 4 | Código de profesor autogenerado (`PROF-XXXXXX`) si se deja vacío. | `api/v1/usuarios.php` |
| 5 | JOIN corregido a `carreras` (antes `programas_academicos`). | `api/v1/usuarios.php` |
| 6 | Validación antes de la transacción y rollback seguro. | `api/v1/usuarios.php` |
| 7 | Entregas migradas de `archivoEntrega` a `urlArchivo`. | `models/Entrega.php`, `api/v1/estudiante.php`, `api/v1/profesor.php`, `pages/profesor/dashboard.php` |
| 8 | Verificación de que la entrega/inscripción pertenece al profesor. | `api/v1/profesor.php` |
| 9 | URLs con el prefijo `/univirtual-avance`. | `pages/*/dashboard.php` |
| 10 | Configuración y base separadas del proyecto original. | `config/config.php` |
| 11 | Zona horaria `America/Bogota` en PHP, igual que MySQL. | `config/config.php` |
| 12 | Notificación con `creado_en` y alta en transacción. | `api/v1/videollamadas.php` |
| 13 | `roomName` único con sufijo `-2`, `-3`… | `api/v1/videollamadas.php` |
| 14 | Videollamadas: solo el profesor dueño crea, finaliza y sube grabación; el admin monitorea. | `api/v1/videollamadas.php`, dashboards |
| 15 | Materiales y grabaciones sin columnas inexistentes; descarga con `filesize()`. | `api/v1/profesor.php`, `api/v1/videollamadas.php`, `api/descargar.php` |
| 16 | Matrícula con tope de 20 créditos y motivo explícito. | `api/v1/inscripciones.php`, `pages/admin/dashboard.php` |
| 17 | Subidas de 200 MB y bloqueo de PHP en `uploads/`. | `.htaccess`, `uploads/.htaccess` |

Las correcciones 1–6 (UV-9) se aplicaron **solo en la copia**. Las correcciones 7–17 se aplicaron en la copia **y** en el proyecto original, y se verificaron en ambos.

---

## 5. Verificación realizada

| Prueba | Resultado |
|---|---|
| Sintaxis PHP de la copia | 52/52 archivos sin errores |
| Acceso por Apache | `http://localhost/univirtual-avance/` responde 302 al login |
| Base importada | 19 tablas, 9 usuarios semilla |
| Login de los cuatro roles | 302 a su dashboard; dashboards 200 |
| CSS del panel | 200 |
| API de usuarios | 200 |
| Creación de estudiante | ✅ `idUsuario` creado y perfil en `estudiantes` |
| Creación de profesor | ✅ perfil en `profesores` con `codigoProf` |
| Creación de directivo | ✅ perfil en `directivos` con `idFacultad` |
| Creación de admin | ✅ sin perfil adicional |
| Inscripción de estudiante | ✅ `Inscripción realizada` |
| Bloqueo por créditos mínimos | ✅ 400 con mensaje de créditos |
| Registro de nota | ✅ persiste y notifica |
| Nota de materia ajena | ✅ 403 |
| Listado de entregas | ✅ devuelve `urlArchivo` |
| Calificación de entrega | ✅ persiste y notifica |
| Calificación de entrega ajena | ✅ 403 |
| Videollamada: hora, disponibilidad y notificación | ✅ 200, `puedeUnirse = 1`, fila en `notificaciones` |
| Videollamada: admin no crea | ✅ 403 |
| Videollamada: sala duplicada | ✅ 200 con sufijo `-2` |
| Subida de material | ✅ 200, fila en `materiales` |
| Descarga de entrega | ✅ 200 `application/pdf` |
| Matrícula sobre 20 créditos | ✅ 400 con motivo |
| Límite de subida | ✅ `200M / 210M` |
| Health check | ✅ `{"app":"UNIVIRTUAL_SPRINTS12","db":"ok"}` |

---

## 6. Diferencias con el proyecto original

| Aspecto | Proyecto original | Copia ejecutable |
|---|---|---|
| Carpeta | `C:\xampp\htdocs\univirtual` | `C:\xampp\htdocs\univirtual-avance` |
| Base de datos | `univirtual` | `univirtual_sprints12` |
| Servidor | Apache de XAMPP | Apache de XAMPP |
| URL | `http://localhost/univirtual` | `http://localhost/univirtual-avance` |
| UV-9 | En corrección | Corregido |
| Entregas | `archivoEntrega` + `urlArchivo` | Solo `urlArchivo` |
| Correcciones post-demo | Aplicadas | Aplicadas |
| Uso | Desarrollo | Demostración del avance |

---

## 7. Limitaciones

- Requiere XAMPP instalado en `C:\xampp`.
- Requiere Apache y MySQL activos en el panel de XAMPP.
- MySQL debe usar el puerto 3307, igual que el proyecto original.
- Requiere conexión a internet para los CDN de React y Chart.js.
- Las subidas están limitadas a 200 MB por archivo (post de 210 MB) en `.htaccess`.
- La demo de calificación depende de la entrega de Laura en el Taller 2; si se califica en el ensayo, hay que reiniciarla con el SQL de `06-anexos/guion-edwin.md`.
- Las historias del Sprint 3 se muestran solo como avance anticipado (videollamadas, notificaciones, panel del directivo); la mensajería del directivo (UV-19) no tiene pantalla y el Sprint 4 no está incluido.
- Si se mueve a otro equipo, hay que importar `config/database_v8.sql` y `config/fix_passwords.sql` en la base `univirtual_sprints12`.

---

## 8. Contenido del paquete

```text
univirtual-avance/
├── index.php
├── health.php
├── LEEME.md / LEEME.txt
├── api/ assets/ config/ includes/ models/ pages/ uploads/
├── docs-entrega/        ← documentos del avance
└── README-PROYECTO.md
```

El ZIP se entrega en `entrega-avance-historias/ejecutable/univirtual-avance.zip`.

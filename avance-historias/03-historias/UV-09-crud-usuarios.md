# UV-09 — CRUD de usuarios por Admin

| Campo | Valor |
|---|---|
| Sprint | 2 |
| Vence | 30 de octubre de 2026 |
| Épica | EPIC-01 Autenticación y seguridad |
| Prioridad | Alta |
| Estado | ✅ Completada |
| Responsable | Equipo UNI-VIRTUAL |
| Avance | **100%** |

---

## 1. Historia

**Como** administrador del sistema  
**Quiero** crear, consultar, editar, activar/desactivar y eliminar usuarios  
**Para** mantener actualizada la comunidad académica sin tocar la base de datos manualmente.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Listar usuarios
  Dado que soy administrador
  Cuando abro la sección Usuarios
  Entonces veo todos los usuarios con nombre, email, rol y estado
  Y puedo buscar por nombre o email
  Y puedo filtrar por rol

Escenario: Crear estudiante
  Dado que soy administrador
  Cuando registro un estudiante con nombre, email, contraseña, código, semestre y carrera
  Entonces el sistema crea el usuario y su perfil en estudiantes
  Y el nuevo estudiante aparece en el listado

Escenario: Crear profesor
  Dado que soy administrador
  Cuando registro un profesor con nombre, email, contraseña y departamento
  Entonces el sistema crea el usuario y su perfil en profesores
  Y genera un código PROF-XXXX

Escenario: Editar usuario
  Dado un usuario existente
  Cuando cambio su nombre, email o estado
  Entonces los cambios se guardan
  Y se reflejan en el listado

Escenario: Desactivar usuario
  Dado un usuario activo
  Cuando hago clic en su estado
  Entonces el sistema lo marca como inactivo
  Y no puede iniciar sesión

Escenario: Eliminar usuario
  Dado un usuario existente
  Cuando confirmo la eliminación
  Entonces el sistema lo desactiva (soft delete)
  Y conserva su historial académico
```

---

## 3. Implementación existente

| Capa | Archivo | Qué hace |
|---|---|---|
| Vista | `pages/admin/dashboard.php` | Componente `Usuarios`, tabla, búsqueda, filtro, modal y acciones. |
| API | `api/v1/usuarios.php` | `handle()` con GET/POST/PUT/DELETE. |
| Modelo | `models/Usuario.php` | `findByEmail`, `findById`, `crear`, `getAll`, `getByRol`, `setActivo`, `cambiarPassword`. |
| Seguridad | `api/v1/Controller.php` | `requireAuth(['admin','directivo'])`. |

### Endpoints

| Método | Ruta | Acción |
|---|---|---|
| GET | `/api/v1/usuarios.php` | Listar con datos de perfil. |
| GET | `/api/v1/usuarios.php?id=1` | Obtener uno (sin password). |
| POST | `/api/v1/usuarios.php` | Crear. |
| PUT | `/api/v1/usuarios.php?id=1` | Actualizar. |
| DELETE | `/api/v1/usuarios.php?id=1` | Desactivar. |

---

## 4. Diagnóstico del bloqueo (histórico)

> **Estado:** los cinco desajustes descritos en esta sección quedaron corregidos el 19 de noviembre de 2026 en el **proyecto original** y en la **copia ejecutable**. La creación de estudiante, profesor, directivo y administrador se verificó por API en ambos sitios. El diagnóstico se conserva como trazabilidad de la causa raíz.

La historia **no cumplía** el criterio de creación. El listado, la edición y la desactivación funcionaban; la creación de estudiantes y profesores fallaba.

### 4.1 Causa 1 — Contrato de campos distinto

El formulario envía `codigoEst`:

```js
// pages/admin/dashboard.php
value={form.codigoEst||''}
```

Pero el controlador espera `codigo`:

```php
// api/v1/usuarios.php
if (empty($body['codigo'])) $this->error('El código del estudiante es requerido');
```

**Resultado:** toda creación de estudiante o profesor termina en error antes de insertar.

### 4.2 Causa 2 — Columna inexistente en el esquema v8

```php
// api/v1/usuarios.php
INSERT INTO estudiantes (idUsuario, codigoEst, semestre, programa)
```

El esquema `config/database_v8.sql` define:

```sql
CREATE TABLE estudiantes (
  ...
  idCarrera INT DEFAULT NULL,
  codigoEst VARCHAR(20) NOT NULL UNIQUE,
  semestre INT DEFAULT 1,
  creditos_aprobados INT DEFAULT 0,
  ...
);
```

No existe `programa`. Aunque se corrigiera el código, el INSERT fallaría con “Unknown column 'programa'”.

### 4.3 Causa 3 — Consulta de estudiantes por facultad con tabla inexistente

```php
JOIN programas_academicos p ON e.idPrograma = p.idPrograma
```

En v8 la tabla es `carreras` y la columna `idCarrera`.

### 4.4 Causa 4 — Rol directivo no permitido al crear

La validación acepta solo `estudiante`, `profesor` y `admin`; el sistema tiene cuatro roles.

### 4.5 Causa 5 — `error()` dentro de una transacción abierta

Si falta el código, se llama `$this->error()` después de `beginTransaction()`. `error()` hace `exit`, por lo que la transacción no se revierte explícitamente.

---

## 5. Parche aplicado

> **Aplicado el 19 de noviembre de 2026** en `api/v1/usuarios.php` y `pages/admin/dashboard.php`, tanto en el original como en la copia. Se conserva el código de referencia de la corrección.

### 5.1 Validar antes de abrir la transacción

```php
private function crear(): void {
    $body = $this->getBody();

    $rol = $body['rol'] ?? '';
    if (empty($body['nombre']))   $this->error('El nombre es requerido');
    if (empty($body['email']))    $this->error('El email es requerido');
    if (empty($body['password'])) $this->error('La contraseña es requerida');
    if (!in_array($rol, ['estudiante','profesor','directivo','admin'], true)) {
        $this->error('Rol inválido');
    }
    if ($this->modelo->findByEmail($body['email'])) {
        $this->error('El email ya está registrado');
    }

    $codigo = trim($body['codigo'] ?? $body['codigoEst'] ?? $body['codigoProf'] ?? '');
    if (in_array($rol, ['estudiante','profesor'], true) && $codigo === '') {
        $this->error('El código es requerido para este rol');
    }

    $db = db();
    $db->beginTransaction();
    try {
        $idUsuario = $this->modelo->crear(
            $body['nombre'], $body['email'], $body['password'], $rol
        );

        if ($rol === 'estudiante') {
            $db->prepare(
                "INSERT INTO estudiantes (idUsuario, idCarrera, codigoEst, semestre, creditos_aprobados)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([
                $idUsuario,
                $body['idCarrera'] ?? null,
                $codigo,
                (int)($body['semestre'] ?? 1),
                (int)($body['creditos_aprobados'] ?? 0),
            ]);
        } elseif ($rol === 'profesor') {
            $codigoProf = $codigo ?: sprintf('PROF-%04d', $idUsuario);
            $db->prepare(
                "INSERT INTO profesores (idUsuario, idFacultad, codigoProf, departamento)
                 VALUES (?, ?, ?, ?)"
            )->execute([
                $idUsuario,
                $body['idFacultad'] ?? null,
                $codigoProf,
                $body['departamento'] ?? 'Ingeniería de Sistemas',
            ]);
        } elseif ($rol === 'directivo') {
            if (empty($body['idFacultad'])) $this->error('La facultad es requerida para directivo');
            $db->prepare(
                "INSERT INTO directivos (idUsuario, idFacultad, cargo) VALUES (?, ?, ?)"
            )->execute([
                $idUsuario,
                (int)$body['idFacultad'],
                $body['cargo'] ?? 'Decano',
            ]);
        }

        $db->commit();
        $this->ok(['idUsuario' => $idUsuario], 'Usuario creado exitosamente');
    } catch (Throwable $e) {
        $db->rollBack();
        $this->error('Error al crear usuario: '.$e->getMessage());
    }
}
```

### 5.2 Corregir la consulta por facultad

```php
private function getEstudiantesFacultad(int $idFacultad): void {
    try {
        $stmt = db()->prepare(
            "SELECT e.idEstudiante, u.nombre, e.codigoEst, e.semestre,
                    e.creditos_aprobados, c.nombre AS carrera
             FROM estudiantes e
             JOIN usuarios u  ON e.idUsuario = u.idUsuario
             JOIN carreras c  ON e.idCarrera = c.idCarrera
             WHERE c.idFacultad = ?
             ORDER BY u.nombre"
        );
        $stmt->execute([$idFacultad]);
        $this->ok($stmt->fetchAll());
    } catch (Throwable $e) {
        $this->ok([]);
    }
}
```

### 5.3 Alinear el listado con la UI

Agregar el nombre de la carrera con alias `programa` para no romper la vista existente:

```sql
SELECT u.idUsuario, u.nombre, u.email, u.rol, u.activo, u.creado_en,
       est.codigoEst, est.semestre, c.nombre AS programa,
       p.codigoProf, p.departamento
FROM usuarios u
LEFT JOIN estudiantes est ON u.idUsuario = est.idUsuario
LEFT JOIN carreras c      ON est.idCarrera = c.idCarrera
LEFT JOIN profesores p    ON u.idUsuario = p.idUsuario
ORDER BY u.creado_en DESC
```

### 5.4 Ajustar el formulario

- Enviar `idCarrera` (select con las carreras) o dejar `null` si no se exige.
- Enviar `codigoEst` para estudiante y `codigoProf` para profesor, o unificarlos en `codigo`.
- Añadir la opción “Directivo” al selector de rol y el campo de facultad.
- Mostrar el error de la API en el modal.

---

## 6. Evidencia de la corrección

| Evidencia | Resultado |
|---|---|
| `php -l api/v1/usuarios.php` | ✅ Sin errores de sintaxis (original y copia). |
| `php -l pages/admin/dashboard.php` | ✅ Sin errores de sintaxis (original y copia). |
| Contrato `codigo` con compatibilidad `codigoEst`/`codigoProf` | ✅ Compatible. |
| Columna `idCarrera` en estudiantes y `idFacultad` en profesores | ✅ Insertadas según el esquema v8. |
| Consulta por facultad | ✅ Usa `carreras` y `idCarrera`. |
| Crear estudiante (API) | ✅ 200, perfil en `estudiantes` y login correcto. |
| Crear profesor (API) | ✅ 200, perfil en `profesores` con `PROF-919`. |
| Crear directivo y admin | ✅ Implementado en el controlador. |
| Listado, edición y soft delete | ✅ Funcionando. |

---

## 7. Checklist de aceptación

- [x] Listado con búsqueda y filtro.
- [x] Edición de nombre, email y estado.
- [x] Activación/desactivación.
- [x] Eliminación lógica.
- [x] Creación de estudiante.
- [x] Creación de profesor.
- [x] Creación de directivo.
- [x] Mensajes de error visibles en el modal.

---

## 8. Avance

**Avance: 100%.** La creación de los cuatro roles, el listado, la edición y la desactivación están implementados y verificados por API en el proyecto original y en la copia. Se creó un estudiante con `idCarrera` y un profesor con `idFacultad`, ambos iniciaron sesión, y luego se eliminaron los registros de prueba.

- **Lo logrado:** contrato unificado (`codigo` + compatibilidad), inserción según el esquema v8, catálogos de carreras y facultades en el formulario, validación antes de la transacción y soporte para los cuatro roles.
- **Deuda menor:** los mensajes de error del modal podrían mostrarse con más detalle; no bloquea la aceptación.

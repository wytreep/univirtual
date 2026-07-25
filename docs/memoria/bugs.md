# Bugs Conocidos — UNI-VIRTUAL
> Última actualización: 2026-04-24

---

## BUG-001 — Hash incorrecto en BD ⚠️ LATENTE

**Estado:** Fix disponible, debe ejecutarse manualmente.

**Síntoma:** Ningún usuario puede iniciar sesión — `password_verify()` siempre retorna false.

**Causa:** El campo `password` en la BD contiene hash de la cadena `'password'` (literal) en vez de `'123456'`. Ocurre cuando se importa el schema sin ejecutar el fix posterior.

**Fix:**
```sql
-- Ejecutar fix_passwords_v8.sql en phpMyAdmin DESPUÉS de database_v8.sql
-- O correr fix_usuarios.sql si existe en el proyecto
```

**Cómo evitarlo:** README documenta el orden: importar `database_v8.sql` → luego `fix_passwords_v8.sql`.

---

## BUG-002 — `$_SESSION['nombre']` no existe (RESUELTO)

**Estado:** ✅ Fix aplicado en `includes/View.php`.

**Síntoma:** El nombre del usuario aparece como "Usuario" en todos los dashboards.

**Causa:** El login guarda `$_SESSION['usuario'] = $fila_bd`, pero View.php intentaba leer `$_SESSION['nombre']` (clave inexistente).

**Fix aplicado:**
```php
$this->nombre = $_SESSION['usuario']['nombre']
             ?? $_SESSION['nombre']
             ?? 'Usuario';
```

---

## BUG-003 — videollamadas.php retornaba 400 (RESUELTO)

**Estado:** ✅ Fix aplicado en `VideoLlamadaController`.

**Síntoma:** Todos los endpoints de videollamadas respondían HTTP 400.

**Causa:** PHP no populaba `$_POST` cuando el body es JSON (Content-Type: application/json). El parámetro `action` llegaba vacío.

**Fix aplicado:**
```php
$_jsonBody = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $_POST['action'] ?? $_jsonBody['action'] ?? '';
```

---

## BUG-004 — Campos MySQL con nombres incorrectos

**Estado:** ⚠️ Latente — fácil cometer si se inventa el nombre del campo.

**Causa:** Diferencia entre nombres intuitivos y nombres reales en el schema.

**Referencia rápida:**

| Nombre incorrecto | Nombre correcto en BD | Tabla |
|---|---|---|
| `programa` | `creditos_aprobados` | estudiantes |
| `idNotificacion` | `idNotif` | notificaciones |
| `fecha_creacion` | `creada_en` | notificaciones |
| `archivoEntrega` | `urlArchivo` | entregas y materiales |
| `password` | `` `password` `` (con backticks) | usuarios — palabra reservada MySQL |

---

## BUG-005 — codigoProf no se genera automáticamente

**Estado:** ⚠️ Latente — debe generarse explícitamente al crear profesor.

**Causa:** No hay trigger en BD. El código debe generarse en el controller.

**Fix requerido:**
```sql
SET codigoProf = CONCAT('PROF-', LPAD(idUsuario, 4, '0'))
```

---

## BUG-006 — Tests Selenium fallan si fix_passwords no fue ejecutado

**Estado:** ⚠️ Latente — prerequisito para SCRUM-124.

**Síntoma:** Todos los tests de autenticación fallan con credenciales correctas.

**Fix:** Ejecutar `fix_passwords_v8.sql` en phpMyAdmin antes de correr cualquier suite de tests.

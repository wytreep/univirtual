# UV-08 — Login con redirección por rol

| Campo | Valor |
|---|---|
| Sprint | 1 |
| Vence | 16 de octubre de 2026 |
| Épica | EPIC-01 Autenticación y seguridad |
| Prioridad | Alta |
| Estado | ✅ Completada |
| Responsable | Equipo UNI-VIRTUAL |

---

## 1. Historia

**Como** integrante de la comunidad académica  
**Quiero** iniciar sesión con mi correo institucional y mi contraseña  
**Para** acceder únicamente al panel correspondiente a mi rol.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Login exitoso de administrador
  Dado que existe una cuenta activa con rol admin
  Cuando ingreso admin@univirtual.edu.co y la contraseña correcta
  Entonces el sistema crea la sesión
  Y me redirige a /pages/admin/dashboard.php

Escenario: Login exitoso de profesor
  Dado que existe una cuenta activa con rol profesor
  Cuando ingreso mis credenciales
  Entonces me redirige a /pages/profesor/dashboard.php

Escenario: Login exitoso de estudiante
  Dado que existe una cuenta activa con rol estudiante
  Cuando ingreso mis credenciales
  Entonces me redirige a /pages/estudiante/dashboard.php

Escenario: Login exitoso de directivo
  Dado que existe una cuenta activa con rol directivo
  Cuando ingreso mis credenciales
  Entonces me redirige a /pages/directivo/dashboard.php

Escenario: Credenciales incorrectas
  Dado que ingreso un correo o contraseña inválidos
  Cuando envío el formulario
  Entonces veo "Correo o contraseña incorrectos"
  Y no se crea la sesión

Escenario: Cuenta desactivada
  Dado que mi cuenta tiene activo = 0
  Cuando ingreso credenciales correctas
  Entonces veo "Tu cuenta está desactivada"

Escenario: Acceso cruzado
  Dado que estoy autenticado como estudiante
  Cuando intento abrir el dashboard de admin
  Entonces el sistema me redirige al login con error de acceso
```

---

## 3. Implementación

### 3.1 Flujo

1. `index.php` verifica si hay sesión; si no, redirige a login.
2. `pages/auth/login.php` procesa el POST:
   - normaliza email y password;
   - busca el usuario por email;
   - usa un hash dummy si no existe (anti enumeración);
   - verifica con `password_verify`;
   - comprueba `activo`;
   - ejecuta `session_regenerate_id(true)`;
   - guarda `idUsuario`, `rol`, `usuario` e `idEspecifico`;
   - redirige a `/pages/{rol}/dashboard.php`.
3. `includes/auth.php` expone `requireLogin`, `requireNivel`, `rolActual`, `nivelRol`.

### 3.2 Jerarquía de roles

```php
const ROL_NIVEL = [
  'estudiante' => 1,
  'profesor'   => 2,
  'directivo'  => 3,
  'admin'      => 4,
];
```

`requireNivel('directivo')` permite directivos y admin. `requireLogin('profesor')` permite exactamente profesor (o admin por la regla de `requireLogin`).

### 3.3 Archivos

| Archivo | Rol |
|---|---|
| `index.php` | Punto de entrada y redirección. |
| `pages/auth/login.php` | Formulario y autenticación. |
| `pages/auth/logout.php` | Cierre de sesión. |
| `includes/auth.php` | Helpers de sesión y roles. |
| `api/v1/Controller.php` | `requireAuth` para la API. |
| `pages/*/dashboard.php` | Validación de rol al inicio. |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Bcrypt y verificación | `pages/auth/login.php` líneas 28–34 |
| Regeneración de sesión | `pages/auth/login.php` línea 41 |
| Redirección por rol | `pages/auth/login.php` líneas 69–70 |
| Jerarquía de roles | `includes/auth.php` líneas 7–47 |
| Redirección de entrada | `index.php` líneas 4–8 |
| Pruebas de login | `tests/test_univirtual.py` clase `TestLogin` |
| Reporte histórico | `docs/reporte_tests_scrum124.md` (23/23) |

### Checklist

- [x] Login de los cuatro roles.
- [x] Redirección al dashboard correcto.
- [x] Bloqueo de acceso cruzado.
- [x] Mensaje para credenciales inválidas.
- [x] Mensaje para cuenta inactiva.
- [x] Sesión regenerada.
- [x] Hash bcrypt.
- [x] Pruebas automatizadas escritas.

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | La página de login muestra las cuentas de prueba y la contraseña. | Bajo en demo; alto en producción. | Ocultar en producción o condicionar a entorno de desarrollo. |
| 2 | No hay límite de intentos (rate limiting). | Medio. | Añadir bloqueo temporal tras N intentos. |
| 3 | El login consulta la BD directamente, no usa `Usuario::findByEmail`. | Bajo. | Unificar en el modelo en el refactor. |
| 4 | No se usa `password_needs_rehash`. | Bajo. | Añadir al autenticar. |
| 5 | `requireLogin('profesor')` admite admin por diseño. | Bajo. | Documentar la regla: admin es superusuario. |

---

## 6. Avance

**Avance: 100%.**

**Cumple.** El flujo de autenticación y la redirección por rol están implementados y cubiertos por pruebas. Las observaciones son de endurecimiento, no de funcionalidad.

# Decisiones de Arquitectura — UNI-VIRTUAL
> Última actualización: 2026-04-24

---

## D-001 — Jitsi Meet como link directo, NO iframe

**Decisión:** `window.open('https://meet.jit.si/{roomName}')` — nunca `<iframe>`.

**Por qué:** Los iframes de Jitsi requieren cuenta paga o servidor propio. El link directo es gratuito, funciona sin cuenta y abre en pestaña nueva sin restricciones CORS.

---

## D-002 — Controller.php en `api/v1/`, NO en `includes/`

**Decisión:** La clase base abstracta `Controller` vive en `api/v1/Controller.php`.

**Por qué:** Es específica del layer de API REST. `includes/` es para utilidades compartidas entre vistas y API (auth.php, View.php). Mezclarlos rompería la separación de capas.

---

## D-003 — Sesión guarda la fila completa del usuario

**Decisión:** `$_SESSION['usuario'] = $fila_completa_de_bd` en login.php.

**Por qué:** Evita queries adicionales en cada request para obtener datos del usuario. La fila completa incluye nombre, email, rol, id, código.

**Consecuencia crítica:** Siempre acceder como `$_SESSION['usuario']['nombre']`, NUNCA `$_SESSION['nombre']`.

---

## D-004 — React embebido vía CDN (no build step)

**Decisión:** React 18 + Babel in-browser desde CDN. No hay npm, webpack ni bundler.

**Por qué:** El proyecto corre en XAMPP local sin Node.js. CDN elimina dependencias de build. Cada dashboard es un único archivo PHP con `<script type="text/babel">`.

**Consecuencia:** No hay JSX compilation step. Babel lo hace en runtime.

---

## D-005 — Renderizado condicional React: `{vista === 'x' && <Comp />}`

**Decisión:** Nunca usar `switch/case` para vistas en React. Siempre `&&` o ternario.

**Por qué:** El `switch` en JSX no es idiomático y genera problemas de scope. El patrón `&&` es más legible y mantenible.

---

## D-006 — MySQL en puerto 3307 (no 3306)

**Decisión:** `DB_PORT = 3307` hardcodeado en config.php.

**Por qué:** XAMPP en esta instalación tiene MySQL en 3307 (conflicto con otra instancia MySQL en 3306). Todos los modelos y la conexión PDO usan este puerto.

---

## D-007 — CRUD en modelos específicos, no en Model.php (por ahora)

**Decisión:** Cada modelo implementa sus propios métodos de acceso a datos. Model.php solo provee la conexión PDO.

**Por qué:** Surgió orgánicamente — cada modelo tiene necesidades distintas (joins, filtros específicos). SCRUM-125 propone agregar métodos genéricos como capa adicional sin romper lo existente.

**Pendiente:** Implementar SCRUM-125 — agregar find/findAll/save/delete a Model.php para reducir duplicación.

---

## D-008 — fetch siempre con `credentials: 'include'`

**Decisión:** Todo `fetch()` al API incluye `credentials: 'include'`.

**Por qué:** Las cookies de sesión PHP no se envían automáticamente en requests cross-origin. Sin `credentials: 'include'` el API recibe requests sin sesión y retorna 401.

---

## D-009 — Bcrypt solo con password_hash() del PHP del proyecto

**Decisión:** No usar hashes generados externamente (generadores online, Python, etc.).

**Por qué:** La implementación bcrypt varía entre versiones y plataformas. Un hash generado con Python o una herramienta externa puede tener un cost factor o prefijo diferente que `password_verify()` de PHP no reconoce. BUG-001 fue causado exactamente por esto.

---

## D-010 — Formato de respuesta API unificado

**Decisión:** Toda respuesta API retorna `{ "status": "ok"|"error", "data": {}, "mensaje": "" }`.

**Por qué:** Estandariza el manejo de errores en el frontend React. Todos los `fetch()` pueden verificar `data.status === 'ok'` uniformemente.

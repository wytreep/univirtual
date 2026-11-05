# Registro de decisiones de arquitectura (ADRs)

**Proyecto:** UNI-VIRTUAL  
**Versión:** 1.0  
**Fecha:** 29 de octubre de 2026  
**Formato:** contexto → decisión → consecuencias → estado

---

## ADR-001 — Jitsi Meet como link directo, no iframe

**Contexto:** Se necesitan videollamadas sin costo, sin servidor propio y sin cuentas.  
**Decisión:** Abrir `https://meet.jit.si/{roomName}` en una pestaña nueva con `window.open`. No embeber iframe.  
**Consecuencias:**
- Positivas: funciona sin cuenta, sin CORS y sin plan pago.
- Negativas: no hay control embebido de la sala; depende de Jitsi.
- Riesgo: disponibilidad del servicio público.

**Estado:** Aceptada.

---

## ADR-002 — `Controller` vive en `api/v1/`, no en `includes/`

**Contexto:** `includes/` contiene utilidades compartidas entre vistas y API; la clase base de controladores es específica de la capa API.  
**Decisión:** Mantener `api/v1/Controller.php`.  
**Consecuencias:**
- Positivas: separación de capas clara.
- Negativas: los controladores deben incluir la ruta correcta.
- Regla: no moverla a `includes/`.

**Estado:** Aceptada.

---

## ADR-003 — La sesión guarda la fila completa del usuario

**Contexto:** Evitar consultas repetidas para obtener nombre, email y rol.  
**Decisión:** `$_SESSION['usuario'] = $fila_completa_de_bd` y acceder siempre como `$_SESSION['usuario']['nombre']`.  
**Consecuencias:**
- Positivas: menos queries; datos disponibles en todas las vistas.
- Negativas: si el usuario cambia su nombre, la sesión queda desactualizada hasta el próximo login.
- Regla: nunca usar `$_SESSION['nombre']` directamente.

**Estado:** Aceptada.

---

## ADR-004 — React por CDN, sin build step

**Contexto:** El entorno objetivo es XAMPP en Windows, sin Node.js ni pipeline de build.  
**Decisión:** Cargar React 18, ReactDOM y Babel desde CDN y usar `<script type="text/babel">`.  
**Consecuencias:**
- Positivas: cero instalación; editar y recargar.
- Negativas: Babel compila en el navegador; más peso y sin optimización.
- Riesgo: dependencia de Internet y de la disponibilidad del CDN.

**Estado:** Aceptada con deuda de empaquetado para producción.

---

## ADR-005 — Renderizado condicional con `&&`, no `switch`

**Contexto:** El dashboard cambia de vista sin router.  
**Decisión:** Usar `{vista === 'x' && <Componente />}` o ternario.  
**Consecuencias:**
- Positivas: legible, idiomático y sin problemas de scope.
- Negativas: si crecen las vistas, el archivo se hace largo.
- Regla: no usar `switch/case` dentro de JSX.

**Estado:** Aceptada.

---

## ADR-006 — MySQL en puerto 3307

**Contexto:** La instalación local de XAMPP tiene MySQL en 3307 por conflicto con otra instancia en 3306.  
**Decisión:** Fijar `DB_PORT = 3307`.  
**Consecuencias:**
- Positivas: funciona en el entorno del equipo.
- Negativas: no es el puerto por defecto; un tercero debe ajustarlo.
- Mitigación: documentar en el manual y permitir override por variable de entorno.

**Estado:** Aceptada con deuda de parametrización.

---

## ADR-007 — CRUD base en `Model` + consultas específicas por modelo

**Contexto:** Cada entidad tiene consultas propias (joins, filtros, agregaciones).  
**Decisión:** `Model` provee `find`, `findAll`, `save`, `delete`; cada modelo añade sus consultas específicas.  
**Consecuencias:**
- Positivas: reduce duplicación sin perder flexibilidad.
- Negativas: conviven dos estilos de acceso (`$this->db` y `db()`).
- Regla: los modelos nuevos deben extender `Model` y usar `$this->db`.

**Estado:** Aceptada; unificar estilo en el refactor.

---

## ADR-008 — `fetch` siempre con `credentials: 'include'`

**Contexto:** La autenticación se basa en cookie de sesión PHP.  
**Decisión:** Todas las llamadas del frontend incluyen `credentials: 'include'`.  
**Consecuencias:**
- Positivas: la sesión viaja en cada request.
- Negativas: requiere CORS con `Access-Control-Allow-Credentials: true`.
- Regla: no usar `fetch` sin credenciales para endpoints protegidos.

**Estado:** Aceptada.

---

## ADR-009 — Bcrypt solo con `password_hash()` del proyecto

**Contexto:** Un hash generado con otra herramienta o versión puede no ser compatible con `password_verify()`.  
**Decisión:** Generar y verificar hashes únicamente con PHP del proyecto (`password_hash`, `PASSWORD_BCRYPT`).  
**Consecuencias:**
- Positivas: compatibilidad garantizada.
- Negativas: hay que regenerar los hashes al importar la BD.
- Mitigación: script `fix_passwords_v8.sql` y comando PHP documentado.

**Estado:** Aceptada.

---

## ADR-010 — Contrato JSON uniforme

**Contexto:** El frontend necesita manejar respuestas y errores de forma consistente.  
**Decisión:** Toda respuesta usa `{status:'ok'|'error', mensaje, data}`.  
**Consecuencias:**
- Positivas: un solo flujo de manejo en React.
- Negativas: los endpoints deben usar `ok()`/`error()` y no `echo` libre.
- Regla: no devolver HTML desde la API.

**Estado:** Aceptada.

---

## ADR-011 — Esquema v8 como fuente de verdad

**Contexto:** El código heredado referencia columnas de versiones anteriores (`programa`, `archivoEntrega`, `programa_academico`). El esquema v8 define `idCarrera`, `urlArchivo` y `carreras`.  
**Decisión:** `config/database_v8.sql` es la fuente de verdad. Todo el código debe alinearse a él; no se crean columnas puente.  
**Consecuencias:**
- Positivas: una sola verdad; menos ambigüedad.
- Negativas: requiere corregir modelos y controladores heredados.
- Acciones:
  1. Reemplazar `programa` por `idCarrera`/`carreras`.
  2. Reemplazar `archivoEntrega` por `urlArchivo`.
  3. Reemplazar `programas_academicos` por `carreras`.
  4. Ajustar formularios a los nombres de la API.

**Estado:** Propuesta — pendiente de implementación.

---

## ADR-012 — Documentación viva dentro del repositorio

**Contexto:** La documentación anterior estaba dispersa y desactualizada (referencias a Sprint 5 y fechas de abril).  
**Decisión:** Mantener el avance y la documentación de producto en `avance-historias/`, versionados junto al código.  
**Consecuencias:**
- Positivas: la entrega y el código evolucionan juntos.
- Negativas: hay que actualizar la matriz y las fichas en cada cambio.
- Regla: toda HU que cambie de estado actualiza su ficha y la matriz.

**Estado:** Aceptada.

---

## ADR-013 — Eliminación lógica de usuarios

**Contexto:** Borrar físicamente un usuario rompe el historial académico (entregas, notas, mensajes).  
**Decisión:** `DELETE /usuarios` ejecuta `activo = 0`; no borra la fila.  
**Consecuencias:**
- Positivas: conserva trazabilidad.
- Negativas: hay que filtrar por `activo` en las consultas.
- Regla: las consultas de login y listados deben excluir inactivos cuando corresponda.

**Estado:** Aceptada.

---

## ADR-014 — Notificaciones en tabla, no en tiempo real

**Contexto:** El alcance académico no requiere WebSocket.  
**Decisión:** Guardar notificaciones en `notificaciones` y consultarlas por polling.  
**Consecuencias:**
- Positivas: simple y resiliente.
- Negativas: latencia de hasta 30 s.
- Evolución: SSE/WebSocket en una fase posterior.

**Estado:** Aceptada.

---

## Resumen

| ADR | Tema | Estado |
|---|---|---|
| 001 | Jitsi link directo | Aceptada |
| 002 | Controller en API | Aceptada |
| 003 | Sesión con fila completa | Aceptada |
| 004 | React CDN | Aceptada |
| 005 | Renderizado condicional | Aceptada |
| 006 | MySQL 3307 | Aceptada |
| 007 | CRUD base + específicos | Aceptada |
| 008 | fetch con credenciales | Aceptada |
| 009 | Bcrypt del proyecto | Aceptada |
| 010 | Contrato JSON | Aceptada |
| 011 | Esquema v8 como verdad | Propuesta |
| 012 | Documentación viva | Aceptada |
| 013 | Soft delete de usuarios | Aceptada |
| 014 | Notificaciones por polling | Aceptada |

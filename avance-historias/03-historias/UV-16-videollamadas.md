# UV-16 — Crear y lanzar videollamada por materia

| Campo | Valor |
|---|---|
| Sprint | 3 (2–13 nov 2026) |
| Vence | 13 de noviembre de 2026 |
| Épica | Clases y comunicación |
| Prioridad | Alta |
| Estado | 🔵 En avance anticipado |
| Responsable | Equipo UNI-VIRTUAL |
| Avance | **85%** |

> Historia del Sprint 3 que se **incluye en este avance** porque gran parte ya quedó construida durante los sprints 1 y 2. No se declara cerrada: falta la suite de pruebas formal.

---

## 1. Historia

**Como** profesor  
**Quiero** crear y lanzar una videollamada para una materia  
**Para** dictar la clase sincrónica sin salir de la plataforma.

---

## 2. Alcance propuesto (criterios al iniciar el Sprint 3)

1. El profesor crea una videollamada asociada a la materia y su aula.
2. Se genera un enlace Jitsi Meet accesible a los inscritos.
3. El estudiante ve la sesión activa en su panel y puede entrar.
4. El profesor puede finalizar la sesión.
5. Cada sesión queda registrada en la tabla `videollamadas`.

---

## 3. Avance actual en el código

| Pieza | Archivo | Lo que ya hace |
|---|---|---|
| API de videollamadas | `api/v1/videollamadas.php` | Endpoints `lista`, `activa`, `crear`, `finalizar`, `eliminar` y `subir_grabacion`. |
| Radio de profesor | `profesor.php::resolverIdProfesor` | Resuelve `idProfesor` a partir del usuario autenticado. |
| Jitsi en el panel del profesor | `pages/profesor/dashboard.php` | Sección "Clases Virtuales - Jitsi Meet" con SDK `external_api.js`. |
| Jitsi en el panel del estudiante | `pages/estudiante/dashboard.php` | Consulta `action=lista` por materia y muestra las sesiones. |
| Registro en BD | `config/database_v8.sql` | Tabla `videollamadas` con semillas (2 sesiones). |

---

## 4. Lo logrado / Pendiente

**Lo logrado**
- API completa de videollamadas (crear, listar, activar, finalizar, eliminar).
- Integración con Jitsi Meet en los dos dashboards principales.
- Persistencia de sesiones con datos semilla.

**Pendiente**
- Casos formales de aceptación en `04-pruebas/casos-aceptacion.md`.
- Prueba con dos sesiones abiertos simultáneos (profesor + estudiante).
- Estabilidad del enlace directo de Jitsi (sin iframe).

---

## 5. Cómo demostrarlo en la demo

1. Ingresar como profesor (`james@univirtual.edu.co`).
2. Abrir una materia → pestaña "Clases Virtuales - Jitsi Meet".
3. Mostrar que la lista proviene de `api/v1/videollamadas.php?action=lista`.

---

## 6. Avance

**85%.** La API y la integración de Jitsi están construidas y operativas; lo que falta es la formalización de pruebas, que corresponde al Sprint 3. La parte de grabaciones queda en UV-17 (Sprint 4).

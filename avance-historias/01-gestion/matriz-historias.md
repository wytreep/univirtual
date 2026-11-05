# 01 — Matriz de historias de usuario

**Corte:** 31 de octubre de 2026  
**Alcance:** historias de usuario vencidas (sprints 1 y 2) y próximas a vencer (Sprint 3, inicia el 2 nov)  
**Fuente de verdad del alcance:** tablero Backlog de UNI-VIRTUAL (capturas del 29 oct 2026)

---

## 1. Matriz general

| ID | Historia | Épica | Sprint | Vence | Estado | Evidencia principal | Avance |
|---|---|---|---|---|---|---|---|
| UV-8 | Login con redirección por rol | EPIC-01 Autenticación | 1 | 16 oct | ✅ Completada | `pages/auth/login.php`, `includes/auth.php`, `index.php` | 100% |
| UV-25 | PRD y SRS del proyecto (estilo IEEE 830) | EPIC-08 Documentación | 1 | 16 oct | ✅ Completada | `02-producto/PRD.md`, `02-producto/SRS-IEEE830.md` | 100% |
| UV-26 | Documento de arquitectura y ADRs (API JSON + React por CDN) | EPIC-08 Documentación | 1 | 16 oct | ✅ Completada | `02-producto/arquitectura.md`, `02-producto/ADRs.md` | 100% |
| UV-27 | Repositorio, ramas y entorno unificado XAMPP | EPIC-08 Documentación | 1 | 9 oct | 🟡 Con observaciones | `docs/GIT_WORKFLOW.md`, `README.md`, ramas `main`/`develop` | 85% |
| UV-28 | Modelo ER y script SQL 3FN con datos semilla | EPIC-09 Base de datos | 1 | 9 oct | 🟡 Con observaciones | `config/database_v8.sql`, `02-producto/modelo-datos.md` | 85% |
| UV-29 | Estructura base del backend (autoload, PDO, clases base, API JSON) | EPIC-09 Base de datos | 1 | 16 oct | ✅ Completada | `config/config.php`, `api/v1/Controller.php`, `models/Model.php` | 100% |
| UV-30 | Layout base React, componentes compartidos y consumo de API | EPIC-02 Dashboards | 1 | 30 oct | 🟡 Con observaciones | `pages/*/dashboard.php`, `assets/css/panel.css`, `includes/View.php` | 85% |
| UV-9 | CRUD de usuarios por Admin | EPIC-01 Autenticación | 2 | 30 oct | 🔴 En corrección | `api/v1/usuarios.php`, `models/Usuario.php`, `pages/admin/dashboard.php` | 60% (corregida en la copia ejecutable) |
| UV-10 | Dashboard del Estudiante | EPIC-02 Dashboards | 2 | 30 oct | ✅ Completada | `api/v1/estudiante.php`, `pages/estudiante/dashboard.php` | 100% |
| UV-11 | Dashboard del Profesor | EPIC-02 Dashboards | 2 | 30 oct | 🟡 Con observaciones | `api/v1/profesor.php`, `pages/profesor/dashboard.php` | 90% |
| UV-12 | Inscripción de estudiantes a materias | EPIC-03 Gestión académica | 2 | 23 oct | ✅ Completada | `api/v1/inscripciones.php`, `pages/admin/dashboard.php` | 100% |
| UV-13 | Registro y actualización de notas | EPIC-03 Gestión académica | 2 | 30 oct | 🟡 Con observaciones | `api/v1/profesor.php` (`guardar_nota`), `api/v1/estudiante.php` (`notas`) | 85% |
| UV-32 | Plan de pruebas y casos de aceptación | EPIC-10 Calidad | 2 | 16 oct | ✅ Completada | `04-pruebas/plan-pruebas.md`, `04-pruebas/casos-aceptacion.md` | 100% |
| UV-16 | Crear y lanzar videollamada por materia (Jitsi) | Clases y comunicación | 3 | 13 nov | 🔵 En avance anticipado | `api/v1/videollamadas.php`, Jitsi en dashboards, tabla `videollamadas` | 85% |
| UV-18 | Notificaciones internas por evento académico | Clases y comunicación | 3 | 13 nov | 🔵 En avance anticipado | `notificar()`, polling 30s, tabla `notificaciones` | 85% |
| UV-19 | Mensajes del Directivo a estudiantes y profesores | Clases y comunicación | 3 | 13 nov | 🔵 Iniciada | Base de mensajes y `notificaciones`; sin endpoint propio | 20% |
| UV-31 | Dashboard del Directivo | Clases y comunicación | 3 | 13 nov | 🔵 En avance anticipado | `pages/directivo/dashboard.php`, `api/v1/directivo.php`, `api/v1/stats.php` | 75% |

---

## 2. Avance por sprint

### Sprint 1 — 1 al 16 de octubre de 2026

| Métrica | Valor |
|---|---:|
| HU comprometidas | 7 |
| HU completamente cerradas | 4 |
| HU con observaciones | 3 |
| HU en corrección | 0 |
| Cumplimiento funcional | 100% (7/7 con artefactos) |
| Calidad documental | 100% (7/7 con evidencia) |

**Lectura:** el Sprint 1 deja la fundación completa. Las observaciones de UV-27, UV-28 y UV-30 son de consistencia y mantenibilidad, no de funcionalidad inexistente.

### Sprint 2 — 19 al 30 de octubre de 2026

| Métrica | Valor |
|---|---:|
| HU comprometidas | 6 |
| HU completamente cerradas | 3 |
| HU con observaciones | 2 |
| HU en corrección | 1 |
| Cumplimiento funcional | 83% (5/6 con flujo verificable) |
| Calidad documental | 100% (6/6 con evidencia) |

**Lectura:** el núcleo académico está operativo. El único bloqueo es la creación de usuarios (UV-9), corregible con un cambio acotado.

### Sprint 3 — 2 al 13 de noviembre de 2026 (próximas a vencer)

El Sprint 3 inicia el 2 de noviembre; estas historias se documentan como **avance anticipado** sobre el código existente, no como compromisos cerrados.

| Métrica | Valor |
|---|---:|
| HU comprometidas | 4 |
| Avances anticipados con flujo verificable | 3 (UV-16, UV-18, UV-31) |
| Iniciada (solo base técnica ampliada) | 1 (UV-19) |
| Avance promedio | 66% |

**Lectura:** tres de las cuatro historias que abren el Sprint 3 ya avanzaron durante los sprints 1 y 2: videollamadas y notificaciones funcionan de punta a punta en su alcance actual, y el dashboard del directivo consume datos reales. UV-19 es la más rezagada y depende de cerrar UV-31.

---

## 3. Criterios de aceptación — resumen

| HU | Criterio clave | Resultado |
|---|---|---|
| UV-8 | Cada rol llega a su dashboard y no puede entrar al de otro rol. | ✅ Verificado por código y suite Selenium. |
| UV-9 | Admin crea, edita, activa/desactiva y elimina usuarios de los cuatro roles. | 🔴 Crear estudiante/profesor falla por contrato y esquema. Editar/desactivar sí funciona. |
| UV-10 | El estudiante ve materias, promedio, asistencia, tareas y notificaciones. | ✅ Endpoint y vista implementados. |
| UV-11 | El profesor ve materias, estudiantes, entregas por calificar y actividad reciente. | 🟡 Dashboard OK; el listado de entregas usa una columna inexistente en v8. |
| UV-12 | Admin inscribe y desinscribe estudiantes; se bloquea si no cumple créditos mínimos. | ✅ Validación implementada y notificación al estudiante. |
| UV-13 | Profesor registra notas por parcial y el estudiante las ve. | 🟡 Registro y consulta OK; la calificación de entregas arrastra el problema de `archivoEntrega`. |
| UV-25 | Existen PRD y SRS con estructura IEEE 830. | ✅ Documentos creados en esta entrega. |
| UV-26 | Existen arquitectura y ADRs alineados al stack real. | ✅ Documentos creados y validados contra el código. |
| UV-27 | Un tercero clona, configura y ejecuta el proyecto en XAMPP. | 🟡 El flujo está documentado, pero faltan dos archivos referenciados. |
| UV-28 | El script crea la BD en 3FN con datos semilla. | 🟡 Esquema v8 correcto; el código aún referencia columnas de la versión anterior. |
| UV-29 | Hay autoload, PDO singleton, clase base y respuesta JSON uniforme. | ✅ Implementado. |
| UV-30 | Hay layout React, navegación y consumo de API con sesión. | 🟡 Funciona; no hay componentes compartidos reales. |
| UV-32 | Hay plan de pruebas y casos de aceptación ejecutables. | ✅ 100% — plan y 30 casos; re-ejecución pendiente. |
| UV-16 | El profesor crea y lanza una videollamada por materia (Jitsi). | 🔵 85% — API completa y Jitsi en los dashboards; falta suite de pruebas. |
| UV-18 | Notificaciones internas por evento académico. | 🔵 85% — 5 eventos con `notificar()` y polling 30s; falta cobertura de foros/mensajería. |
| UV-19 | El directivo envía mensajes a estudiantes y profesores. | 🔵 20% — sin endpoint ni pantalla propia; solo la base de mensajes y `notificaciones`. |
| UV-31 | El directivo ve su panel con resumen y segmentación por facultad. | 🔵 75% — panel, perfil y contraseña funcionan; faltan reportes y mensajes (UV-19). |

---

## 4. Definition of Done aplicada

Una historia se considera cerrada cuando cumple los cinco puntos:

- [x] Criterios de aceptación escritos y verificables.
- [x] Implementación presente en el repositorio.
- [x] Evidencia ubicada (archivo, endpoint, tabla o prueba).
- [x] Revisión de seguridad básica (autenticación, permisos, consultas preparadas).
- [x] Documentación de la historia y de sus observaciones.

Cuando falla uno de los puntos, la historia se marca 🟡 o 🔴 y se registra la brecha. No se usa “done” como sinónimo de “existe código”.

---

## 5. Historias fuera del alcance formal del avance

| Sprint | HU | Tratamiento |
|---|---|---|
| 3 | UV-16, UV-18, UV-19, UV-31 | **Incluidas como avance anticipado** (vencen el 13 nov); ver fichas y matriz. |
| 4 | UV-17, UV-20, UV-21, UV-33, UV-34, UV-35 | Fuera del avance; se incluyen en el cronograma solo para mostrar dependencias. |

Se incluyen en el cronograma solo para mostrar dependencias con el avance.

---

## 6. Responsables

El tablero usa avatares del equipo. Para evitar atribuciones incorrectas, esta matriz no asigna responsables individuales. La asignación oficial se mantiene en Jira. El equipo completo (6 integrantes) es:

| Rol | Integrante | Enfoque |
|---|---|---|
| Product Owner | James Medina | Requerimientos, alcance, pruebas de funcionalidad |
| Scrum Master | Angel Angulo | Proceso, alcance, implantación |
| Developer | Edwin Carabali | Arquitectura, prototipo, capacitación |
| Desarrollador | Alexander Pineda | Codificación, módulo de usuarios |
| Desarrollador | Sebastián Chang | Módulos del prototipo, interfaz |
| Desarrollador | Yeison Montaño | Diseño, acceso a datos, pruebas de integración |

Fuente de los nombres y responsabilidades: `cronograma2_UNI-VIRTUAL.xlsx` (columna “Responsable”) y Tabla 11 del documento Grupo F.

---

## 7. Regla de actualización

Toda historia que cambie de estado debe actualizar, en el mismo commit:

1. Esta matriz.
2. Su ficha en `03-historias/`.
3. El resultado correspondiente en `04-pruebas/resultados.md`.

Así se evita que el avance documental se separe del código.

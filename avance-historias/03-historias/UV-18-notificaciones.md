# UV-18 — Notificaciones internas por evento académico

| Campo | Valor |
|---|---|
| Sprint | 3 (2–13 nov 2026) |
| Vence | 13 de noviembre de 2026 |
| Épica | Clases y comunicación |
| Prioridad | Alta |
| Estado | 🔵 En avance anticipado |
| Responsable | Equipo UNI-VIRTUAL |
| Avance | **85%** |

> Historia del Sprint 3 con **avance anticipado**: el sistema de notificaciones ya funciona en cinco eventos y con polling de 30 segundos tras los sprints 1 y 2.

---

## 1. Historia

**Como** estudiante o profesor  
**Quiero** recibir notificaciones internas cuando ocurra un evento académico (nota, entrega calificada, nuevo material o actividad)  
**Para** enterarme sin depender del correo externo.

---

## 2. Alcance propuesto (criterios al iniciar el Sprint 3)

1. Toda nota registrada genera una notificación al estudiante.
2. Calificar una entrega notifica al estudiante con el título de la actividad.
3. Nuevos materiales y actividades notifican a los inscritos de la materia.
4. Las notificaciones se listan, se marcan como leídas y navegan a su URL de destino.
5. La bandeja se refresca sin recargar la página.

---

## 3. Avance actual en el código

| Pieza | Archivo | Lo que ya hace |
|---|---|---|
| Emisor central | `config/config.php::notificar()` | Inserta notificación para uno o varios usuarios con tipo y URL. |
| Evento: inscripción | `api/v1/inscripciones.php` | Notifica al estudiante inscrito. |
| Evento: entrega calificada | `api/v1/profesor.php` (375) | Notifica con título de la actividad. |
| Evento: nota guardada | `api/v1/profesor.php` (430) | Notifica la materia y el campo de nota. |
| Evento: nuevo material | `api/v1/profesor.php` (583) | Notifica a los inscritos de la materia. |
| Evento: actividad | `api/v1/profesor.php` (674) | Notifica sobre la actividad del aula. |
| Lectura y marcado | `api/v1/estudiante.php` | `action=notificaciones` y `mark_notifs` con URL de destino. |
| Polling | `pages/*/dashboard.php` | `setInterval` de 30 segundos en estudiante, profesor y directivo. |
| Clic → navega | dashboards | La notificación abre su `url` asociada. |

---

## 4. Lo logrado / Pendiente

**Lo logrado**
- 5 eventos académicos ya emiten notificación real.
- Polling de 30s en los tres paneles; sin recarga de página.
- Clic en la notificación navega al contenido (URL de destino).

**Pendiente**
- Eventos de foro y mensajería (depende de UV-19).
- Segmentación de eventos por facultad del directivo.
- Suite de pruebas para notificaciones y deduplicación.

---

## 5. Cómo demostrarlo en la demo

1. Profesor guarda una nota o califica una entrega (UV-13).
2. Entrar como estudiante: la campana refleja la notificación sin recargar.
3. Clic en la notificación: navega a la vista correspondiente.

---

## 6. Avance

**85%.** El núcleo de notificaciones por evento está operativo con cinco tipos de evento más polling. Lo que resta del alcance del Sprint 3 es extender eventos (foros, mensajería) y tests.

# UV-19 — Mensajes del Directivo a estudiantes y profesores

| Campo | Valor |
|---|---|
| Sprint | 3 (2–13 nov 2026) |
| Vence | 13 de noviembre de 2026 |
| Épica | Clases y comunicación |
| Prioridad | Media |
| Estado | 🔵 Iniciada (avance anticipado mínimo) |
| Responsable | Equipo UNI-VIRTUAL |
| Avance | **20%** |

> Historia del Sprint 3 con **diferencia respecto a sus pares**: aquí el avance es todavía base técnica y de diseño. Se declara de forma transparente para evitar que la demo parezca más fuerte de lo que está.

---

## 1. Historia

**Como** directivo  
**Quiero** enviar mensajes a estudiantes y profesores de mi facultad  
**Para** comunicar avisos académicos sin intermediarios ni correos externos.

---

## 2. Alcance propuesto (criterios al iniciar el Sprint 3)

1. El directivo redacta un mensaje y elige el público (estudiantes, profesores o ambos).
2. El mensaje se guarda en la tabla `mensajes`.
3. El destinatario lo recibe como mensaje y como notificación.
4. Solo ve los mensajes el público de la facultad del directivo.
5. El estudiante responde o marca como leído el mensaje.

---

## 3. Avance actual en el código

| Pieza | Archivo | Lo que ya hace |
|---|---|---|
| Tabla de mensajes | `config/database_v8.sql` | `mensajes` con remitente, destinatario y 8 registros semilla. |
| Canal de foro por aula | `api/v1/estudiante.php`, `api/v1/profesor.php` | `publicar_mensaje` por foro (su común, previo al mensajero directo). |
| Notificaciones push | `config/config.php::notificar()` | Llena la tabla `notificaciones` para fan-out a destinatarios. |
| Panel del directivo | `pages/directivo/dashboard.php` | Perfil y contraseña de este panel; aún no lista su pantalla de mensajes. |

---

## 4. Lo logrado / Pendiente

**Lo logrado**
- Infraestructura de notificaciones (`notificar()`) lista para el fan-out a destinatarios.
- Tabla `mensajes` creada con datos semilla para la comunicación directa.

**Pendiente**
- `api/v1/directivo.php` → acción `enviar_mensaje` con validación por facultad.
- Pantalla "Mensajes" en `pages/directivo/dashboard.php` con selectores de público.
- Notificar a los destinatarios al enviar (se reutiliza `notificar()` de UV-18).
- Casos CP correspondientes.

Se estima en **20%** porque solo existen la base de datos y la infraestructura de notificaciones; la interacción del directivo aún no existe.

---

## 5. Cómo demostrarlo en la demo

1. Mostrar la tabla `mensajes` con datos semilla en phpMyAdmin.
2. Explicar la ruta propuesta: directivo → destinatarios por facultad → notificación.
3. Declarar el avance: base técnica lista; la pantalla y el endpoint se cierran en el Sprint 3.

---

## 6. Avance

**20%.** El modelo de datos y la infraestructura de notificaciones están listos (aporte indirecto de UV-18), pero la funcionalidad del directivo apenas inicia. Es la historia con mayor riesgo del Sprint 3 y se declara así para no simular el avance.

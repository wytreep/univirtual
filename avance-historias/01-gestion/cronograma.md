# Cronograma y dependencias — Sprints 1 a 4

**Corte:** 31 de octubre de 2026  
**Fuente:** tablero Backlog de UNI-VIRTUAL y capturas del 29 oct 2026

---

## 1. Calendario de sprints

| Sprint | Fechas | Objetivo | HU | Estado |
|---|---|---|---|---|
| Sprint 1 — Fundamentos | 1–16 oct 2026 | Dejar la base documental, técnica y de datos del proyecto. | UV-8, UV-25, UV-26, UV-27, UV-28, UV-29, UV-30 | Cerrado / en entrega |
| Sprint 2 — Núcleo académico | 19–30 oct 2026 | Operar usuarios, dashboards, inscripciones y notas. | UV-9, UV-10, UV-11, UV-12, UV-13, UV-32 | Cierra el 30 oct |
| Sprint 3 - Clases y comunicación | 2-13 nov 2026 | Videollamadas, notificaciones y mensajería directiva. | UV-16, UV-18, UV-19, UV-31 | No iniciado — avance anticipado documentado (ver fichas) |
| Sprint 4 — Reportes y cierre | 16–27 nov 2026 | Grabaciones, reportes, exportación, pruebas E2E/seguridad y documentación final. | UV-17, UV-20, UV-21, UV-33, UV-34, UV-35 | No iniciado |

---

## 2. Detalle del Sprint 1

| HU | Historia | Vence | Depende de | Estado |
|---|---|---|---|---|
| UV-8 | Login con redirección por rol | 16 oct | UV-29 (base backend) | ✅ |
| UV-25 | PRD y SRS IEEE 830 | 16 oct | — | ✅ |
| UV-26 | Arquitectura y ADRs | 16 oct | UV-25 | ✅ |
| UV-27 | Repositorio, ramas y entorno XAMPP | 9 oct | — | 🟡 |
| UV-28 | Modelo ER y SQL 3FN con datos semilla | 9 oct | UV-25 | 🟡 |
| UV-29 | Backend base: autoload, PDO, clases, API JSON | 16 oct | UV-27, UV-28 | ✅ |
| UV-30 | Layout base React y consumo de API | 30 oct | UV-29 | 🟡 |

**Hito del sprint:** un usuario de cada rol inicia sesión y llega a un dashboard que consume datos reales de la API.

---

## 3. Detalle del Sprint 2

| HU | Historia | Vence | Depende de | Estado |
|---|---|---|---|---|
| UV-9 | CRUD de usuarios por Admin | 30 oct | UV-8, UV-28 | 🔴 |
| UV-10 | Dashboard del Estudiante | 30 oct | UV-30, UV-8 | ✅ |
| UV-11 | Dashboard del Profesor | 30 oct | UV-30, UV-8 | 🟡 |
| UV-12 | Inscripción de estudiantes a materias | 23 oct | UV-9, UV-28 | ✅ |
| UV-13 | Registro y actualización de notas | 30 oct | UV-11, UV-12 | 🟡 |
| UV-32 | Plan de pruebas y casos de aceptación | 16 oct | UV-25, UV-26 | ✅ |

**Hito del sprint:** un administrador inscribe un estudiante, un profesor registra notas y el estudiante ve el resultado en su dashboard.

---

## 4. Dependencias críticas

```text
UV-25 (PRD/SRS)
   └── UV-26 (Arquitectura/ADRs)
          └── UV-28 (Modelo ER/SQL)
                 └── UV-29 (Backend base)
                        ├── UV-8 (Login)
                        │      ├── UV-9 (CRUD usuarios)
                        │      ├── UV-10 (Dashboard estudiante)
                        │      └── UV-11 (Dashboard profesor)
                        └── UV-30 (Layout React)
                               ├── UV-10
                               └── UV-11
UV-9 ── UV-12 (Inscripciones) ── UV-13 (Notas)
UV-25/26 ── UV-32 (Plan de pruebas)
```

### Ruta crítica del avance

1. `UV-28` → `UV-29` → `UV-8` (sin base de datos y backend no hay login).
2. `UV-8` → `UV-9` → `UV-12` → `UV-13` (sin usuarios no hay inscripciones ni notas).
3. `UV-29` → `UV-30` → `UV-10`/`UV-11` (sin API y layout no hay dashboards).

Cualquier retraso en UV-9 impacta directamente UV-12 y UV-13. Por eso su corrección es la primera prioridad del cierre.

---

## 5. Vista previa del Sprint 3

| HU | Historia | Depende de | Avance en el corte |
|---|---|---|---|
| UV-16 | Crear y lanzar videollamada por materia | UV-11, Jitsi Meet | 🔵 85% — API y Jitsi operativos en dashboards. |
| UV-18 | Notificaciones internas por evento académico | UV-10/11, `notificar()` | 🔵 85% — 5 eventos y polling 30s. |
| UV-19 | Mensajes del Directivo a estudiantes y profesores | UV-31 | 🔵 20% — tabla `mensajes` e infraestructura de notificaciones; sin endpoint ni pantalla. |
| UV-31 | Dashboard del Directivo | UV-8, UV-28 | 🔵 75% — panel con datos reales; falta segmentación por facultad y mensajería. |

**Nota:** el sprint abre el 2 de noviembre; estos porcentajes corresponden al avance anticipado construido durante los sprints 1 y 2 y no declaran historias cerradas. Ver fichas en `03-historias/UV-16`, `UV-18`, `UV-19` y `UV-31`.

## 6. Vista previa del Sprint 4

| HU | Historia | Depende de | Riesgo |
|---|---|---|---|
| UV-17 | Subida y reproducción de grabaciones | UV-16 | `stream_video.php` ya soporta Range. |
| UV-20 | Reporte de rendimiento académico por materia | UV-13 | Consultas de agregación. |
| UV-21 | Exportación de reporte individual en PDF | UV-20, FPDF | FPDF puede no estar instalado; hay fallback HTML. |
| UV-33 | Suite E2E Selenium 4 + Python | UV-32 | Requiere servidor y datos estables. |
| UV-34 | Revisión de seguridad OWASP | Todo el sistema | Sesiones, CSRF, SQLi, XSS. |
| UV-35 | Documentación final y manuales | Todo el sistema | Consolidar lo producido en este avance. |

---

## 7. Ritmo y capacidad

| Sprint | HU | Días hábiles | Observación |
|---|---:|---:|---|
| Sprint 1 | 7 | 12 | Dos HU con fecha 9 oct; el resto al 16 oct. |
| Sprint 2 | 6 | 10 | UV-12 vence antes (23 oct) y es dependencia de UV-13. |
| Sprint 3 | 4 | 10 | Menos HU, más integración externa (Jitsi). |
| Sprint 4 | 6 | 10 | Cierre, pruebas y documentación. |

**Recomendación:** primero propagar el parche de UV-9 (ya verificado en la copia ejecutable) al repositorio original y re-ejecutar la suite antes de abrir el sprint con nuevas HU. La deuda de esquema ya se contuvo en la copia; puede propagarse a videollamadas y reportes si no se alinea el original.

---

## 8. Hitos de entrega

| Fecha | Hito | Evidencia |
|---|---|---|
| 16 oct 2026 | Cierre del Sprint 1 | Documentos y login funcionando |
| 30 oct 2026 | Cierre del Sprint 2 | Núcleo académico y plan de pruebas |
| 31 oct 2026 | Corte de este avance | Esta carpeta (17 fichas) |
| 2 nov 2026 | Inicio del Sprint 3 (ya con avance anticipado documentado) | Fichas UV-16/18/19/31 |
| 27 nov 2026 | Cierre del proyecto | Sprint 4 y documentación final |

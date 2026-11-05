# Acta de entrega de avance — Historias vencidas y próximas a vencer

**Proyecto:** UNI-VIRTUAL — Plataforma de Educación Virtual  
**Institución:** Institución Universitaria Antonio José Camacho  
**Acta:** AV-2026-10-31  
**Fecha de corte:** 31 de octubre de 2026  
**Lugar:** Entorno local XAMPP / repositorio Git del proyecto

---

## 1. Participantes

| Rol | Nombre | Participación |
|---|---|---|
| Product Owner | James Medina | Define alcance y acepta el avance |
| Scrum Master | Angel Angulo | Verifica proceso y evidencias |
| Developer | Edwin Carabali | Presenta implementación y documentación |
| Desarrollador | Alexander Pineda | Módulo de usuarios y flujo de entregas |
| Desarrollador | Sebastián Chang | Interfaz y módulos del prototipo |
| Desarrollador | Yeison Montaño | Diseño de datos y pruebas de integración |
| Docente / Cliente | Por confirmar | Recibe la entrega |

---

## 2. Objeto del acta

Dejar constancia de la entrega del avance del proyecto UNI-VIRTUAL conforme al alcance acordado: **las historias de usuario ya vencidas** (sprints 1 y 2, con fecha límite al 30 de octubre) y **las próximas a vencer** (Sprint 3, con fecha límite el 13 de noviembre), documentadas como avance anticipado. Se incluye el estado por porcentaje de avance, las evidencias técnicas y las observaciones que deben atenderse antes del cierre definitivo.

---

## 3. Alcance entregado

### 3.1 Sprint 1 — Fundación (1–16 oct 2026, vencido)

| HU | Entregable | Estado | Avance |
|---|---|---|
| UV-8 | Login con redirección por rol | Aceptado | 100% |
| UV-25 | PRD y SRS IEEE 830 | Aceptado | 100% |
| UV-26 | Arquitectura y ADRs | Aceptado | 100% |
| UV-27 | Repositorio, ramas y entorno XAMPP | Aceptado con observaciones | 85% |
| UV-28 | Modelo ER y script SQL 3FN | Aceptado con observaciones | 85% |
| UV-29 | Backend base (autoload, PDO, Controller, API JSON) | Aceptado | 100% |
| UV-30 | Layout React y consumo de API | Aceptado con observaciones | 85% |

### 3.2 Sprint 2 — Núcleo académico (19–30 oct 2026, vencido)

| HU | Entregable | Estado | Avance |
|---|---|---|---|
| UV-9 | CRUD de usuarios por Admin | En corrección — corregida y verificada en la copia ejecutable | 60% |
| UV-10 | Dashboard del Estudiante | Aceptado | 100% |
| UV-11 | Dashboard del Profesor | Aceptado con observaciones | 90% |
| UV-12 | Inscripción de estudiantes a materias | Aceptado | 100% |
| UV-13 | Registro y actualización de notas | Aceptado con observaciones | 85% |
| UV-32 | Plan de pruebas y casos de aceptación | Aceptado | 100% |

### 3.3 Sprint 3 — Clases y comunicación (2–13 nov 2026, próximas a vencer)

Estas historias aún no inician formalmente (el sprint abre el 2 de noviembre); se entrega el **avance anticipado** construido durante los sprints 1 y 2, sin declararlas cerradas.

| HU | Entregable | Estado | Avance |
|---|---|---|---|
| UV-16 | Videollamada por materia (Jitsi) | Avance anticipado | 85% |
| UV-18 | Notificaciones internas por evento | Avance anticipado | 85% |
| UV-19 | Mensajes del Directivo | Iniciada — solo base técnica | 20% |
| UV-31 | Dashboard del Directivo | Avance anticipado | 75% |

---

## 4. Evidencias presentadas

1. Repositorio Git con ramas `main` y `develop` y tag `v8.0.0`.
2. 61 archivos PHP verificados con `php -l` sin errores de sintaxis.
3. Esquema `config/database_v8.sql` con 19 tablas y datos semilla.
4. Documentos de producto, arquitectura, ADRs y modelo de datos.
5. 17 fichas de historia con criterios, evidencia y porcentaje de avance (13 vencidas + 4 próximas a vencer).
6. Plan de pruebas con 30 casos de aceptación.
7. Suite Selenium con reporte histórico de 23/23 pruebas aprobadas.
8. Plantillas de configuración y hashes para reproducir el entorno.

---

## 5. Observaciones y compromisos

| # | Observación | Compromiso | Responsable | Fecha objetivo |
|---|---|---|---|---|
| O-01 | UV-9 no crea estudiantes ni profesores por incompatibilidad de campos. | Corregir contrato del formulario/API y alinear con el esquema v8. **Hecho y verificado en la copia ejecutable; falta aplicar al repositorio original.** | Developer | Antes de la demo |
| O-02 | `archivoEntrega` vs `urlArchivo` en el módulo de entregas. | Unificar el nombre según el esquema v8. **Hecho en la copia ejecutable.** | Developer | Sprint 3 |
| O-03 | Faltan `config.example.php` y `fix_passwords_v8.sql`. | Publicar las plantillas incluidas en `05-entrega/plantillas/`. | Developer | Antes de la entrega |
| O-04 | Semestre y semana académica fijos a 2026-I. | Parametrizar desde configuración. | Developer | Sprint 3 |
| O-05 | Componentes React duplicados entre dashboards. | Extraer componentes compartidos en el refactor. | Developer | Sprint 4 |

---

## 6. Criterios de aceptación del acta

El avance se considera **recibido con salvedades** cuando:

- [x] Las 17 historias (13 vencidas + 4 próximas) tienen ficha, evidencia y porcentaje de avance.
- [x] Los documentos de producto y arquitectura existen.
- [x] El entorno es reproducible con el manual y las plantillas.
- [x] Las observaciones están registradas con responsable y fecha.
- [ ] UV-9 completa su flujo de creación (pendiente).
- [ ] La suite de pruebas se re-ejecuta contra el estado actual (pendiente).

---

## 7. Aprobaciones

| Rol | Nombre | Firma | Fecha |
|---|---|---|---|
| Product Owner | James Medina | ____________________ | ____/____/2026 |
| Scrum Master | Angel Angulo | ____________________ | ____/____/2026 |
| Developer | Edwin Carabali | ____________________ | ____/____/2026 |
| Desarrollador | Alexander Pineda | ____________________ | ____/____/2026 |
| Desarrollador | Sebastián Chang | ____________________ | ____/____/2026 |
| Desarrollador | Yeison Montaño | ____________________ | ____/____/2026 |
| Docente / Cliente | ____________________ | ____________________ | ____/____/2026 |

---

## 8. Anexos

- `01-gestion/matriz-historias.md`
- `03-historias/` (17 fichas: 13 vencidas + 4 próximas)
- `04-pruebas/plan-pruebas.md`
- `04-pruebas/resultados.md`
- `05-entrega/checklist-entrega.md`
- `05-entrega/riesgos-y-deuda.md`

---

**Nota:** esta acta no declara el cierre del proyecto ni el cierre del Sprint 3. Declara la entrega del avance de las historias vencidas y del avance anticipado de las próximas a vencer, y deja trazabilidad de lo aceptado, lo observado y lo pendiente.

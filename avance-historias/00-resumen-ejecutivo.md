# 00 — Resumen ejecutivo del avance

**Proyecto:** UNI-VIRTUAL  
**Corte:** 31 de octubre de 2026 · **Actualizado:** 6 de noviembre de 2026  
**Alcance:** HU vencidas (sprints 1 y 2) y HU próximas a vencer (Sprint 3)  
**Preparado para:** entrega de avance académico y revisión del Product Owner

> **Alcance:** no es el avance completo de los sprints 1 y 2. Es el avance de las HUs **ya vencidas** y de las **próximas a vencer**, con su estado real.

---

## 1. Situación

UNI-VIRTUAL es una plataforma web de educación virtual para la Institución Universitaria Antonio José Camacho. El avance cubre la **fundación del producto** (autenticación, documentación, arquitectura, repositorio, base de datos, backend y layout React) y el **núcleo académico** (usuarios, dashboards de estudiante y profesor, inscripciones, notas y plan de pruebas).

Al corte, **12 de las 13 historias vencidas tienen implementación verificable** en el repositorio. Una historia —UV-9, CRUD de usuarios— presenta una brecha bloqueante en el flujo de creación, originada por la convivencia de dos versiones del esquema de base de datos y por nombres de campos distintos entre el formulario y la API. La brecha es acotada, tiene parche propuesto y ya está **aplicado y verificado en la copia ejecutable**.

Además, se documenta el **avance anticipado de las 4 historias del Sprint 3** (próximas a vencer el 13 de noviembre): videollamadas y notificaciones ya funcionan de punta a punta en su alcance actual, el dashboard del directivo consume datos reales, y solo la mensajería del directivo está en fase inicial.

---

## 2. Cifras del avance

| Indicador | Valor |
|---|---:|
| HU vencidas evaluadas (sprints 1–2) | 13 |
| HU vencidas con implementación y evidencia | 12 |
| HU vencidas completamente cerradas | 7 |
| HU vencidas con observaciones no bloqueantes | 5 |
| HU vencidas en corrección | 1 |
| HU próximas a vencer con avance anticipado (Sprint 3) | 4 (promedio 66%) |
| Cobertura documental | 100% (17/17 con ficha y avance) |
| Archivos PHP con sintaxis válida | 61/61 (+52/52 en la copia ejecutable) |
| Roles implementados | 4 (admin, directivo, profesor, estudiante) |
| Tablas del esquema v8 | 19 |
| Endpoints API principales | 12 controladores |
| Suites de prueba disponibles | 3 (Selenium/unittest, API, E2E) |
| Última ejecución Selenium registrada | 23/23 aprobadas (24 abr 2026) |
| Fallos de la demo corregidos y verificados | 5 (original y copia) |

> El porcentaje de cumplimiento funcional se reporta por historia (ver `01-gestion/matriz-historias.md`) y no se redondea: cada HU muestra su avance real, lo logrado y lo pendiente.

---

## 3. Qué se entrega

### 3.1 Gestión
- Matriz de las 17 historias (13 vencidas + 4 próximas) con porcentaje de avance, criterios y evidencia.
- Acta de entrega del avance.
- Cronograma de los cuatro sprints y dependencias.

### 3.2 Producto y arquitectura
- PRD con visión, usuarios, alcance, métricas y roadmap.
- SRS con estructura IEEE 830 y requisitos RF-01 a RF-13.
- Documento de arquitectura con vistas C4, capas, patrones y flujos.
- Catálogo de decisiones de arquitectura (ADR-001 a ADR-014).
- Modelo de datos con diagrama ER, análisis de 3FN y diccionario.
- Manual de instalación y operación local en XAMPP.

### 3.3 Evidencia por historia
- 17 fichas con: historia, criterios de aceptación en Gherkin, implementación, archivos, evidencia y **porcentaje de avance** (13 vencidas del avance + 4 próximas a vencer del Sprint 3).
- Trazabilidad HU → requisito → endpoint → modelo → vista → prueba.

### 3.4 Calidad
- Plan de pruebas con estrategia, alcance, criterios de entrada/salida y riesgos.
- 30 casos de aceptación.
- Reporte de resultados con verificación estática y ejecuciones disponibles.
- Plantillas faltantes para reproducir el entorno (`config.example.php`, `fix_passwords_v8.sql`).

### 3.5 Entrega y demo
- Checklist previo a la entrega.
- Guion de demostración por rol y por HU.
- Registro de riesgos y deuda técnica con plan de corrección.
- Copia ejecutable independiente (`univirtual-avance.zip`), servida por Apache en `http://localhost/univirtual-avance/`, con los parches de UV-9 y `urlArchivo` aplicados.

---

## 4. Logros principales

1. **Autenticación con cuatro roles y redirección automática.** Login con bcrypt, regeneración de sesión, control de cuentas activas y separación de dashboards por rol.
2. **Documentación de producto completa.** Por primera vez el proyecto cuenta con PRD, SRS IEEE 830, arquitectura y ADRs en un mismo lugar y alineados con el código.
3. **Base de datos normalizada y reproducible.** Esquema v8 con 19 tablas, claves foráneas, índices y datos semilla; modelo ER documentado.
4. **Backend POO con API JSON.** `db()` singleton, autoload de modelos, `Controller` abstracto, respuestas `{status, data, mensaje}` y controladores por dominio.
5. **Dashboards React funcionales.** Estudiante y profesor consumen la API con `fetch` y `credentials: 'include'`, sobre un sistema de diseño compartido (`panel.css`).
6. **Núcleo académico operativo.** Inscripciones con validación de créditos mínimos, registro de notas por parciales y notificaciones automáticas.
7. **Cultura de pruebas.** Suite Selenium con 23 pruebas aprobadas y suites de API/E2E preparadas para la siguiente fase.
8. **Avance anticipado del Sprint 3.** Videollamadas (Jitsi), notificaciones con polling de 30s y dashboard del directivo ya avanzaron sin haberse comprometido formalmente; se documentan como muestra del ritmo del equipo y del avance real.
9. **Corrección de los cinco fallos de la demo.** Zona horaria, `creado_en`, sala única, `urlArchivo` y columnas inexistentes quedaron corregidos y probados en original y copia; el detalle está en `06-anexos/correcciones-demo.md`.

---

## 5. Hallazgos que el equipo debe conocer

| # | Hallazgo | Severidad | Impacto | Referencia |
|---|---|---|---|---|
| H-01 | El formulario de usuarios envía `codigoEst` y el controlador espera `codigo`; además intenta insertar `programa`, columna que no existe en v8. | 🔴 Alta | La creación de estudiantes y profesores falla; UV-9 no se puede aceptar. | `03-historias/UV-09-crud-usuarios.md` |
| H-02 | El código usaba `archivoEntrega` en entregas, pero el esquema v8 define `urlArchivo`. | 🟠 Media | ✅ Corregido y probado en original y copia; afectaba UV-11 y UV-13. | `06-anexos/correcciones-demo.md` |
| H-03 | `config.example.php` y `fix_passwords_v8.sql` están referenciados pero no existen en el repositorio. | 🟠 Media | Un clon nuevo no puede configurarse siguiendo el README. | `05-entrega/plantillas/` |
| H-04 | El semestre y la semana académica están fijos a `2026-I` y `2026-01-19` en el dashboard del estudiante. | 🟡 Baja | Las métricas de avance no corresponden al corte de octubre de 2026. | `03-historias/UV-10-dashboard-estudiante.md` |
| H-05 | Cada dashboard redefine sus propios componentes React; no hay carpeta de componentes compartidos. | 🟡 Baja | Duplicación y mayor costo de mantenimiento. | `03-historias/UV-30-frontend.md` |
| H-06 | Cinco fallos de integración vistos en la demo: hora, `creada_en`, `roomName` duplicado, `archivoEntrega` y columnas de subida. | 🔴 Alta | ✅ Corregidos y verificados el 6 nov 2026 en original y copia. | `06-anexos/correcciones-demo.md` |

El detalle, la causa raíz y el parche propuesto de cada hallazgo están en `05-entrega/riesgos-y-deuda.md`.

---

## 6. Decisión recomendada

**Entregar el avance con salvedades documentadas.** La base es sólida y demuestra el avance de las historias vencidas (sprints 1 y 2) más el avance anticipado de las próximas (Sprint 3). Antes de la demo se recomienda:

1. Propagar el parche de UV-9 al repositorio original (30–45 min); ya está aplicado y verificado en la copia ejecutable.
2. Para el entorno de demo: la base `univirtual_sprints12` ya está importada y tiene la entrega pendiente de Laura para demostrar la calificación. Si se monta el ZIP en otro equipo, importar `config/database_v8.sql` y `config/fix_passwords.sql`.
3. Re-ejecutar la suite Selenium y anexar el reporte actualizado.
4. Usar el guion de demo actualizado, que incluye la creación de usuarios en la copia ejecutable, la calificación en vivo y el recorrido por UV-16, UV-18 y UV-31, con la salvedad de UV-19.
5. Los cinco fallos de la demo ya están cerrados; no se presentan como pendientes.

Si el docente exige el 100% de las historias cerradas, el único bloqueo real es UV-9. Los demás hallazgos son deuda técnica que no impide sustentar el avance.

---

## 7. Próximos pasos (después de la entrega)

| Prioridad | Acción | HU relacionada |
|---|---|---|
| 1 | Propagar al repositorio original el parche del CRUD de usuarios (ya verificado en la copia ejecutable). | UV-9 |
| 2 | Cerrar las 4 HU del Sprint 3: endpoint de mensajes (UV-19), segmentación por facultad (UV-31), casos CP de videollamadas y notificaciones. | UV-16, UV-18, UV-19, UV-31 |
| 3 | Unificar el esquema: `urlArchivo`, `idCarrera`, `creditos_aprobados`. | UV-28 / refactor |
| 3 | Extraer componentes React compartidos y un `api` único. | UV-30 / refactor |
| 4 | Parametrizar semestre, fechas y `BASE_URL`. | UV-10 / UV-27 |
| 5 | Convertir la suite de pruebas en parte del flujo de entrega. | UV-32 |

Estas acciones son la entrada al refactor de código limpio y arquitectura que se realizará en la siguiente fase.

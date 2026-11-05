# UNI-VIRTUAL — Avance de historias vencidas y próximas a vencer

**Proyecto:** Plataforma de Educación Virtual — Institución Universitaria Antonio José Camacho  
**Documento:** Entrega de avance académico  
**Corte:** 31 de octubre de 2026  
**Alcance:** las historias de usuario **ya vencidas** (sprints 1 y 2, cerrados al 30 oct) y las **próximas a vencer** (Sprint 3, vence el 13 nov)  
**Historias cubiertas:** 17 — vencidas: UV-8, UV-25, UV-26, UV-27, UV-28, UV-29, UV-30, UV-9, UV-10, UV-11, UV-12, UV-13, UV-32 · próximas: UV-16, UV-18, UV-19, UV-31  
**Versión del documento:** 2.0

> **Importante:** esta entrega no declara los sprints 1 y 2 como completos ni cubre todas sus historias. Se enfoca en las **HUs que ya vencieron** y en las que **vencen pronto** (Sprint 3, 13 nov), para mostrar el avance real de esas historias.

---

## 1. Qué contiene esta carpeta

Esta carpeta reúne **todo el contenido que debe acompañar el avance del proyecto**: gestión de las historias vencidas y próximas a vencer, documentos de producto y arquitectura, evidencia técnica por historia, plan y casos de prueba, y los insumos para la demostración y la entrega.

No reemplaza al repositorio: lo **documenta, verifica y ordena** para que el avance pueda sustentarse ante el docente o el cliente.

```text
avance-historias/
├── README.md                         ← este índice
├── 00-resumen-ejecutivo.md           ← estado global, cifras y hallazgos
├── 01-gestion/
│   ├── equipo.md                     ← integrantes, roles y reparto de exposición
│   ├── matriz-historias.md           ← las 17 HU con avance y evidencia
│   ├── acta-entrega-avance.md        ← acta formal de entrega
│   └── cronograma.md                 ← sprints 1–4 y dependencias
├── 02-producto/
│   ├── PRD.md                        ← visión, alcance y requisitos de producto
│   ├── SRS-IEEE830.md                ← especificación de requisitos IEEE 830
│   ├── arquitectura.md               ← arquitectura, capas, patrones y C4
│   ├── ADRs.md                       ← decisiones de arquitectura
│   ├── modelo-datos.md               ← modelo ER, 3FN y diccionario de datos
│   └── manual-instalacion.md         ← entorno XAMPP, BD y ejecución
├── 03-historias/                     ← una ficha de evidencia por HU
│   ├── UV-08-login.md
│   ├── UV-09-crud-usuarios.md
│   ├── UV-10-dashboard-estudiante.md
│   ├── UV-11-dashboard-profesor.md
│   ├── UV-12-inscripciones.md
│   ├── UV-13-notas.md
│   ├── UV-16-videollamadas.md        ← Sprint 3 (avance anticipado)
│   ├── UV-18-notificaciones.md       ← Sprint 3 (avance anticipado)
│   ├── UV-19-mensajes-directivo.md   ← Sprint 3 (avance anticipado)
│   ├── UV-25-documentacion.md
│   ├── UV-26-arquitectura.md
│   ├── UV-27-repositorio.md
│   ├── UV-28-base-datos.md
│   ├── UV-29-backend.md
│   ├── UV-30-frontend.md
│   ├── UV-31-dashboard-directivo.md  ← Sprint 3 (avance anticipado)
│   └── UV-32-pruebas.md
├── 04-pruebas/
│   ├── plan-pruebas.md               ← estrategia, alcance y criterios
│   ├── casos-aceptacion.md           ← CP-01 a CP-30
│   └── resultados.md                 ← ejecución y evidencia disponible
├── 05-entrega/
│   ├── checklist-entrega.md          ← lista de verificación previa
│   ├── guion-demo.md                 ← recorrido de la demostración
│   ├── riesgos-y-deuda.md            ← hallazgos y plan de corrección
│   ├── ejecutable.md                 ← copia independiente servida por Apache
│   └── plantillas/
│       ├── config.example.php        ← plantilla de configuración faltante
│       └── fix_passwords_v8.sql      ← script de hashes faltante
└── 06-anexos/
    ├── trazabilidad.md               ← HU → RF → código → prueba
    ├── inventario-evidencias.md      ← archivos y rutas verificadas
    ├── guion-diagramas.md            ← guion para explicar los diagramas
    ├── diagramas/                    ← PNG, fuentes PlantUML y galería
    └── glosario.md                   ← términos del dominio y técnicos
```

---

## 2. Estado general del avance

| Estado | HU | Cantidad |
|---|---|---:|
| ✅ Completada (vencidas, 100%) | UV-8, UV-10, UV-12, UV-25, UV-26, UV-29, UV-32 | 7 |
| 🟡 Con observaciones (85–90%) | UV-11, UV-13, UV-27, UV-28, UV-30 | 5 |
| 🔴 En corrección (60%; corregida en la copia ejecutable) | UV-9 | 1 |
| 🔵 Avance anticipado (Sprint 3) | UV-16 (85%), UV-18 (85%), UV-31 (75%), UV-19 (20%) | 4 |
| **Total** | | **17** |

- **Cobertura documental:** 17/17 HU con ficha, criterios y evidencia.
- **Cobertura funcional:** de las 13 vencidas, 12/13 con implementación verificable en el repositorio (92%); de las 4 próximas, 3/4 con flujo verificable (75%).
- **Bloqueo identificado:** la creación de usuarios desde el panel admin (UV-9) no completa el flujo por una incompatibilidad entre el formulario, el controlador y el esquema v8. El diagnóstico y el parche propuesto están en [`03-historias/UV-09-crud-usuarios.md`](03-historias/UV-09-crud-usuarios.md).
- **Verificación estática:** 61 archivos PHP revisados con `php -l`, sin errores de sintaxis (29 oct 2026).
- **Pruebas automatizadas:** última ejecución registrada 23/23 aprobadas (24 abr 2026, Selenium). La re-ejecución contra el estado actual está pendiente y se describe en [`04-pruebas/resultados.md`](04-pruebas/resultados.md).

> **Lectura recomendada para la entrega:** primero este README, luego `00-resumen-ejecutivo.md`, después `01-gestion/matriz-historias.md` y finalmente las fichas de las HU que se van a demostrar.

---

## 3. HU incluidas y por qué

### Sprint 1 — vencido el 16 de octubre de 2026

| HU | Historia | Avance |
|---|---|---|
| UV-8 | Login con redirección por rol | ✅ 100% |
| UV-25 | PRD y SRS del proyecto (estilo IEEE 830) | ✅ 100% |
| UV-26 | Documento de arquitectura y ADRs | ✅ 100% |
| UV-27 | Repositorio, ramas y entorno unificado XAMPP | 🟡 85% |
| UV-28 | Modelo ER y script SQL 3FN con datos semilla | 🟡 85% |
| UV-29 | Estructura base del backend (autoload, PDO, clases base, API JSON) | ✅ 100% |
| UV-30 | Layout base React, componentes compartidos y consumo de API | 🟡 85% |

### Sprint 2 — vencido el 30 de octubre de 2026

| HU | Historia | Avance |
|---|---|---|
| UV-9 | CRUD de usuarios por Admin | 🔴 60% (corregida en la copia ejecutable) |
| UV-10 | Dashboard del Estudiante | ✅ 100% |
| UV-11 | Dashboard del Profesor | 🟡 90% |
| UV-12 | Inscripción de estudiantes a materias | ✅ 100% |
| UV-13 | Registro y actualización de notas | 🟡 85% |
| UV-32 | Plan de pruebas y casos de aceptación | ✅ 100% |

### Sprint 3 — inicia el 2 de noviembre de 2026 (próximas a vencer)

Estas historias no se declaran cerradas: se entrega el **avance anticipado** construido durante los sprints 1 y 2.

| HU | Historia | Avance |
|---|---|---|
| UV-16 | Crear y lanzar videollamada por materia (Jitsi) | 🔵 85% |
| UV-18 | Notificaciones internas por evento académico | 🔵 85% |
| UV-19 | Mensajes del Directivo a estudiantes y profesores | 🔵 20% |
| UV-31 | Dashboard del Directivo | 🔵 75% |

---

## 4. Cómo usar esta carpeta

1. **Para sustentar:** abrir `00-resumen-ejecutivo.md` y `01-gestion/matriz-historias.md`.
2. **Para revisar una HU:** abrir su ficha en `03-historias/`; cada una tiene criterios, archivos, evidencia y porcentaje de avance.
3. **Para probar el sistema:** seguir `02-producto/manual-instalacion.md` y luego `04-pruebas/plan-pruebas.md`.
4. **Para la demo:** seguir `05-entrega/guion-demo.md` y marcar `05-entrega/checklist-entrega.md`.
5. **Para ejecutar sin tocar el proyecto original:** descomprimir `univirtual-avance.zip` en `C:\xampp\htdocs\`, iniciar Apache y MySQL en XAMPP y abrir `http://localhost/univirtual-avance/`. Ver `05-entrega/ejecutable.md`.
6. **Para la siguiente fase de refactor:** leer `05-entrega/riesgos-y-deuda.md`; allí está el backlog técnico priorizado.

---

## 5. Convenciones de estado

| Símbolo | Significado |
|---|---|
| ✅ Completada | Implementación, criterios y evidencia presentes; avance 100%. |
| 🟡 Con observaciones | Funciona, pero tiene deuda técnica, inconsistencia de datos o evidencia parcial. |
| 🔴 En corrección | No cumple uno o más criterios de aceptación en el estado actual. |
| 🔵 Avance anticipado | HU del Sprint 3 (próxima); se documenta el avance existente sin declararla cerrada. |
| ⏳ Pendiente de verificación | El artefacto existe, pero no se ejecutó contra el entorno actual. |

---

## 6. Alcance explícito

**Incluido:** las 13 HU vencidas de los sprints 1 y 2 y el avance anticipado de las 4 HU que conforman el Sprint 3 (UV-16, UV-18, UV-19, UV-31). Documentación de producto, arquitectura, base de datos, evidencias, pruebas y demo.

**No incluido:** el cierre formal del Sprint 3 (pruebas de sus HU) y el Sprint 4 (UV-17, UV-20, UV-21, UV-33, UV-34, UV-35). Se referencian en `01-gestion/cronograma.md` solo para mostrar dependencias.

---

## 7. Nota de transparencia

El repositorio contiene una base funcional heredada (v8) sobre la que se montan las historias de este avance. Durante la auditoría se encontraron **inconsistencias de esquema** entre el código y `config/database_v8.sql` (por ejemplo `programa` vs `idCarrera`, `archivoEntrega` vs `urlArchivo`). Se documentan con severidad y propuesta de corrección en `05-entrega/riesgos-y-deuda.md`. No se ocultaron: forman parte del avance y son la entrada natural al refactor solicitado.

# Checklist de entrega — Avance Sprints 1 y 2

**Corte:** 31 de octubre de 2026  
**Uso:** marcar antes de subir el entregable y adjuntar como acta.

---

## 1. Estructura del entregable

| # | Elemento | Ruta | Estado |
|---|---|---|---:|
| 1 | Índice y estado | `avance-historias/README.md` | ✅ |
| 2 | Resumen ejecutivo | `avance-historias/00-resumen-ejecutivo.md` | ✅ |
| 3 | Matriz de historias | `01-gestion/matriz-historias.md` | ✅ |
| 4 | Acta de entrega | `01-gestion/acta-entrega-avance.md` | ✅ |
| 5 | Cronograma | `01-gestion/cronograma.md` | ✅ |
| 6 | PRD | `02-producto/PRD.md` | ✅ |
| 7 | SRS IEEE 830 | `02-producto/SRS-IEEE830.md` | ✅ |
| 8 | Arquitectura | `02-producto/arquitectura.md` | ✅ |
| 9 | ADRs | `02-producto/ADRs.md` | ✅ |
| 10 | Modelo de datos | `02-producto/modelo-datos.md` | ✅ |
| 11 | Manual de instalación | `02-producto/manual-instalacion.md` | ✅ |
| 12 | Fichas de historia (13) | `03-historias/` | ✅ |
| 13 | Plan de pruebas | `04-pruebas/plan-pruebas.md` | ✅ |
| 14 | Casos de aceptación | `04-pruebas/casos-aceptacion.md` | ✅ |
| 15 | Resultados | `04-pruebas/resultados.md` | ✅ |
| 16 | Checklist de entrega | `05-entrega/checklist-entrega.md` | ✅ |
| 17 | Guion de demo | `05-entrega/guion-demo.md` | ✅ |
| 18 | Riesgos y deuda | `05-entrega/riesgos-y-deuda.md` | ✅ |
| 19 | Plantilla de configuración | `05-entrega/plantillas/config.example.php` | ✅ |
| 20 | Script de hashes | `05-entrega/plantillas/fix_passwords_v8.sql` | ✅ |
| 21 | Trazabilidad | `06-anexos/trazabilidad.md` | ✅ |
| 22 | Inventario de evidencias | `06-anexos/inventario-evidencias.md` | ✅ |
| 23 | Glosario | `06-anexos/glosario.md` | ✅ |
| 24 | Guía de la copia ejecutable | `05-entrega/ejecutable.md` | ✅ |
| 25 | Guion de Edwin (arquitectura y calificación) | `06-anexos/guion-edwin.md` | ✅ |
| 26 | Correcciones de la demo (5 fallos) | `06-anexos/correcciones-demo.md` | ✅ |

---

## 2. Contenido mínimo por historia

Cada ficha de `03-historias/` debe tener:

- [x] Cabecera con sprint, vencimiento, épica, prioridad y estado.
- [x] Historia en formato “Como / Quiero / Para”.
- [x] Criterios de aceptación en Gherkin.
- [x] Implementación o contenido entregado.
- [x] Evidencia con rutas verificables.
- [x] Checklist de cumplimiento.
- [x] Observaciones con impacto y recomendación.
- [x] Veredicto explícito (cumple / parcial / no cumple).

---

## 3. Verificación técnica

| # | Verificación | Comando | Estado |
|---|---|---|---:|
| 1 | Sintaxis PHP | `php -l` sobre 61 archivos | ✅ 61/61 |
| 2 | Esquema de base | 19 tablas en `database_v8.sql` | ✅ |
| 3 | Contrato JSON | `Controller::ok()` / `error()` | ✅ |
| 4 | Autenticación por rol | `Controller::requireAuth()` | ✅ |
| 5 | Suites re-ejecutadas | `pytest` | ⏳ Pendiente (entorno detenido) |
| 6 | Demo verificada en navegador | Manual | ✅ Original y copia (6 nov 2026) |
| 7 | Videollamada: hora, notificación y sala única | Manual / API | ✅ 200, 403 y sufijo `-2` |
| 8 | Subida y descarga de material, entrega y grabación | Manual / API | ✅ 200 y `application/pdf` |
| 9 | Matrícula limitada a 20 créditos | Manual / API | ✅ Botón deshabilitado y 400 con motivo |

---

## 4. Consistencia documental

| # | Verificación | Estado |
|---|---|---:|
| 1 | Conteo de tablas unificado en 19 | ⏳ Corregir en README, PRD, SRS y arquitectura. |
| 2 | URL del repositorio unificada | ⏳ README y GIT_WORKFLOW difieren. |
| 3 | Fechas de sprint 2026 | ✅ |
| 4 | Estados de HU coherentes entre matriz, fichas y resultados | ✅ |
| 5 | Sin referencias a archivos inexistentes | ⏳ Se resuelve con `05-entrega/plantillas/`. |

---

## 5. Bloqueos declarados

| # | Bloqueo | Impacto | Responsable | Plan |
|---|---|---|---|---|
| 1 | UV-9: creación de usuario falla por contrato y esquema en el original. | Alto | Backend | Corregida en la copia; falta replicar el parche en el original. |
| 2 | Entregas usaban `archivoEntrega` en lugar de `urlArchivo`. | Alto | Backend | ✅ Corregido y probado en original y copia. |
| 3 | Apache/MySQL detenidos al corte. | Medio | Equipo | ✅ XAMPP levantado y flujo probado. |
| 4 | Faltan `config.example.php` y `fix_passwords_v8.sql`. | Medio | DevOps | ✅ Ya incluidos en `05-entrega/plantillas/`. |
| 5 | Cinco fallos vistos en la demo de clase. | Alto | Backend | ✅ Corregidos y verificados; ver `06-anexos/correcciones-demo.md`. |

---

## 6. Firma de conformidad

| Rol | Nombre | Fecha | Firma |
|---|---|---|---|
| Product Owner | James Medina | | |
| Scrum Master | Angel Angulo | | |
| Developer | Edwin Carabali | | |
| Desarrollador | Alexander Pineda | | |
| Desarrollador | Sebastián Chang | | |
| Desarrollador | Yeison Montaño | | |
| Docente | | | |

---

## 7. Decisión

- [x] Aplicar primero los parches de los cinco fallos de la demo y re-ejecutar la verificación.
- [ ] Declarar UV-9 como bloqueo solo en el proyecto original.
- [ ] Esperar a la suite E2E para declarar el cierre del Sprint 3.

**Recomendación:** entregar el avance con la evidencia actual. Los cinco fallos de la demo ya están corregidos en original y copia; UV-9 se mantiene como corrección exclusiva de la copia y las pruebas E2E quedan como deuda declarada.

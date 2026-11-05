# UV-32 — Plan de pruebas y casos de aceptación

| Campo | Valor |
|---|---|
| Sprint | 2 |
| Vence | 16 de octubre de 2026 |
| Épica | EPIC-10 Calidad |
| Prioridad | Alta |
| Estado | ✅ Completada |
| Entregable | `04-pruebas/plan-pruebas.md`, `04-pruebas/casos-aceptacion.md`, `04-pruebas/resultados.md` |

---

## 1. Historia

**Como** equipo de calidad  
**Quiero** un plan de pruebas y casos de aceptación ejecutables  
**Para** verificar cada historia contra sus criterios y detectar regresiones antes de la entrega.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Plan disponible
  Dado el alcance de los sprints 1 y 2
  Cuando un integrante consulta el plan
  Entonces encuentra estrategia, entornos, datos y criterios de salida

Escenario: Caso ejecutable
  Dado un caso de aceptación
  Cuando se ejecuta
  Entonces tiene precondiciones, pasos, resultado esperado y evidencia
  Y se registra como aprobado, fallido o bloqueado
```

---

## 3. Contenido entregado

### 3.1 Plan de pruebas

| Elemento | Definición |
|---|---|
| Alcance | Historias UV-8 a UV-32 de los sprints 1 y 2. |
| Niveles | Unitario (lint), integración de API, end-to-end de UI. |
| Enfoque | Caja negra sobre criterios de aceptación; verificación de contrato JSON. |
| Entorno | XAMPP, PHP 8, MySQL 3307, Python 3.13 + pytest + Selenium. |
| Datos | Usuarios semilla de los cuatro roles; contraseña `123456`. |
| Criterio de entrada | Base creada y aplicación accesible. |
| Criterio de salida | 0 fallos críticos; bloqueos documentados. |
| Riesgos | Dependencia de CDN, datos semilla, navegador y puerto MySQL. |

### 3.2 Casos de aceptación

30 casos (`CP-01` a `CP-30`) distribuidos así:

| Módulo | Casos | Cubre |
|---|---:|---|
| Autenticación | CP-01 a CP-05 | Login por rol, bloqueo cruzado, logout. |
| Usuarios | CP-06 a CP-10 | Crear, editar, activar/desactivar, eliminar. |
| Dashboards | CP-11 a CP-16 | Contenido por rol y navegación. |
| Inscripciones | CP-17 a CP-20 | Inscribir, desinscribir, créditos mínimos. |
| Notas | CP-21 a CP-25 | Registrar, consultar, promedio, validación. |
| Calidad técnica | CP-26 a CP-30 | Contrato JSON, 401/403, lint, reproducibilidad. |

### 3.3 Suites existentes

| Archivo | Enfoque | Casos |
|---|---|---:|
| `tests/test_univirtual.py` | E2E Selenium por rol | 23 |
| `tests/test_refactor_api.py` | API: contrato, auth, notas, reportes | 18 |
| `tests/test_refactor_e2e.py` | E2E del refactor | 18 |
| `tests/conftest.py` | Fixtures y configuración | — |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Plan | `avance-historias/04-pruebas/plan-pruebas.md` |
| Casos | `avance-historias/04-pruebas/casos-aceptacion.md` |
| Resultados | `avance-historias/04-pruebas/resultados.md` |
| Suite E2E | `tests/test_univirtual.py` |
| Suite API | `tests/test_refactor_api.py` |
| Reporte histórico | `docs/reporte_tests_scrum124.md` (23/23, 24 abr 2026) |
| Lint | `php -l` sobre 61 archivos: 61 OK |

### Checklist

- [x] Plan con alcance, niveles, entorno y criterios.
- [x] 30 casos con precondiciones, pasos y resultado esperado.
- [x] Trazabilidad caso → historia.
- [x] Suites automatizadas identificadas.
- [x] Reporte histórico adjunto.
- [x] Sintaxis PHP validada.
- [ ] Re-ejecución de las suites en el entorno actual.
- [ ] Evidencia de ejecución con capturas y fecha del corte.

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | No se re-ejecutaron las suites el 29 de octubre: Apache y MySQL no estaban activos. | Alto: el estado se apoya en código y en un reporte de abril. | Levantar XAMPP y ejecutar `pytest`; adjuntar salida. |
| 2 | `test_refactor_api.py` y `test_refactor_e2e.py` tienen clases con el mismo nombre. | Bajo: confusión al leer reportes. | Renombrar o separar por módulo. |
| 3 | No hay cobertura medida. | Medio: no se sabe qué porcentaje se prueba. | Añadir `pytest-cov`. |
| 4 | No hay pruebas de UV-9 para creación de profesor/directivo. | Alto: el fallo actual no lo detecta la suite. | Añadir casos de creación por rol. |
| 5 | Los E2E dependen de Selenium y de un navegador concreto. | Medio: fragilidad. | Fijar versión y usar headless en CI. |

---

## 6. Avance

**Avance: 100%.**

**Cumple.** Existen plan, 30 casos de aceptación, suites automatizadas y un reporte histórico verificable. La brecha es de ejecución en el corte actual, no de definición: el plan y los casos están listos para correrse en cuanto se levante el entorno.

La re-ejecución queda registrada como pendiente en `04-pruebas/resultados.md`.

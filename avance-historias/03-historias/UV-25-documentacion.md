# UV-25 — PRD y SRS del proyecto (estilo IEEE 830)

| Campo | Valor |
|---|---|
| Sprint | 1 |
| Vence | 16 de octubre de 2026 |
| Épica | EPIC-08 Documentación |
| Prioridad | Alta |
| Estado | ✅ Completada |
| Entregable | `02-producto/PRD.md`, `02-producto/SRS-IEEE830.md` |

---

## 1. Historia

**Como** equipo de desarrollo  
**Quiero** documentar la visión del producto y los requisitos del sistema  
**Para** alinear el alcance, priorizar el backlog y tener un contrato verificable con el Product Owner.

---

## 2. Criterios de aceptación

```gherkin
Escenario: PRD disponible
  Dado el inicio del proyecto
  Cuando el equipo consulta la documentación
  Entonces encuentra un PRD con problema, visión, usuarios, alcance, métricas y roadmap
  Y está fechado y versionado

Escenario: SRS con estructura IEEE 830
  Dado el PRD aprobado
  Cuando se revisa el SRS
  Entonces contiene introducción, descripción general, requisitos específicos y apéndices
  Y cada requisito tiene identificador, prioridad y criterio verificable
```

---

## 3. Contenido entregado

### 3.1 PRD

| Sección | Contenido |
|---|---|
| Resumen ejecutivo | Qué es UNI-VIRTUAL y por qué existe. |
| Problema | Dolor de estudiantes, profesores, directivos y admin. |
| Visión | Una sola puerta, cada rol ve lo suyo, el dato manda. |
| Usuarios | Cuatro roles con necesidades y dashboards. |
| Objetivos y métricas | Centralización, entregas a tiempo, visibilidad, calidad. |
| Alcance | Sprint 1–2 dentro; Sprint 3–4 fuera; no objetivos. |
| Funcionalidades | Épicas y trazabilidad a HU. |
| Requisitos | RF-01 a RF-13. |
| Flujo principal | Login → dashboard → operación → notificación. |
| Restricciones | PHP, MySQL 3307, React CDN, XAMPP. |
| Roadmap | Cuatro sprints. |
| Riesgos | Deriva de esquema, CDN, datos semilla. |

### 3.2 SRS

| Sección IEEE 830 | Contenido |
|---|---|
| 1. Introducción | Propósito, alcance, definiciones, referencias. |
| 2. Descripción general | Perspectiva, funciones, usuarios, restricciones, supuestos. |
| 3. Requisitos específicos | Interfaces, RF-01 a RF-13, RNF-01 a RNF-18, datos e interfaz. |
| 4. Apéndices | Trazabilidad, fuera de alcance, glosario. |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| PRD completo | `avance-historias/02-producto/PRD.md` |
| SRS completo | `avance-historias/02-producto/SRS-IEEE830.md` |
| Trazabilidad HU → RF | `06-anexos/trazabilidad.md` |
| Glosario | `06-anexos/glosario.md` |

### Checklist

- [x] PRD con problema, visión y usuarios.
- [x] Objetivos y métricas.
- [x] Alcance dentro/fuera.
- [x] Requisitos funcionales priorizados.
- [x] SRS con estructura IEEE 830.
- [x] Requisitos no funcionales.
- [x] Trazabilidad a historias.
- [x] Documento versionado y fechado.

---

## 5. Observaciones

| # | Observación | Recomendación |
|---|---|---|
| 1 | El SRS es una adaptación académica de IEEE 830, no una certificación formal. | Declararlo así en la entrega. |
| 2 | Los requisitos de Sprint 3–4 están fuera del SRS para mantener el foco. | Ampliarlos en la documentación final (UV-35). |
| 3 | Algunas métricas no tienen línea base histórica. | Medirlas durante el semestre. |

---

## 6. Avance

**Avance: 100%.**

**Cumple.** El proyecto cuenta con PRD y SRS completos, alineados con las 13 historias del avance y con el código real. Es la base documental que faltaba.

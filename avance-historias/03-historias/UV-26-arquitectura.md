# UV-26 — Documento de arquitectura y ADRs

| Campo | Valor |
|---|---|
| Sprint | 1 |
| Vence | 16 de octubre de 2026 |
| Épica | EPIC-08 Documentación |
| Prioridad | Alta |
| Estado | ✅ Completada |
| Entregable | `02-producto/arquitectura.md`, `02-producto/ADRs.md` |

---

## 1. Historia

**Como** equipo de desarrollo  
**Quiero** documentar la arquitectura y las decisiones técnicas  
**Para** que cualquier integrante entienda cómo está construido el sistema y por qué.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Arquitectura documentada
  Dado el código del proyecto
  Cuando un nuevo integrante lee la documentación
  Entonces entiende las capas, los contenedores y los flujos principales
  Y puede ubicar dónde implementar una nueva funcionalidad

Escenario: Decisiones registradas
  Dado un cambio arquitectónico relevante
  Cuando se consulta el registro
  Entonces encuentra contexto, decisión, consecuencias y estado
  Y la decisión está vinculada a un ADR
```

---

## 3. Contenido entregado

### 3.1 Documento de arquitectura

| Sección | Contenido |
|---|---|
| Objetivos y atributos de calidad | Seguridad, mantenibilidad, usabilidad. |
| C4 nivel 1 | Contexto: actores, sistema, Jitsi, CDN. |
| C4 nivel 2 | Contenedores: React, PHP, MySQL, uploads. |
| C4 nivel 3 | Componentes: vistas, controladores, modelos, infraestructura. |
| Capas | Presentación, API, dominio/datos, infraestructura. |
| Patrones | Singleton, Template Method, MVC, Composite, DTO. |
| Flujos | Login, request API, inscripción, nota. |
| Seguridad | Bcrypt, sesión, roles, PDO, uploads, CORS. |
| Frontend | React CDN, `panel.css`, `fetch` con credenciales. |
| Despliegue | XAMPP local y topología productiva sugerida. |
| Trade-offs | CDN vs build, sesión vs JWT, monolito vs microservicios. |
| Desviaciones | Deriva de esquema y deuda de componentes. |

### 3.2 ADRs

| ADR | Decisión |
|---|---|
| 001 | Jitsi como link directo, no iframe. |
| 002 | `Controller` en `api/v1/`. |
| 003 | Sesión con fila completa del usuario. |
| 004 | React por CDN, sin build step. |
| 005 | Renderizado condicional con `&&`. |
| 006 | MySQL en puerto 3307. |
| 007 | CRUD base + consultas específicas. |
| 008 | `fetch` con `credentials: 'include'`. |
| 009 | Bcrypt solo con PHP del proyecto. |
| 010 | Contrato JSON uniforme. |
| 011 | Esquema v8 como fuente de verdad. |
| 012 | Documentación viva en el repositorio. |
| 013 | Soft delete de usuarios. |
| 014 | Notificaciones por polling. |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Arquitectura completa | `avance-historias/02-producto/arquitectura.md` |
| Catálogo de ADRs | `avance-historias/02-producto/ADRs.md` |
| Decisiones previas | `docs/memoria/decisiones.md` |
| Diagramas PlantUML | `diagrams/diagrama_v8.puml`, `diagrams/secuencia_login_v8.puml` |
| Código que respalda | `config/config.php`, `api/v1/Controller.php`, `includes/View.php`, `models/Model.php` |

### Checklist

- [x] Vista de contexto.
- [x] Vista de contenedores.
- [x] Vista de componentes.
- [x] Capas y responsabilidades.
- [x] Patrones identificados y justificados.
- [x] Flujos de login y API.
- [x] Seguridad documentada.
- [x] Trade-offs explícitos.
- [x] ADRs con contexto, decisión y consecuencias.
- [x] Deuda técnica reconocida.

---

## 5. Observaciones

| # | Observación | Recomendación |
|---|---|---|
| 1 | El diagrama PlantUML existente es de dominio, no de despliegue. | Complementar con C4 en la documentación final. |
| 2 | La documentación previa (`docs/memoria/`) hablaba de Sprint 5 y fechas de abril. | Esta entrega la reemplaza para el semestre 2026-II. |
| 3 | ADR-011 está propuesta, no implementada. | Aplicarla en el refactor. |
| 4 | No hay ADR de la elección de MySQL vs PostgreSQL. | Añadir si el docente lo solicita. |

---

## 6. Avance

**Avance: 100%.**

**Cumple.** La arquitectura está documentada con niveles C4, patrones, flujos y decisiones. Los ADRs permiten reconstruir el porqué de las elecciones técnicas y dejan explícita la deuda.

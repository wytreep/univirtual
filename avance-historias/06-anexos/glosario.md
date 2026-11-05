# Glosario

Términos usados en el avance de UNI-VIRTUAL (historias vencidas de los sprints 1 y 2 y avance anticipado del Sprint 3).

---

## A

**ADR (Architecture Decision Record)**  
Registro breve de una decisión arquitectónica con su contexto, alternativas, decisión y consecuencias. Se guardan en `02-producto/ADRs.md`.

**API REST**  
Interfaz HTTP que expone recursos en JSON. En UNI-VIRTUAL vive en `api/v1/` y responde con el contrato `{status, mensaje, data}`.

**Autoload**  
Carga automática de clases. El proyecto usa `glob()` sobre `models/`; el refactor lo cambia por `spl_autoload_register`.

---

## B

**Bcrypt**  
Algoritmo de hash de contraseñas con sal. El proyecto usa `password_hash(..., PASSWORD_BCRYPT)` con prefijo `$2y$`.

**Backlog**  
Lista priorizada de historias y tareas. Es la fuente del alcance de cada sprint.

---

## C

**C4**  
Modelo de diagramas en cuatro niveles: contexto, contenedores, componentes y código.

**Caso de aceptación**  
Condición verificable que una historia debe cumplir. Se escribe en Gherkin y se ejecuta como `CP-xx`.

**CORS**  
Política del navegador para peticiones entre orígenes. `Controller` habilita `http://localhost` con credenciales.

**CRUD**  
Crear, leer, actualizar y eliminar. Aplica a usuarios, materias, actividades y otras entidades.

**Clean code**  
Conjunto de prácticas para código legible, con nombres claros, funciones pequeñas y responsabilidades separadas. Es la fase posterior a este avance.

---

## D

**Definition of Done (DoD)**  
Criterios para considerar una historia terminada: aceptación escrita, implementación, evidencia, revisión de seguridad y documentación.

**DTO (Data Transfer Object)**  
Objeto para transportar datos entre capas. Se usa de forma implícita en las respuestas JSON.

---

## E

**Endpoint**  
Ruta de la API que atiende una operación. Ejemplo: `api/v1/usuarios.php`.

**Épica**  
Agrupación de historias con un objetivo común. El proyecto usa `EPIC-01` a `EPIC-10`.

---

## F

**FK (Foreign Key)**  
Clave foránea que garantiza integridad referencial entre tablas.

**3FN (Tercera Forma Normal)**  
Nivel de normalización que elimina dependencias transitivas. El esquema v8 la cumple.

---

## G

**Gherkin**  
Lenguaje de especificación con `Dado / Cuando / Entonces`. Se usa en los criterios de aceptación.

---

## H

**HU (Historia de Usuario)**  
Requerimiento expresado como “Como / Quiero / Para”. Se identifica con `UV-xx`.

---

## J

**Jitsi Meet**  
Servicio de videollamadas. El sistema enlaza a la sala, no la embebe en iframe (ADR-001).

**JSON**  
Formato de intercambio de la API. Contrato: `status`, `mensaje`, `data`.

---

## M

**MVC**  
Patrón Modelo–Vista–Controlador. En el proyecto: `models/`, `pages/`, `api/v1/`.

**Migración**  
Script versionado que cambia el esquema. El proyecto aún no las tiene; es deuda registrada.

---

## N

**Normalización**  
Proceso de organizar tablas para evitar redundancia y anomalías de actualización.

---

## P

**PDO**  
Capa de acceso a datos de PHP. Se usa con consultas preparadas y `ERRMODE_EXCEPTION`.

**Product Owner (PO)**  
Rol Scrum responsable del valor y del orden del backlog.

**PRD (Product Requirements Document)**  
Documento de requisitos de producto: visión, usuarios, alcance y métricas.

---

## R

**React**  
Biblioteca de UI. Se carga por CDN en los dashboards, sin build step (ADR-004).

**Refactor**  
Reestructuración del código sin cambiar su comportamiento externo.

**Riesgo**  
Evento incierto con probabilidad e impacto. Se prioriza en `05-entrega/riesgos-y-deuda.md`.

---

## S

**Scrum Master (SM)**  
Rol que facilita el proceso Scrum y elimina impedimentos.

**Selenium**  
Herramienta de automatización de navegador usada en las pruebas E2E.

**Singleton**  
Patrón que garantiza una única instancia. `db()` lo implementa con `static $pdo`.

**Sprint**  
Iteración de duración fija. En este avance: Sprint 1 (1–16 oct) y Sprint 2 (19–30 oct).

**SRS (Software Requirements Specification)**  
Especificación de requisitos de software. Se documenta con estructura IEEE 830.

---

## T

**Template Method**  
Patrón donde una clase base define el flujo y las subclases implementan pasos. Se usa en `includes/View.php`.

**Trazabilidad**  
Capacidad de relacionar historia, criterio, caso y evidencia. Se documenta en `06-anexos/trazabilidad.md`.

---

## U

**UV**  
Prefijo de las historias de UNI-VIRTUAL. Ejemplo: `UV-9`.

---

## V

**View**  
Clase abstracta que verifica sesión, calcula iniciales e inyecta `window.__S`; cada dashboard implementa `render()`.

---

## W

**window.\_\_S**  
Objeto JavaScript inyectado por PHP con los datos de sesión (`idUsuario`, `nombre`, `rol`, `email`, `initials`).

---

## X

**XAMPP**  
Paquete Apache + MySQL + PHP usado como entorno local. MySQL se configura en el puerto 3307.

---

## Z

**Zona horaria**  
Configuración de fecha/hora. Las fechas del avance se expresan en hora local de Colombia (UTC−5).

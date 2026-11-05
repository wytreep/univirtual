# UV-12 — Inscripción de estudiantes a materias

| Campo | Valor |
|---|---|
| Sprint | 2 |
| Vence | 23 de octubre de 2026 |
| Épica | EPIC-03 Gestión académica |
| Prioridad | Alta |
| Estado | ✅ Completada |
| Responsable | Equipo UNI-VIRTUAL |

---

## 1. Historia

**Como** administrador  
**Quiero** inscribir y desinscribir estudiantes en materias  
**Para** construir la matrícula académica del semestre respetando los requisitos de créditos.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Inscribir estudiante que cumple requisitos
  Dado un estudiante con 72 créditos aprobados
  Y una materia que exige 48 créditos
  Cuando el administrador lo inscribe
  Entonces la inscripción se guarda
  Y el estudiante recibe una notificación

Escenario: Bloquear por créditos insuficientes
  Dado un estudiante con 40 créditos aprobados
  Y una materia que exige 48 créditos
  Cuando el administrador intenta inscribirlo
  Entonces el sistema bloquea la operación
  Y muestra un mensaje con los créditos requeridos y los disponibles

Escenario: Evitar duplicados
  Dado un estudiante ya inscrito en una materia
  Cuando el administrador intenta inscribirlo de nuevo
  Entonces el sistema rechaza la operación

Escenario: Desinscribir
  Dado un estudiante inscrito
  Cuando el administrador lo desinscribe
  Entonces la inscripción se elimina
  Y el aula deja de mostrarlo
```

---

## 3. Implementación

### 3.1 API

`api/v1/inscripciones.php`:

| Método | Parámetros | Acción |
|---|---|---|
| GET | `?idMateria=1` | Estudiantes ya inscritos. |
| GET | `?idMateria=1&disponibles=1` | Estudiantes no inscritos con `puede_inscribirse`. |
| POST | `{idEstudiante, idMateria}` | Inscribir con validación. |
| DELETE | `?idEstudiante=1&idMateria=1` | Desinscribir. |

### 3.2 Reglas implementadas

1. `requireAuth('admin')` en todas las operaciones.
2. Verificación de inscripción duplicada.
3. Lectura de `materias.creditos_minimos`.
4. Lectura de `estudiantes.creditos_aprobados`.
5. Bloqueo si `creditos_aprobados < creditos_minimos`.
6. `INSERT` en `inscripciones`.
7. Notificación al estudiante con `notificar()`.
8. Filtro `NOT IN` para excluir ya inscritos del listado de disponibles.
9. Restricción `UNIQUE(idEstudiante, idMateria)` como última defensa.

### 3.3 Mensaje de bloqueo

```text
El estudiante no cumple los créditos mínimos requeridos:
necesita 48 créditos aprobados, tiene 40.
```

Cumple el criterio funcional, aunque el texto puede acortarse a “El estudiante necesita 48 créditos aprobados, tiene 40” para coincidir literalmente con la historia.

### 3.4 Interfaz

`pages/admin/dashboard.php`, componente `Inscripciones`:

- Selector de materia.
- Tabla de inscritos.
- Tabla de disponibles con indicador `puede_inscribirse`.
- Botones “+ Inscribir” y “Desinscribir”.
- Confirmación visual del resultado.

### 3.5 Archivos

| Archivo | Rol |
|---|---|
| `api/v1/inscripciones.php` | Lógica de inscripción. |
| `pages/admin/dashboard.php` | UI de inscripciones. |
| `config/database_v8.sql` | Tablas `inscripciones`, `materias`, `estudiantes`. |
| `config/config.php` | `notificar()`. |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Validación de créditos | `api/v1/inscripciones.php` líneas 91–109 |
| Bloqueo de duplicados | `api/v1/inscripciones.php` líneas 84–89 |
| Listado de disponibles | `api/v1/inscripciones.php` líneas 40–58 |
| Inscripción y notificación | `api/v1/inscripciones.php` líneas 111–132 |
| Desinscripción | `api/v1/inscripciones.php` líneas 135–143 |
| UI de inscripciones | `pages/admin/dashboard.php` líneas 236–265 |
| Restricción única | `config/database_v8.sql` línea 127 |

### Checklist

- [x] Inscribir estudiante.
- [x] Desinscribir estudiante.
- [x] Validar créditos mínimos.
- [x] Evitar duplicados.
- [x] Notificar al estudiante.
- [x] Listar inscritos y disponibles.
- [x] Restricción única en BD.
- [x] Interfaz administrativa.

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | La validación de duplicado y el INSERT no están en una transacción. | Bajo: la restricción única evita duplicados, pero el mensaje sería genérico. | Envolver en transacción y capturar `1062`. |
| 2 | El mensaje no coincide literalmente con el criterio. | Bajo. | Ajustar el texto. |
| 3 | No se valida el cupo máximo ni el máximo de créditos del semestre (`creditos_max_semestre`). | Medio. | Añadir validación en el refactor. |
| 4 | No se valida que la materia esté activa. | Bajo. | Añadir `AND activa = 1`. |
| 5 | El endpoint exige rol admin; un directivo no puede inscribir. | Decisión de negocio. | Documentar o ampliar roles. |

---

## 6. Avance

**Avance: 100%.**

**Cumple.** La inscripción con validación de créditos es una de las funcionalidades mejor cerradas del avance. Las observaciones son de robustez y reglas de negocio adicionales.

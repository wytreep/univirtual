# UV-13 — Registro y actualización de notas

| Campo | Valor |
|---|---|
| Sprint | 2 |
| Vence | 30 de octubre de 2026 |
| Épica | EPIC-03 Gestión académica |
| Prioridad | Alta |
| Estado | 🟡 Completada con observaciones |
| Responsable | Equipo UNI-VIRTUAL |

---

## 1. Historia

**Como** profesor  
**Quiero** registrar y actualizar las notas de mis estudiantes  
**Para** que el estudiante consulte su rendimiento y el sistema calcule su promedio.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Registrar nota de parcial
  Dado que soy profesor de una materia
  Y el estudiante está inscrito
  Cuando ingreso una nota de 4.5 en nota_parcial1
  Entonces el sistema guarda la nota
  Y el estudiante recibe una notificación

Escenario: Actualizar nota existente
  Dado que el estudiante ya tiene una nota
  Cuando el profesor la modifica
  Entonces el sistema actualiza el valor
  Y el cambio se refleja en la consulta del estudiante

Escenario: Nota fuera de rango
  Dado que el profesor ingresa 7.0
  Cuando guarda
  Entonces el sistema normaliza el valor al rango 0–5

Escenario: Consulta del estudiante
  Dado que el estudiante tiene notas
  Cuando abre Calificaciones
  Entonces ve cada materia con parciales, talleres y nota final
  Y ve el promedio y el estado
```

---

## 3. Implementación

### 3.1 Registro de notas consolidadas

```http
POST /api/v1/profesor.php
{
  "action": "guardar_nota",
  "idEstudiante": 1,
  "idMateria": 1,
  "campo": "nota_parcial1",
  "nota": 4.5
}
```

Flujo:

1. `requireAuth('profesor')`.
2. Resuelve `idInscripcion` desde `idEstudiante` + `idMateria` si no viene.
3. Valida que `campo` esté en `['nota_parcial1','nota_parcial2','nota_talleres','nota_final']`.
4. Normaliza la nota con `max(0, min(5, $nota))`.
5. `UPDATE inscripciones SET {campo} = ?`.
6. Notifica al estudiante.

### 3.2 Calificación de entregas

```http
POST /api/v1/profesor.php
{
  "action": "calificar_entrega",
  "idEntrega": 10,
  "nota": 4.2,
  "feedback": "Buen trabajo"
}
```

Actualiza `entregas.nota` y `entregas.feedback`.

### 3.3 Consulta del estudiante

```http
GET /api/v1/estudiante.php?action=notas&idEst=1
```

Calcula por materia:

```php
$promedio = media(nota_parcial1, nota_parcial2, nota_talleres);
$nota     = nota_final ?? $promedio;
$estado   = $nota === null ? 'en_curso' : ($nota >= 3.0 ? 'aprobado' : 'reprobando');
```

### 3.4 Archivos

| Archivo | Rol |
|---|---|
| `api/v1/profesor.php` | `guardarNota`, `calificarEntrega`. |
| `api/v1/estudiante.php` | `notas()` con promedio y estado. |
| `models/Materia.php` | `actualizarNota` (alternativa). |
| `models/Calificacion.php` | Cálculos de calificación. |
| `pages/profesor/dashboard.php` | Tabla editable de notas. |
| `pages/estudiante/dashboard.php` | Sección Calificaciones. |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Registro de nota | `api/v1/profesor.php` líneas 388–440 |
| Campos permitidos | `api/v1/profesor.php` línea 408 |
| Normalización | `api/v1/profesor.php` línea 413 |
| Notificación | `api/v1/profesor.php` líneas 429–437 |
| Calificación de entrega | `api/v1/profesor.php` líneas 350–386 |
| Consulta de notas | `api/v1/estudiante.php` líneas 166–222 |
| Cálculo de estado | `api/v1/estudiante.php` líneas 199–204 |
| Pruebas de calificaciones | `tests/test_refactor_api.py` clase `TestCalificaciones` |

### Checklist

- [x] Registrar nota por parcial.
- [x] Actualizar nota existente.
- [x] Normalizar rango 0–5.
- [x] Notificar al estudiante.
- [x] Consultar notas con promedio.
- [x] Calcular estado por materia.
- [x] Calificar entrega con feedback.
- [ ] Listar entregas con el esquema v8.

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | `guardarNota()` declara `$campo` y `$nota` dos veces (líneas 393–394 y 405–406). | Bajo: código redundante. | Eliminar duplicados. |
| 2 | No se valida que el profesor sea dueño de la materia/inscripción. | Alto: un profesor podría modificar notas de otra materia conociendo IDs. | Verificar `materias.idProfesor = idProfesorSesion`. |
| 3 | La calificación de entregas usa `entregas.nota`, pero el listado de entregas usa `archivoEntrega`. | El tab Calificar puede fallar antes de calificar. | Unificar `urlArchivo`. |
| 4 | `nota_final` no se calcula automáticamente. | El profesor debe ingresarla. | Calcular o sugerir con ponderación configurable. |
| 5 | No hay historial de cambios de nota. | Bajo para el alcance; alto para auditoría. | Añadir tabla de auditoría en el futuro. |
| 6 | El promedio del estudiante no pondera créditos. | Indicador aproximado. | Documentar o ponderar. |

> La observación 2 es la más importante: hoy la seguridad se apoya en que el frontend solo muestra las materias del profesor, pero la API no verifica la pertenencia en `guardarNota`. Debe corregirse en el refactor.

---

## 6. Avance

**Avance: 85%.** El registro, la consulta y el bloqueo de créditos están verificados en la copia ejecutable (nota persiste, nota sobre materia ajena responde 403).

**Cumple parcialmente.** El registro, la actualización y la consulta de notas funcionan. La calificación de entregas y la autorización por materia requieren corrección antes de considerar la historia totalmente cerrada.

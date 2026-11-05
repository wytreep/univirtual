# UV-11 — Dashboard del Profesor

| Campo | Valor |
|---|---|
| Sprint | 2 |
| Vence | 30 de octubre de 2026 |
| Épica | EPIC-02 Dashboards |
| Prioridad | Alta |
| Estado | 🟡 Completada con observaciones |
| Responsable | Equipo UNI-VIRTUAL |

---

## 1. Historia

**Como** profesor  
**Quiero** ver mis materias, estudiantes, entregas pendientes y actividad reciente  
**Para** priorizar mi trabajo docente y calificar a tiempo.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Panel con materias
  Dado que soy profesor y tengo materias asignadas
  Cuando abro mi dashboard
  Entonces veo cada materia con su aula
  Y veo el número de estudiantes, materiales y actividades
  Y veo cuántas entregas están sin calificar

Escenario: Entregas recientes
  Dado que mis estudiantes han entregado actividades
  Cuando abro el dashboard
  Entonces veo las entregas recientes con estudiante, actividad y materia
  Y veo si están calificadas o pendientes

Escenario: Aula virtual
  Dado que selecciono una materia
  Cuando abro su aula
  Entonces veo materiales, actividades, foros, estudiantes y asistencia
  Y puedo calificar entregas

Escenario: Acceso restringido
  Dado que soy estudiante
  Cuando intento abrir el dashboard del profesor
  Entonces el sistema me redirige al login
```

---

## 3. Implementación

### 3.1 Vista

`pages/profesor/dashboard.php` define `ProfesorView extends View` y una SPA React con:

| Sección | Contenido |
|---|---|
| Dashboard | Tarjetas de materias, entregas sin calificar y actividad reciente. |
| Mis materias | Listado de materias con acceso al aula. |
| Calificar | Entregas por materia y modal de calificación. |
| Reportes | Enlaces a reportes de notas y asistencia. |
| Notificaciones | Lista y marcado de leídas. |
| Configuración | Nombre y contraseña. |

### 3.2 Endpoints

| Acción | Método | Descripción |
|---|---|---|
| `dashboard` | GET | Materias, totales, entregas recientes y notificaciones. |
| `aula` | GET | Materiales, actividades, foros, estudiantes y asistencia. |
| `entregas` | GET | Entregas de un aula. |
| `calificar_entrega` | POST | Nota y feedback. |
| `guardar_nota` | POST | Nota consolidada por parcial. |
| `registrar_asistencia` | POST | Asistencia por fecha. |
| `subir_material` | POST | Archivo o enlace. |
| `crear_actividad` | POST | Nueva actividad. |

### 3.3 Consulta principal

```sql
SELECT m.*, av.idAula,
       (SELECT COUNT(*) FROM inscripciones WHERE idMateria = m.idMateria) AS total_est,
       (SELECT COUNT(*) FROM materiales WHERE idAula = av.idAula) AS total_materiales,
       (SELECT COUNT(*) FROM actividades WHERE idAula = av.idAula) AS total_actividades,
       (SELECT COUNT(*) FROM entregas e
        JOIN actividades a ON e.idActividad = a.idActividad
        WHERE a.idAula = av.idAula AND e.nota IS NULL) AS sinCalificar
FROM materias m
JOIN aulas_virtuales av ON av.idMateria = m.idMateria
WHERE m.idProfesor = ? AND m.activa = 1
ORDER BY m.nombre
```

### 3.4 Archivos

| Archivo | Rol |
|---|---|
| `pages/profesor/dashboard.php` | Vista y SPA. |
| `api/v1/profesor.php` | Endpoints del profesor. |
| `models/Profesor.php` | Datos del docente. |
| `models/Materia.php` | Materias y estudiantes. |
| `models/Material.php` | Materiales. |
| `models/Actividad.php` | Actividades. |
| `models/Entrega.php` | Entregas. |
| `models/Asistencia.php` | Asistencia. |
| `models/Notificacion.php` | Notificaciones. |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Endpoint dashboard | `api/v1/profesor.php` líneas 77–146 |
| Totales por materia | `api/v1/profesor.php` líneas 81–95 |
| Entregas recientes | `api/v1/profesor.php` líneas 108–123 |
| Aula virtual | `api/v1/profesor.php` líneas 149–273 |
| Entregas por aula | `api/v1/profesor.php` líneas 275–315 |
| Vista y navegación | `pages/profesor/dashboard.php` líneas 875–928 |
| Pruebas de panel | `tests/test_univirtual.py` clase `TestProfesor` |

### Checklist

- [x] Dashboard con materias.
- [x] Totales de estudiantes, materiales y actividades.
- [x] Entregas recientes.
- [x] Entregas sin calificar.
- [x] Aula virtual con tabs.
- [x] Acceso restringido por rol.
- [ ] Listado de entregas compatible con el esquema v8.

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | `entregas()` selecciona `e.archivoEntrega`, pero v8 define `urlArchivo`. | El tab Calificar falla al listar entregas. | Renombrar la columna en la consulta. |
| 2 | `eliminarActividad()` también consulta `archivoEntrega` para borrar archivos. | No elimina los archivos físicos correctamente. | Usar `urlArchivo`. |
| 3 | El dashboard no muestra un gráfico de progreso; solo tarjetas. | Menor riqueza visual. | Añadir Chart.js en Sprint 4. |
| 4 | El profesor puede ver materias de otros si conoce el `idAula`; `aula()` valida pertenencia, `entregas()` no lo hace explícitamente. | Riesgo de acceso a datos ajenos. | Validar `idProfesor` en `entregas()`. |
| 5 | El tab Reportes apunta a endpoints que pueden depender de FPDF. | Puede fallar si FPDF no está instalado. | Verificar fallback HTML. |

---

## 6. Avance

**Avance: 90%.** Verificado también en la copia ejecutable, donde la lista de entregas ya responde con `urlArchivo`.

**Cumple parcialmente.** El dashboard y el aula son funcionales; el listado de entregas arrastra la incompatibilidad de columna. Corregir `archivoEntrega → urlArchivo` cierra la observación y habilita la calificación de entregas.

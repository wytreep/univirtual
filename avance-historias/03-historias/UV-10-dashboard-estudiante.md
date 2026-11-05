# UV-10 — Dashboard del Estudiante

| Campo | Valor |
|---|---|
| Sprint | 2 |
| Vence | 30 de octubre de 2026 |
| Épica | EPIC-02 Dashboards |
| Prioridad | Alta |
| Estado | ✅ Completada |
| Responsable | Equipo UNI-VIRTUAL |

---

## 1. Historia

**Como** estudiante  
**Quiero** ver en un solo panel mis materias, notas, asistencia, tareas pendientes y notificaciones  
**Para** saber qué debo hacer sin revisar varias herramientas.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Panel con datos académicos
  Dado que soy estudiante y tengo materias inscritas
  Cuando abro mi dashboard
  Entonces veo el nombre y código de cada materia
  Y veo el profesor asignado
  Y veo mi promedio general
  Y veo mi porcentaje de asistencia

Escenario: Tareas próximas
  Dado que tengo actividades sin entregar
  Cuando abro el dashboard
  Entonces veo las próximas tareas con fecha y materia
  Y puedo entrar al aula de la materia

Escenario: Notificaciones
  Dado que tengo notificaciones sin leer
  Cuando abro el dashboard
  Entonces veo el badge con el número de no leídas
  Y puedo abrir la sección de notificaciones

Escenario: Acceso restringido
  Dado que soy profesor
  Cuando intento abrir el dashboard de estudiante
  Entonces el sistema me redirige al login
```

---

## 3. Implementación

### 3.1 Vista

| Elemento | Archivo |
|---|---|
| Clase `EstudianteView extends View` | `pages/estudiante/dashboard.php` |
| SPA React | mismo archivo, `<script type="text/babel">` |
| Navegación | `dashboard`, `materias`, `tareas`, `calificaciones`, `notificaciones`, `configuracion`, `aula` |
| Estilos | `assets/css/panel.css` |

### 3.2 Endpoint principal

```http
GET /api/v1/estudiante.php?action=dashboard&idEst={id}
```

Respuesta:

```json
{
  "status": "ok",
  "data": {
    "materias": [],
    "tareas": [],
    "proximasTareas": [],
    "notificaciones": [],
    "estadisticas": {
      "totalMaterias": 4,
      "pendientes": 2,
      "tareasProximas": 1,
      "promedio": 4.1,
      "asistencia": 87.5,
      "creditos": 14,
      "noLeidas": 3,
      "semanasTranscurridas": 13,
      "totalSemanas": 20,
      "entregasHechas": 6,
      "totalActividades": 8
    }
  }
}
```

### 3.3 Cálculos

| Métrica | Fórmula | Archivo |
|---|---|---|
| Promedio | media de parciales y talleres no nulos | `api/v1/estudiante.php` |
| Asistencia | `Asistencia::getPorcentaje` | `models/Asistencia.php` |
| Tareas próximas | `Actividad::getProximasSinEntregar` | `models/Actividad.php` |
| Entregas | `Entrega::getConteoEstudiante` | `models/Entrega.php` |
| No leídas | `Notificacion::contarNoLeidas` | `models/Notificacion.php` |

### 3.4 Archivos

| Archivo | Rol |
|---|---|
| `pages/estudiante/dashboard.php` | Vista y componentes React. |
| `api/v1/estudiante.php` | Endpoint `dashboard`, `materias`, `tareas`, `notas`. |
| `models/Estudiante.php` | Materias y tareas pendientes. |
| `models/Asistencia.php` | Porcentaje de asistencia. |
| `models/Actividad.php` | Próximas actividades. |
| `models/Entrega.php` | Conteo de entregas. |
| `models/Notificacion.php` | Notificaciones y no leídas. |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Endpoint dashboard | `api/v1/estudiante.php` líneas 65–127 |
| Cálculo de promedio | `api/v1/estudiante.php` líneas 78–83 |
| Asistencia | `api/v1/estudiante.php` líneas 86–87 |
| Tareas próximas | `api/v1/estudiante.php` líneas 90–91 |
| Notificaciones | `api/v1/estudiante.php` líneas 94–96 |
| Vista y navegación | `pages/estudiante/dashboard.php` líneas 760–791 |
| Pruebas de panel | `tests/test_univirtual.py` clase `TestEstudiante` |

### Checklist

- [x] Panel con materias.
- [x] Promedio general.
- [x] Porcentaje de asistencia.
- [x] Tareas próximas.
- [x] Notificaciones y badge.
- [x] Navegación a aula.
- [x] Acceso restringido por rol.
- [x] Pruebas de panel escritas.

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | Semestre fijo `2026-I` en la vista y en `configuracion`. | Las métricas no corresponden a octubre de 2026 (semestre 2026-II). | Leer el semestre activo desde `configuracion`. |
| 2 | Semana de inicio fija `2026-01-19` en el endpoint. | `semanasTranscurridas` queda fuera de rango o saturada. | Parametrizar `fecha_inicio_semestre`. |
| 3 | El promedio incluye notas de todas las materias sin ponderar créditos. | Es un indicador aproximado. | Documentar o ponderar por créditos. |
| 4 | `tareas` incluye actividades vencidas y próximas sin separar por estado en el dashboard. | Puede confundir. | Añadir pestañas “Pendientes” y “Vencidas”. |
| 5 | El endpoint recibe `idEst` por query, aunque la sesión ya lo tiene. | Riesgo de manipulación si no se valida contra sesión. | Usar siempre `idEspecifico` de la sesión. |

> La observación 5 es relevante para seguridad: hoy el controlador toma `idEst` de la sesión (`$this->idEst`), pero la vista lo envía en la URL. Conviene eliminar el parámetro de la URL y dejar que el servidor lo resuelva.

---

## 6. Avance

**Avance: 100%.**

**Cumple.** El dashboard del estudiante está implementado y consume datos reales. Las observaciones son de parametrización y endurecimiento, no bloquean la aceptación.

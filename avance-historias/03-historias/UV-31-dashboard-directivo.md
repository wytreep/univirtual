# UV-31 — Dashboard del Directivo

| Campo | Valor |
|---|---|
| Sprint | 3 (2–13 nov 2026) |
| Vence | 13 de noviembre de 2026 |
| Cerrada | 26 de noviembre de 2026 |
| Épica | Clases y comunicación |
| Prioridad | Alta |
| Estado | ✅ Cerrada |
| Responsable | Equipo UNI-VIRTUAL |
| Avance | **100%** |

> El panel del directivo quedó completo: resumen, listados segmentados por
> facultad, mensajería (UV-19), perfil, contraseña y una pestaña de **Reportes**
> con cifras por carrera, materia y profesor, distribución de notas y
> asistencia. El periodo académico dejó de estar fijo y se calcula desde la
> fecha del servidor.

---

## 1. Historia

**Como** directivo  
**Quiero** un panel con el resumen y los reportes de mi facultad
(estudiantes, profesores, materias)  
**Para** monitorear la operación académica sin pedir reportes manuales.

---

## 2. Criterios de aceptación

1. El directivo ve el resumen de **su** facultad.
2. Puede listar profesores y estudiantes de su facultad.
3. Puede listar las materias de su facultad (por carrera o por profesor).
4. Puede consultar reportes por carrera, materia y profesor.
5. Ve la distribución de notas finales y el porcentaje de asistencia.
6. Puede enviar mensajes a estudiantes y profesores (UV-19).
7. Perfil y contraseña se configuran desde su panel.
8. Los datos respetan la jerarquía y no cruzan facultades.

---

## 3. Segmentación por facultad

La fuente de verdad es `directivos.idFacultad` para el `idUsuario` de la
sesión. A partir de ella:

| Entidad | Filtro aplicado |
|---|---|
| Profesores | `profesores.idFacultad = :facultad` |
| Estudiantes | `estudiantes.idCarrera → carreras.idFacultad = :facultad` |
| Materias | `materias.idCarrera → carreras.idFacultad` **o** `materias.idProfesor → profesores.idFacultad` |
| Inscripciones / notas | Materias de la facultad |
| Asistencia | Materias de la facultad |

Si un directivo no tiene facultad asignada, la API devuelve conjuntos vacíos
(`idFacultad = 0`); nunca se filtran datos de otra facultad.

---

## 4. Implementación

| Pieza | Archivo | Qué hace |
|---|---|---|
| API del directivo | `api/v1/directivo.php` | `resumen`, `profesores`, `estudiantes`, `materias`, `reportes`, `facultad`, perfil y contraseña. |
| Panel React | `pages/directivo/dashboard.php` | Pestaña **Reportes**, facultad y periodo reales, KPI, tablas y gráficas Chart.js. |
| Modelos | `models/Usuario.php`, `models/Materia.php` | `getByRol()` y `getAll()` aceptan `?int $idFacultad`. |
| Periodo dinámico | `includes/periodo.php` | Etiqueta, año, semestre, inicio, fin y semana actual calculados. |
| Mensajería (UV-19) | `api/v1/comunicados.php` | Envío por facultad y público, bandeja y notificación. |

### Endpoints

- `GET api/v1/directivo.php?action=resumen`
- `GET api/v1/directivo.php?action=reportes`
- `GET api/v1/directivo.php?action=profesores`
- `GET api/v1/directivo.php?action=estudiantes`
- `GET api/v1/directivo.php?action=materias`
- `GET api/v1/directivo.php?action=facultad`
- `POST api/v1/directivo.php` con `action=actualizar_perfil` o `cambiar_password`

El reporte consolidado devuelve:

```json
{
  "facultad": { "idFacultad": 1, "nombre": "Facultad de Ingeniería", "codigo": "FING" },
  "periodo":  { "etiqueta": "2026-II", "inicio": "2026-07-20", "semanaActual": 19 },
  "resumen":  { "totalEstudiantes": 4, "tasaAprobacion": 100.0 },
  "carreras": [], "materias": [], "profesores": [],
  "distribucionNotas": [], "asistencia": {}
}
```

---

## 5. Periodo académico dinámico

`includes/periodo.php` reemplaza los literales `2026-I` y `2026-01-19`:

- Enero–junio → `AAAA-I`; julio–diciembre → `AAAA-II`.
- Inicio institucional: tercer lunes de enero o de julio.
- Duración: 20 semanas; la semana actual se calcula desde el inicio.
- Si `configuracion` guarda un inicio coherente con el periodo calculado, se
  respeta; si está desactualizado, se ignora y se usa el cálculo.
- Para pruebas deterministas se puede definir `UNIVIRTUAL_FECHA=AAAA-MM-DD`.

Archivos que ya consumen el helper: login, header, `descargar.php`,
`XlsxWriter.php`, los cuatro dashboards y `api/v1/estudiante.php`.

---

## 6. Evidencia de pruebas

| Caso | Resultado |
|---|---|
| CP-14 — resumen del directivo | ✅ Facultad, KPI y periodo reales. |
| CP-32 — segmentación por facultad | ✅ Facultad 1 devuelve 3 profesores/4 estudiantes/8 materias; facultad 2 devuelve 0/0/0 y 4 carreras. |
| CP-33 — reportes por carrera, materia, profesor, notas y asistencia | ✅ Endpoint y pestaña con tablas y gráficas. |
| CP-34 — periodo dinámico | ✅ `2026-02-15→2026-I`, `2026-09-10→2026-II`, `2027-03-01→2027-I`, `2028-11-01→2028-II`. |

Ver `04-pruebas/resultados.md` para el detalle de ejecución en original y copia.

---

## 7. Cómo demostrarlo

1. Ingresar como directivo (`diana@univirtual.edu.co` / `123456`).
2. Abrir **Resumen**: nombre real de la facultad, periodo `2026-II` y semana.
3. Abrir **Reportes**: KPI, gráficas y tablas por carrera, materia y profesor.
4. Abrir **Profesores**, **Estudiantes** y **Materias**: solo datos de la
   facultad, con la carrera visible.
5. Abrir **Mensajes**: enviar un comunicado (UV-19).

---

## 8. Avance

**100%.** Todos los criterios quedaron implementados y verificados en el
proyecto original y en la copia ejecutable. No quedan pendientes funcionales
para UV-31.

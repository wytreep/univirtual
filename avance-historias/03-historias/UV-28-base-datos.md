# UV-28 — Modelo ER y script SQL en 3FN con datos semilla

| Campo | Valor |
|---|---|
| Sprint | 1 |
| Vence | 9 de octubre de 2026 |
| Épica | EPIC-09 Base de datos |
| Prioridad | Alta |
| Estado | 🟡 Con observaciones |
| Entregable | `config/database_v8.sql`, `02-producto/modelo-datos.md` |

---

## 1. Historia

**Como** equipo de desarrollo  
**Quiero** un modelo entidad-relación y un script SQL normalizado  
**Para** crear la base de datos con datos de prueba y sostener las historias académicas.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Crear la base desde cero
  Dado un MySQL vacío
  Cuando ejecuto config/database_v8.sql
  Entonces se crea la base univirtual con todas sus tablas
  Y se cargan usuarios, facultades, carreras y materias de prueba

Escenario: Integridad referencial
  Dado el esquema cargado
  Cuando inserto una inscripción con idEstudiante inexistente
  Entonces la clave foránea rechaza la operación
```

---

## 3. Contenido entregado

### 3.1 Tablas (19)

| # | Tabla | Propósito |
|---:|---|---|
| 1 | `configuracion` | Parámetros globales del sistema. |
| 2 | `usuarios` | Cuenta, credenciales, rol y estado. |
| 3 | `facultades` | Unidades académicas. |
| 4 | `carreras` | Programas adscritos a una facultad. |
| 5 | `directivos` | Perfil del rol directivo. |
| 6 | `estudiantes` | Perfil académico del estudiante. |
| 7 | `profesores` | Perfil y especialidad del docente. |
| 8 | `materias` | Asignaturas, créditos y profesor. |
| 9 | `inscripciones` | Relación estudiante–materia. |
| 10 | `aulas_virtuales` | Aula por materia y periodo. |
| 11 | `materiales` | Archivos y enlaces del aula. |
| 12 | `actividades` | Tareas, talleres y evaluaciones. |
| 13 | `entregas` | Respuesta del estudiante a una actividad. |
| 14 | `foros` | Hilos de discusión. |
| 15 | `mensajes` | Mensajes dentro de un foro. |
| 16 | `asistencia` | Registro por estudiante y fecha. |
| 17 | `notificaciones` | Avisos por usuario. |
| 18 | `videollamadas` | Sesiones Jitsi por materia. |
| 19 | `grabaciones_videollamadas` | Grabaciones asociadas a una sesión. |

### 3.2 Normalización

| Forma normal | Justificación |
|---|---|
| 1FN | Todos los atributos son atómicos; no hay listas ni campos multivaluados. |
| 2FN | Las tablas puente (`inscripciones`, `asistencia`, `entregas`) usan claves compuestas o sustitutas y dependen de la clave completa. |
| 3FN | Los datos de facultad y carrera no se repiten en `estudiantes`; se referencian por `idCarrera`. El nombre del profesor no se duplica en `materias`; se usa `idProfesor`. |

### 3.3 Integridad

- Claves primarias autoincrementales en todas las tablas.
- Claves foráneas con `ON DELETE CASCADE` o `RESTRICT` según el caso.
- Índices en `usuarios.email`, `usuarios.codigo`, `inscripciones(idEstudiante, idMateria)`.
- Restricciones `UNIQUE` en correo, código de usuario y combinaciones puente.

### 3.4 Datos semilla

El script incluye:

- Configuración global del periodo.
- Facultades y carreras.
- Usuarios de los cuatro roles (`admin`, `directivo`, `profesor`, `estudiante`).
- Materias con créditos y profesor asignado.
- Inscripciones, actividades, materiales y notificaciones de ejemplo.

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Script completo | `config/database_v8.sql` |
| Documento de modelo | `avance-historias/02-producto/modelo-datos.md` |
| Diagrama | `diagrams/diagrama_v8.puml` |
| Configuración de conexión | `config/config.php` (`DB_NAME=univirtual`) |

### Checklist

- [x] 19 tablas creadas con PK.
- [x] Claves foráneas declaradas.
- [x] Índices en columnas de búsqueda.
- [x] Datos semilla para los cuatro roles.
- [x] Modelo documentado con entidades y relaciones.
- [x] Justificación de 3FN.
- [ ] Tabla `pruebas` creada por el script.
- [ ] Código alineado al esquema v8 (sin columnas de la versión anterior).

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | `models/Prueba.php` y `tests/conftest.py` esperan una tabla `pruebas` que el script no crea. | Medio: el módulo de pruebas no persiste. | Añadir la tabla o retirar el modelo si el módulo no entra al alcance. |
| 2 | `models/Entrega.php` y tres endpoints usan `archivoEntrega`; el esquema v8 define `urlArchivo`. | Alto: las entregas fallan al guardar y al listar. | Migrar el código a `urlArchivo` (ver UV-09 y UV-11). |
| 3 | `api/v1/usuarios.php` consulta `programas_academicos` y `e.idPrograma`, que no existen; el esquema usa `carreras` y `idCarrera`. | Alto: el listado de estudiantes por facultad falla. | Corregir el JOIN y el nombre de la columna. |
| 4 | El README y otros documentos declaran 13 o 17 tablas. | Bajo: inconsistencia documental. | Unificar en 19 tablas. |
| 5 | Los hashes de los usuarios semilla no siempre coinciden con `123456`. | Medio: no se puede iniciar sesión tras importar. | Ejecutar `fix_passwords_v8.sql` incluido en `05-entrega/plantillas/`. |
| 6 | No hay script de migración incremental; solo el `CREATE` completo. | Bajo para el avance; alto en producción. | Añadir migraciones versionadas en el refactor. |

---

## 6. Avance

**Avance: 85%.** Quedan: referencias del código a columnas/tablas de la versión anterior y la tabla `pruebas` que el modelo espera. La base `univirtual_sprints12` de la copia ejecutable importa limpio (19 tablas, 9 usuarios).

**Cumple parcialmente.** El esquema v8 es sólido: 19 tablas, claves foráneas, índices y datos semilla, con normalización defendible hasta 3FN. No se marca como completo porque el código todavía referencia columnas y una tabla de la versión anterior, y porque falta la tabla `pruebas` que el propio modelo espera.

El script es la fuente de verdad; la deuda está en el código que aún no lo respeta.

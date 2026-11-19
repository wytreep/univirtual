# Resultados de pruebas — corte 31 de octubre de 2026

> **Estado vigente (19 nov 2026):** este documento conserva la corrida del corte. Los fallos de integración que afectaban CP-13 y CP-25 ya están corregidos y verificados en original y copia; ver **§9**. UV-9 se propagó al original y UV-19 se cerró con CP-31; ver **§10**. Las suites Selenium/API siguen pendientes de re-ejecución.

| Campo | Valor |
|---|---|
| Fecha del corte | 31 de octubre de 2026 |
| Versión | `main` (`ef578ee`) + carpeta de avance sin commit |
| Entorno | XAMPP local + copia ejecutable `univirtual-avance` (Apache activo) |
| Alcance | 13 historias vencidas (sprints 1 y 2) y las 4 del Sprint 3; UV-19 se cerró con CP-31 |
| Casos definidos | 31 |
| Ejecutados en el corte | 1 (lint) + verificación de la copia ejecutable |
| Verificados por código / historial | 29 |
| Fallos | 2 (históricos, corregidos) |
| Bloqueos | 1 (histórico, corregido) |

---

## 1. Advertencia de transparencia

Al momento del corte de documentos (29 oct), **Apache y MySQL no estaban en ejecución**. No fue posible abrir `http://localhost/univirtual` ni re-ejecutar las suites Selenium. Por lo tanto:

- Los resultados **no** se presentan como una corrida nueva.
- Se combinan tres fuentes: (a) el reporte histórico del 24 de abril de 2026, (b) inspección estática del código al 29 de octubre, y (c) `php -l`.
- Todo caso que requiere UI o base de datos se marca como **verificado por código/historial**, no como ejecutado hoy.
- **Complemento del 31 oct:** con los servidores activos se verificó la **copia ejecutable** `univirtual-avance` (base `univirtual_sprints12`): creación de usuarios de los cuatro roles (parche UV-9 aplicado), inscripciones, bloqueo por créditos, notas, entregas con `urlArchivo` y health check — ver detalle en `05-entrega/ejecutable.md`. Esos hallazgos no alteran los CP del repositorio original: ahí el parche de UV-9 sigue pendiente de aplicar.

Esta distinción es deliberada: declarar una corrida que no ocurrió invalidaría el entregable.

---

## 2. Evidencia disponible

### 2.1 Validación estática

```
php -l sobre 61 archivos PHP
Resultado: 61 OK, 0 errores de sintaxis
```

### 2.2 Reporte histórico — 24 de abril de 2026

| Suite | Casos | Resultado | Tiempo |
|---|---:|---|---|
| `tests/test_univirtual.py` | 23 | 23/23 passed | 3 min 6 s |

| Clase | Casos | Resultado |
|---|---:|---|
| TestLogin | 5 | ✅ |
| TestAdmin | 3 | ✅ |
| TestProfesor | 4 | ✅ |
| TestEstudiante | 5 | ✅ |
| TestDirectivo | 4 | ✅ |
| TestVideollamadas | 2 | ✅ |

**Fuente:** `docs/reporte_tests_scrum124.md`.

### 2.3 Suites disponibles para la re-ejecución

| Suite | Casos | Estado |
|---|---:|---|
| `tests/test_univirtual.py` | 23 | Pendiente de re-ejecutar. |
| `tests/test_refactor_api.py` | 18 | Pendiente de re-ejecutar. |
| `tests/test_refactor_e2e.py` | 18 | Pendiente de re-ejecutar. |

---

## 3. Resultado por caso

| ID | Módulo | Resultado | Base de la verificación |
|---|---|---|---|
| CP-01 | Login admin | ✅ | Código + historial |
| CP-02 | Login profesor | ✅ | Código + historial |
| CP-03 | Login estudiante | ✅ | Código + historial |
| CP-04 | Login directivo | ✅ | Código + historial |
| CP-05 | Bloqueo cruzado | ✅ | Código + historial |
| CP-06 | Crear estudiante | ✅ | Corregido y verificado en original y copia (19 nov 2026) |
| CP-07 | Crear profesor | ✅ | Corregido y verificado en original y copia (19 nov 2026) |
| CP-08 | Editar usuario | ✅ | Código |
| CP-09 | Activar/desactivar | ✅ | Código |
| CP-10 | Eliminar usuario | ✅ | Código (soft delete) |
| CP-11 | Dashboard admin | ✅ | Código + historial |
| CP-12 | Dashboard estudiante | 🟡 | Código: periodo fijo `2026-I` |
| CP-13 | Dashboard profesor | 🟡 | Código: `archivoEntrega` |
| CP-14 | Dashboard directivo | ✅ | Código + historial |
| CP-15 | Navegación | ✅ | Código + historial |
| CP-16 | Videollamadas | ✅ | Código + historial |
| CP-17 | Inscribir | ✅ | Código |
| CP-18 | Desinscribir | ✅ | Código |
| CP-19 | Créditos mínimos | ✅ | Código + rama UV-104 |
| CP-20 | Notificación | ✅ | Código |
| CP-21 | Registrar nota | ✅ | Código |
| CP-22 | Consultar nota | ✅ | Código |
| CP-23 | Promedio | ✅ | Código |
| CP-24 | Validación de nota | ✅ | Código |
| CP-25 | Calificar entrega | ✅ | Corregido con `urlArchivo` y verificado en original y copia |
| CP-26 | Contrato JSON | ✅ | Código (`Controller`) |
| CP-27 | 401 | ✅ | Código (`requireAuth`) |
| CP-28 | 403 | ✅ | Código (`requireAuth`) |
| CP-29 | Sintaxis PHP | ✅ | Ejecutado: 52/52 archivos de aplicación en original y copia |
| CP-30 | Reproducibilidad | 🟡 | Faltan dos archivos referenciados |
| CP-31 | Comunicado del directivo | ✅ | Ejecutado en original y copia (19 nov 2026) |

---

## 4. Resumen

| Estado | Casos | % |
|---|---:|---:|
| ✅ Aprobado | 29 | 94% |
| 🟡 Aprobado con observación | 2 | 6% |
| 🔴 Fallido | 0 | 0% |
| ⛔ Bloqueado | 0 | 0% |
| **Total** | **30** | **100%** |

**Cumplimiento funcional:** 27/30 (90%) si se cuentan como válidos los aprobados con observación.  
**Cumplimiento estricto:** 24/30 (80%).

---

## 5. Hallazgos que explican los fallos

| Hallazgo | Archivos | Efecto |
|---|---|---|
| El frontend envía `codigoEst`/`codigoProf`; la API lee `codigo`. | `pages/admin/dashboard.php`, `api/v1/usuarios.php` | La creación de estudiante y profesor falla. |
| La API inserta `programa`, columna ausente en v8. | `api/v1/usuarios.php` | Error SQL al crear. |
| El profesor se crea sin `codigo`, campo requerido. | `api/v1/usuarios.php` | Restricción `NOT NULL`. |
| El rol `directivo` no se acepta en el `match` de creación. | `api/v1/usuarios.php` | No se puede crear directivo. |
| `getEstudiantesFacultad` usa `programas_academicos` e `idPrograma`. | `api/v1/usuarios.php` | JOIN inválido. |
| Entregas usan `archivoEntrega`; v8 define `urlArchivo`. | `models/Entrega.php`, `api/v1/estudiante.php`, `api/v1/profesor.php` | Carga, listado y calificación fallan. |

El parche completo del primer bloque está en `03-historias/UV-09-crud-usuarios.md`. El segundo se detalla en `05-entrega/riesgos-y-deuda.md`.

---

## 6. Re-ejecución pendiente

### 6.1 Preparación

```powershell
# 1. Iniciar Apache y MySQL desde XAMPP
# 2. Importar la base
C:\xampp\mysql\bin\mysql.exe -u root -P 3307 < config\database_v8.sql

# 3. Reparar hashes si es necesario
C:\xampp\mysql\bin\mysql.exe -u root -P 3307 univirtual < avance-historias\05-entrega\plantillas\fix_passwords_v8.sql
```

### 6.2 Ejecución

```powershell
cd C:\xampp\htdocs\univirtual
python -m pytest tests\test_univirtual.py -v
python -m pytest tests\test_refactor_api.py -v
python -m pytest tests\test_refactor_e2e.py -v
```

### 6.3 Registro esperado

Al terminar, actualizar esta tabla:

| Suite | Fecha | Casos | Passed | Failed | Evidencia |
|---|---|---:|---:|---:|---|
| `test_univirtual.py` | pendiente | 23 | — | — | — |
| `test_refactor_api.py` | pendiente | 18 | — | — | — |
| `test_refactor_e2e.py` | pendiente | 18 | — | — | — |

---

## 7. Verificación de la copia ejecutable

El 29 de octubre se construyó y ejecutó una copia independiente en `C:\xampp\htdocs\univirtual-avance`, con base propia `univirtual_sprints12` y acceso por Apache en `http://localhost/univirtual-avance/`. Esta corrida **sí se ejecutó** y sirve como evidencia de los parches.

| Prueba | Resultado |
|---|---|
| Sintaxis PHP de la copia | 52/52 archivos de aplicación sin errores |
| Importación del esquema | 21 tablas (19 base + 2 de UV-19), 9 usuarios semilla |
| Login de los cuatro roles | 302 al dashboard correcto |
| Dashboards de los cuatro roles | HTTP 200 |
| Crear estudiante | ✅ perfil en `estudiantes` con `idCarrera` |
| Crear profesor | ✅ perfil en `profesores` con `codigoProf` |
| Crear directivo | ✅ perfil en `directivos` |
| Crear admin | ✅ sin perfil adicional |
| Inscribir estudiante | ✅ |
| Bloqueo por créditos mínimos | ✅ HTTP 400 con mensaje |
| Registrar nota | ✅ persiste y notifica |
| Nota sobre materia ajena | ✅ HTTP 403 |
| Listar entregas | ✅ devuelve `urlArchivo` |
| Calificar entrega | ✅ persiste y notifica |
| Calificar entrega ajena | ✅ HTTP 403 |
| Health check | ✅ `UNIVIRTUAL_SPRINTS12` / `db:ok` |

**Alcance:** la copia demuestra que los parches de UV-9 y `urlArchivo` funcionan. Las suites Selenium/API del proyecto original siguen pendientes de re-ejecución, porque el corte documental se hizo con Apache y MySQL detenidos.

El detalle está en `05-entrega/ejecutable.md`.

---

## 8. Conclusión

El sistema sostiene 27 de 30 criterios con evidencia de código o historial. Los tres restantes no son fallos difusos: tienen causa identificada, archivo y línea, y un parche propuesto. La entrega es honesta al declarar que la corrida completa queda pendiente por el entorno detenido.

---

## 9. Verificación post-demo — 6 de noviembre de 2026

Con Apache y MySQL activos se repitieron los flujos que fallaron en la demo de clase, **en el proyecto original y en la copia ejecutable**. El detalle de causa y arreglo está en `06-anexos/correcciones-demo.md`.

| Prueba | Original | Copia | Evidencia |
|---|---|---|---|
| `health.php` | — (no existe) | ✅ | HTTP 200 en la copia |
| Crear videollamada como profesor | ✅ 200 | ✅ 200 | Fila en `videollamadas` |
| Crear videollamada como admin | ✅ 403 | ✅ 403 | Mensaje de permisos |
| Segunda sala a la misma hora | ✅ 200 | ✅ 200 | `roomName` con sufijo `-2` |
| Notificación al inscrito | ✅ | ✅ | Fila con `creado_en` |
| Profesor ajeno finaliza | ✅ 403 | ✅ 403 | Sin cambios en la fila |
| Disponibilidad 10 min antes | ✅ | ✅ | `puedeUnirse = 1` |
| Subir material | ✅ 200 | ✅ 200 | Fila en `materiales` |
| Subir entrega | ✅ 200 | ✅ 200 | Fila con `urlArchivo` |
| Descargar entrega | ✅ 200 PDF | ✅ 200 PDF | `application/pdf` |
| Inscripción sobre 20 créditos | ✅ 400 | ✅ 400 | Motivo explícito |
| Límite de subida | ✅ 200M/210M | ✅ 200M/210M | `.htaccess` |

**Casos actualizados:**

| Caso | Antes | Ahora |
|---|---|---|
| CP-13 Dashboard profesor | 🟡 por `archivoEntrega` | ✅ listado y calificación con `urlArchivo` |
| CP-25 Calificar entrega | ⛔ bloqueado | ✅ califica, descarga y notifica |
| CP-19 Créditos mínimos | ✅ | ✅ + tope de 20 créditos y motivo |
| CP-16 Videollamadas | ✅ historial | ✅ verificado en vivo, con permisos y sala única |

**Lo que sigue pendiente:** re-ejecutar `test_univirtual.py`, `test_refactor_api.py` y `test_refactor_e2e.py`. CP-06 y CP-07 quedaron cerrados el 19 de noviembre al propagar UV-9 al original; ver §10.

---

## 10. Cierre de UV-9 y UV-19 — 19 de noviembre de 2026

Con Apache y MySQL activos se verificó el cierre de las dos historias pendientes, **en el proyecto original y en la copia ejecutable**. Los datos de prueba se eliminaron al terminar.

### 10.1 UV-9 — creación de usuarios en el original

| Prueba | Original | Copia | Evidencia |
|---|---|---|---|
| `php -l` de los archivos modificados | ✅ | ✅ | 12/12 sin errores |
| Crear estudiante con `codigo`, `idCarrera` y semestre | ✅ 200 | ✅ 200 | Fila en `estudiantes` con `idCarrera=1` |
| Crear profesor con `codigo`, `idFacultad` y departamento | ✅ 200 | ✅ 200 | Fila en `profesores` con `idFacultad=1` |
| Login del usuario creado | ✅ 302 a su dashboard | ✅ 302 | Sesión y rol correctos |
| Listado con carrera/facultad | ✅ | ✅ | JOIN a `carreras` |
| Desactivar (soft delete) | ✅ 200 | ✅ 200 | `activo = 0` |

**Casos actualizados:** CP-06 y CP-07 pasan de 🔴 a ✅.

### 10.2 UV-19 — comunicados del directivo

| Prueba | Original | Copia | Evidencia |
|---|---|---|---|
| Migración aplicada | ✅ 2 tablas | ✅ 2 tablas | `comunicados`, `comunicado_destinatarios` |
| Enviar a estudiantes | ✅ 4 destinatarios | ✅ (incluido en “ambos”) | Fila en `comunicados` + fan-out |
| Enviar a profesores | ✅ 3 destinatarios | ✅ | Profesores activos de la facultad |
| Enviar a ambos | ✅ | ✅ 7 destinatarios | Sin duplicados |
| Bandeja del estudiante | ✅ “Nuevo” | ✅ | Asunto, remitente, facultad y fecha |
| Marcar como leído | ✅ `noLeidos` 1 → 0 | ✅ | `leido_en` con hora |
| Aislamiento por rol | ✅ Profesor no recibe aviso de estudiantes | ✅ | Bandeja vacía |
| Notificación | ✅ URL a `.../estudiante/dashboard.php?view=mensajes` | ✅ URL a `.../univirtual-avance/...` | Fila en `notificaciones` |
| Validación sin asunto | ✅ 422 | ✅ 422 | Mensaje claro |
| Público inválido | ✅ 422 | ✅ 422 | Mensaje claro |
| Estudiante intenta enviar | ✅ 400 | ✅ 400 | Acción no válida para su rol |
| `php -l` | ✅ | ✅ | 12/12 archivos modificados |

**Caso agregado:** CP-31 pasa a ✅. La ficha `03-historias/UV-19-mensajes-directivo.md` queda al 100%.

### 10.3 Estado de la base al cierre

| Base | Tablas | Usuarios | Notificaciones | Comunicados de prueba |
|---|---:|---:|---:|---:|
| `univirtual` (original) | 21 | 20 | 92 | 0 |
| `univirtual_sprints12` (copia) | 21 | 9 | 9 | 0 |

**Conclusión:** las 13 historias vencidas quedan sin bloqueos funcionales (8 cerradas y 5 con observaciones no bloqueantes). Del Sprint 3, UV-19 está cerrada y UV-16, UV-18 y UV-31 conservan deuda de pruebas y reportes declarada.

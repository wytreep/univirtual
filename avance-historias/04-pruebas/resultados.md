# Resultados de pruebas — corte 31 de octubre de 2026

> **Estado vigente (6 nov 2026):** este documento conserva la corrida del corte. Los fallos de integración que afectaban CP-13 y CP-25 ya están corregidos y verificados en original y copia; ver **§9**. Las suites Selenium/API siguen pendientes de re-ejecución.

| Campo | Valor |
|---|---|
| Fecha del corte | 31 de octubre de 2026 |
| Versión | `main` (`ef578ee`) + carpeta de avance sin commit |
| Entorno | XAMPP local + copia ejecutable `univirtual-avance` (Apache activo) |
| Alcance | 13 historias vencidas (sprints 1 y 2); el Sprint 3 se reporta como avance anticipado en fichas, sin casos CP |
| Casos definidos | 30 |
| Ejecutados en el corte | 1 (lint) + verificación de la copia ejecutable |
| Verificados por código / historial | 29 |
| Fallos | 2 |
| Bloqueos | 1 |

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
| CP-06 | Crear estudiante | 🔴 | Código: contrato `codigoEst` vs `codigo` |
| CP-07 | Crear profesor | 🔴 | Código: falta `codigo` y usa `programa` |
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
| CP-25 | Calificar entrega | ⛔ | Bloqueado por `archivoEntrega` |
| CP-26 | Contrato JSON | ✅ | Código (`Controller`) |
| CP-27 | 401 | ✅ | Código (`requireAuth`) |
| CP-28 | 403 | ✅ | Código (`requireAuth`) |
| CP-29 | Sintaxis PHP | ✅ | Ejecutado hoy: 61/61 |
| CP-30 | Reproducibilidad | 🟡 | Faltan dos archivos referenciados |

---

## 4. Resumen

| Estado | Casos | % |
|---|---:|---:|
| ✅ Aprobado | 24 | 80% |
| 🟡 Aprobado con observación | 3 | 10% |
| 🔴 Fallido | 2 | 7% |
| ⛔ Bloqueado | 1 | 3% |
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
| Sintaxis PHP de la copia | 52/52 sin errores |
| Importación del esquema | 19 tablas, 9 usuarios semilla |
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

**Lo que sigue pendiente:** re-ejecutar `test_univirtual.py`, `test_refactor_api.py` y `test_refactor_e2e.py`; CP-06 y CP-07 siguen fallando **solo en el original** porque el parche de UV-9 no se replicó allí.

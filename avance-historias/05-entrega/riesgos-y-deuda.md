# Riesgos y deuda técnica

**Corte:** 31 de octubre de 2026  
**Alcance:** código, datos, configuración y documentación de UNI-VIRTUAL.

Este documento concentra los hallazgos que no se corrigieron en la fase documental. Es la entrada directa a la fase de refactor.

---

## 1. Riesgos priorizados

| # | Riesgo | Probabilidad | Impacto | Severidad | Mitigación |
|---|---|---|---|---|---|
| R-01 | Crear usuario desde el admin falla (UV-9). | Alta | Alto | Crítica | Aplicar parche de contrato y esquema. |
| R-02 | Entregas no se listan ni se califican (`archivoEntrega`). | Alta | Alto | Crítica | Renombrar a `urlArchivo` en modelo y endpoints. |
| R-03 | `getEstudiantesFacultad` rompe por tabla inexistente. | Alta | Medio | Alta | Corregir JOIN a `carreras`/`idCarrera`. |
| R-04 | Un tercero no puede configurar el proyecto (falta `config.example.php`). | Media | Alto | Alta | Plantilla incluida en `05-entrega/plantillas/`. |
| R-05 | Usuarios semilla no inician sesión por hash distinto. | Media | Alto | Alta | Ejecutar `fix_passwords_v8.sql`. |
| R-06 | Excepción no controlada devuelve HTML y rompe el contrato JSON. | Media | Medio | Media | Manejador global de excepciones. |
| R-07 | CORS fijo en `http://localhost`. | Media | Medio | Media | Origen configurable por entorno. |
| R-08 | URL de API hardcodeada en los cuatro dashboards. | Alta | Medio | Media | Inyectar `BASE_URL` en `window.API`. |
| R-09 | Periodo académico fijo (`2026-I`) y semana fija. | Alta | Medio | Media | Leer de configuración o API. |
| R-10 | `guardarNota` no verifica que la materia pertenezca al profesor. | Media | Alto | Alta | Validar propiedad antes de escribir. |
| R-11 | Autoload por `glob` carga todos los modelos. | Alta | Bajo | Baja | `spl_autoload_register`. |
| R-12 | Babel transforma JSX en el navegador. | Alta | Bajo | Baja | Precompilar o usar `htm`. |
| R-13 | No hay migraciones incrementales. | Media | Alto | Media | Versionar migraciones. |
| R-14 | No hay `LICENSE` ni `CONTRIBUTING.md`. | Baja | Bajo | Baja | Añadir si se exige. |

---

## 2. Deuda por capa

### 2.1 Datos

| Deuda | Archivo | Acción |
|---|---|---|
| Columna `archivoEntrega` vs `urlArchivo`. | `models/Entrega.php`, `api/v1/estudiante.php`, `api/v1/profesor.php` | Unificar en `urlArchivo`. |
| Tabla `pruebas` ausente. | `config/database_v8.sql`, `models/Prueba.php` | Crear tabla o retirar el modelo. |
| JOIN a `programas_academicos`. | `api/v1/usuarios.php` | Usar `carreras`. |
| Sin migraciones. | `config/` | Carpeta `migrations/` con orden y versión. |

### 2.2 Backend

| Deuda | Archivo | Acción |
|---|---|---|
| Uso mixto de `$this->db` y `db()`. | `api/v1/*.php` | Estandarizar en `db()`. |
| CORS fijo. | `api/v1/Controller.php` | Leer de configuración. |
| Sin manejador global de errores. | `config/config.php` | `set_exception_handler`. |
| `die()` con detalle de conexión. | `config/config.php` | Loguear y mostrar mensaje genérico. |
| Validación de propiedad en notas. | `api/v1/profesor.php` | Verificar `idProfesor` de la materia. |
| Autoload por `glob`. | `config/config.php` | `spl_autoload_register`. |
| Código repetido de lectura de body y validación. | `api/v1/*.php` | Helpers en `Controller`. |

### 2.3 Frontend

| Deuda | Archivo | Acción |
|---|---|---|
| Cuatro dashboards duplican layout. | `pages/*/dashboard.php` | Componentes `Layout`, `Sidebar`, `Card`. |
| URL de API hardcodeada. | `pages/*/dashboard.php` | `window.API = BASE_URL + '/api/v1'`. |
| Build de desarrollo de React. | `includes/View.php` | `production.min.js`. |
| JSX en el navegador. | `pages/*/dashboard.php` | Precompilar. |
| Estilos en línea. | `pages/*/dashboard.php` | Mover a `panel.css`. |
| Periodo fijo. | `pages/estudiante/dashboard.php` | Leer de `configuracion`. |
| Manejo de errores dispar. | `pages/*/dashboard.php` | Componente `ErrorState`. |

### 2.4 Documentación

| Deuda | Acción |
|---|---|
| Conteo de tablas (13/17/19). | Unificar en 19. |
| URL del repositorio. | Unificar README y GIT_WORKFLOW. |
| Archivos referenciados ausentes. | Incluir plantillas. |
| Reporte de pruebas de abril. | Re-ejecutar y actualizar. |

---

## 3. Plan de refactor propuesto

| Fase | Objetivo | Entregable | Riesgo |
|---|---|---|---|
| F0 | Reparar bloqueos funcionales. | Parche UV-9 + `urlArchivo`. | Bajo, cambios acotados. |
| F1 | Configuración por entorno. | `config.example.php`, CORS y `BASE_URL`. | Bajo. |
| F2 | Robustez del backend. | Manejador de errores, validación de propiedad, helpers. | Medio. |
| F3 | Componentes de frontend. | `Layout`, `Sidebar`, `Card`, `ErrorState`. | Medio. |
| F4 | Datos. | Migraciones y tabla `pruebas`. | Medio. |
| F5 | Calidad. | Pruebas de UV-9, cobertura, CI. | Medio. |
| F6 | Documentación. | Unificación de conteos y URLs. | Bajo. |

**Regla:** cada fase termina con `php -l`, la suite correspondiente y la matriz de historias actualizada.

---

## 4. Parches listos para aplicar

### 4.1 UV-09 — creación de usuarios

Resumen:

1. El dashboard debe enviar `codigo`, no `codigoEst`/`codigoProf`.
2. La API debe insertar `idCarrera`, no `programa`.
3. El profesor debe recibir `codigo`.
4. El `match` debe aceptar `directivo`.
5. `getEstudiantesFacultad` debe usar `carreras` e `idCarrera`.

El detalle con código está en `03-historias/UV-09-crud-usuarios.md`.

### 4.2 Entregas — `archivoEntrega`

```sql
-- Verificación previa
SELECT COUNT(*) FROM entregas;
```

```php
// models/Entrega.php
"INSERT INTO entregas (idActividad, idEstudiante, urlArchivo, nombreOriginal, comentario)
 VALUES (?,?,?,?,?)
 ON DUPLICATE KEY UPDATE urlArchivo = VALUES(urlArchivo), ..."
```

Aplicar el mismo renombrado en:

- `api/v1/estudiante.php` (INSERT de entrega).
- `api/v1/profesor.php` (`entregas()` y `eliminarActividad()`).

### 4.3 Configuración

Copiar `05-entrega/plantillas/config.example.php` a `config/config.php` y ajustar credenciales.

### 4.4 Hashes

Ejecutar `05-entrega/plantillas/fix_passwords_v8.sql` si el login semilla falla.

---

## 5. Criterio de salida del refactor

- [ ] UV-9 crea los cuatro roles sin error.
- [ ] Entregas se listan, descargan y califican.
- [ ] No hay URLs ni periodos hardcodeados.
- [ ] Existe manejador global de errores JSON.
- [ ] Las suites pasan en verde.
- [ ] La matriz de historias queda con sus 17 historias al 100% (13 vencidas + 4 del Sprint 3).

---

## 6. Conclusión

La deuda no es estructural: el sistema tiene capas, contrato JSON, sesión y esquema normalizado. Lo que falta es consistencia entre el contrato del frontend, el del backend y el esquema v8, más higiene de configuración y componentes.

Eso es exactamente lo que aborda la siguiente fase.

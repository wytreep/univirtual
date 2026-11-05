# Plan de pruebas — UNI-VIRTUAL (historias vencidas de los sprints 1 y 2)

| Campo | Valor |
|---|---|
| Proyecto | UNI-VIRTUAL |
| Versión evaluada | v8.0.0 + estado de `main` al 31 oct 2026 |
| Corte | 31 de octubre de 2026 |
| Responsable | Equipo de calidad |
| Documentos relacionados | `casos-aceptacion.md`, `resultados.md` |

---

## 1. Objetivo

Verificar que las historias ya vencidas de los sprints 1 y 2 cumplen sus criterios de aceptación, que la API mantiene un contrato JSON estable y que el sistema es reproducible en un entorno XAMPP limpio. Las historias del Sprint 3 (próximas a vencer) se reportan como avance anticipado y se prueban cuando el sprint inicie formalmente.

---

## 2. Alcance

### 2.1 Dentro del alcance

| Épica | Historias |
|---|---|
| EPIC-01 Autenticación | UV-8, UV-9 |
| EPIC-02 Dashboards | UV-10, UV-11, UV-30 |
| EPIC-03 Gestión académica | UV-12, UV-13 |
| EPIC-08 Documentación | UV-25, UV-26, UV-27 |
| EPIC-09 Base de datos | UV-28, UV-29 |
| EPIC-10 Calidad | UV-32 |

### 2.2 Fuera del alcance

- Historias de los sprints 3 y 4 (UV-16, UV-17, UV-18, UV-19, UV-20, UV-21, UV-31, UV-33, UV-34, UV-35).
- Pruebas de carga y estrés.
- Compatibilidad con navegadores distintos de Chromium.
- Despliegue en producción.

---

## 3. Estrategia

| Nivel | Técnica | Herramienta | Qué cubre |
|---|---|---|---|
| Estático | Análisis de sintaxis | `php -l` | 61 archivos PHP. |
| Integración | Caja negra sobre HTTP | `curl` / `requests` | Contrato JSON, códigos 401/403, endpoints. |
| Sistema | Caja negra sobre UI | Selenium + pytest | Login, dashboards, navegación, formularios. |
| Documental | Revisión por checklist | Manual | PRD, SRS, arquitectura, ADRs, modelo. |

**Enfoque:** caja negra guiada por criterios de aceptación. Cada caso tiene precondición, pasos, resultado esperado y evidencia.

---

## 4. Entorno

| Componente | Versión / valor |
|---|---|
| Sistema operativo | Windows 11 |
| Servidor | XAMPP (Apache 2.4, PHP 8.x) |
| Base de datos | MySQL 8 en `127.0.0.1:3307` |
| Base | `univirtual`, script `config/database_v8.sql` |
| URL | `http://localhost/univirtual` |
| Python | 3.13 |
| pytest | 9.x |
| Selenium | 4.x |
| Navegador | Chrome / Chromium |

**Datos de prueba:** usuarios semilla de los cuatro roles con contraseña `123456`. Si el hash importado no coincide, ejecutar `05-entrega/plantillas/fix_passwords_v8.sql`.

---

## 5. Casos

Se definen 30 casos (`CP-01` a `CP-30`) en `casos-aceptacion.md`. La distribución por módulo es:

| Módulo | Casos | Cantidad |
|---|---|---:|
| Autenticación | CP-01 – CP-05 | 5 |
| Usuarios | CP-06 – CP-10 | 5 |
| Dashboards | CP-11 – CP-16 | 6 |
| Inscripciones | CP-17 – CP-20 | 4 |
| Notas y entregas | CP-21 – CP-25 | 5 |
| Calidad técnica | CP-26 – CP-30 | 5 |
| **Total** | | **30** |

---

## 6. Criterios de entrada y salida

### Entrada

- [ ] Apache y MySQL activos.
- [ ] Base `univirtual` creada desde `database_v8.sql`.
- [ ] `config/config.php` con credenciales válidas.
- [ ] Usuarios semilla con contraseña operativa.

### Salida

- 0 fallos críticos abiertos.
- Todo bloqueo documentado con causa y responsable.
- Resultados registrados en `resultados.md` con fecha y evidencia.

---

## 7. Clasificación de resultados

| Estado | Significado |
|---|---|
| ✅ Aprobado | El resultado observado coincide con el esperado. |
| 🟡 Aprobado con observación | Funciona, pero hay una desviación no bloqueante. |
| 🔴 Fallido | El resultado no coincide y afecta al criterio de aceptación. |
| ⛔ Bloqueado | No se pudo ejecutar por una dependencia externa. |

---

## 8. Riesgos de la ejecución

| # | Riesgo | Mitigación |
|---|---|---|
| 1 | Apache o MySQL detenidos. | Iniciar XAMPP antes de la sesión. |
| 2 | CDN de React/Babel no disponible. | Ejecutar con caché o red estable. |
| 3 | Datos semilla alterados por pruebas previas. | Reimportar el script antes de la corrida. |
| 4 | Hash de contraseña incorrecto. | Aplicar `fix_passwords_v8.sql`. |
| 5 | Selenium sin driver compatible. | Usar `webdriver-manager` o Chrome actualizado. |

---

## 9. Trazabilidad

Cada caso referencia su historia. La matriz completa está en `06-anexos/trazabilidad.md` y el resumen por historia en `resultados.md`.

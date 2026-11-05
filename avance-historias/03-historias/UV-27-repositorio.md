# UV-27 — Repositorio, ramas y entorno unificado XAMPP

| Campo | Valor |
|---|---|
| Sprint | 1 |
| Vence | 9 de octubre de 2026 |
| Épica | EPIC-08 Documentación |
| Prioridad | Media |
| Estado | 🟡 Con observaciones |
| Entregable | `README.md`, `docs/GIT_WORKFLOW.md`, ramas `main` / `develop` |

---

## 1. Historia

**Como** integrante del equipo  
**Quiero** un repositorio con ramas definidas y un entorno XAMPP reproducible  
**Para** clonar el proyecto y ejecutarlo sin depender de la máquina de otro compañero.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Clonar y ejecutar desde cero
  Dado un equipo con XAMPP instalado
  Cuando clono el repositorio y sigo el README
  Entonces configuro la conexión MySQL
  Y cargo el script de base de datos
  Y abro el sistema en http://localhost/univirtual

Escenario: Flujo de ramas
  Dado un cambio en desarrollo
  Cuando el integrante trabaja
  Entonces crea una rama feature/UV-{id}-{descripcion}
  Y la integra a develop mediante pull request
  Y main solo recibe merges estables
```

---

## 3. Estado del repositorio

### 3.1 Ramas

| Rama | Tipo | Último commit | Observación |
|---|---|---|---|
| `main` | Producción | `ef578ee` — chore: clean repo, fix .gitignore | Rama activa del clon local. |
| `develop` | Integración | `461369d` — en curso (25 jul 2026) | Existe local y en `origin`. |
| `origin/feature/UV-104-creditos-minimos` | Feature | `9bb1b63` — validación créditos mínimos (23 mar 2026) | Ya integrada; quedó remota. |
| `v8.0.0` | Tag | `a590320` — initial commit v8.0 (24 abr 2026) | Hito del Sprint 5. |

### 3.2 Flujo documentado

`docs/GIT_WORKFLOW.md` define:

- `main` estable, solo merges desde `develop`.
- `develop` como integración del sprint.
- Ramas `feature/UV-{id}-{descripcion}` y `hotfix/UV-{descripcion}`.
- Convención de commits y pasos de pull request.

### 3.3 Entorno

| Elemento | Valor documentado |
|---|---|
| Servidor | XAMPP (Apache + MySQL + PHP 8) |
| Ruta | `C:\xampp\htdocs\univirtual` |
| URL local | `http://localhost/univirtual` |
| MySQL | `127.0.0.1:3307`, usuario `root`, sin contraseña por defecto |
| Base de datos | `univirtual` |
| Script | `config/database_v8.sql` |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Instrucciones de instalación | `README.md` (secciones Requisitos, Instalación, Base de datos) |
| Flujo de ramas | `docs/GIT_WORKFLOW.md` |
| Script de base de datos | `config/database_v8.sql` |
| Configuración real | `config/config.php` |
| Historial | `git log --oneline --graph --all` |

### Checklist

- [x] Repositorio Git inicializado con `main` y `develop`.
- [x] Convención de ramas documentada.
- [x] Pasos de clonación e instalación en el README.
- [x] Script SQL único para crear la base.
- [ ] Plantilla `config/config.example.php` presente en el repositorio.
- [ ] Script `fix_passwords_v8.sql` presente en el repositorio.
- [ ] URL del repositorio consistente en todos los documentos.

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | El README referencia `config/config.example.php`, pero el archivo no existe. | Alto: un tercero no puede configurar sin copiar el archivo real (que está en `.gitignore`). | Crear `config/config.example.php` con valores de ejemplo. Se entrega en `05-entrega/plantillas/`. |
| 2 | La documentación menciona `fix_passwords_v8.sql` para regenerar hashes, pero no está en el repositorio. | Medio: los usuarios semilla pueden no iniciar sesión tras un import. | Incluir el script con el hash bcrypt verificado. Se entrega en `05-entrega/plantillas/`. |
| 3 | `docs/GIT_WORKFLOW.md` usa `github.com/uniajc/univirtual`; el remoto real apunta a otra cuenta. | Bajo: confusión al clonar. | Unificar la URL en README y GIT_WORKFLOW. |
| 4 | `main` está por delante de `develop` en actividad reciente; `develop` quedó en julio. | Medio: el flujo documentado no refleja la operación real. | Reintegrar `main` → `develop` y retomar el flujo por PR. |
| 5 | El README declara “13 tablas”; el esquema v8 crea 19. | Bajo: inconsistencia documental. | Corregir el conteo a 19. |
| 6 | La rama `feature/UV-104-creditos-minimos` sigue publicada aunque ya fue integrada. | Bajo: ruido en el repositorio. | Eliminar la rama remota tras verificar el merge. |
| 7 | No hay archivo `LICENSE` ni `CONTRIBUTING.md`. | Bajo: el docente puede solicitarlos. | Añadir si el entregable lo exige. |

---

## 6. Avance

**Avance: 85%.** Quedan: URL distinta en `README.md` frente al remoto, `config.example.php` y `fix_passwords_v8.sql` entregados en `05-entrega/plantillas/` pero aún integrados al repo, y `develop` rezagado desde el 25 de julio.

**Cumple parcialmente.** El repositorio existe, tiene ramas y un flujo documentado, y el README explica la instalación. No cumple al 100% porque faltan dos archivos que la propia documentación referencia (`config.example.php` y `fix_passwords_v8.sql`) y porque hay inconsistencias de URL y de conteo de tablas.

La brecha es de empaquetado, no de arquitectura: se cierra copiando las dos plantillas incluidas en `05-entrega/plantillas/` y corrigiendo el README.

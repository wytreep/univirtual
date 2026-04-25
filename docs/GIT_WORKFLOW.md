# UNI-VIRTUAL — Git & GitHub Workflow
**Repositorio:** github.com/uniajc/univirtual  
**Rama principal:** `main` | **Rama de desarrollo:** `develop`

---

## ESTRUCTURA DE RAMAS

```
main          ← producción estable (solo merge desde develop vía PR)
  └── develop ← integración continua del sprint
        ├── feature/UV-101-roles-jerarquia
        ├── feature/UV-104-creditos-minimos
        ├── feature/UV-105-fix-foro-estado
        ├── feature/UV-109-configuracion-perfil
        └── hotfix/UV-fix-asistencia-fecha
```

---

## COMANDOS DEL SPRINT 5

### 1. Clonar y configurar
```bash
git clone https://github.com/uniajc/univirtual.git
cd univirtual
git checkout develop
git pull origin develop
```

### 2. Crear rama para una tarea
```bash
# Formato: feature/UV-{id}-{descripcion-corta}
git checkout -b feature/UV-104-creditos-minimos
```

### 3. Flujo de trabajo diario
```bash
# Ver estado
git status

# Agregar cambios
git add config/database_v8.sql
git add api/v1/inscripciones.php
git add pages/admin/dashboard.php

# Commit con referencia a Jira
git commit -m "feat(UV-104): validacion creditos minimos al inscribir

- Agrega campo creditos_minimos a tabla materias
- InscripcionesController valida creditos antes de inscribir
- Frontend muestra estado cumple/no-cumple por estudiante
- Notifica al estudiante cuando es inscrito exitosamente

Resolves: UV-104"

# Push
git push origin feature/UV-104-creditos-minimos
```

### 4. Pull Request
```
Título:   [UV-104] Validación créditos mínimos al inscribir
Base:     develop
Compare:  feature/UV-104-creditos-minimos

Descripción:
## Cambios
- BD: campo `creditos_minimos` en `materias` (default 0 = sin requisito)
- BD: campo `creditos_aprobados` en `estudiantes`
- API: `InscripcionesController::inscribir()` valida créditos
- API: `GET ?disponibles=1` filtra estudiantes ya inscritos
- UI: columna "Cumple req." en tabla de disponibles

## Cómo probar
1. Crear materia con creditos_minimos = 48
2. Intentar inscribir estudiante con creditos_aprobados = 40
3. Debe mostrar error: "El estudiante necesita 48 créditos..."
4. Inscribir con estudiante que tiene 72 créditos → debe funcionar

Closes UV-104
```

### 5. Merge a develop (tras aprobación de PR)
```bash
git checkout develop
git merge --no-ff feature/UV-104-creditos-minimos -m "Merge UV-104: creditos minimos"
git push origin develop
git branch -d feature/UV-104-creditos-minimos
```

### 6. Release a main (fin de sprint)
```bash
git checkout main
git merge --no-ff develop -m "Release Sprint 5 — UV v8.0"
git tag -a v8.0 -m "Sprint 5: jerarquia roles, directivo, creditos, configuracion, notificaciones"
git push origin main --tags
```

---

## CONVENCIÓN DE COMMITS

```
feat(UV-XXX):    nueva funcionalidad
fix(UV-XXX):     corrección de bug
refactor(UV-XXX): refactorización sin cambio funcional
docs(UV-XXX):    documentación
test(UV-XXX):    tests
hotfix(UV-XXX):  corrección urgente en producción
```

---

## .gitignore

```
# Config sensible
config/config.php

# Uploads
uploads/entregas/
uploads/materiales/
uploads/grabaciones/

# Python cache
__pycache__/
*.pyc

# XAMPP logs
*.log
```

---

## RELEASES

| Tag | Fecha | Descripción |
|-----|-------|-------------|
| v5.0 | 2026-02-15 | MVP: auth, dashboards, materiales, actividades |
| v6.0 | 2026-03-01 | Jitsi Meet, reportes PDF/CSV/XLSX |
| v7.0 | 2026-03-15 | Fix regressions, XLSX Python, sistema de auditoría Selenium |
| v8.0 | 2026-03-23 | Jerarquía roles, panel directivo, configuración, notificaciones RT, créditos |

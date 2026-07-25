# Suite de tests — Refactor POO (SCRUM-130)

Red de seguridad para el refactor masivo del proyecto a POO completo.

## Estructura

| Archivo | Tipo | Velocidad | Qué cubre |
|---|---|---|---|
| `test_univirtual.py` | E2E original | 🟡 | 23 tests existentes — login, navegación básica |
| `test_refactor_api.py` | **API directa** | 🟢 Rápido | Contratos JSON, dashboards, calificaciones, CRUD |
| `test_refactor_e2e.py` | **E2E Selenium** | 🔴 Lento | UI conectada al backend, navegación SPA real |

## Cómo se usan en el refactor

```
ANTES de tocar código:
  1. Correr toda la suite — todo debe pasar (o documentar fallos conocidos)
  2. Snapshot del resultado → este es el baseline

DURANTE cada fase del refactor:
  3. Tras cada cambio importante, correr al menos test_refactor_api.py
  4. Si falla algo que pasaba antes → revertir y revisar

ANTES de cerrar la fase:
  5. Correr la suite COMPLETA (api + e2e + univirtual)
  6. Debe pasar al mismo nivel que el baseline
```

## Comandos

```bash
# Setup inicial
pip install pytest requests selenium

# Tests de API (rápidos — preferir durante desarrollo)
python -m pytest tests/test_refactor_api.py -v

# Tests E2E (lentos — preferir antes de cerrar fase)
python -m pytest tests/test_refactor_e2e.py -v

# Toda la suite (al final de cada fase)
python -m pytest tests/ -v

# Solo una clase
python -m pytest tests/test_refactor_api.py::TestCalificaciones -v
```

## Bugs conocidos detectados por la suite

| Bug | Detectado por | Severidad |
|---|---|---|
| `reportes.php:111` consulta `i.nota_habilitacion` (columna inexistente) | `TestReportes::test_reporte_notas_no_crashea` | 🔴 Alta |

## Datos seed que asumen los tests

De `config/database_v8.sql`:

- **idProfesor=1** → James Medina, enseña IS-II (idMateria=1) y RC-201
- **idEstudiante=1** → Edwin Carabali, inscrito en 4 materias
- **idMateria=1** → "Ingeniería de Software II", idAula=1
- **Contraseña universal de seed**: `123456`

Si la BD se reinicia o cambia el seed, ajustar las constantes al inicio de cada archivo.

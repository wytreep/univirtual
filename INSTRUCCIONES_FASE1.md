# 📦 Fase 1 — Modelos base + Fix del bug

## 📋 Archivos en este paquete

| Archivo | Tipo | Destino en el proyecto |
|---|---|---|
| `reportes.php` | ✏️ Modificado | `api/v1/reportes.php` |
| `PruebasController.php` | ✏️ Modificado | `api/v1/PruebasController.php` |
| `Prueba.php` | ✏️ Modificado | `models/Prueba.php` |
| `Calificacion.php` | 🆕 Nuevo | `models/Calificacion.php` |

## 🎯 Qué se hizo

### 1.1 — Fix del bug `nota_habilitacion` (`reportes.php`)

**Antes** (línea 111): la query del reporte de notas incluía `i.nota_habilitacion`, columna que no existe en `database_v8.sql`. Causaba un PDOException 1054 que crasheaba el reporte de notas en el panel de admin.

**Después**: removido `nota_habilitacion` y agregado `nota_talleres` (que sí existe y faltaba), para que el reporte muestre las 4 notas reales del schema.

✅ **Resuelve**: `tests/test_refactor_api.py::TestReportes::test_reporte_notas_no_crashea`

### 1.2 — `Prueba.php` ahora extiende Model

**Antes**: `Prueba.php` era el único modelo del proyecto que no extendía `Model`. Era una clase suelta con queries crudas (`db()->prepare`).

**Después**: extiende `Model`, usa `$this->db->prepare` como el resto. Mismos métodos públicos (`getLista`, `getResumen`, `insertar`, `limpiar`) — **100% backwards compatible**.

### 1.3 — Hardening de `PruebasController.php`

Agregado `require_once` explícito del modelo. Antes funcionaba solo por el autoload de `config/config.php` línea 64. Ahora también funciona si por alguna razón el autoload falla.

### 1.4 — Modelo nuevo `Calificacion.php`

Façade que centraliza toda la lógica de notas dispersa en el proyecto. No reemplaza a `Entrega::calificar()` ni a `Materia::actualizarNota()` todavía — los controllers seguirán usando lo viejo hasta Fase 3-4.

Por ahora **el modelo está creado pero no se usa**. En Fases 3 y 4 los controllers grandes migrarán a usarlo. La idea es:

1. Dejar el modelo listo y testeado por unidad
2. Migrar consumidores uno a uno con la red de tests
3. Eventualmente borrar el código duplicado

## ✅ Cómo aplicar los cambios

```bash
# Estás en: C:\xampp\htdocs\univirtual_2\univirtual

# 1) Reemplazar los 3 archivos modificados
copy <ruta_descarga>\reportes.php          api\v1\reportes.php
copy <ruta_descarga>\PruebasController.php api\v1\PruebasController.php
copy <ruta_descarga>\Prueba.php            models\Prueba.php

# 2) Agregar el nuevo modelo
copy <ruta_descarga>\Calificacion.php      models\Calificacion.php
```

## 🧪 Validación esperada

Tras aplicar los cambios, corre los tests:

```bash
py -m pytest tests/test_refactor_api.py -v
```

**Resultado esperado: 18/18 PASSED** (el bug `nota_habilitacion` debe estar resuelto).

Si algo más falla, mándame el output. Si pasa todo, **arrancamos Fase 2** (enriquecer modelos: Materia, Notificacion, Usuario + crear modelos nuevos Videollamada y Configuracion).

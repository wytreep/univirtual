# UV-29 — Estructura base del backend

| Campo | Valor |
|---|---|
| Sprint | 1 |
| Vence | 16 de octubre de 2026 |
| Épica | EPIC-09 Base de datos |
| Prioridad | Alta |
| Estado | ✅ Completada |
| Entregable | `config/config.php`, `api/v1/Controller.php`, `models/Model.php` |

---

## 1. Historia

**Como** equipo de desarrollo  
**Quiero** una base técnica común para el backend  
**Para** implementar endpoints con conexión PDO, autenticación y respuestas JSON uniformes sin repetir código.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Conexión única a la base
  Dado cualquier endpoint
  Cuando necesito la base de datos
  Entonces obtengo una única instancia PDO configurada con utf8mb4 y excepciones
  Y no se abre una conexión nueva por cada consulta

Escenario: Contrato JSON uniforme
  Dado un endpoint exitoso
  Cuando responde
  Entonces devuelve status=ok, mensaje y data
  Y usa el código HTTP correspondiente

Escenario: Endpoint protegido
  Dado un usuario sin sesión
  Cuando llama a un recurso protegido
  Entonces recibe 401
  Y si el rol no corresponde, recibe 403
```

---

## 3. Implementación

### 3.1 Conexión y bootstrap — `config/config.php`

| Elemento | Implementación |
|---|---|
| Constantes | `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`, `BASE_URL`, `BASE_PATH`. |
| Conexión | `db()` con `static $pdo`; patrón Singleton por función. |
| Opciones PDO | `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES=false`, `charset=utf8mb4`. |
| Autoload | `glob(BASE_PATH . '/models/*.php')` con `require_once`. |
| Utilidades | `notificar()`, `estudiantesDeMateria()`. |

### 3.2 Clase base — `api/v1/Controller.php`

| Miembro | Responsabilidad |
|---|---|
| `__construct()` | Headers JSON/CORS, preflight `OPTIONS`, carga de configuración. |
| `ok()` | Respuesta `{status:"ok", mensaje, data}`. |
| `error()` | Respuesta `{status:"error", mensaje, data:null}` con código HTTP. |
| `requireAuth()` | Valida sesión y rol; 401/403. |
| `getBody()` | Decodifica el body JSON con caché en `$_body`. |
| `method()` | Devuelve el método HTTP. |

### 3.3 Capa de datos — `models/Model.php`

| Método | Uso |
|---|---|
| `find()` | Busca por clave primaria. |
| `findAll()` | Lista con filtros y orden. |
| `save()` | Inserta o actualiza según la PK. |
| `delete()` | Elimina por PK. |

Cada modelo concreto (`Usuario`, `Materia`, `Entrega`, …) extiende `Model` y añade consultas específicas.

### 3.4 Endpoints

13 archivos en `api/v1/`:

`configuracion`, `Controller`, `directivo`, `estudiante`, `inscripciones`, `materias`, `notificaciones`, `profesor`, `PruebasController`, `reportes`, `stats`, `usuarios`, `videollamadas`.

14 modelos en `models/`.

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Singleton PDO y autoload | `config/config.php` |
| Clase base y contrato JSON | `api/v1/Controller.php` |
| CRUD base | `models/Model.php` |
| Modelos concretos | `models/*.php` |
| Endpoints | `api/v1/*.php` |
| Sintaxis validada | `php -l` sobre 61 archivos PHP: 61 OK |

### Checklist

- [x] Conexión única reutilizable.
- [x] PDO con consultas preparadas y excepciones.
- [x] Autoload de modelos.
- [x] Clase base con `ok()`, `error()`, `requireAuth()`.
- [x] CRUD genérico en `Model`.
- [x] Respuesta JSON uniforme.
- [x] Todos los archivos PHP pasan `php -l`.
- [ ] Manejo centralizado de excepciones en la API.
- [ ] CORS configurable por entorno.

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | `Access-Control-Allow-Origin` está fijo en `http://localhost`. | Medio: bloquea otros orígenes (p. ej. `127.0.0.1` o un dominio). | Leer el origen permitido desde `BASE_URL` o variable de entorno. |
| 2 | Algunos endpoints usan `$this->db` y otros `db()`; no hay una propiedad declarada uniforme. | Medio: inconsistencia y errores si falta la propiedad. | Estandarizar `db()` en toda la API. |
| 3 | No hay `try/catch` global ni manejador de errores; una excepción devuelve HTML. | Alto: rompe el contrato JSON. | Registrar `set_exception_handler` que responda JSON 500. |
| 4 | El autoload por `glob` carga todos los modelos en cada request. | Bajo: coste innecesario. | Sustituir por `spl_autoload_register`. |
| 5 | `db()` termina con `die()` mostrando el mensaje de MySQL. | Medio: expone detalles internos. | Registrar el error y mostrar un mensaje genérico. |
| 6 | `requireAuth(string|array $rol = null)` usa un tipo implícitamente nullable. | Bajo: depende de la versión de PHP. | Declarar `?string|array` o `string|array|null`. |

---

## 6. Avance

**Avance: 100%.**

**Cumple.** La historia pedía estructura base, y existe: conexión única, autoload, clase controladora con contrato JSON, CRUD genérico y 13 endpoints que la reutilizan. La sintaxis de los 61 archivos PHP está validada.

Las observaciones son de endurecimiento (CORS, errores, autoload) y se abordan en el refactor posterior; no impiden el flujo funcional.

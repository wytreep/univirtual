# UV-19 — Mensajes del Directivo a estudiantes y profesores

| Campo | Valor |
|---|---|
| Sprint | 3 (2–13 nov 2026) |
| Vence | 13 de noviembre de 2026 |
| Épica | Clases y comunicación |
| Prioridad | Media |
| Estado | ✅ Completada |
| Responsable | Equipo UNI-VIRTUAL |
| Avance | **100%** |

> Historia del Sprint 3 cerrada el 19 de noviembre de 2026. El directivo envía comunicados a estudiantes y profesores de su facultad; cada destinatario los ve en su bandeja y recibe una notificación.

---

## 1. Historia

**Como** directivo  
**Quiero** enviar mensajes a estudiantes y profesores de mi facultad  
**Para** comunicar avisos académicos sin intermediarios ni correos externos.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Enviar un comunicado a estudiantes
  Dado que soy directivo de una facultad
  Cuando escribo un asunto y un mensaje y elijo "Solo estudiantes"
  Entonces el sistema crea el comunicado
  Y lo distribuye a los estudiantes activos de mi facultad
  Y cada uno recibe una notificación

Escenario: Enviar a profesores
  Dado que soy directivo de una facultad
  Cuando elijo "Solo profesores"
  Entonces solo los profesores activos de mi facultad reciben el comunicado

Escenario: Leer un comunicado
  Dado que soy estudiante o profesor destinatario
  Cuando abro Mensajes
  Entonces veo el asunto, el remitente, la facultad y la fecha
  Y al abrirlo queda marcado como leído

Escenario: Aislamiento por facultad
  Dado que soy directivo de Ingeniería
  Cuando envío un comunicado
  Entonces ningún estudiante o profesor de otra facultad lo recibe

Escenario: Validación
  Dado que soy directivo
  Cuando envío un comunicado sin asunto o sin mensaje
  Entonces el sistema responde 422 con un mensaje claro
```

---

## 3. Implementación

| Capa | Archivo | Qué hace |
|---|---|---|
| Migración | `config/migracion_uv19_comunicados.sql` | Crea `comunicados` y `comunicado_destinatarios` (2 tablas nuevas). |
| API | `api/v1/comunicados.php` | Bandeja, enviados, envío y marcado de lectura. |
| Panel del directivo | `pages/directivo/dashboard.php` | Pestaña **Mensajes**: formulario y tabla de enviados con leídos. |
| Panel del estudiante | `pages/estudiante/dashboard.php` | Pestaña **Mensajes** con bandeja y marca de leído. |
| Panel del profesor | `pages/profesor/dashboard.php` | Pestaña **Mensajes** con la misma bandeja. |
| Notificaciones | `config/config.php::notificar()` | Fan-out por rol, con URL a la bandeja correspondiente (UV-18). |

### 3.1 Modelo de datos

La tabla `mensajes` existente es del **foro por aula** (`idForo NOT NULL`), no de mensajería directa. Por eso UV-19 usa dos tablas propias:

| Tabla | Columnas principales | Propósito |
|---|---|---|
| `comunicados` | `idComunicado`, `idRemitente`, `idFacultad`, `publico`, `asunto`, `contenido`, `creado_en` | Cabecera del aviso. |
| `comunicado_destinatarios` | `idComunicado`, `idUsuario`, `leido`, `leido_en` | Fan-out por destinatario y estado de lectura. |

`publico` es `estudiantes`, `profesores` o `ambos`. Las claves foráneas eliminan en cascada los destinatarios cuando se borra un comunicado o un usuario.

### 3.2 Endpoints

| Método | Ruta | Rol | Acción |
|---|---|---|---|
| GET | `/api/v1/comunicados.php?action=bandeja` | estudiante, profesor | Comunicados recibidos con estado de lectura. |
| GET | `/api/v1/comunicados.php?action=enviados` | directivo | Enviados con total de destinatarios y leídos. |
| POST | `/api/v1/comunicados.php` `{action:'enviar', publico, asunto, contenido}` | directivo | Crea, distribuye y notifica. |
| POST | `/api/v1/comunicados.php` `{action:'marcar_leido', idComunicado}` | estudiante, profesor | Marca como leído solo si es destinatario. |

### 3.3 Reglas de negocio

1. El directivo solo envía a la facultad registrada en `directivos.idFacultad`.
2. Estudiantes: se resuelven por `estudiantes.idCarrera → carreras.idFacultad`.
3. Profesores: se resuelven por `profesores.idFacultad`.
4. Solo se incluyen usuarios con `activo = 1` y el rol correspondiente.
5. Asunto máximo 150 caracteres; mensaje máximo 5000.
6. El alta es transaccional: si falla una notificación, no queda un comunicado a medias.
7. El marcado de lectura es idempotente y no revela comunicados de terceros.

---

## 4. Cómo demostrarlo

1. Entrar como directivo (`diana@univirtual.edu.co` / `123456`).
2. Abrir **Mensajes** y enviar un comunicado con público **Estudiantes y profesores**.
3. Mostrar la fila en **Enviados** con el número de destinatarios y leídos.
4. Entrar como estudiante (`laura@univirtual.edu.co`) → **Mensajes**: el comunicado aparece como “Nuevo”.
5. Abrirlo: se despliega el contenido y queda marcado como leído.
6. Entrar como profesor (`james@univirtual.edu.co`) → **Mensajes**: el mismo comunicado aparece en su bandeja.
7. Mostrar la notificación generada en el panel del estudiante.

**Frase para la demo:** “El directivo no escribe en la base: elige un público, el sistema resuelve los destinatarios de su facultad y entrega el aviso por dos canales, bandeja y notificación.”

---

## 5. Evidencia de verificación

| Prueba | Resultado |
|---|---|
| Enviar a estudiantes | ✅ 200, 4 destinatarios en Ingeniería |
| Enviar a profesores | ✅ 200, 3 destinatarios en Ingeniería |
| Bandeja del estudiante | ✅ Asunto, remitente, facultad y estado “Nuevo” |
| Marcar como leído | ✅ `noLeidos` pasa de 1 a 0 |
| Profesor no recibe el aviso de estudiantes | ✅ Bandeja vacía |
| Notificación al destinatario | ✅ Fila con `creado_en` y URL a `dashboard.php?view=mensajes` |
| Asunto vacío | ✅ 422 con mensaje claro |
| Público inválido | ✅ 422 |
| Estudiante intenta enviar | ✅ 400 (acción no válida para su rol) |
| Copia ejecutable | ✅ Mismo flujo con URLs de `univirtual-avance` |
| `php -l` en los archivos modificados | ✅ Sin errores |

Los datos de prueba se eliminaron al terminar; las bandejas quedaron limpias para la demo.

---

## 6. Pendiente y deuda

- Casos formales CP en `04-pruebas/casos-aceptacion.md` (registrados como CP-31).
- Adjuntar capturas de la bandeja y del formulario en la entrega.
- La respuesta del destinatario al comunicado no está implementada; el alcance acordado era aviso unidireccional + notificación.
- No hay envío por correo externo; es mensajería interna, como pide la historia.

---

## 7. Avance

**100%.** El flujo completo está operativo y verificado en original y copia: el directivo envía, el sistema distribuye por facultad y rol, el destinatario lee y el estado de lectura se refleja en el panel del directivo. Las notificaciones se generan en el mismo request.

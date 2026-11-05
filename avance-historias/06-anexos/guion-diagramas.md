# Guion de diagramas para explicar el avance

**Corte:** 4 de noviembre de 2026  
**Alcance:** historias vencidas (Sprint 1 y 2) e historias próximas a vencer (Sprint 3, 13 nov)  
**Carpeta de imágenes:** `06-anexos/diagramas/`  
**Galería para proyectar:** `06-anexos/diagramas/galeria.html`

---

## 1. Orden recomendado para la exposición

| # | Diagrama | Archivo | Qué demuestra | HU relacionadas | Tiempo |
|---|---|---|---|---|---:|
| 1 | Casos de uso | `Figura_01_casos_de_uso.png` | Qué hace el sistema y qué puede hacer cada rol. | Todas | 1 min |
| 2 | Arquitectura | `Figura_02_arquitectura.png` | React + API JSON en PHP + MySQL; separación por capas. | UV-29, UV-30 | 1 min |
| 3 | Modelo entidad-relación | `Figura_04_modelo_er.png` | Base de datos relacional en 3FN; 19 tablas en v8. | UV-28 | 1 min |
| 4 | Secuencia de login | `Figura_09_secuencia_login.png` | Autenticación, sesión y redirección por rol. | UV-8 | 1 min |
| 5 | Secuencia de inscripción | `Figura_10_secuencia_inscripcion.png` | Validación de materia, duplicados y créditos mínimos. | UV-12 | 1 min |
| 6 | Entrega y calificación | `Figura_11_secuencia_entrega_calificacion.png` | Subida de archivo, calificación y notificación. | UV-11, UV-13 | 1 min |
| 7 | Videollamada | `Figura_12_secuencia_videollamada.png` | Creación de sala Jitsi, notificación y asistencia. | UV-16 (próxima) | 1 min |
| 8 | Gantt de sprints | `Figura_16_gantt_sprints.png` | Línea de tiempo y ubicación de las HUs vencidas/próximas. | Gestión | 1 min |

**Total:** 8 minutos. Si hay preguntas técnicas, se añaden despliegue, clases o EDT.

---

## 2. Guion corto por diagrama

### 2.1 Casos de uso — “qué hace el sistema”

**Qué decir:**
> “Este diagrama muestra los cuatro actores —estudiante, profesor, directivo y administrador— y los casos de uso que ya están cubiertos. La autenticación es transversal: todos entran por login y cada rol llega a su propio dashboard. Los flujos académicos centrales son inscripción, entrega, calificación y consulta de notas; las videollamadas y notificaciones son el avance del Sprint 3.”

**Mensaje clave:** el alcance está delimitado por roles y por flujos verificables, no por pantallas sueltas.

---

### 2.2 Arquitectura — “cómo está construido”

**Qué decir:**
> “El navegador ejecuta React 18 por CDN y consume una API JSON en PHP. Apache sirve el front controller; los controladores validan sesión y rol; los modelos usan PDO con sentencias preparadas; y MySQL guarda los datos. Los archivos de entregas y materiales se guardan en `uploads/`. Esta separación es la que permite que el mismo backend atienda a los cuatro dashboards.”

**Mensaje clave:** no es un monolito de páginas sueltas: hay capas, API y una sola fuente de datos.

---

### 2.3 Modelo entidad-relación — “dónde vive la información”

**Qué decir:**
> “El modelo parte de `usuarios` y su rol. Alrededor están `facultades`, `carreras`, `estudiantes`, `profesores` y `directivos`. La operación académica está en `materias`, `aulas_virtuales`, `inscripciones`, `materiales`, `actividades` y `entregas`. La comunicación y las videollamadas están en `foros`, `mensajes`, `notificaciones`, `videollamadas` y `grabaciones`. En total son 19 tablas en la versión 8.”

**Mensaje clave:** el esquema soporta el flujo completo y ya incluye las tablas del Sprint 3.

**Aclaración importante:** el PNG `Figura_04` es conceptual y usa nombres de una versión anterior (`roles`, `tareas`, `calificaciones`). Para defender nombres exactos, usar `fuentes/diagrama_v8.puml` o `config/database_v8.sql`.

---

### 2.4 Secuencia de login — UV-8

**Qué decir:**
> “El usuario envía correo y contraseña. El backend busca por correo con una consulta preparada, verifica el hash con `password_verify` y regenera la sesión. Si el usuario no existe, igual se ejecuta una verificación con un hash dummy para no filtrar por tiempo de respuesta. Al final redirige según el rol: admin, directivo, profesor o estudiante.”

**Mensaje clave:** el login no solo autentica: también controla el acceso cruzado entre roles.

**Versión v8:** `fuentes/secuencia_login_v8.puml` muestra el tiempo constante y `idEspecifico`.

---

### 2.5 Secuencia de inscripción — UV-12

**Qué decir:**
> “El estudiante elige una materia. El controlador verifica que exista, que no esté ya inscrito y que cumpla los créditos mínimos. Si cumple, inserta la inscripción; si no, devuelve un mensaje claro. En la copia ejecutable se probó el bloqueo: un estudiante con 0 créditos no puede inscribir una materia que exige 48.”

**Mensaje clave:** la regla de negocio de créditos está en el backend, no solo en la interfaz.

---

### 2.6 Entrega y calificación — UV-11 y UV-13

**Qué decir:**
> “El estudiante sube un archivo dentro del plazo; el sistema valida tipo, tamaño y fecha, guarda el archivo en `uploads/entregas/` y registra la ruta. El profesor califica, se actualiza la nota y se genera una notificación para el estudiante. En la copia ejecutable se corrigió el campo `archivoEntrega` por `urlArchivo`, que es el que existe en el esquema v8, y se validó que el profesor solo califique materias que dicta.”

**Mensaje clave:** el flujo de evaluación cierra el ciclo académico y notifica al estudiante.

---

### 2.7 Videollamada — UV-16 (próxima a vencer)

**Qué decir:**
> “El profesor programa una videollamada; el backend genera la sala de Jitsi y notifica a los inscritos. El estudiante ve la videollamada en su dashboard, registra asistencia y entra a la sala. Es una de las historias del Sprint 3 que ya tiene avance anticipado, por eso se incluye en este corte.”

**Mensaje clave:** no estamos mostrando solo lo vencido; también lo que vence el 13 de noviembre y ya tiene base funcional.

---

### 2.8 Gantt de sprints — “dónde estamos”

**Qué decir:**
> “El Gantt ubica los cuatro sprints. El corte del 31 de octubre cierra Sprint 1 y 2; el 13 de noviembre vence el Sprint 3. La entrega se concentra en las HUs vencidas y en las próximas a vencer, no en todos los compromisos de todos los sprints. Por eso hay 13 HUs vencidas y 4 próximas, con avance medido.”

**Mensaje clave:** el alcance es por historia y por fecha, no por “sprint completo”.

---

## 3. Versión express de 3 minutos

Si el tiempo es corto, mostrar solo estas cuatro:

1. **Casos de uso** — qué hace el sistema.
2. **Arquitectura** — cómo está construido.
3. **Login** — seguridad y roles (UV-8).
4. **Gantt** — alcance y fechas.

Cerrar con:
> “De las 13 HUs vencidas, 7 están cerradas, 5 tienen observaciones no bloqueantes y 1 estaba en corrección; esa corrección ya está aplicada y verificada en la copia ejecutable. De las 4 próximas, 3 tienen avance anticipado y 1 está iniciada.”

---

## 4. Qué está implementado y qué se debe decir con cuidado

| HU | Estado | Cómo explicarlo |
|---|---|---|
| UV-8 | ✅ Cerrada | Login y redirección por rol verificados. |
| UV-9 | 🟡 Corregida en la copia | En el proyecto original estaba en corrección; en la copia ejecutable ya crea los cuatro roles. |
| UV-10 | ✅ Cerrada | Dashboard del estudiante con datos reales. |
| UV-11 | 🟡 Con observaciones | Funciona; faltan detalles de mantenibilidad. |
| UV-12 | ✅ Cerrada | Inscripción y validación de créditos. |
| UV-13 | 🟡 Con observaciones | Registro de notas funciona; se añadió validación de propiedad. |
| UV-16 | 🟡 Avance anticipado | Jitsi y notificaciones operativos en su alcance actual. |
| UV-18 | 🟡 Avance anticipado | Notificaciones por evento y polling. |
| UV-19 | 🔴 Iniciada | Solo base de mensajes; no presentar como terminada. |
| UV-31 | 🟡 Avance anticipado | Dashboard del directivo con datos reales; falta segmentación. |

**Regla de oro:** decir “implementado y verificado” solo para lo que está en la tabla como ✅; para el resto, “avance” o “en corrección”.

---

## 5. Advertencia sobre los diagramas PNG

Los PNG de `docs/png` son diagramas **conceptuales** de una versión anterior. Sirven para explicar la idea, pero algunos nombres no coinciden con el esquema v8:

| Diagrama anterior | Esquema v8 actual |
|---|---|
| `roles` | `usuarios.rol` |
| `tareas` | `actividades` |
| `calificaciones` | `entregas.nota` / `inscripciones.nota_*` |
| `asistencias` | `asistencia` |
| `id_usuario`, `correo` | `idUsuario`, `email` |

Para una defensa técnica, usar:
- `fuentes/diagrama_v8.puml` — modelo de dominio correcto.
- `fuentes/secuencia_login_v8.puml` — login con tiempo constante.
- `fuentes/diagramas_grupo_f.puml` — 6 diagramas v8 (arquitectura, modelos, controladores, vistas, casos de uso y ER).

Si el docente pregunta por la diferencia, responder:
> “Los PNG son la vista conceptual del diseño; el código y el script SQL v8 son la fuente de verdad. La versión 8 agregó facultades, carreras, configuración y grabaciones, y pasó de 17 a 19 tablas.”

---

## 6. Preguntas probables y respuestas cortas

| Pregunta | Respuesta |
|---|---|
| ¿Por qué incluyen HUs del Sprint 3? | Porque vencen el 13 de noviembre y ya tienen avance; el corte es de HUs vencidas y próximas, no de sprints completos. |
| ¿Entonces no terminaron los sprints 1 y 2? | No se declaran sprints completos: se reportan 13 HUs vencidas con su avance real. 7 cerradas, 5 con observaciones y 1 corregida en la copia. |
| ¿UV-9 está lista? | En el proyecto original estaba en corrección; en la copia ejecutable ya se probó la creación de los cuatro roles. |
| ¿Qué falta? | UV-19 (mensajería del directivo), reportes/exportación, pruebas E2E y revisión OWASP del Sprint 4. |
| ¿Cómo se ejecuta? | Apache y MySQL en XAMPP; abrir `http://localhost/univirtual-avance/`. |
| ¿Por qué React por CDN? | Para evitar un build de Node en el entorno académico y mantener el despliegue simple en XAMPP. |

---

## 7. Dónde están los archivos

| Recurso | Ruta |
|---|---|
| Galería para proyectar | `06-anexos/diagramas/galeria.html` |
| PNG de los diagramas | `06-anexos/diagramas/` |
| Fuentes PlantUML | `06-anexos/diagramas/fuentes/` |
| PDF completo de diagramas | `...\univirtual\docs\Diagramas_UNI-VIRTUAL.pdf` |
| Diagramas v8 del repositorio | `diagrams/diagrama_v8.puml`, `diagrams/secuencia_login_v8.puml`, `diagrams/diagramas_grupo_f.puml` |
| Documento de arquitectura | `02-producto/arquitectura.md` |
| Modelo de datos | `02-producto/modelo-datos.md` |
| Matriz de HUs | `01-gestion/matriz-historias.md` |

---

## 8. Consejo de exposición

1. Empezar con el alcance: “no venimos a decir que los sprints están completos; venimos a mostrar el avance de las HUs vencidas y próximas a vencer”.
2. Mostrar primero los diagramas 1-3 (qué, cómo y dónde).
3. Bajar a los flujos 4-6 (login, inscripción, notas) con la demo abierta.
4. Cerrar con el Gantt y la tabla de estados.
5. No prometer lo que está en 🟡 o 🔴; decir “esto es el avance y esto es lo que falta”.

---

## 9. Reparto de la exposición por integrante

**Regla de la exposición: exponen los seis.** Cada integrante presenta al menos un bloque, opera una demo o explica una decisión, y queda con un área de preguntas. Nadie de espectador.

**Equipo completo (6 integrantes)** — roles Scrum del acta y responsabilidades del cronograma (`cronograma2_UNI-VIRTUAL.xlsx`, columna “Responsable”):

| Rol | Integrante | Enfoque según el cronograma |
|---|---|---|
| Product Owner | James Medina | Requerimientos, alcance, pruebas de funcionalidad, manual de usuario |
| Scrum Master | Angel Angulo | Ideación, alcance, implantación, manual de instalación |
| Developer | Edwin Carabali | Prototipo, arquitectura, capacitación |
| Desarrollador | Alexander Pineda | Codificación, módulo de gestión de usuarios |
| Desarrollador | Sebastián Chang | Módulos del prototipo, interfaz |
| Desarrollador | Yeison Montaño | Diseño, acceso a datos, pruebas de integración, diccionario de datos |

> **Aclaración:** la matriz de historias no asigna responsables individuales por diagrama; la asignación oficial vive en Jira. El reparto de abajo es **operativo para la sustentación** y se apoya en las responsabilidades del cronograma, no en una atribución histórica del tablero.

### 9.1 Reparto equitativo (6 integrantes)

Todos exponen al menos un diagrama y todos quedan entre **1:55 y 2:10**:

| Integrante | Bloques | Tiempo | Demo que opera | Respaldo |
|---|---|---:|---|---|
| James | Apertura y alcance (0:45) + Gantt y estados (0:55) + cierre (0:30) | 2:10 | — | Angel |
| Sebastián | Casos de uso (1:10) + Videollamada (0:55) | 2:05 | Sala Jitsi | Yeison |
| Edwin | Arquitectura (1:10) + Calificación (0:55) | 2:05 | Calificar entrega | Alexander |
| Yeison | Modelo ER (1:10) + Entrega de actividad (0:55) | 2:05 | Subir archivo | Edwin |
| Alexander | Login (1:25) + demo (0:30) | 1:55 | Login con los 4 roles | Edwin |
| Angel | Inscripción (1:25) + demo (0:30) | 1:55 | Bloqueo por créditos | James |

**Diferencia máxima:** 15 segundos entre el que más y el que menos expone.
**Total:** 12:15 de contenido + 0:30 de frases de cierre + transiciones ≈ **13:15**.

**Por qué así:**

- James abre y cierra porque es el PO, y el Gantt vuelve a ser alcance: no repite tema.
- Alexander y Angel tienen los dos bloques más largos (login e inscripción) y por eso no llevan un segundo diagrama.
- Yeison toma la entrega porque explica dónde se guarda el archivo y cómo se consulta; Edwin cierra el ciclo con la calificación, que es donde estuvo la corrección.
- Sebastián une los dos frentes de interfaz: casos de uso y videollamada.

### 9.2 Orden de intervención

| # | Bloque | Expone | Tiempo |
|---|---|---|---:|
| 1 | Apertura y alcance | James | 0:45 |
| 2 | Casos de uso | Sebastián | 1:10 |
| 3 | Arquitectura | Edwin | 1:10 |
| 4 | Modelo ER | Yeison | 1:10 |
| 5 | Login (UV-8) + demo | Alexander | 1:55 |
| 6 | Inscripción (UV-12) + demo | Angel | 1:55 |
| 7 | Entrega de actividad (UV-11) | Yeison | 0:55 |
| 8 | Calificación y notificación (UV-13) | Edwin | 0:55 |
| 9 | Videollamada (UV-16) | Sebastián | 0:55 |
| 10 | Gantt y estados de las 17 HUs | James | 0:55 |
| 11 | Cierre | James conduce; 5 s por integrante | 1:00 |

Ningún expositor repite seguido: la secuencia rota James → Sebastián → Edwin → Yeison → Alexander → Angel → Yeison → Edwin → Sebastián → James.

**Regla de transición:** quien termina dice “te dejo con…”, entrega el teclado y se queda al lado para responder preguntas de su bloque.

### 9.3 Versión de 10 minutos

Si el docente limita el tiempo, se recorta proporcionalmente (unos 20 s por bloque) y se mantiene el reparto:

| Integrante | Bloques | Tiempo |
|---|---|---:|
| James | Apertura (0:40) + Gantt y estados (0:45) + cierre (0:25) | 1:50 |
| Sebastián | Casos de uso (1:00) + Videollamada (0:40) | 1:40 |
| Edwin | Arquitectura (1:00) + Calificación (0:40) | 1:40 |
| Yeison | Modelo ER (1:00) + Entrega (0:40) | 1:40 |
| Alexander | Login (1:05) + demo (0:20) | 1:25 |
| Angel | Inscripción (1:05) + demo (0:20) | 1:25 |

**Total:** ~9:40. Se conservan los seis con su bloque; solo se acortan las demos y las explicaciones, no se elimina a nadie.

### 9.4 Quién responde qué

| Área de preguntas | Responde | Respaldo |
|---|---|---|
| Alcance, fechas y priorización | James | Angel |
| Proceso, evidencias y criterios de aceptación | Angel | James |
| Arquitectura, API y código | Edwin | Alexander |
| Calificación, permisos y `urlArchivo` | Edwin | Alexander |
| Base de datos, tablas y diccionario | Yeison | Edwin |
| Almacenamiento de entregas y materiales | Yeison | Edwin |
| Interfaz, React y experiencia | Sebastián | Edwin |
| Videollamadas y Jitsi | Sebastián | Yeison |
| Usuarios, roles y sesiones | Alexander | Edwin |
| Cronometrar y avisar los tiempos | Angel | — |
| Tener a mano matriz, resultados y ZIP | Angel | Sebastián |

### 9.5 Todos saben todo (mínimo común)

Que todos expongan no significa que cada uno sepa solo su parte. Antes de la exposición, los seis deben poder responder sin leer:

1. ¿Cuál es el alcance real y por qué no se declaran sprints completos?
2. ¿Cuántas HUs están cerradas, con observaciones, en corrección e iniciadas?
3. ¿Qué se corrigió en la copia ejecutable (UV-9 y `urlArchivo`)?
4. ¿Cómo se abre el sistema y con qué usuarios?
5. ¿Qué falta para el cierre (UV-19, reportes, pruebas E2E)?
6. ¿Por qué React por CDN y no un build de Node?

Las respuestas cortas están en la sección 6 de este documento.

### 9.6 Checklist 10 minutos antes

1. Apache y MySQL arriba; `http://localhost/univirtual-avance/` responde.
2. Sesión cerrada y pestañas listas: login, estudiante, profesor, directivo.
3. `galeria.html` abierta en el proyector y probada en modo pantalla completa.
4. Usuarios de prueba verificados: `admin@`, `james@`, `edwin@`, `diana@` con `123456`.
5. Cada expositor tiene su diagrama, su demo y su frase de cierre.
6. Acordar quién dice la frase de alcance al inicio: “no venimos a decir que los sprints están completos”.
7. Ensayo completo una vez, con cronómetro y transiciones incluidas.
8. Nadie lee diapositivas ni el celular: cada bloque se dice de memoria con el diagrama de apoyo.

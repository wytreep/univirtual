# Guion de James Medina — apertura, Gantt/estados y cierre

**Rol:** Product Owner
**Tiempo total:** 2:10 (apertura 0:45 + Gantt y estados 0:55 + cierre 0:30)
**Versión de 10 minutos:** 1:50 (apertura 0:40 + Gantt 0:45 + cierre 0:25)

---

## 0. Antes de salir

- [ ] Saber de memoria: **17 historias = 13 vencidas + 4 próximas**.
- [ ] Saber de memoria: **8 cerradas, 5 con observaciones; del Sprint 3, UV-19 cerrada y tres con avance**.
- [ ] Tener abiertas: `Figura_16_gantt_sprints.png`, `01-gestion/matriz-historias.md` y la tabla de estados.
- [ ] Frase de alcance ensayada: “no venimos a decir que los sprints están completos”.
- [ ] Acordar con Sebastián la transición: “te dejo con Sebastián, que muestra qué hace el sistema”.

---

## 1. Apertura y alcance — 0:45

**Qué proyectar:** portada del documento o `00-resumen-ejecutivo.md`.
**Qué decir, textual:**

> “Buenos días. Somos el equipo de UNI-VIRTUAL, la plataforma de educación virtual de la Institución Universitaria Antonio José Camacho.
>
> Antes de mostrar pantallas quiero ser preciso con el alcance: **no venimos a decir que los sprints uno y dos están completos**. Venimos a mostrar el avance real de las diecisiete historias que ya vencieron o que vencen el trece de noviembre: trece vencidas y cuatro próximas.
>
> Cada una tiene estado, evidencia y porcentaje. Y si algo está en corrección, lo decimos.
>
> Arrancamos con qué hace el sistema.”

**Transición:**

> “Te dejo con Sebastián, que les muestra qué hace el sistema y para quién.”

**Regla:** no leer la diapositiva; mirar al auditorio y decir los números sin titubear.

---

## 2. Gantt y estados de las 17 historias — 0:55

**Qué proyectar:** `Figura_16_gantt_sprints.png` y, al lado, la tabla de estados de la sección 4.
**Qué decir, textual:**

> “Este es el cronograma. El corte del treinta y uno de octubre cerró los sprints uno y dos; el sprint tres vence el trece de noviembre.
>
> Por eso el corte no es por sprint completo, sino **por historia y por fecha**: de las trece vencidas, ocho están cerradas y cinco tienen observaciones no bloqueantes. La UV-9, que estaba en corrección, ya quedó corregida y probada en el proyecto original y en la copia.
>
> De las cuatro próximas, **UV-19 ya está cerrada**: el directivo envía comunicados a su facultad y el destinatario los recibe en su bandeja y como notificación. UV-16 y UV-18 están al ochenta y cinco por ciento, y UV-31 al setenta y cinco.
>
> Eso es lo que hay. Lo que falta no lo vamos a maquillar.”

**Si preguntan en medio:** “Eso lo respondo al final con la matriz; sigo con las fechas para no perder el hilo.”

---

## 3. Cierre — 0:30

**Qué proyectar:** tabla de estados (sección 4) y, si se puede, la matriz abierta.

> “Para cerrar: de las diecisiete historias, **siete cerradas, cinco con observaciones, una corregida y cuatro próximas con avance medido**.
>
> Lo que falta está identificado: mensajería del directivo, reportes y pruebas de extremo a extremo. No lo escondemos.
>
> Una frase por integrante y terminamos.”

**Ronda de frases (5 segundos cada uno):**

| Integrante | Frase |
|---|---|
| Sebastián | “Los casos de uso y la videollamada muestran que la interfaz cubre el flujo completo.” |
| Edwin | “La arquitectura y la calificación están implementadas y verificadas en la copia.” |
| Yeison | “El modelo de datos soporta las diecinueve tablas y el almacenamiento de entregas.” |
| Alexander | “El login y los cuatro roles funcionan y están probados.” |
| Angel | “La inscripción valida créditos y la matriz deja la evidencia de cada historia.” |
| James | “Y lo pendiente está a la vista. Gracias.” |

---

## 4. Tabla de estados (para proyectar en el bloque 2 y en el cierre)

| Estado | Historias | Cantidad |
|---|---|---:|
| ✅ Cerradas | UV-8, UV-9, UV-10, UV-12, UV-25, UV-26, UV-29, UV-32 | 8 |
| 🟡 Con observaciones | UV-11, UV-13, UV-27, UV-28, UV-30 | 5 |
| ✅/🔵 Próximas (13 nov) | UV-19 (100%), UV-16 (85%), UV-18 (85%), UV-31 (75%) | 4 |
| **Total** | | **17** |

**Sprints:** Sprint 1 del 1 al 16 de octubre · Sprint 2 del 19 al 30 de octubre · Sprint 3 del 2 al 13 de noviembre.

---

## 5. Preguntas que le corresponden (alcance y fechas)

| Pregunta | Respuesta corta |
|---|---|
| ¿Por qué incluyen historias del Sprint 3? | Porque vencen el 13 de noviembre y ya tienen avance. El corte es por historia, no por sprint. |
| ¿Entonces no terminaron los sprints 1 y 2? | No se declaran completos. Se reportan las 13 historias vencidas con su estado real. |
| ¿Cuántas historias son y cuántas cerraron? | 17: 13 vencidas y 4 próximas. Cerradas 8, con observaciones 5; del Sprint 3, UV-19 cerrada y tres con avance. |
| ¿UV-9 está lista? | Sí. Quedó corregida y probada en el proyecto original y en la copia: crea los cuatro roles. |
| ¿Qué falta? | Los reportes del directivo (UV-31), pruebas E2E, revisión OWASP y la formalización de casos del Sprint 3. |
| ¿Cómo sé que la matriz no es maquillaje? | Cada fila tiene archivo, evidencia y prueba; y hay una copia ejecutable para verificarlo en vivo. |
| ¿Por qué 17 y no todas? | Son las vencidas y las próximas a vencer. Las demás están fuera de este corte y se ven en el Sprint 4. |
| ¿Las fechas son reales? | Sí: Sprint 1 del 1 al 16 de octubre, Sprint 2 del 19 al 30, Sprint 3 del 2 al 13 de noviembre. |
| ¿Qué pasa si no llegan al 13 de noviembre? | UV-19 ya se cerró el 19 de noviembre; el riesgo restante es UV-31 (reportes) y la formalización de pruebas. |

---

## 6. Errores que no puede cometer

1. Decir “sprints completos” o “todo terminado”. El mensaje es **avance real**.
2. Decir que UV-9 solo funciona en la copia: ya está corregida en el original y en la copia.
3. Presentar UV-19 como avance parcial: está cerrada, con bandeja, envío por facultad y notificación.
4. Leer el Gantt línea por línea. Se muestran las tres fechas y los cuatro números.
5. Prometer fechas nuevas que el equipo no acordó.

---

## 7. Versión de 10 minutos

| Bloque | Tiempo | Recorte |
|---|---:|---|
| Apertura | 0:40 | Quitar la presentación del equipo; entrar directo al alcance. |
| Gantt y estados | 0:45 | Decir solo: 13 vencidas, 8 cerradas, 5 con observaciones; del Sprint 3, UV-19 cerrada y tres con avance. |
| Cierre | 0:25 | Una sola frase de balance y la ronda de 5 segundos. |
| **Total** | **1:50** | |

---

## 8. Archivos que respaldan su parte

| Recurso | Ruta |
|---|---|
| Gantt | `06-anexos/diagramas/Figura_16_gantt_sprints.png` |
| Matriz de historias | `01-gestion/matriz-historias.md` |
| Resumen ejecutivo | `00-resumen-ejecutivo.md` |
| Galería para proyectar | `06-anexos/diagramas/galeria.html` |
| Copia ejecutable | `http://localhost/univirtual-avance/` |

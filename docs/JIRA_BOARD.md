# UNI-VIRTUAL — Jira Board (Sprint 5)
**Proyecto:** UV | **Herramienta:** Jira Software | **Metodología:** Scrum

---

## EPICS

| Epic | Código | Estado |
|------|--------|--------|
| Autenticación y Seguridad | UV-EP1 | ✅ Completada |
| Paneles de Usuario | UV-EP2 | ✅ Completada |
| Gestión Académica | UV-EP3 | ✅ Completada |
| Jerarquía y Roles | UV-EP4 | ✅ Completada |
| Comunicación en Tiempo Real | UV-EP5 | ✅ Completada |
| Videollamadas | UV-EP6 | ✅ Completada |
| Reportes y Exportación | UV-EP7 | ✅ Completada |

---

## SPRINT 5 — Activo (23 Mar → 4 Abr 2026)

### ✅ DONE

| ID | Historia | Puntos | Responsable |
|----|----------|--------|-------------|
| UV-101 | Jerarquía de roles: admin, directivo, profesor, estudiante | 5 | Edwin |
| UV-102 | Panel directivo con estadísticas de facultad | 8 | Edwin |
| UV-103 | 13 carreras en 4 facultades en la BD | 2 | Edwin |
| UV-104 | Créditos mínimos por materia + validación al inscribir | 5 | Edwin |
| UV-105 | Fix: foro — estado de mensaje por foro (no compartido) | 3 | Edwin |
| UV-106 | Fix: notificaciones con clic navega a URL de destino | 3 | Edwin |
| UV-107 | Notificaciones polling 30s (tiempo real simulado) | 3 | Edwin |
| UV-108 | Fix: login — tiempo constante anti timing-attack | 2 | Edwin |
| UV-109 | Sección Configuración: cambiar nombre y contraseña | 5 | Edwin |
| UV-110 | Fix: accesos rápidos del profesor con navegación funcional | 2 | Edwin |
| UV-111 | Asistencia por fecha de clase con selector | 3 | Edwin |
| UV-112 | Fix: inscripciones — filtrar estudiantes ya inscritos | 3 | Edwin |
| UV-113 | Notificaciones del profesor con polling y clic-to-navigate | 3 | Edwin |

### ✅ Completada

| ID | Historia | Puntos | Responsable |
|----|----------|--------|-------------|
| UV-114 | Grabaciones de videollamadas (upload manual) | 8 | Pendiente |
| UV-115 | Sistema de notificaciones push (WebSocket o SSE) | 13 | Pendiente |
| UV-116 | Panel de configuración global para admin | 5 | Pendiente |

### 📋 TO DO (Sprint 6)

| ID | Historia | Prioridad | Puntos |
|----|----------|-----------|--------|
| UV-117 | Módulo de mensajería directa entre usuarios | Media | 8 |
| UV-118 | Calendario académico integrado | Media | 5 |
| UV-119 | Integración Jitsi con grabación automática | Alta | 13 |
| UV-120 | Exportar calificaciones a Excel desde el admin | Media | 3 |
| UV-121 | Módulo de evaluaciones y rúbricas | Baja | 13 |

---

## CRITERIOS DE ACEPTACIÓN — Sprint 5

### UV-104 — Créditos mínimos
**DADO QUE** un administrador intenta inscribir un estudiante con 40 créditos aprobados en una materia que requiere 48  
**CUANDO** hace clic en "+ Inscribir"  
**ENTONCES** el sistema bloquea la inscripción y muestra: "El estudiante necesita 48 créditos aprobados, tiene 40"

### UV-107 — Notificaciones tiempo real
**DADO QUE** un profesor sube un material nuevo  
**CUANDO** han pasado máximo 30 segundos desde la acción  
**ENTONCES** todos los estudiantes inscritos ven el badge de notificación actualizado sin recargar la página

### UV-109 — Configuración
**DADO QUE** un usuario (cualquier rol) va a Configuración  
**CUANDO** cambia su contraseña ingresando la actual correctamente  
**ENTONCES** la contraseña se actualiza y puede iniciar sesión con la nueva contraseña inmediatamente

---

## VELOCIDAD DEL EQUIPO

| Sprint | Comprometido | Completado | Velocidad |
|--------|-------------|------------|-----------|
| Sprint 1 | 21 | 21 | 100% |
| Sprint 2 | 34 | 28 | 82% |
| Sprint 3 | 34 | 31 | 91% |
| Sprint 4 | 34 | 34 | 100% |
| Sprint 5 | 48 | 35 | 73% (en curso) |

**Promedio:** 91% | **Velocidad proyectada Sprint 6:** 38 puntos

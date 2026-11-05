# UV-31 — Dashboard del Directivo

| Campo | Valor |
|---|---|
| Sprint | 3 (2–13 nov 2026) |
| Vence | 13 de noviembre de 2026 |
| Épica | Clases y comunicación |
| Prioridad | Alta |
| Estado | 🔵 En avance anticipado |
| Responsable | Equipo UNI-VIRTUAL |
| Avance | **75%** |

> Historia del Sprint 3 con **avance anticipado fuerte**: el panel del directivo ya consume datos reales y ofrece perfil y contraseña; lo faltante es la segmentación por facultad y la mensajería (UV-19).

---

## 1. Historia

**Como** directivo  
**Quiero** un panel con el resumen de mi facultad (estudiantes, profesores, materias)  
**Para** monitorear la operación académica sin pedir reportes manuales.

---

## 2. Alcance propuesto (criterios al iniciar el Sprint 3)

1. El directivo ve resumen por facultad.
2. Puede listar profesores y estudiantes de su facultad.
3. Puede enviar mensajes a estudiantes y profesores (UV-19).
4. Perfil y contraseña configurados desde su panel.
5. Los datos reflejan su rol jerárquico (restricción por rol).

---

## 3. Avance actual en el código

| Pieza | Archivo | Lo que ya hace |
|---|---|---|
| API del directivo | `api/v1/directivo.php` | `resumen`, `profesores`, `estudiantes`, `materias`, `actualizarPerfil`, `cambiarPassword`. |
| Panel React | `pages/directivo/dashboard.php` | Carga con `<?= BASE_URL ?>`, navegación por pestañas y polling de notificaciones. |
| Estadísticas globales | `api/v1/stats.php` | Totales de usuarios, estudiantes, profesores, materias, entregas. |
| Acceso por rol (UV-8) | `includes/auth.php` | El directivo entra y no accede a paneles ajenos. |

---

## 4. Lo logrado / Pendiente

**Lo logrado**
- Panel operativo con resumen, listados, perfil y contraseña, usando datos reales.
- API JSON con contrato uniforme; se consume con credenciales de sesión.
- Polling de notificaciones (comparte la base de UV-18).

**Pendiente**
- Segmentación de resultados por la facultad del directivo.
- Sección de mensajes (dependencia directa de UV-19).
- Gráficas de desempeño por facultad (reportes, afecta también UV-20).

---

## 5. Cómo demostrarlo en la demo

1. Ingresar como directivo (`diana@univirtual.edu.co`).
2. Mostrar resumen con cifras reales de la base `univirtual_sprints12`.
3. Cambiar el nombre del directivo desde el panel (perfil).

---

## 6. Avance

**75%.** El panel y su API funcionan con datos reales. Los pendientes agrupan dos familias: segmentación por facultad y mensajería. El resto se cierra en el Sprint 3.

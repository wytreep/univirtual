# Reporte de Tests — SCRUM-124
**Fecha:** 2026-04-24  
**Suite:** `tests/test_univirtual.py`  
**Framework:** Python 3.13 + Selenium 4.41 + pytest 9.0.3  
**Entorno:** XAMPP local — http://localhost/univirtual

---

## Resultado: ✅ 23/23 PASSED

**Tiempo total:** 3 minutos 6 segundos

---

## Detalle por clase

| Clase | Descripción | Tests | Resultado |
|---|---|---|---|
| TestLogin | Autenticación por rol y bloqueo cruzado | 5 | ✅ 5/5 |
| TestAdmin | Panel admin, sidebar, navegación usuarios | 3 | ✅ 3/3 |
| TestProfesor | Dashboard, materias, sidebar, configuración | 4 | ✅ 4/4 |
| TestEstudiante | Panel, banner, nav, configuración | 5 | ✅ 5/5 |
| TestDirectivo | Panel, sidebar, resumen facultad, configuración | 4 | ✅ 4/4 |
| TestVideollamadas | Tab visible (SCRUM-118), formato link Jitsi | 2 | ✅ 2/2 |

---

## Bug corregido durante la ejecución

**Archivo:** `tests/test_univirtual.py` — líneas 62, 142, 312  
**Problema:** Contraseña `"1234567"` (7 caracteres) en vez de `"123456"` para james@univirtual.edu.co  
**Impacto:** 6 tests fallaban en cascada — TestLogin::test_login_profesor + todos los TestProfesor + TestVideollamadas  
**Fix:** Reemplazado `"1234567"` → `"123456"` en las 3 ocurrencias  

---

## Cobertura verificada

- Login de 4 roles (admin, profesor, estudiante, directivo)
- Bloqueo de acceso cruzado entre roles
- Carga correcta de los 4 dashboards
- Sidebar y navegación por sección
- Configuración de perfil (SCRUM-121)
- Tab de Videollamadas en aula del profesor (SCRUM-118)
- Formato correcto de links Jitsi Meet

---

## Comando de ejecución

```bash
"C:\Users\carab\AppData\Local\Programs\Python\Python313\python.exe" -m pytest tests/test_univirtual.py -v
```

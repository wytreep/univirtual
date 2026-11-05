# UV-30 — Layout base React y consumo de API

| Campo | Valor |
|---|---|
| Sprint | 1 |
| Vence | 30 de octubre de 2026 |
| Épica | EPIC-02 Dashboards |
| Prioridad | Alta |
| Estado | 🟡 Con observaciones |
| Entregable | `pages/*/dashboard.php`, `includes/View.php`, `assets/css/panel.css` |

---

## 1. Historia

**Como** usuario del sistema  
**Quiero** un layout común con navegación y datos en vivo  
**Para** usar el panel de mi rol sin recargar la página y con una experiencia consistente.

---

## 2. Criterios de aceptación

```gherkin
Escenario: Layout con sesión
  Dado un usuario autenticado
  Cuando abre su dashboard
  Entonces ve su nombre, iniciales y menú lateral
  Y el layout es consistente con los demás roles

Escenario: Consumo de API
  Dado un componente React
  Cuando solicita datos
  Entonces usa fetch con credenciales
  Y muestra carga, error y datos vacíos

Escenario: Navegación sin recarga
  Dado el panel abierto
  Cuando cambio de sección
  Entonces el contenido se actualiza sin recargar la página
```

---

## 3. Implementación

### 3.1 Base PHP — `includes/View.php`

Aplica **Template Method**:

1. `requireLogin($rolRequerido)`.
2. Extrae datos de `$_SESSION['usuario']`.
3. Calcula iniciales.
4. Inyecta `window.__S` con `sessionJS()`.
5. Carga React por CDN con `reactCDN()`.
6. Deja `render()` a cada subclase.

| Subclase | Rol |
|---|---|
| `pages/admin/dashboard.php` | Administrador |
| `pages/directivo/dashboard.php` | Directivo |
| `pages/profesor/dashboard.php` | Profesor |
| `pages/estudiante/dashboard.php` | Estudiante |

### 3.2 Frontend

| Elemento | Implementación |
|---|---|
| React | 18 UMD por CDN (`react.development.js`, `react-dom.development.js`). |
| JSX | Babel Standalone en el navegador. |
| Gráficas | Chart.js 4.4 por CDN. |
| Estilos | `assets/css/panel.css`. |
| Estado de sesión | `window.__S`. |
| API | `window.API = '/univirtual/api/v1'`. |
| Peticiones | `fetch` con `credentials: 'include'`. |

### 3.3 Secciones por rol

| Rol | Secciones |
|---|---|
| Admin | Usuarios, materias, inscripciones, reportes, estadísticas. |
| Directivo | Facultad, docentes, estudiantes, indicadores. |
| Profesor | Materias, materiales, actividades, entregas, notas, foros, videollamadas. |
| Estudiante | Materias, tareas, notas, asistencia, foros, videollamadas, configuración. |

---

## 4. Evidencia

| Evidencia | Ubicación |
|---|---|
| Template Method | `includes/View.php` |
| Dashboards | `pages/admin/dashboard.php`, `pages/directivo/dashboard.php`, `pages/profesor/dashboard.php`, `pages/estudiante/dashboard.php` |
| Estilos | `assets/css/panel.css` |
| Sesión inyectada | `window.__S` en cada dashboard |
| Consumo de API | `fetch(`${window.API}/...`, { credentials:'include' })` |

### Checklist

- [x] Layout con sidebar, header y avatar.
- [x] Sesión inyectada sin llamada extra.
- [x] React y Chart.js por CDN.
- [x] Navegación por estado sin recarga.
- [x] Consumo de API con credenciales.
- [x] Estilos centralizados en `panel.css`.
- [ ] Componentes compartidos reales (header, sidebar, cards).
- [ ] URL de API relativa al `BASE_URL`.
- [ ] Build de producción en lugar de Babel en el navegador.

---

## 5. Observaciones

| # | Observación | Impacto | Recomendación |
|---|---|---|---|
| 1 | La URL `/univirtual/api/v1` está escrita a mano en los cuatro dashboards. | Alto: si cambia la carpeta o el dominio, se rompe todo. | Inyectar `BASE_URL` desde PHP en `window.API`. |
| 2 | Se usa `react.development.js`, no la build de producción. | Medio: más peso y advertencias en consola. | Cambiar a `react.production.min.js` o compilar. |
| 3 | Babel transforma JSX en el navegador. | Medio: coste de arranque y riesgo de seguridad. | Precompilar o usar `htm`/`createElement`. |
| 4 | Header, sidebar y tarjetas se repiten en cada dashboard. | Alto: cuatro copias que divergen. | Extraer componentes compartidos (`Layout`, `Sidebar`, `Card`). |
| 5 | Hay estilos en línea mezclados con `panel.css`. | Medio: difícil mantener el tema. | Mover a clases CSS. |
| 6 | El dashboard de estudiante fija el semestre `2026-I` y la semana `2026-01-19`. | Alto: datos incorrectos fuera de ese periodo. | Leer periodo desde configuración o API. |
| 7 | No hay manejo uniforme de errores de red. | Medio: pantallas en blanco. | Componente `ErrorState` y reintento. |

---

## 6. Avance

**Avance: 85%.** Quedan: URLs absolutas con prefijo `/univirtual` en varios dashboards, semestre y semana fijados en el panel del estudiante, y React en modo desarrollo por CDN.

**Cumple parcialmente.** El layout existe, es consistente entre roles, inyecta la sesión y consume la API con credenciales. La navegación sin recarga funciona.

No se marca completo porque no hay componentes compartidos reales: cada dashboard duplica estructura y estilos, y las URLs y el periodo están codificados. Es deuda de mantenibilidad, no ausencia de funcionalidad; se resuelve en el refactor posterior.

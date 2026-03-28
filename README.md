# UNI-VIRTUAL
Plataforma web de educación virtual — Institución Universitaria Antonio José Camacho

## Stack
- **Backend:** PHP 8 + MySQL 8 (puerto 3307) — Arquitectura POO 3 capas (MVC)
- **Frontend:** React 18 (SPA embebida vía Babel CDN)
- **Servidor:** Apache / XAMPP
- **Testing:** Selenium 4 + Python 3
- **Videollamadas:** Jitsi Meet (sin cuenta requerida)

## Requisitos
- XAMPP 8.x (PHP 8.0+, MySQL 8, Apache)
- MySQL en puerto **3307** (no el 3306 por defecto)
- Python 3.10+ con Selenium 4 (para tests)

## Instalación

### 1. Clonar el repositorio
```bash
git clone https://github.com/uniajc/univirtual.git
cd univirtual
```

### 2. Configurar la base de datos
1. Abrir **phpMyAdmin** → http://localhost/phpmyadmin
2. Crear base de datos: `univirtual`
3. Importar el schema: `config/database_v8.sql`
4. Importar los hashes correctos: `config/fix_passwords_v8.sql`

> ⚠️ **IMPORTANTE:** Ejecutar `fix_passwords_v8.sql` DESPUÉS de `database_v8.sql`.  
> Sin este paso, ningún usuario puede iniciar sesión.

### 3. Configurar la conexión
Copiar el archivo de configuración de ejemplo:
```bash
cp config/config.example.php config/config.php
```
Editar `config/config.php` con tus credenciales MySQL:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');   // Puerto XAMPP por defecto
define('DB_NAME', 'univirtual');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'http://localhost/univirtual');
```

### 4. Copiar en XAMPP
Copiar la carpeta del proyecto en `C:\xampp\htdocs\univirtual\`

### 5. Verificar
Abrir: http://localhost/univirtual  
Deberías ver la pantalla de login.

## Credenciales de prueba
| Email | Contraseña | Rol |
|-------|-----------|-----|
| admin@univirtual.edu.co | 123456 | Administrador |
| diana@univirtual.edu.co | 123456 | Directivo |
| james@univirtual.edu.co | 123456 | Profesor |
| edwin@univirtual.edu.co | 123456 | Estudiante |

## Estructura del proyecto
```
univirtual/
├── api/v1/              → Controladores API REST (PHP POO)
│   ├── Controller.php         (clase abstracta base — Template Method)
│   ├── usuarios.php           (UsuarioController)
│   ├── materias.php           (MateriaController)
│   ├── inscripciones.php      (InscripcionesController)
│   ├── profesor.php           (ProfesorController)
│   ├── estudiante.php         (EstudianteController)
│   ├── stats.php              (StatsController)
│   ├── videollamadas.php      (VideoLlamadaController — SCRUM-119)
│   └── reportes.php           (ReportesController)
├── assets/css/
│   └── panel.css              (CSS variables compartidas — sistema de diseño)
├── config/
│   ├── config.php             (NO en git — ver config.example.php)
│   ├── config.example.php     (plantilla de configuración)
│   ├── database_v8.sql        (schema MySQL — 17 tablas)
│   └── fix_passwords_v8.sql   (hashes correctos de 123456)
├── includes/
│   ├── auth.php               (requireLogin, requireNivel)
│   └── View.php               (clase abstracta base — SCRUM-123)
├── models/                    → Modelos PHP POO
│   ├── Model.php              (abstracto, Singleton PDO)
│   ├── Usuario.php
│   ├── Estudiante.php
│   ├── Profesor.php
│   └── ...
├── pages/                     → Vistas PHP (extienden View)
│   ├── auth/login.php
│   ├── admin/dashboard.php    (AdminView)
│   ├── directivo/dashboard.php (DirectivoView)
│   ├── profesor/dashboard.php  (ProfesorView)
│   └── estudiante/dashboard.php (EstudianteView)
├── uploads/                   (NO en git — archivos de usuarios)
└── tests_selenium_v8.py       (suite de testing automatizado)
```

## Flujo de trabajo Git (Scrum)

### Ramas
- `main` — código estable, solo merges desde develop
- `develop` — integración continua del sprint
- `feature/SCRUM-NNN-descripcion` — una rama por issue

### Crear una rama de feature
```bash
git checkout develop
git pull origin develop
git checkout -b feature/SCRUM-119-videollamada-controller-poo
```

### Convención de commits
```
feat(SCRUM-NNN): descripción corta en español
fix(SCRUM-NNN): descripción del bug corregido
docs(SCRUM-NNN): cambios en documentación
refactor(SCRUM-NNN): refactorización sin cambio de comportamiento
test(SCRUM-NNN): agregar o modificar tests
```

### Ejemplos
```bash
git commit -m "feat(SCRUM-118): crear VideoLlamadaController con fix 400"
git commit -m "fix(SCRUM-097): corregir hash bcrypt en fix_passwords_v8.sql"
git commit -m "refactor(SCRUM-119): mover videollamadas.php a clase POO"
git commit -m "docs(SCRUM-000): actualizar Grupo_F_v3.docx con diagramas PlantUML"
```

### Merge a develop
```bash
git checkout develop
git merge feature/SCRUM-119-videollamada-controller-poo
git push origin develop
```

## Tags de versión
```bash
git tag -a v8.0.0 -m "Sprint 5: bugs críticos resueltos, 4 roles, Jitsi Meet"
git push origin v8.0.0
```

## Ejecutar tests Selenium
```bash
pip install selenium webdriver-manager requests
python tests_selenium_v8.py              # Con navegador visible
python tests_selenium_v8.py --headless   # Sin ventana (CI/CD)
```

> ⚠️ Ejecutar `fix_passwords_v8.sql` antes de los tests.

## Equipo
| Rol | Integrante |
|-----|-----------|
| Product Owner | James Medina |
| Scrum Master | Ángel Angulo |
| Developer | Edwin Carabali |

**Jira:** https://medinajames666.atlassian.net/jira/software/projects/SCRUM/boards

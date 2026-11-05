# Manual de instalación y ejecución local — UNI-VIRTUAL

**Entorno objetivo:** Windows + XAMPP  
**Stack:** Apache 2.4 · PHP 8.2 · MySQL 8 · React 18 (CDN)  
**Fecha:** 29 de octubre de 2026

---

## 1. Requisitos previos

| Componente | Versión mínima | Verificación |
|---|---|---|
| XAMPP | 8.2 | `C:\xampp\xampp-control.exe` |
| PHP | 8.0 | `C:\xampp\php\php.exe -v` |
| MySQL | 8.0 | Panel de XAMPP |
| Apache | 2.4 | Panel de XAMPP |
| Git | 2.30 | `git --version` |
| Navegador | Chrome/Edge/Firefox actual | — |

> En esta instalación MySQL usa el puerto **3307**, no 3306.

---

## 2. Obtener el proyecto

```bash
cd C:\xampp\htdocs
git clone https://github.com/wytreep/univirtual.git
cd univirtual
git checkout develop
```

Si ya existe la carpeta, verificar:

```bash
git status
git branch
```

La rama de integración es `develop`. `main` se reserva para entregas.

---

## 3. Configurar la conexión

### 3.1 Crear `config/config.php`

Copiar la plantilla incluida en esta carpeta:

```bash
copy avance-historias\05-entrega\plantillas\config.example.php config\config.php
```

Editar los valores:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'univirtual');
define('BASE_URL', 'http://localhost/univirtual');
define('BASE_PATH', realpath(__DIR__ . '/..'));
```

> `config/config.php` está en `.gitignore`; nunca se sube al repositorio.

### 3.2 Verificar la extensión PDO MySQL

En `C:\xampp\php\php.ini` deben estar activas:

```ini
extension=pdo_mysql
extension=mysqli
```

Reiniciar Apache después de cambiar `php.ini`.

---

## 4. Crear la base de datos

### Opción A — phpMyAdmin

1. Abrir `http://localhost/phpmyadmin`.
2. Pestaña **Importar**.
3. Seleccionar `config/database_v8.sql`.
4. Ejecutar.
5. Verificar que aparecen 19 tablas.

### Opción B — MySQL Workbench

1. Conectar a `127.0.0.1:3307`, usuario `root`.
2. Abrir `config/database_v8.sql`.
3. Ejecutar todo (`Ctrl + Shift + Enter`).

### Opción C — Línea de comandos

```bash
C:\xampp\mysql\bin\mysql.exe -u root -P 3307 < config\database_v8.sql
```

---

## 5. Regenerar los hashes de contraseña

El script v8 incluye un hash, pero si el login falla o se importó otra versión, regenerar con PHP del proyecto:

```bash
C:\xampp\php\php.exe -r "echo password_hash('123456', PASSWORD_BCRYPT), PHP_EOL;"
```

Copiar el hash resultante y ejecutar la plantilla:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -P 3307 univirtual < avance-historias\05-entrega\plantillas\fix_passwords_v8.sql
```

O hacerlo manualmente en phpMyAdmin:

```sql
UPDATE usuarios
SET password = '$2y$10$...'
WHERE email IN (
  'admin@univirtual.edu.co',
  'james@univirtual.edu.co',
  'maria@univirtual.edu.co',
  'edwin@univirtual.edu.co',
  'angel@univirtual.edu.co',
  'laura@univirtual.edu.co',
  'carlos@univirtual.edu.co',
  'diana@univirtual.edu.co',
  'roberto@univirtual.edu.co'
);
```

> No usar hashes generados por herramientas externas. Ver ADR-009.

---

## 6. Permisos de escritura

**Windows/XAMPP:** normalmente no requiere cambios.

**Linux/macOS:**

```bash
chmod -R 755 uploads/
chmod -R 777 uploads/materiales uploads/entregas uploads/enunciados uploads/avatares
```

Verificar que exista `uploads/.htaccess` para impedir ejecución de scripts.

---

## 7. Configuración recomendada de PHP

En `C:\xampp\php\php.ini`:

```ini
upload_max_filesize = 200M
post_max_size       = 200M
max_execution_time  = 300
max_input_time      = 300
memory_limit        = 256M
display_errors      = On    ; solo desarrollo
```

Reiniciar Apache.

---

## 8. Iniciar la aplicación

1. Abrir XAMPP Control Panel.
2. Iniciar **Apache** y **MySQL**.
3. Navegar a `http://localhost/univirtual`.
4. Debe aparecer la pantalla de login.

### Cuentas de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Admin | `admin@univirtual.edu.co` | `123456` |
| Directivo | `diana@univirtual.edu.co` | `123456` |
| Profesor | `james@univirtual.edu.co` | `123456` |
| Profesor | `maria@univirtual.edu.co` | `123456` |
| Profesor | `roberto@univirtual.edu.co` | `123456` |
| Estudiante | `edwin@univirtual.edu.co` | `123456` |
| Estudiante | `angel@univirtual.edu.co` | `123456` |
| Estudiante | `laura@univirtual.edu.co` | `123456` |
| Estudiante | `carlos@univirtual.edu.co` | `123456` |

---

## 9. Verificación rápida

| # | Verificación | Resultado esperado |
|---:|---|---|
| 1 | `http://localhost/univirtual` | Redirige a login. |
| 2 | Login admin | Abre panel admin. |
| 3 | Login estudiante | Abre panel estudiante. |
| 4 | Abrir `/api/v1/estudiante.php?action=dashboard` sin sesión | HTTP 401 JSON. |
| 5 | Abrir con sesión de estudiante | JSON `status: ok`. |
| 6 | Revisar `config/config.php` | Puerto 3307. |
| 7 | Ejecutar `php -l` sobre un archivo | `No syntax errors detected`. |

---

## 10. Ejecutar pruebas

```bash
pip install selenium webdriver-manager requests pytest
python -m pytest tests/ -v
```

Para la suite histórica:

```bash
python -m pytest tests/test_univirtual.py -v
```

> Requiere Apache y MySQL activos y los hashes correctos.

---

## 11. Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| “Error de conexión MySQL” | MySQL apagado o puerto incorrecto | Iniciar MySQL; revisar `DB_PORT=3307`. |
| “Correo o contraseña incorrectos” con datos correctos | Hash de otra versión | Regenerar con `password_hash` (sección 5). |
| Página en blanco | Error PHP oculto | Activar `display_errors` y revisar `logs/error.log`. |
| CSS roto | `BASE_URL` incorrecto | Ajustar a la ruta real. |
| No se ven imágenes/materiales | Archivo no existe en `uploads/` | Verificar ruta y permisos. |
| `php` no se reconoce en PowerShell | PHP no está en PATH | Usar `C:\xampp\php\php.exe` o agregarlo al PATH. |
| API responde 401 | Cookie de sesión no enviada | Usar `credentials: 'include'`. |
| API responde 403 | Rol sin permiso | Probar con el rol correcto. |
| Error al subir archivos grandes | Límites de `php.ini` | Ajustar `upload_max_filesize` y `post_max_size`. |

---

## 12. Estructura mínima esperada

```text
univirtual/
├── api/v1/                 Controladores JSON
├── assets/css/             main.css, panel.css
├── config/
│   ├── config.php          Local (no versionado)
│   └── database_v8.sql     Esquema
├── includes/               auth.php, View.php, utilidades
├── models/                 Modelos por entidad
├── pages/                  Vistas por rol
├── uploads/                Archivos (no versionados)
├── tests/                  Suites automatizadas
└── index.php               Punto de entrada
```

---

## 13. Checklist de instalación

- [ ] XAMPP con Apache y MySQL activos.
- [ ] Proyecto en `C:\xampp\htdocs\univirtual`.
- [ ] `config/config.php` creado y con puerto 3307.
- [ ] Base `univirtual` importada con 19 tablas.
- [ ] Hashes regenerados.
- [ ] `uploads/` con permisos de escritura.
- [ ] Login de los cuatro roles probado.
- [ ] API respondiendo JSON.
- [ ] Suite de pruebas ejecutada.

---

**Fin del manual.**

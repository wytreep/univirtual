-- =====================================================================
--  UNI-VIRTUAL — Reparación de contraseñas de usuarios semilla
--  Base: univirtual (MySQL 8)
--  Uso:
--    mysql -u root -P 3307 univirtual < fix_passwords_v8.sql
--
--  Contraseña resultante para TODOS los usuarios: 123456
--  Hash bcrypt generado con password_hash('123456', PASSWORD_BCRYPT)
--  Coste 10, prefijo $2y$ (PHP).
--
--  ADVERTENCIA: script solo para entorno local/académico.
--  No ejecutar en producción.
-- =====================================================================

USE univirtual;

-- 1. Verificar el estado actual (opcional, solo lectura)
SELECT idUsuario, email, rol, LEFT(password, 7) AS prefijo_hash
  FROM usuarios
 ORDER BY idUsuario;

-- 2. Restablecer la contraseña de todos los usuarios semilla
UPDATE usuarios
   SET password = '$2y$10$5NZo3/bxwBeEkzKsG8v3M.SQa.5OSDm9Z3U7GF6LzpbaI//UXwYFq'
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

-- 3. Verificación: debe afectar 9 filas y mostrar el mismo prefijo
SELECT idUsuario, email, rol, LEFT(password, 7) AS prefijo_hash
  FROM usuarios
 WHERE email LIKE '%@univirtual.edu.co'
 ORDER BY idUsuario;

-- 4. Comprobación de login (ejecutar desde PHP, no desde SQL):
--    php -r "var_dump(password_verify('123456',
--      '$2y$10$5NZo3/bxwBeEkzKsG8v3M.SQa.5OSDm9Z3U7GF6LzpbaI//UXwYFq'));"
--    Resultado esperado: bool(true)

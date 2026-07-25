-- ═══════════════════════════════════════════════════════
--  UNI-VIRTUAL — Fix BUG-001: Activar usuarios inactivos
--  Ejecutar en phpMyAdmin → base 'univirtual'
-- ═══════════════════════════════════════════════════════

-- 1. Ver estado actual
SELECT idUsuario, nombre, email, rol, activo
FROM usuarios
ORDER BY rol, nombre;

-- 2. Activar Maria, Angel y Carlos
UPDATE usuarios
SET activo = 1
WHERE email IN (
    'maria@univirtual.edu.co',
    'angel@univirtual.edu.co',
    'carlos@univirtual.edu.co'
);

-- 3. Verificar corrección
SELECT email, activo
FROM usuarios
WHERE email IN (
    'maria@univirtual.edu.co',
    'angel@univirtual.edu.co',
    'carlos@univirtual.edu.co'
);
-- Todos deben mostrar activo = 1

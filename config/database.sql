-- ============================================================
-- UNI-VIRTUAL — Script SQL completo v5
-- Institución Universitaria Antonio José Camacho
-- Motor: MySQL 8.0+ / MariaDB 10.6+
--
-- Instrucciones:
--   1. Abrir phpMyAdmin o MySQL Workbench
--   2. Ejecutar este script completo (Ctrl+Shift+Enter)
--   3. Todas las cuentas usan contraseña: 123456
-- ============================================================

CREATE DATABASE IF NOT EXISTS univirtual
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE univirtual;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS
  notificaciones, asistencia, entregas, actividades,
  mensajes, foros, materiales, aulas_virtuales,
  inscripciones, materias, profesores, estudiantes, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ── USUARIOS ──────────────────────────────────────────────────
CREATE TABLE usuarios (
  idUsuario  INT          AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  rol        ENUM('estudiante','profesor','admin') NOT NULL DEFAULT 'estudiante',
  activo     TINYINT(1)   DEFAULT 1,
  creado_en  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rol (rol)
);

-- ── ESTUDIANTES ───────────────────────────────────────────────
CREATE TABLE estudiantes (
  idEstudiante INT         AUTO_INCREMENT PRIMARY KEY,
  idUsuario    INT         NOT NULL UNIQUE,
  codigoEst   VARCHAR(20) NOT NULL UNIQUE,
  semestre    INT          DEFAULT 1,
  programa    VARCHAR(100) DEFAULT 'Ingeniería de Sistemas',
  FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario) ON DELETE CASCADE
);

-- ── PROFESORES ────────────────────────────────────────────────
CREATE TABLE profesores (
  idProfesor  INT         AUTO_INCREMENT PRIMARY KEY,
  idUsuario   INT         NOT NULL UNIQUE,
  codigoProf  VARCHAR(20) NOT NULL UNIQUE,
  departamento VARCHAR(100) DEFAULT 'Sistemas',
  FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario) ON DELETE CASCADE
);

-- ── MATERIAS ──────────────────────────────────────────────────
CREATE TABLE materias (
  idMateria   INT          AUTO_INCREMENT PRIMARY KEY,
  idProfesor  INT          NOT NULL,
  nombre      VARCHAR(150) NOT NULL,
  codigo      VARCHAR(20)  NOT NULL UNIQUE,
  creditos    INT          DEFAULT 3,
  semestre    INT          DEFAULT 1,
  descripcion TEXT,
  color       VARCHAR(7)   DEFAULT '#2560a8',
  activa      TINYINT(1)   DEFAULT 1,
  FOREIGN KEY (idProfesor) REFERENCES profesores(idProfesor) ON DELETE CASCADE,
  INDEX idx_profesor (idProfesor)
);

-- ── INSCRIPCIONES ─────────────────────────────────────────────
CREATE TABLE inscripciones (
  idInscripcion INT           AUTO_INCREMENT PRIMARY KEY,
  idEstudiante  INT           NOT NULL,
  idMateria     INT           NOT NULL,
  inscrito_en   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  nota_parcial1 DECIMAL(3,2)  DEFAULT NULL,
  nota_parcial2 DECIMAL(3,2)  DEFAULT NULL,
  nota_talleres DECIMAL(3,2)  DEFAULT NULL,
  nota_final    DECIMAL(3,2)  DEFAULT NULL,
  FOREIGN KEY (idEstudiante) REFERENCES estudiantes(idEstudiante) ON DELETE CASCADE,
  FOREIGN KEY (idMateria)    REFERENCES materias(idMateria)     ON DELETE CASCADE,
  UNIQUE KEY uq_insc (idEstudiante, idMateria)
);

-- ── AULAS VIRTUALES ───────────────────────────────────────────
CREATE TABLE aulas_virtuales (
  idAula      INT       AUTO_INCREMENT PRIMARY KEY,
  idMateria   INT       NOT NULL UNIQUE,
  descripcion TEXT,
  creada_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idMateria) REFERENCES materias(idMateria) ON DELETE CASCADE
);

-- ── MATERIALES ────────────────────────────────────────────────
-- urlArchivo: ruta relativa desde raíz del proyecto
--             (ej: uploads/materiales/archivo.pdf)
--             o URL externa si tipo='otro' y es un enlace
CREATE TABLE materiales (
  idMaterial    INT          AUTO_INCREMENT PRIMARY KEY,
  idAula        INT          NOT NULL,
  idProfesor    INT          NOT NULL,
  titulo        VARCHAR(200) NOT NULL,
  descripcion   TEXT,
  tipo          ENUM('pdf','video','presentacion','otro') DEFAULT 'pdf',
  urlArchivo    VARCHAR(500) NOT NULL,
  nombreOriginal VARCHAR(255),
  tamano        BIGINT       DEFAULT 0,
  descargas     INT          DEFAULT 0,
  subido_en     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idAula)      REFERENCES aulas_virtuales(idAula) ON DELETE CASCADE,
  FOREIGN KEY (idProfesor)  REFERENCES profesores(idProfesor),
  INDEX idx_aula (idAula)
);

-- ── ACTIVIDADES ───────────────────────────────────────────────
CREATE TABLE actividades (
  idActividad  INT           AUTO_INCREMENT PRIMARY KEY,
  idAula       INT           NOT NULL,
  titulo       VARCHAR(200)  NOT NULL,
  descripcion  TEXT,
  fechaEntrega DATETIME      NOT NULL,
  puntaje_max  DECIMAL(3,1)  DEFAULT 5.0,
  enunciado    VARCHAR(500)  DEFAULT NULL,
  creada_en    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idAula) REFERENCES aulas_virtuales(idAula) ON DELETE CASCADE,
  INDEX idx_aula_act (idAula)
);

-- ── ENTREGAS ──────────────────────────────────────────────────
CREATE TABLE entregas (
  idEntrega      INT          AUTO_INCREMENT PRIMARY KEY,
  idActividad    INT          NOT NULL,
  idEstudiante   INT          NOT NULL,
  archivoEntrega VARCHAR(500) NOT NULL,
  nombreOriginal VARCHAR(255),
  comentario     TEXT,
  nota           DECIMAL(3,2) DEFAULT NULL,
  feedback       TEXT         DEFAULT NULL,
  entregado_en   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idActividad)  REFERENCES actividades(idActividad)  ON DELETE CASCADE,
  FOREIGN KEY (idEstudiante) REFERENCES estudiantes(idEstudiante) ON DELETE CASCADE,
  UNIQUE KEY uq_entrega (idActividad, idEstudiante)
);

-- ── FOROS ─────────────────────────────────────────────────────
CREATE TABLE foros (
  idForo      INT          AUTO_INCREMENT PRIMARY KEY,
  idAula      INT          NOT NULL,
  titulo      VARCHAR(200) NOT NULL,
  descripcion TEXT,
  abierto     TINYINT(1)   DEFAULT 1,
  creado_en   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idAula) REFERENCES aulas_virtuales(idAula) ON DELETE CASCADE
);

-- ── MENSAJES (Patrón Composite: idPadre NULL = raíz) ─────────
CREATE TABLE mensajes (
  idMensaje   INT       AUTO_INCREMENT PRIMARY KEY,
  idForo      INT       NOT NULL,
  idUsuario   INT       NOT NULL,
  idPadre     INT       DEFAULT NULL,
  contenido   TEXT      NOT NULL,
  publicado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idForo)    REFERENCES foros(idForo)       ON DELETE CASCADE,
  FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario) ON DELETE CASCADE,
  FOREIGN KEY (idPadre)   REFERENCES mensajes(idMensaje) ON DELETE SET NULL,
  INDEX idx_foro (idForo)
);

-- ── ASISTENCIA ────────────────────────────────────────────────
CREATE TABLE asistencia (
  idAsistencia INT      AUTO_INCREMENT PRIMARY KEY,
  idMateria    INT      NOT NULL,
  idEstudiante INT      NOT NULL,
  fecha        DATE     NOT NULL,
  asistio      TINYINT(1) DEFAULT 0,
  FOREIGN KEY (idMateria)    REFERENCES materias(idMateria),
  FOREIGN KEY (idEstudiante) REFERENCES estudiantes(idEstudiante),
  UNIQUE KEY uq_asist (idMateria, idEstudiante, fecha),
  INDEX idx_materia_fecha (idMateria, fecha)
);

-- ── NOTIFICACIONES ────────────────────────────────────────────
CREATE TABLE notificaciones (
  idNotif    INT          AUTO_INCREMENT PRIMARY KEY,
  idUsuario  INT          NOT NULL,
  titulo     VARCHAR(200) NOT NULL,
  mensaje    TEXT         NOT NULL,
  tipo       ENUM('info','tarea','nota','foro','material','sistema') DEFAULT 'info',
  leida      TINYINT(1)   DEFAULT 0,
  url        VARCHAR(500) DEFAULT NULL,
  creado_en  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario) ON DELETE CASCADE,
  INDEX idx_usuario_leida (idUsuario, leida)
);


-- ============================================================
-- DATOS DE PRUEBA
-- Contraseña de todos: 123456
-- Hash bcrypt generado con password_hash('123456', PASSWORD_DEFAULT)
-- ============================================================

INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Admin Sistema',    'admin@univirtual.edu.co',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('James Medina',     'james@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor'),
('Maria Angulo',     'maria@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor'),
('Edwin Carabali',   'edwin@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante'),
('Angel Angulo',     'angel@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante'),
('Laura Córdoba',    'laura@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante'),
('Carlos Pino',      'carlos@univirtual.edu.co',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante');

-- idUsuario: 1=admin, 2=james, 3=maria, 4=edwin, 5=angel, 6=laura, 7=carlos
INSERT INTO profesores (idUsuario, codigoProf, departamento) VALUES
(2, 'PROF-001', 'Ingeniería de Sistemas'),
(3, 'PROF-002', 'Ingeniería de Sistemas');

-- idProfesor: 1=james, 2=maria
INSERT INTO estudiantes (idUsuario, codigoEst, semestre, programa) VALUES
(4, 'EST-2024-001', 5, 'Ingeniería de Sistemas'),
(5, 'EST-2024-002', 5, 'Ingeniería de Sistemas'),
(6, 'EST-2024-003', 5, 'Ingeniería de Sistemas'),
(7, 'EST-2024-004', 5, 'Ingeniería de Sistemas');

-- idEstudiante: 1=edwin, 2=angel, 3=laura, 4=carlos
INSERT INTO materias (idProfesor, nombre, codigo, creditos, semestre, descripcion, color) VALUES
(1, 'Ingeniería de Software II',  'IS-201', 4, 5, 'Patrones de diseño, arquitectura de software y metodologías ágiles. Proyecto: plataforma educativa UNI-VIRTUAL.', '#2462b0'),
(2, 'Bases de Datos Avanzadas',   'BD-301', 3, 5, 'MySQL avanzado: procedimientos almacenados, triggers, optimización de consultas e índices.', '#1a7a48');

-- idMateria: 1=IS-201, 2=BD-301
INSERT INTO aulas_virtuales (idMateria, descripcion) VALUES
(1, 'Aula virtual del curso Ingeniería de Software II — Semestre 2026-I'),
(2, 'Aula virtual del curso Bases de Datos Avanzadas — Semestre 2026-I');

-- idAula: 1=IS-201, 2=BD-301
-- Inscripciones con notas parciales (nota_final NULL = en curso)
INSERT INTO inscripciones (idEstudiante, idMateria, nota_parcial1, nota_parcial2, nota_talleres) VALUES
(1, 1, 4.0, 3.8, 4.5),   -- edwin en IS-201
(1, 2, 3.8, 4.0, 4.2),   -- edwin en BD-301
(2, 1, 3.9, 3.5, 4.0),   -- angel en IS-201
(2, 2, 4.1, 3.9, 3.8),   -- angel en BD-301
(3, 1, 3.0, 2.8, 3.2),   -- laura en IS-201
(3, 2, 3.5, 3.2, 3.0),   -- laura en BD-301
(4, 1, 4.6, 4.8, 4.7),   -- carlos en IS-201
(4, 2, 4.5, 4.4, 4.7);   -- carlos en BD-301

-- Materiales (urlArchivo = ruta relativa desde raíz del proyecto)
INSERT INTO materiales (idAula, idProfesor, titulo, descripcion, tipo, urlArchivo, nombreOriginal, tamano) VALUES
(1, 1, 'Unidad 1 — Introducción a los Patrones de Diseño',
        'GoF: Patrones creacionales, estructurales y de comportamiento.',
        'pdf', 'uploads/materiales/u1_patrones.pdf', 'Unidad1_Patrones.pdf', 2048000),
(1, 1, 'Video — Patrón Singleton en PHP 8',
        'Implementación del Singleton para conexión PDO en nuestro proyecto.',
        'video', 'uploads/materiales/singleton_php8.mp4', 'Singleton_PHP8.mp4', 52428800),
(1, 1, 'Slides — Arquitectura MVC',
        'Presentación de la arquitectura de tres capas aplicada en UNI-VIRTUAL.',
        'presentacion', 'uploads/materiales/mvc_slides.pptx', 'ArquitecturaMVC.pptx', 3145728),
(2, 2, 'Guía — Procedimientos Almacenados MySQL',
        'Stored procedures, funciones y triggers en MySQL 8.',
        'pdf', 'uploads/materiales/guia_stored_procs.pdf', 'Guia_StoredProcedures.pdf', 1572864),
(2, 2, 'Enlace — Documentación oficial MySQL 8.0',
        'Referencia completa de MySQL 8 en dev.mysql.com.',
        'otro', 'https://dev.mysql.com/doc/refman/8.0/en/', '', 0);

-- Actividades
INSERT INTO actividades (idAula, titulo, descripcion, fechaEntrega, puntaje_max) VALUES
(1, 'Taller 1 — Diagrama de Clases UML',
    'Elaborar el diagrama de clases del sistema UNI-VIRTUAL usando PlantUML o draw.io. Incluir las relaciones entre todas las entidades y los patrones de diseño aplicados.',
    '2026-02-15 23:59:00', 5.0),
(1, 'Taller 2 — CRUD PHP 8 POO',
    'Implementar un módulo CRUD completo con PHP 8 POO (clases, interfaces, herencia). Entregar código fuente más informe técnico.',
    '2026-03-10 23:59:00', 5.0),
(1, 'Proyecto Final — UNI-VIRTUAL',
    'Entrega final del proyecto de aula: plataforma educativa funcional con al menos 5 módulos implementados, documentación y presentación.',
    '2026-06-01 23:59:00', 5.0),
(2, 'Taller SQL Avanzado',
    'JOINs complejos, subconsultas correlacionadas, procedimientos almacenados y optimización con EXPLAIN.',
    '2026-03-05 23:59:00', 5.0),
(2, 'Proyecto BD — Modelo E/R Normalizado',
    'Diseñar y normalizar (hasta 3FN) el modelo de base de datos del sistema asignado. Entregar script SQL completo con datos de prueba.',
    '2026-05-30 23:59:00', 5.0);

-- Entregas de los talleres vencidos (Taller 1 y Taller 2 IS-201, Taller SQL BD-301)
INSERT INTO entregas (idActividad, idEstudiante, archivoEntrega, nombreOriginal, comentario, nota, feedback) VALUES
(1, 1, 'uploads/entregas/t1_is201_edwin.pdf',   'DiagramaClases_Edwin.pdf',  'Incluye todos los patrones del proyecto.', 4.5, 'Excelente diagrama, buena representación de herencia.'),
(1, 2, 'uploads/entregas/t1_is201_angel.pdf',   'DiagramaUML_Angel.pdf',     'Hice el diagrama en draw.io.',             4.0, 'Buen trabajo, falta detallar las multiplicidades.'),
(1, 3, 'uploads/entregas/t1_is201_laura.pdf',   'ClasesLaura.pdf',           'Me costó la parte de interfaces.',         3.2, 'Falta incluir las interfaces del controlador.'),
(1, 4, 'uploads/entregas/t1_is201_carlos.pdf',  'UML_Carlos.pdf',            'Detallé los 3 patrones GOF usados.',       4.8, 'Sobresaliente. Muy completo y bien documentado.'),
(2, 1, 'uploads/entregas/t2_is201_edwin.zip',   'CRUD_PHP_Edwin.zip',        'CRUD con PDO y validaciones en JS.',       4.2, 'Buen código, agregar manejo de excepciones.'),
(2, 2, 'uploads/entregas/t2_is201_angel.zip',   'ModuloCRUD_Angel.zip',      'Usé el patrón Repository.',                4.3, 'Correcto, buen uso del patrón Repository.'),
(4, 1, 'uploads/entregas/tsql_bd_edwin.sql',    'SQLAvanzado_Edwin.sql',     'JOINs y 3 stored procedures.',             4.1, 'Correcto. Mejorar la optimización con índices.'),
(4, 3, 'uploads/entregas/tsql_bd_laura.sql',    'Consultas_Laura.sql',       'Completé los JOINs pero me costó SP.',     3.0, 'Falta implementar el trigger solicitado.'),
(4, 4, 'uploads/entregas/tsql_bd_carlos.sql',   'SQL_Carlos.sql',            'Incluí todos los ejercicios más optimización EXPLAIN.', 4.7, 'Excelente trabajo. Muy buena optimización.');

-- Foros
INSERT INTO foros (idAula, titulo, descripcion) VALUES
(1, 'Dudas — Ingeniería de Software II',   'Espacio para preguntas sobre el curso, talleres y proyecto final.'),
(1, 'Discusión — Proyecto UNI-VIRTUAL',    'Canal de discusión oficial del proyecto de semestre.'),
(2, 'Consultas SQL y MySQL Avanzado',       'Dudas sobre stored procedures, triggers y optimización.');

-- Mensajes con patrón Composite (idPadre NULL = raíz, int = respuesta)
INSERT INTO mensajes (idForo, idUsuario, idPadre, contenido) VALUES
-- Foro 1: Dudas IS-201
(1, 2,    NULL, 'Bienvenidos al foro de IS-II. Aquí resolvemos dudas sobre patrones de diseño, arquitectura y el proyecto. Revisen primero el material de la Unidad 1.'),
(1, 4,    NULL, '¿El patrón Singleton lo usamos solo para la conexión PDO o también para otros objetos del sistema?'),
(1, 2,    2,    'Edwin, principalmente para la conexión a base de datos con PDO. Para otros objetos, evalúa si realmente necesitas una única instancia. En nuestro proyecto lo aplicamos en config.php con la función db().'),
(1, 5,    2,    'Entendido profe, entonces los modelos no deben ser Singleton porque necesitamos múltiples instancias para diferentes registros, ¿correcto?'),
(1, 2,    4,    'Exacto Angel. Los modelos representan entidades de datos y pueden tener múltiples instancias. El Singleton aplica cuando necesitas un único punto de acceso global, como la conexión a BD.'),
-- Foro 2: Proyecto
(2, 2,    NULL, 'Hilo oficial del proyecto UNI-VIRTUAL. Aquí coordinamos avances, dudas de arquitectura y revisión de código. Sprint actual: Sprint 3 — Módulo Profesor.'),
(2, 4,    NULL, '¿El diagrama de clases del Taller 1 nos sirve como base para la documentación del proyecto final?'),
(2, 2,    7,    'Sí Edwin, actualícenlo para que refleje la arquitectura real implementada. Recuerden documentar los patrones: Singleton, Template Method y Composite.'),
-- Foro 3: BD Avanzada
(3, 3,    NULL, 'Bienvenidos al foro de BD Avanzadas. Compartan sus dudas sobre MySQL, stored procedures y la optimización de consultas del taller.'),
(3, 7,    NULL, '¿Los triggers pueden llamar a stored procedures dentro? Tengo un trigger ON INSERT en entregas y quiero notificar automáticamente al profesor.'),
(3, 3,    10,   'Carlos, sí es posible con CALL dentro del trigger. Sin embargo, hay restricciones: el SP no puede hacer commits ni rollbacks propios. Para notificaciones considera mejor manejarlo a nivel de aplicación en PHP.');

-- Asistencia — IS-201 (idMateria=1), 8 fechas de clase
INSERT INTO asistencia (idMateria, idEstudiante, fecha, asistio) VALUES
-- Edwin (1)
(1,1,'2026-01-20',1),(1,1,'2026-01-27',1),(1,1,'2026-02-03',0),
(1,1,'2026-02-10',1),(1,1,'2026-02-17',1),(1,1,'2026-02-24',1),
(1,1,'2026-03-03',1),(1,1,'2026-03-10',1),
-- Angel (2)
(1,2,'2026-01-20',1),(1,2,'2026-01-27',0),(1,2,'2026-02-03',1),
(1,2,'2026-02-10',1),(1,2,'2026-02-17',1),(1,2,'2026-02-24',0),
(1,2,'2026-03-03',1),(1,2,'2026-03-10',1),
-- Laura (3) — riesgo de pérdida por inasistencias
(1,3,'2026-01-20',0),(1,3,'2026-01-27',1),(1,3,'2026-02-03',0),
(1,3,'2026-02-10',0),(1,3,'2026-02-17',1),(1,3,'2026-02-24',1),
(1,3,'2026-03-03',0),(1,3,'2026-03-10',1),
-- Carlos (4) — asistencia perfecta
(1,4,'2026-01-20',1),(1,4,'2026-01-27',1),(1,4,'2026-02-03',1),
(1,4,'2026-02-10',1),(1,4,'2026-02-17',1),(1,4,'2026-02-24',1),
(1,4,'2026-03-03',1),(1,4,'2026-03-10',1);

-- Asistencia — BD-301 (idMateria=2), 5 fechas
INSERT INTO asistencia (idMateria, idEstudiante, fecha, asistio) VALUES
(2,1,'2026-01-22',1),(2,1,'2026-01-29',1),(2,1,'2026-02-05',1),(2,1,'2026-02-12',0),(2,1,'2026-02-19',1),
(2,2,'2026-01-22',1),(2,2,'2026-01-29',1),(2,2,'2026-02-05',0),(2,2,'2026-02-12',1),(2,2,'2026-02-19',1),
(2,3,'2026-01-22',0),(2,3,'2026-01-29',1),(2,3,'2026-02-05',1),(2,3,'2026-02-12',1),(2,3,'2026-02-19',0),
(2,4,'2026-01-22',1),(2,4,'2026-01-29',1),(2,4,'2026-02-05',1),(2,4,'2026-02-12',1),(2,4,'2026-02-19',1);

-- Notificaciones para los estudiantes
INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo, url) VALUES
-- Edwin (idUsuario=4)
(4, 'Nuevo material publicado',   'Prof. James Medina publicó: Unidad 1 — Patrones de Diseño', 'material', ''),
(4, 'Actividad por vencer',       'Taller 2 — CRUD PHP 8 POO vence el 10/03/2026',             'tarea',    ''),
(4, 'Calificación registrada',    'Tu entrega del Taller 1 fue calificada: 4.5/5.0',           'nota',     ''),
-- Angel (idUsuario=5)
(5, 'Nuevo material publicado',   'Prof. James Medina publicó: Video — Patrón Singleton en PHP 8', 'material', ''),
(5, 'Respuesta en el foro',       'Prof. James Medina respondió tu mensaje en "Dudas IS-II"',   'foro',     ''),
-- Laura (idUsuario=6)
(6, 'Alerta de asistencia',       'Tu asistencia en IS-201 está por debajo del 75% requerido',  'sistema',  ''),
(6, 'Calificación registrada',    'Tu entrega del Taller 1 fue calificada: 3.2/5.0',           'nota',     ''),
-- Carlos (idUsuario=7)
(7, 'Nueva actividad creada',     'Prof. James Medina publicó: Proyecto Final — UNI-VIRTUAL',   'tarea',    ''),
(7, 'Calificación registrada',    'Tu entrega Taller SQL fue calificada: 4.7/5.0',             'nota',     '');


-- ============================================================
-- VERIFICACIÓN — Conteo de registros por tabla
-- ============================================================
SELECT 'usuarios'        AS tabla, COUNT(*) AS registros FROM usuarios
UNION ALL SELECT 'estudiantes',    COUNT(*) FROM estudiantes
UNION ALL SELECT 'profesores',     COUNT(*) FROM profesores
UNION ALL SELECT 'materias',       COUNT(*) FROM materias
UNION ALL SELECT 'inscripciones',  COUNT(*) FROM inscripciones
UNION ALL SELECT 'aulas_virtuales',COUNT(*) FROM aulas_virtuales
UNION ALL SELECT 'materiales',     COUNT(*) FROM materiales
UNION ALL SELECT 'actividades',    COUNT(*) FROM actividades
UNION ALL SELECT 'entregas',       COUNT(*) FROM entregas
UNION ALL SELECT 'foros',          COUNT(*) FROM foros
UNION ALL SELECT 'mensajes',       COUNT(*) FROM mensajes
UNION ALL SELECT 'asistencia',     COUNT(*) FROM asistencia
UNION ALL SELECT 'notificaciones', COUNT(*) FROM notificaciones;


-- ══════════════════════════════════════════════════════════
-- VIDEOLLAMADAS — Módulo Jitsi Meet (RF9)
-- Agregado en Sprint 4
-- ══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS videollamadas (
  idVideoLlamada INT          AUTO_INCREMENT PRIMARY KEY,
  idMateria      INT          NOT NULL,
  idProfesor     INT          NOT NULL,
  titulo         VARCHAR(200) NOT NULL,
  roomName       VARCHAR(200) NOT NULL UNIQUE, -- ID único de sala en Jitsi
  descripcion    TEXT,
  programada_en  DATETIME     NOT NULL,
  duracion_min   INT          DEFAULT 90,
  activa         TINYINT(1)   DEFAULT 1,
  creada_en      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idMateria)   REFERENCES materias(idMateria)     ON DELETE CASCADE,
  FOREIGN KEY (idProfesor)  REFERENCES profesores(idProfesor)  ON DELETE CASCADE,
  INDEX idx_materia_video (idMateria),
  INDEX idx_programada (programada_en)
);

-- Datos de prueba para videollamadas
INSERT INTO videollamadas (idMateria, idProfesor, titulo, roomName, descripcion, programada_en, duracion_min) VALUES
(1, 1, 'Clase 9 — Patrón Composite y Observer',
    'univirtual-IS201-20260320-0800',
    'Revisión de los patrones Composite y Observer aplicados en el proyecto UNI-VIRTUAL.',
    '2026-03-20 08:00:00', 90),
(2, 2, 'Clase 8 — Stored Procedures y Triggers',
    'univirtual-BD301-20260320-1000',
    'Ejercicios prácticos de stored procedures con parámetros IN/OUT y triggers ON INSERT.',
    '2026-03-20 10:00:00', 90);

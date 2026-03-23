-- ═══════════════════════════════════════════════════════════════════
--  UNI-VIRTUAL — Database Schema v8
--  Institución Universitaria Antonio José Camacho
--
--  Novedades v8:
--    · Jerarquía de roles: admin > directivo > profesor > estudiante
--    · Tablas: facultades, carreras, configuracion, grabaciones
--    · Créditos mínimos por materia y acumulados por estudiante
--    · Tiempo constante en login (anti timing-attack via PHP)
--    · Más programas/carreras académicas
-- ═══════════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS grabaciones_videollamadas, videollamadas,
  notificaciones, asistencia, mensajes, foros, entregas,
  actividades, materiales, aulas_virtuales, inscripciones,
  materias, profesores, estudiantes, directivos,
  carreras, facultades, configuracion, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ── CONFIGURACIÓN GLOBAL ──────────────────────────────────────────
CREATE TABLE configuracion (
  clave    VARCHAR(80)  PRIMARY KEY,
  valor    TEXT         NOT NULL,
  tipo     ENUM('texto','numero','booleano','json') DEFAULT 'texto',
  descripcion VARCHAR(200),
  updated_at TIMESTAMP  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── USUARIOS ─────────────────────────────────────────────────────
-- Roles: admin (superadmin) | directivo | profesor | estudiante
CREATE TABLE usuarios (
  idUsuario  INT          AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL,
  email      VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  rol        ENUM('estudiante','profesor','directivo','admin') NOT NULL DEFAULT 'estudiante',
  activo     TINYINT(1)   DEFAULT 1,
  avatar     VARCHAR(255) DEFAULT NULL,
  creado_en  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rol (rol),
  INDEX idx_email (email)
);

-- ── FACULTADES ────────────────────────────────────────────────────
CREATE TABLE facultades (
  idFacultad   INT          AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(150) NOT NULL,
  codigo       VARCHAR(20)  NOT NULL UNIQUE,
  descripcion  TEXT,
  activa       TINYINT(1)   DEFAULT 1
);

-- ── CARRERAS / PROGRAMAS ──────────────────────────────────────────
CREATE TABLE carreras (
  idCarrera   INT          AUTO_INCREMENT PRIMARY KEY,
  idFacultad  INT          NOT NULL,
  nombre      VARCHAR(150) NOT NULL,
  codigo      VARCHAR(20)  NOT NULL UNIQUE,
  semestres   INT          DEFAULT 10,
  creditos_total INT       DEFAULT 160,
  activa      TINYINT(1)   DEFAULT 1,
  FOREIGN KEY (idFacultad) REFERENCES facultades(idFacultad) ON DELETE CASCADE
);

-- ── DIRECTIVOS ────────────────────────────────────────────────────
CREATE TABLE directivos (
  idDirectivo INT          AUTO_INCREMENT PRIMARY KEY,
  idUsuario   INT          NOT NULL UNIQUE,
  idFacultad  INT          NOT NULL,
  cargo       VARCHAR(100) DEFAULT 'Decano',
  FOREIGN KEY (idUsuario)  REFERENCES usuarios(idUsuario)   ON DELETE CASCADE,
  FOREIGN KEY (idFacultad) REFERENCES facultades(idFacultad) ON DELETE CASCADE
);

-- ── ESTUDIANTES ───────────────────────────────────────────────────
CREATE TABLE estudiantes (
  idEstudiante   INT         AUTO_INCREMENT PRIMARY KEY,
  idUsuario      INT         NOT NULL UNIQUE,
  idCarrera      INT         DEFAULT NULL,
  codigoEst      VARCHAR(20) NOT NULL UNIQUE,
  semestre       INT         DEFAULT 1,
  creditos_aprobados INT     DEFAULT 0,
  FOREIGN KEY (idUsuario)  REFERENCES usuarios(idUsuario) ON DELETE CASCADE,
  FOREIGN KEY (idCarrera)  REFERENCES carreras(idCarrera) ON DELETE SET NULL
);

-- ── PROFESORES ────────────────────────────────────────────────────
CREATE TABLE profesores (
  idProfesor   INT          AUTO_INCREMENT PRIMARY KEY,
  idUsuario    INT          NOT NULL UNIQUE,
  idFacultad   INT          DEFAULT NULL,
  codigoProf   VARCHAR(20)  NOT NULL UNIQUE,
  departamento VARCHAR(100) DEFAULT 'Sistemas',
  FOREIGN KEY (idUsuario)  REFERENCES usuarios(idUsuario) ON DELETE CASCADE,
  FOREIGN KEY (idFacultad) REFERENCES facultades(idFacultad) ON DELETE SET NULL
);

-- ── MATERIAS ──────────────────────────────────────────────────────
CREATE TABLE materias (
  idMateria        INT          AUTO_INCREMENT PRIMARY KEY,
  idProfesor       INT          NOT NULL,
  idCarrera        INT          DEFAULT NULL,
  nombre           VARCHAR(150) NOT NULL,
  codigo           VARCHAR(20)  NOT NULL UNIQUE,
  creditos         INT          DEFAULT 3,
  creditos_minimos INT          DEFAULT 0,   -- créditos mínimos para matricularse
  semestre         INT          DEFAULT 1,
  descripcion      TEXT,
  color            VARCHAR(7)   DEFAULT '#2462b0',
  activa           TINYINT(1)   DEFAULT 1,
  FOREIGN KEY (idProfesor) REFERENCES profesores(idProfesor) ON DELETE CASCADE,
  FOREIGN KEY (idCarrera)  REFERENCES carreras(idCarrera) ON DELETE SET NULL,
  INDEX idx_profesor (idProfesor)
);

-- ── INSCRIPCIONES ─────────────────────────────────────────────────
CREATE TABLE inscripciones (
  idInscripcion INT           AUTO_INCREMENT PRIMARY KEY,
  idEstudiante  INT           NOT NULL,
  idMateria     INT           NOT NULL,
  inscrito_en   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  nota_parcial1 DECIMAL(3,2)  DEFAULT NULL,
  nota_parcial2 DECIMAL(3,2)  DEFAULT NULL,
  nota_talleres DECIMAL(3,2)  DEFAULT NULL,
  nota_final    DECIMAL(3,2)  DEFAULT NULL,
  UNIQUE KEY uq_est_mat (idEstudiante, idMateria),
  FOREIGN KEY (idEstudiante) REFERENCES estudiantes(idEstudiante) ON DELETE CASCADE,
  FOREIGN KEY (idMateria)    REFERENCES materias(idMateria)    ON DELETE CASCADE
);

-- ── AULAS VIRTUALES ───────────────────────────────────────────────
CREATE TABLE aulas_virtuales (
  idAula     INT       AUTO_INCREMENT PRIMARY KEY,
  idMateria  INT       NOT NULL UNIQUE,
  creada_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idMateria) REFERENCES materias(idMateria) ON DELETE CASCADE
);

-- ── MATERIALES ────────────────────────────────────────────────────
CREATE TABLE materiales (
  idMaterial    INT          AUTO_INCREMENT PRIMARY KEY,
  idAula        INT          NOT NULL,
  idProfesor    INT          NOT NULL,
  titulo        VARCHAR(150) NOT NULL,
  tipo          ENUM('pdf','video','presentacion','otro') DEFAULT 'pdf',
  urlArchivo    VARCHAR(500) NOT NULL,
  nombreOriginal VARCHAR(255) DEFAULT NULL,
  descripcion   TEXT,
  descargas     INT          DEFAULT 0,
  subido_en     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idAula)     REFERENCES aulas_virtuales(idAula) ON DELETE CASCADE,
  FOREIGN KEY (idProfesor) REFERENCES profesores(idProfesor)  ON DELETE CASCADE
);

-- ── ACTIVIDADES ───────────────────────────────────────────────────
CREATE TABLE actividades (
  idActividad  INT          AUTO_INCREMENT PRIMARY KEY,
  idAula       INT          NOT NULL,
  titulo       VARCHAR(150) NOT NULL,
  descripcion  TEXT,
  fechaEntrega DATETIME     NOT NULL,
  puntaje_max  DECIMAL(4,2) DEFAULT 5.00,
  creada_en    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idAula) REFERENCES aulas_virtuales(idAula) ON DELETE CASCADE
);

-- ── ENTREGAS ──────────────────────────────────────────────────────
CREATE TABLE entregas (
  idEntrega      INT          AUTO_INCREMENT PRIMARY KEY,
  idActividad    INT          NOT NULL,
  idEstudiante   INT          NOT NULL,
  urlArchivo     VARCHAR(500) NOT NULL,
  nombreOriginal VARCHAR(255) DEFAULT NULL,
  comentario     TEXT,
  nota           DECIMAL(3,2) DEFAULT NULL,
  feedback       TEXT,
  entregado_en   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_act_est (idActividad, idEstudiante),
  FOREIGN KEY (idActividad)  REFERENCES actividades(idActividad)   ON DELETE CASCADE,
  FOREIGN KEY (idEstudiante) REFERENCES estudiantes(idEstudiante)  ON DELETE CASCADE
);

-- ── FOROS ─────────────────────────────────────────────────────────
CREATE TABLE foros (
  idForo      INT          AUTO_INCREMENT PRIMARY KEY,
  idAula      INT          NOT NULL,
  titulo      VARCHAR(150) NOT NULL,
  descripcion TEXT,
  abierto     TINYINT(1)   DEFAULT 1,
  creado_en   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idAula) REFERENCES aulas_virtuales(idAula) ON DELETE CASCADE
);

-- ── MENSAJES ──────────────────────────────────────────────────────
CREATE TABLE mensajes (
  idMensaje    INT       AUTO_INCREMENT PRIMARY KEY,
  idForo       INT       NOT NULL,
  idUsuario    INT       NOT NULL,
  idPadre      INT       DEFAULT NULL,
  contenido    TEXT      NOT NULL,
  publicado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idForo)    REFERENCES foros(idForo)       ON DELETE CASCADE,
  FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario) ON DELETE CASCADE,
  FOREIGN KEY (idPadre)   REFERENCES mensajes(idMensaje) ON DELETE SET NULL,
  INDEX idx_foro (idForo)
);

-- ── ASISTENCIA ────────────────────────────────────────────────────
CREATE TABLE asistencia (
  idAsistencia INT       AUTO_INCREMENT PRIMARY KEY,
  idMateria    INT       NOT NULL,
  idEstudiante INT       NOT NULL,
  fecha        DATE      NOT NULL,
  asistio      TINYINT(1) DEFAULT 0,
  observacion  VARCHAR(200) DEFAULT NULL,
  UNIQUE KEY uq_mat_est_fecha (idMateria, idEstudiante, fecha),
  FOREIGN KEY (idMateria)    REFERENCES materias(idMateria)        ON DELETE CASCADE,
  FOREIGN KEY (idEstudiante) REFERENCES estudiantes(idEstudiante)  ON DELETE CASCADE,
  INDEX idx_materia_fecha (idMateria, fecha)
);

-- ── NOTIFICACIONES ────────────────────────────────────────────────
CREATE TABLE notificaciones (
  idNotif    INT          AUTO_INCREMENT PRIMARY KEY,
  idUsuario  INT          NOT NULL,
  titulo     VARCHAR(150) NOT NULL,
  mensaje    TEXT         NOT NULL,
  tipo       ENUM('info','tarea','nota','foro','material','sistema','asistencia','videollamada') DEFAULT 'info',
  url        VARCHAR(500) DEFAULT '',
  leida      TINYINT(1)   DEFAULT 0,
  creado_en  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario) ON DELETE CASCADE,
  INDEX idx_usuario_leida (idUsuario, leida),
  INDEX idx_creado (creado_en)
);

-- ── VIDEOLLAMADAS ─────────────────────────────────────────────────
CREATE TABLE videollamadas (
  idVideoLlamada INT          AUTO_INCREMENT PRIMARY KEY,
  idMateria      INT          NOT NULL,
  idProfesor     INT          NOT NULL,
  titulo         VARCHAR(200) NOT NULL,
  roomName       VARCHAR(200) NOT NULL UNIQUE,
  descripcion    TEXT,
  programada_en  DATETIME     NOT NULL,
  duracion_min   INT          DEFAULT 90,
  estado         ENUM('programada','en_curso','finalizada') DEFAULT 'programada',
  activa         TINYINT(1)   DEFAULT 1,
  creada_en      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idMateria)  REFERENCES materias(idMateria)    ON DELETE CASCADE,
  FOREIGN KEY (idProfesor) REFERENCES profesores(idProfesor) ON DELETE CASCADE,
  INDEX idx_materia_video (idMateria),
  INDEX idx_programada (programada_en)
);

-- ── GRABACIONES ───────────────────────────────────────────────────
CREATE TABLE grabaciones_videollamadas (
  idGrabacion    INT          AUTO_INCREMENT PRIMARY KEY,
  idVideoLlamada INT          NOT NULL,
  urlGrabacion   VARCHAR(500) NOT NULL,
  duracion_seg   INT          DEFAULT 0,
  tamano_mb      DECIMAL(8,2) DEFAULT 0,
  subida_por     INT          NOT NULL,   -- idUsuario
  creada_en      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idVideoLlamada) REFERENCES videollamadas(idVideoLlamada) ON DELETE CASCADE,
  FOREIGN KEY (subida_por)     REFERENCES usuarios(idUsuario) ON DELETE CASCADE
);

-- ═══════════════════════════════════════════════════════════════════
--  DATOS DE PRUEBA
-- ═══════════════════════════════════════════════════════════════════

-- Configuración global
INSERT INTO configuracion (clave, valor, tipo, descripcion) VALUES
('nombre_institucion', 'Institución Universitaria Antonio José Camacho', 'texto', 'Nombre oficial de la institución'),
('semestre_activo',    '2026-I',  'texto',  'Semestre académico actual'),
('creditos_max_semestre', '20',   'numero', 'Máximo de créditos por semestre'),
('fecha_inicio_semestre', '2026-01-19', 'texto', 'Fecha de inicio del semestre'),
('notificaciones_email', '0',     'booleano','Enviar notificaciones por email'),
('url_jitsi',          'https://meet.jit.si', 'texto', 'Servidor Jitsi para videollamadas');

-- Facultades
INSERT INTO facultades (nombre, codigo, descripcion) VALUES
('Facultad de Ingeniería',           'FING',  'Programas de ingeniería y tecnología'),
('Facultad de Ciencias Empresariales','FCE',  'Administración, contaduría y afines'),
('Facultad de Ciencias de la Salud', 'FCS',   'Tecnologías en salud'),
('Facultad de Humanidades',          'FHUM',  'Comunicación, trabajo social y licenciaturas');

-- Carreras
INSERT INTO carreras (idFacultad, nombre, codigo, semestres, creditos_total) VALUES
-- Ingeniería
(1, 'Ingeniería de Sistemas',             'IS',   10, 160),
(1, 'Ingeniería Electrónica',             'IE',   10, 160),
(1, 'Tecnología en Sistematización de Datos','TSD', 6, 96),
(1, 'Tecnología en Electrónica Industrial','TEI',  6, 96),
-- Ciencias Empresariales
(2, 'Administración de Empresas',         'AE',   10, 160),
(2, 'Contaduría Pública',                 'CP',   10, 160),
(2, 'Tecnología en Gestión Empresarial',  'TGE',   6, 96),
(2, 'Mercadeo y Publicidad',              'MP',   10, 152),
-- Salud
(3, 'Tecnología en Atención Prehospitalaria','TAP', 6, 96),
(3, 'Tecnología en Regencia de Farmacia', 'TRF',   6, 96),
-- Humanidades
(4, 'Comunicación Social y Medios Digitales','CSMD',8,128),
(4, 'Trabajo Social',                     'TS',    8, 128),
(4, 'Licenciatura en Inglés',             'LI',    8, 128);

-- Usuarios (password: 123456 — hash bcrypt)
-- hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Admin Sistema',    'admin@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('James Medina',     'james@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor'),
('Maria Angulo',     'maria@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor'),
('Edwin Carabali',   'edwin@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante'),
('Angel Angulo',     'angel@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante'),
('Laura Córdoba',    'laura@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante'),
('Carlos Pino',      'carlos@univirtual.edu.co',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante'),
('Diana Salcedo',    'diana@univirtual.edu.co',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'directivo'),
('Roberto García',   'roberto@univirtual.edu.co',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor');
-- idUsuario: 1=admin, 2=james, 3=maria, 4=edwin, 5=angel, 6=laura, 7=carlos, 8=diana, 9=roberto

INSERT INTO profesores (idUsuario, idFacultad, codigoProf, departamento) VALUES
(2, 1, 'PROF-001', 'Ingeniería de Sistemas'),
(3, 1, 'PROF-002', 'Ingeniería de Sistemas'),
(9, 1, 'PROF-003', 'Ingeniería Electrónica');
-- idProfesor: 1=james, 2=maria, 3=roberto

INSERT INTO directivos (idUsuario, idFacultad, cargo) VALUES
(8, 1, 'Decana de Ingeniería');

INSERT INTO estudiantes (idUsuario, idCarrera, codigoEst, semestre, creditos_aprobados) VALUES
(4, 1, 'EST-2024-001', 5, 72),  -- Edwin, IS, semestre 5
(5, 1, 'EST-2024-002', 5, 70),  -- Angel, IS
(6, 1, 'EST-2024-003', 5, 68),  -- Laura, IS
(7, 1, 'EST-2024-004', 5, 75);  -- Carlos, IS

INSERT INTO materias (idProfesor, idCarrera, nombre, codigo, creditos, creditos_minimos, semestre, descripcion, color) VALUES
(1, 1, 'Ingeniería de Software II', 'IS-201', 4, 48, 5,
 'Patrones de diseño GOF, arquitectura MVC y desarrollo de software en equipo.', '#2462b0'),
(2, 1, 'Bases de Datos Avanzadas',  'BD-301', 4, 48, 5,
 'MySQL avanzado, stored procedures, triggers y optimización de consultas.', '#1a7a48'),
(1, 1, 'Redes de Computadores',     'RC-201', 3, 24, 4,
 'Protocolos TCP/IP, routing, switching y seguridad en redes.', '#d4a843'),
(2, 1, 'Programación Web',          'PW-301', 3, 36, 4,
 'HTML5, CSS3, JavaScript moderno, PHP y frameworks frontend.', '#c0392b'),
(3, 1, 'Electrónica Digital',       'ED-101', 3,  0, 2,
 'Circuitos digitales, álgebra booleana y diseño lógico.', '#6d28d9');

INSERT INTO aulas_virtuales (idMateria) VALUES (1),(2),(3),(4),(5);

INSERT INTO inscripciones (idEstudiante, idMateria) VALUES
(1,1),(1,2),(1,3),(1,4),
(2,1),(2,2),(2,3),
(3,1),(3,2),(3,4),
(4,1),(4,2),(4,3),(4,4);

-- Materiales
INSERT INTO materiales (idAula, idProfesor, titulo, tipo, urlArchivo, nombreOriginal, descripcion) VALUES
(1, 1, 'Unidad 1 — Patrones GOF',         'pdf',          'uploads/materiales/u1_patrones.pdf',        'Unidad1_Patrones.pdf', 'Patrones Creacionales, Estructurales y de Comportamiento'),
(1, 1, 'Clase 1 — Introducción MVC',       'presentacion', 'uploads/materiales/clase1_mvc.pptx',        'Clase1_MVC.pptx',      'Arquitectura Modelo-Vista-Controlador'),
(1, 1, 'Video — Singleton explicado',      'video',        'uploads/materiales/singleton.mp4',          'Singleton.mp4',        'Implementación del patrón Singleton en PHP'),
(2, 2, 'Unidad 1 — Stored Procedures',    'pdf',          'uploads/materiales/u1_sp.pdf',              'U1_SP.pdf',            'Sintaxis y uso de stored procedures en MySQL 8'),
(2, 2, 'Taller SP — Ejercicios',          'pdf',          'uploads/materiales/taller_sp.pdf',          'Taller_SP.pdf',        'Ejercicios prácticos de stored procedures');

-- Actividades
INSERT INTO actividades (idAula, titulo, descripcion, fechaEntrega, puntaje_max) VALUES
(1, 'Taller 1 — Patrones Creacionales',  'Implementar Singleton, Factory y Builder en PHP', '2026-04-01 23:59:00', 5.0),
(1, 'Taller 2 — Patrones Estructurales', 'Implementar Adapter, Decorator y Composite',       '2026-04-15 23:59:00', 5.0),
(2, 'Taller SQL — Stored Procedures',    'Crear 5 stored procedures con IN/OUT params',      '2026-04-05 23:59:00', 5.0),
(2, 'Taller SQL — Triggers',             'Implementar triggers ON INSERT y ON UPDATE',        '2026-04-20 23:59:00', 5.0);

-- Entregas
INSERT INTO entregas (idActividad, idEstudiante, urlArchivo, nombreOriginal, comentario, nota, feedback) VALUES
(1,1,'uploads/entregas/t1_is201_edwin.pdf','Patrones_Edwin.pdf','Implementé los 3 patrones con ejemplos reales.',4.5,'Excelente implementación, muy bien documentado.'),
(1,2,'uploads/entregas/t1_is201_angel.pdf','Patrones_Angel.pdf','Implementé Singleton y Factory correctamente.',4.0,'Buen trabajo, faltó Builder con más detalle.'),
(1,3,'uploads/entregas/t1_is201_laura.pdf','Patrones_Laura.pdf','Me costó la parte de interfaces.',3.2,'Falta incluir las interfaces del controlador.'),
(1,4,'uploads/entregas/t1_is201_carlos.pdf','UML_Carlos.pdf','Detallé los 3 patrones GOF usados.',4.8,'Sobresaliente. Muy completo y bien documentado.'),
(3,1,'uploads/entregas/tsql_bd_edwin.sql','SQL_Edwin.sql','Incluí los 5 stored procedures.',4.3,'Muy buena implementación. Buena lógica.'),
(3,4,'uploads/entregas/tsql_bd_carlos.sql','SQL_Carlos.sql','Incluí todos los ejercicios más optimización.',4.7,'Excelente trabajo. Muy buena optimización.');

-- Foros
INSERT INTO foros (idAula, titulo, descripcion) VALUES
(1, 'Dudas IS-II',                  'Preguntas sobre patrones de diseño, arquitectura y el proyecto final.'),
(1, 'Recursos Adicionales IS-II',   'Comparte links, artículos y recursos útiles para la materia.'),
(2, 'Dudas BD Avanzadas',           'Consultas sobre MySQL, stored procedures y triggers.');

-- Mensajes
INSERT INTO mensajes (idForo, idUsuario, idPadre, contenido) VALUES
(1, 2, NULL, 'Bienvenidos al foro de IS-II. Aquí resolvemos dudas sobre patrones de diseño, arquitectura y el proyecto. Revisen primero el material de la Unidad 1.'),
(1, 4, NULL, '¿Para el taller 1 podemos usar PHP o es obligatorio Java?'),
(1, 2,    2, 'Pueden usar PHP perfectamente. Lo importante es la correcta implementación del patrón, no el lenguaje.'),
(1, 5, NULL, '¿El patrón Builder aplica cuando tenemos objetos con muchos parámetros opcionales?'),
(1, 2,    4, 'Exacto, Angel. El Builder es ideal cuando un constructor tiene más de 4-5 parámetros opcionales.'),
(3, 3, NULL, 'Bienvenidos al foro de BD Avanzadas. Compartan sus dudas sobre MySQL, stored procedures y la optimización de consultas del taller.'),
(3, 7, NULL, '¿Un stored procedure puede llamar a otro SP dentro de él?'),
(3, 3,    7, 'Carlos, sí es posible con CALL dentro del trigger. Sin embargo, hay restricciones: el SP no puede hacer commits ni rollbacks propios.');

-- Asistencia
INSERT INTO asistencia (idMateria, idEstudiante, fecha, asistio) VALUES
-- IS-201
(1,1,'2026-01-20',1),(1,1,'2026-01-27',1),(1,1,'2026-02-03',1),(1,1,'2026-02-10',1),(1,1,'2026-02-17',1),(1,1,'2026-02-24',0),
(1,2,'2026-01-20',1),(1,2,'2026-01-27',1),(1,2,'2026-02-03',0),(1,2,'2026-02-10',1),(1,2,'2026-02-17',1),(1,2,'2026-02-24',1),
(1,3,'2026-01-20',1),(1,3,'2026-01-27',0),(1,3,'2026-02-03',0),(1,3,'2026-02-10',0),(1,3,'2026-02-17',1),(1,3,'2026-02-24',1),
(1,4,'2026-01-20',1),(1,4,'2026-01-27',1),(1,4,'2026-02-03',1),(1,4,'2026-02-10',1),(1,4,'2026-02-17',1),(1,4,'2026-02-24',1),
-- BD-301
(2,1,'2026-01-21',1),(2,1,'2026-01-28',1),(2,1,'2026-02-04',1),(2,1,'2026-02-11',0),
(2,2,'2026-01-21',1),(2,2,'2026-01-28',0),(2,2,'2026-02-04',1),(2,2,'2026-02-11',1),
(2,3,'2026-01-21',1),(2,3,'2026-01-28',1),(2,3,'2026-02-04',0),(2,3,'2026-02-11',0),
(2,4,'2026-01-21',1),(2,4,'2026-01-28',1),(2,4,'2026-02-04',1),(2,4,'2026-02-11',1);

-- Notificaciones
INSERT INTO notificaciones (idUsuario, titulo, mensaje, tipo, url) VALUES
(4,'Nuevo material disponible','James Medina subió "Video — Singleton explicado" en IS-201','material','/univirtual/pages/estudiante/dashboard.php'),
(5,'Nuevo material disponible','James Medina subió "Video — Singleton explicado" en IS-201','material','/univirtual/pages/estudiante/dashboard.php'),
(4,'Entrega calificada','Tu entrega "Taller 1" en IS-201 fue calificada: 4.5/5','nota','/univirtual/pages/estudiante/dashboard.php'),
(7,'Entrega calificada','Tu entrega "Taller 1" en IS-201 fue calificada: 4.8/5','nota','/univirtual/pages/estudiante/dashboard.php'),
(5,'Respuesta en el foro','Prof. James Medina respondió tu mensaje en "Dudas IS-II"','foro','/univirtual/pages/estudiante/dashboard.php'),
(4,'Nueva actividad','James Medina publicó "Taller 2 — Patrones Estructurales" en IS-201','tarea','/univirtual/pages/estudiante/dashboard.php'),
(2,'Nueva entrega recibida','Edwin Carabali entregó "Taller SQL — Stored Procedures"','tarea','/univirtual/pages/profesor/dashboard.php'),
(3,'Nueva entrega recibida','Carlos Pino entregó "Taller SQL — Stored Procedures"','tarea','/univirtual/pages/profesor/dashboard.php'),
(8,'Reporte facultad','Se registraron 14 nuevas inscripciones esta semana en Ingeniería','info','/univirtual/pages/directivo/dashboard.php');

-- Videollamadas
INSERT INTO videollamadas (idMateria, idProfesor, titulo, roomName, descripcion, programada_en, duracion_min) VALUES
(1, 1, 'Clase 9 — Patrón Composite y Observer',    'univirtual-IS201-20260320-0800',
 'Revisión de los patrones Composite y Observer aplicados en el proyecto.',  '2026-03-20 08:00:00', 90),
(2, 2, 'Clase 8 — Stored Procedures con IN/OUT',   'univirtual-BD301-20260320-1000',
 'Ejercicios prácticos de stored procedures con parámetros IN/OUT.',          '2026-03-20 10:00:00', 90);

-- ── VERIFICACIÓN ──────────────────────────────────────────────────
SELECT 'facultades'     AS tabla, COUNT(*) AS registros FROM facultades
UNION ALL SELECT 'carreras',        COUNT(*) FROM carreras
UNION ALL SELECT 'usuarios',        COUNT(*) FROM usuarios
UNION ALL SELECT 'directivos',      COUNT(*) FROM directivos
UNION ALL SELECT 'profesores',      COUNT(*) FROM profesores
UNION ALL SELECT 'estudiantes',     COUNT(*) FROM estudiantes
UNION ALL SELECT 'materias',        COUNT(*) FROM materias
UNION ALL SELECT 'inscripciones',   COUNT(*) FROM inscripciones
UNION ALL SELECT 'aulas_virtuales', COUNT(*) FROM aulas_virtuales
UNION ALL SELECT 'materiales',      COUNT(*) FROM materiales
UNION ALL SELECT 'actividades',     COUNT(*) FROM actividades
UNION ALL SELECT 'entregas',        COUNT(*) FROM entregas
UNION ALL SELECT 'foros',           COUNT(*) FROM foros
UNION ALL SELECT 'mensajes',        COUNT(*) FROM mensajes
UNION ALL SELECT 'asistencia',      COUNT(*) FROM asistencia
UNION ALL SELECT 'notificaciones',  COUNT(*) FROM notificaciones
UNION ALL SELECT 'videollamadas',   COUNT(*) FROM videollamadas
UNION ALL SELECT 'configuracion',   COUNT(*) FROM configuracion;

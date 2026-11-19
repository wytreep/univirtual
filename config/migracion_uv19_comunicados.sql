-- UV-19 — Comunicados del directivo a estudiantes y profesores
--
-- La tabla `mensajes` existente es del foro por aula (idForo NOT NULL).
-- Para la mensajería directa se agregan dos tablas:
--   comunicados             → cabecera del aviso (remitente, facultad, público)
--   comunicado_destinatarios → fan-out por usuario y estado de lectura
--
-- Ejecutar en la base del proyecto:
--   mysql -u root -P 3307 univirtual < config/migracion_uv19_comunicados.sql
--   mysql -u root -P 3307 univirtual_sprints12 < config/migracion_uv19_comunicados.sql

CREATE TABLE IF NOT EXISTS `comunicados` (
  `idComunicado` int(11) NOT NULL AUTO_INCREMENT,
  `idRemitente`  int(11) NOT NULL,
  `idFacultad`   int(11) NOT NULL,
  `publico`      enum('estudiantes','profesores','ambos') NOT NULL DEFAULT 'ambos',
  `asunto`       varchar(150) NOT NULL,
  `contenido`    text NOT NULL,
  `creado_en`    timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idComunicado`),
  KEY `idx_comunicados_remitente` (`idRemitente`),
  KEY `idx_comunicados_facultad` (`idFacultad`),
  CONSTRAINT `comunicados_ibfk_1` FOREIGN KEY (`idRemitente`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE,
  CONSTRAINT `comunicados_ibfk_2` FOREIGN KEY (`idFacultad`) REFERENCES `facultades` (`idFacultad`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comunicado_destinatarios` (
  `idComunicado` int(11) NOT NULL,
  `idUsuario`    int(11) NOT NULL,
  `leido`        tinyint(1) NOT NULL DEFAULT 0,
  `leido_en`     datetime DEFAULT NULL,
  PRIMARY KEY (`idComunicado`,`idUsuario`),
  KEY `idx_cd_usuario` (`idUsuario`,`leido`),
  CONSTRAINT `cd_ibfk_1` FOREIGN KEY (`idComunicado`) REFERENCES `comunicados` (`idComunicado`) ON DELETE CASCADE,
  CONSTRAINT `cd_ibfk_2` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

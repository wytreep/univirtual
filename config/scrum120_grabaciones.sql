-- ═══════════════════════════════════════════════════════════════════
--  UNI-VIRTUAL — Migración SCRUM-120
--  Tabla de grabaciones de videollamadas
-- ═══════════════════════════════════════════════════════════════════

-- Crear tabla de grabaciones si no existe
CREATE TABLE IF NOT EXISTS grabaciones_videollamadas (
  idGrabacion    INT          AUTO_INCREMENT PRIMARY KEY,
  idVideoLlamada INT          NOT NULL,
  urlGrabacion   VARCHAR(500) NOT NULL,
  duracion_seg   INT          DEFAULT 0,
  tamano_mb      DECIMAL(8,2) DEFAULT 0,
  nombre         VARCHAR(255) DEFAULT NULL,
  subida_por     INT          NOT NULL,
  creada_en      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idVideoLlamada) REFERENCES videollamadas(idVideoLlamada) ON DELETE CASCADE,
  FOREIGN KEY (subida_por)     REFERENCES usuarios(idUsuario) ON DELETE CASCADE,
  INDEX idx_video (idVideoLlamada)
);

-- Agregar columna minutosParaInicio si no existe (se calcula en el query pero por seguridad)
-- Nota: TIMESTAMPDIFF se usa en el query, no necesitamos columna física

-- Crear directorio para grabaciones
-- Esto se hace manualmente: mkdir uploads/grabaciones && chmod 755 uploads/grabaciones

SELECT 'Migración SCRUM-120 completada' AS estado;

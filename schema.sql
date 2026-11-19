-- ===================================================
-- BASE DE DATOS: colegio_nova
-- PROYECTO: Sistema de Registro de Tardanzas NOVA
-- ===================================================

CREATE DATABASE IF NOT EXISTS `colegio_nova` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `colegio_nova`;

-- 1. Tabla Coordinadora (Usuarios del Sistema)
CREATE TABLE IF NOT EXISTS `coordinadora` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `contraseña` VARCHAR(255) NOT NULL,
  `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla Grupos
CREATE TABLE IF NOT EXISTS `grupos` (
  `id_grupo` INT AUTO_INCREMENT PRIMARY KEY,
  `grado` VARCHAR(100) NOT NULL,
  `nombre_grupo` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla Estudiante
CREATE TABLE IF NOT EXISTS `estudiante` (
  `id_estudiante` INT AUTO_INCREMENT PRIMARY KEY,
  `documento` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `grado` VARCHAR(100) NOT NULL,
  `grupo` VARCHAR(100) NOT NULL,
  `huella` VARCHAR(255) DEFAULT NULL,
  `fotografia` VARCHAR(255) DEFAULT NULL,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_documento (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla Llegada Tarde
CREATE TABLE IF NOT EXISTS `llegada_tarde` (
  `id_llegada` INT AUTO_INCREMENT PRIMARY KEY,
  `id_estudiante` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `hora` TIME NOT NULL,
  `excusa` TINYINT(1) DEFAULT 0,
  `observacion` TEXT DEFAULT NULL,
  FOREIGN KEY (`id_estudiante`) REFERENCES `estudiante`(`id_estudiante`) ON DELETE CASCADE,
  INDEX idx_estudiante_fecha (`id_estudiante`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla Excusas
CREATE TABLE IF NOT EXISTS `excusas` (
  `id_excusa` INT AUTO_INCREMENT PRIMARY KEY,
  `id_llegada` INT NOT NULL,
  `archivo` VARCHAR(255) NOT NULL,
  `estado` ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
  `fecha_subida` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_llegada`) REFERENCES `llegada_tarde`(`id_llegada`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla Compromisos
CREATE TABLE IF NOT EXISTS `compromisos` (
  `id_compromiso` INT AUTO_INCREMENT PRIMARY KEY,
  `id_estudiante` INT NOT NULL,
  `fecha_firma` DATE NOT NULL,
  `estado` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`id_estudiante`) REFERENCES `estudiante`(`id_estudiante`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla Alertas
CREATE TABLE IF NOT EXISTS `alertas` (
  `id_alerta` INT AUTO_INCREMENT PRIMARY KEY,
  `id_estudiante` INT NOT NULL,
  `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `tipo` VARCHAR(50) DEFAULT '3_tardanzas',
  FOREIGN KEY (`id_estudiante`) REFERENCES `estudiante`(`id_estudiante`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabla Exportacion
CREATE TABLE IF NOT EXISTS `exportacion` (
  `id_exportacion` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NOT NULL,
  `formato` ENUM('pdf', 'excel') NOT NULL,
  `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_usuario`) REFERENCES `coordinadora`(`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- DATOS SEMILLA (SEED DATA)
-- ===================================================

-- Usuario por defecto (soporta admin / admin123)
INSERT INTO `coordinadora` (`usuario`, `contraseña`) VALUES
('admin', 'admin123')
ON DUPLICATE KEY UPDATE `contraseña` = VALUES(`contraseña`);

-- Grupos iniciales
INSERT INTO `grupos` (`grado`, `nombre_grupo`) VALUES
('10', 'A'), ('10', 'B'), ('11', 'A'), ('9', 'C');

-- Estudiantes iniciales
INSERT INTO `estudiante` (`documento`, `nombre`, `grado`, `grupo`, `huella`) VALUES
('1089647435', 'Pepito Perez', '10', 'B', 'HUELLA_PEPITO_1089647435'),
('1002345678', 'Maria Rodriguez', '11', 'A', 'HUELLA_MARIA_1002345678'),
('1098765432', 'Carlos Gomez', '9', 'C', 'HUELLA_CARLOS_1098765432');

-- Historial inicial para Pepito Perez (id_estudiante: 1)
INSERT INTO `llegada_tarde` (`id_estudiante`, `fecha`, `hora`, `excusa`, `observacion`) VALUES
(1, '2026-07-15', '07:15:00', 1, 'Cita médica'),
(1, '2026-07-18', '08:30:00', 0, NULL),
(1, '2026-07-22', '10:45:00', 1, 'Transporte averiado'),
(1, '2026-07-27', '12:20:00', 1, 'Calamidad doméstica'),
(1, '2026-08-03', '14:55:00', 0, NULL),
(1, '2026-08-10', '13:40:00', 1, 'Cita odontológica');

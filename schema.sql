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
  `nombre_original` VARCHAR(255) DEFAULT NULL,
  `tipo_mime` VARCHAR(100) DEFAULT NULL,
  `contenido` MEDIUMBLOB DEFAULT NULL,
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

-- No se incluyen usuarios ni datos personales de ejemplo en el repositorio público.


-- Sesiones PHP compartidas entre instancias (necesario en despliegues serverless).
CREATE TABLE IF NOT EXISTS `php_sessions` (
  `session_id` VARCHAR(128) NOT NULL PRIMARY KEY,
  `session_data` MEDIUMBLOB NOT NULL,
  `last_activity` INT NOT NULL,
  INDEX `idx_php_sessions_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

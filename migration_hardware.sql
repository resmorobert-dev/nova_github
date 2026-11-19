-- Ejecutar UNA SOLA VEZ sobre la base de datos colegio_nova ya instalada.
-- Agrega soporte para el lector de huella real (sensor R307/AS608 con ESP32).

USE `colegio_nova`;

-- Ranura (ID) que ocupa la huella del estudiante dentro de la memoria del sensor
ALTER TABLE `estudiante`
  ADD COLUMN `huella_id` INT NULL DEFAULT NULL,
  ADD UNIQUE KEY `uq_estudiante_huella_id` (`huella_id`);

-- Órdenes que la página le da al lector (registrar una huella nueva)
CREATE TABLE IF NOT EXISTS `huella_comando` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slot` INT NOT NULL,
  `documento` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `grado` VARCHAR(100) NOT NULL,
  `grupo` VARCHAR(100) NOT NULL,
  `estado` ENUM('pendiente','en_proceso','ok','error') NOT NULL DEFAULT 'pendiente',
  `mensaje` VARCHAR(255) DEFAULT NULL,
  `creado` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Huellas que el sensor reconoció y que el kiosco aún no ha consumido
CREATE TABLE IF NOT EXISTS `huella_evento` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slot` INT NOT NULL,
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  `creado` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_evento (`usado`, `creado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

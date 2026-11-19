-- Ejecutar SOLO si la base de datos colegio_nova ya existe y fue instalada con una versión anterior.
-- La operación evita que una misma huella quede asociada a dos estudiantes.

SET @idx_exists := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'estudiante'
      AND index_name = 'uq_estudiante_huella'
);

SET @sql := IF(
    @idx_exists = 0,
    'ALTER TABLE estudiante ADD UNIQUE KEY uq_estudiante_huella (huella)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

<?php
/** Conexión PDO para NOVA. En Vercel, configurar estas variables en el panel. */
define('APP_TIMEZONE', 'America/Bogota');
define('APP_UTC_OFFSET', '-05:00');
date_default_timezone_set(APP_TIMEZONE);

// getenv() permite usar secretos configurados en Vercel sin guardarlos en Git.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'colegio_nova');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET time_zone = '" . APP_UTC_OFFSET . "'");
} catch (PDOException $e) {
    error_log('NOVA DB connection error: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa la configuración del administrador.');
}

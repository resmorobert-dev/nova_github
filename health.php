<?php
/* Diagnóstico: /health.php responde OK sin tocar la base de datos.
   /health.php?db=1 comprueba además la conexión (sin mostrar detalles). */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
echo "php: ok\n";
if (($_GET['db'] ?? '') === '1') {
    ob_start();
    require __DIR__ . '/config/db.php';
    ob_end_clean();
    if (isset($pdo)) {
        try {
            $pdo->query('SELECT 1 FROM php_sessions LIMIT 1');
            echo "db: ok (tabla php_sessions encontrada)\n";
        } catch (Throwable $e) {
            echo "db: conecta, pero falta ejecutar schema.sql\n";
        }
    } else {
        echo "db: sin conexion (revisa las variables DB_*)\n";
    }
}

<?php
/**
 * Estado para el NAVEGADOR (kiosco y pantalla de agregar estudiante).
 *   ?accion=hay_evento        -> {"hay": true|false}  (público, no revela datos del estudiante)
 *   ?accion=comando&id=N      -> {"estado": "...", "mensaje": "..."}  (requiere sesión de coordinación)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$accion = $_GET['accion'] ?? '';

if ($accion === 'hay_evento') {
    $n = (int)$pdo->query(
        "SELECT COUNT(*) FROM huella_evento ev
         INNER JOIN estudiante s ON s.huella_id = ev.slot
         WHERE ev.usado = 0 AND ev.creado >= (NOW() - INTERVAL 20 SECOND)"
    )->fetchColumn();
    echo json_encode(['hay' => $n > 0]);
    exit;
}

if ($accion === 'comando') {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Sesión expirada. Inicia sesión de nuevo.']);
        exit;
    }
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare(
        "SELECT estado, mensaje, (creado < (NOW() - INTERVAL 2 MINUTE)) AS vencido
         FROM huella_comando WHERE id = :id LIMIT 1"
    );
    $stmt->execute(['id' => $id]);
    $c = $stmt->fetch();

    if (!$c) {
        echo json_encode(['estado' => 'error', 'mensaje' => 'Captura no encontrada.']);
    } else if ($c['estado'] === 'pendiente' && (int)$c['vencido'] === 1) {
        echo json_encode(['estado' => 'expirado', 'mensaje' => 'El lector no respondió. Revisa que esté encendido y conectado al WiFi.']);
    } else {
        echo json_encode(['estado' => $c['estado'], 'mensaje' => $c['mensaje'] ?? '']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'accion_invalida']);

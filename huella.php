<?php
/**
 * API para el DISPOSITIVO (ESP32 + sensor de huella). No es para el navegador.
 * Toda petición debe traer la cabecera  X-API-Key  con la clave de config/hardware.php
 *
 * Acciones (POST, campo "accion"):
 *   comando      -> responde  NINGUNO  o  ENROLAR|<id_comando>|<slot>
 *   progreso     -> id, mensaje             (texto que verá la coordinadora)
 *   resultado    -> id, estado=ok|error, mensaje
 *                   si es ok, se crea el estudiante con su huella_id (slot)
 *   identificado -> slot                    (el sensor reconoció una huella)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/hardware.php';

header('Content-Type: text/plain; charset=utf-8');

$clave = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (HUELLA_API_KEY === 'CAMBIAR_ESTA_CLAVE_LARGA_Y_SECRETA' || !hash_equals(HUELLA_API_KEY, $clave)) {
    http_response_code(401);
    echo 'NO_AUTORIZADO';
    exit;
}

$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

if ($accion === 'comando') {
    $c = $pdo->query(
        "SELECT id, slot FROM huella_comando
         WHERE estado = 'pendiente' AND creado >= (NOW() - INTERVAL 2 MINUTE)
         ORDER BY id ASC LIMIT 1"
    )->fetch();

    if ($c) {
        $pdo->prepare("UPDATE huella_comando SET estado = 'en_proceso', mensaje = 'Coloque el dedo en el sensor' WHERE id = :id AND estado = 'pendiente'")
            ->execute(['id' => $c['id']]);
        echo 'ENROLAR|' . (int)$c['id'] . '|' . (int)$c['slot'];
    } else {
        echo 'NINGUNO';
    }
    exit;
}

if ($accion === 'progreso') {
    $id  = (int)($_POST['id'] ?? 0);
    $msg = mb_substr(trim($_POST['mensaje'] ?? ''), 0, 255);
    $pdo->prepare("UPDATE huella_comando SET mensaje = :m WHERE id = :id AND estado = 'en_proceso'")
        ->execute(['m' => $msg, 'id' => $id]);
    echo 'OK';
    exit;
}

if ($accion === 'resultado') {
    $id     = (int)($_POST['id'] ?? 0);
    $estado = (($_POST['estado'] ?? '') === 'ok') ? 'ok' : 'error';
    $msg    = mb_substr(trim($_POST['mensaje'] ?? ''), 0, 255);

    $stmt = $pdo->prepare("SELECT * FROM huella_comando WHERE id = :id AND estado = 'en_proceso' LIMIT 1");
    $stmt->execute(['id' => $id]);
    $c = $stmt->fetch();

    if (!$c) {
        echo 'COMANDO_INVALIDO';
        exit;
    }

    if ($estado === 'ok') {
        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "INSERT INTO estudiante (nombre, documento, grado, grupo, huella, huella_id)
                 VALUES (:nombre, :doc, :grado, :grupo, :huella, :slot)"
            )->execute([
                'nombre' => $c['nombre'],
                'doc'    => $c['documento'],
                'grado'  => $c['grado'],
                'grupo'  => $c['grupo'],
                'huella' => 'SENSOR_' . $c['slot'],
                'slot'   => $c['slot'],
            ]);
            $pdo->prepare("UPDATE huella_comando SET estado = 'ok', mensaje = 'Huella registrada' WHERE id = :id")
                ->execute(['id' => $id]);
            $pdo->commit();
            echo 'OK';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $pdo->prepare("UPDATE huella_comando SET estado = 'error', mensaje = :m WHERE id = :id")
                ->execute(['m' => 'No se pudo guardar el estudiante (documento o ranura repetidos).', 'id' => $id]);
            echo 'ERROR';
        }
    } else {
        $pdo->prepare("UPDATE huella_comando SET estado = 'error', mensaje = :m WHERE id = :id")
            ->execute(['m' => ($msg !== '' ? $msg : 'El sensor no pudo registrar la huella.'), 'id' => $id]);
        echo 'OK';
    }
    exit;
}

if ($accion === 'identificado') {
    $slot = (int)($_POST['slot'] ?? 0);

    $stmt = $pdo->prepare("SELECT id_estudiante FROM estudiante WHERE huella_id = :s LIMIT 1");
    $stmt->execute(['s' => $slot]);
    if (!$stmt->fetch()) {
        echo 'SIN_ESTUDIANTE';
        exit;
    }

    // Evita duplicar el evento si el sensor lee dos veces seguidas
    $rec = $pdo->prepare("SELECT COUNT(*) FROM huella_evento WHERE slot = :s AND creado >= (NOW() - INTERVAL 5 SECOND)");
    $rec->execute(['s' => $slot]);
    if ((int)$rec->fetchColumn() === 0) {
        $pdo->prepare("INSERT INTO huella_evento (slot) VALUES (:s)")->execute(['s' => $slot]);
    }
    echo 'OK';
    exit;
}

http_response_code(400);
echo 'ACCION_INVALIDA';

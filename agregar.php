<?php
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../config/hardware.php';
require_auth();

$paso = 1;
$mensaje = '';
$modo_sensor = (HUELLA_MODO === 'sensor');
$id_comando = 0;

if (isset($_GET['listo'])) {
    $paso = 3; // Éxito (el lector terminó de registrar la huella)
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'datos_estudiante') {
        $_SESSION['nuevo_nombre'] = trim($_POST['nombre'] ?? '');
        $_SESSION['nuevo_doc'] = trim($_POST['documento'] ?? '');
        $_SESSION['nuevo_grado'] = trim($_POST['grado'] ?? '');
        $_SESSION['nuevo_grupo'] = trim($_POST['grupo'] ?? '');

        if (!empty($_SESSION['nuevo_nombre']) && !empty($_SESSION['nuevo_doc'])) {
            $paso = 2; // Capturar huella
        } else {
            $mensaje = "Nombre y Documento son obligatorios.";
        }
    } else if ($accion === 'iniciar_captura' && $modo_sensor) {
        // Le pedimos al lector (ESP32) que registre una huella nueva
        $nombre = $_SESSION['nuevo_nombre'] ?? '';
        $doc = $_SESSION['nuevo_doc'] ?? '';
        $grado = $_SESSION['nuevo_grado'] ?? '';
        $grupo = $_SESSION['nuevo_grupo'] ?? '';

        if ($nombre === '' || $doc === '') {
            $mensaje = "Nombre y Documento son obligatorios.";
            $paso = 1;
        } else {
            $stmtCheck = $pdo->prepare("SELECT id_estudiante FROM estudiante WHERE documento = :doc LIMIT 1");
            $stmtCheck->execute(['doc' => $doc]);

            if ($stmtCheck->fetch()) {
                $mensaje = "El documento ya está registrado en el sistema.";
                $paso = 1;
            } else {
                // Buscar la primera ranura libre del sensor (las usadas por estudiantes o capturas en curso no cuentan)
                $ocupadas = $pdo->query(
                    "SELECT huella_id AS s FROM estudiante WHERE huella_id IS NOT NULL
                     UNION
                     SELECT slot FROM huella_comando
                     WHERE estado IN ('pendiente','en_proceso') AND creado >= (NOW() - INTERVAL 2 MINUTE)"
                )->fetchAll(PDO::FETCH_COLUMN);
                $ocupadas = array_map('intval', $ocupadas);

                $slot = 0;
                for ($i = 1; $i <= HUELLA_MAX_SLOTS; $i++) {
                    if (!in_array($i, $ocupadas, true)) {
                        $slot = $i;
                        break;
                    }
                }

                if ($slot === 0) {
                    $mensaje = "La memoria del lector de huella está llena.";
                    $paso = 1;
                } else {
                    $stmtCmd = $pdo->prepare(
                        "INSERT INTO huella_comando (slot, documento, nombre, grado, grupo, estado, mensaje)
                         VALUES (:slot, :doc, :nombre, :grado, :grupo, 'pendiente', 'Esperando al lector...')"
                    );
                    $stmtCmd->execute([
                        'slot' => $slot, 'doc' => $doc, 'nombre' => $nombre,
                        'grado' => $grado, 'grupo' => $grupo
                    ]);
                    $id_comando = (int)$pdo->lastInsertId();
                    $paso = 2;
                }
            }
        }
    } else if ($accion === 'guardar_huella' && !$modo_sensor) {
        // Modo simulado (sin hardware)
        $nombre = $_SESSION['nuevo_nombre'] ?? '';
        $doc = $_SESSION['nuevo_doc'] ?? '';
        $grado = $_SESSION['nuevo_grado'] ?? '10';
        $grupo = $_SESSION['nuevo_grupo'] ?? 'A';
        $huella = "HUELLA_" . $doc;

        // Validar no duplicado (RNF-08)
        $stmtCheck = $pdo->prepare("SELECT id_estudiante FROM estudiante WHERE documento = :doc LIMIT 1");
        $stmtCheck->execute(['doc' => $doc]);

        if ($stmtCheck->fetch()) {
            $mensaje = "El documento ya está registrado en el sistema.";
            $paso = 1;
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO estudiante (nombre, documento, grado, grupo, huella) VALUES (:nombre, :doc, :grado, :grupo, :huella)");
            $stmtIns->execute([
                'nombre' => $nombre,
                'doc' => $doc,
                'grado' => $grado,
                'grupo' => $grupo,
                'huella' => $huella
            ]);
            $paso = 3; // Éxito
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Agregar Estudiante</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
    <script>document.documentElement.classList.add(window.CSSViewTransitionRule ? 'vt' : 'no-vt');</script>
    <script src="../assets/js/nova.js?v=3" defer></script>
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <header class="header-nova">
        <div class="header-container">
            <span class="nova-text">NOVA</span>
            <div class="logo-nova">
                <img src="../assets/escudo.png" alt="Escudo" class="shield-img" onerror="this.style.display='none'">
                <span class="andes-text">LOS ANDES</span>
            </div>
            <div class="header-icon">
                <img src="../assets/huella_icon.png" alt="" class="header-icon-img" width="48" height="48">
            </div>
        </div>
        <div class="yellow-bar"></div>
    </header>

    <main id="contenido" class="main-container center-content">
        <?php if ($paso === 1): ?>
            <h1 class="page-title">Agregar a un estudiante</h1>

            <?php if (!empty($mensaje)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <form action="agregar.php" method="POST" class="form-container">
                <input type="hidden" name="accion" value="datos_estudiante">
                <div class="form-group"><input type="text" name="nombre" placeholder="Nombre" required class="input-field"></div>
                <div class="form-group"><input type="text" name="documento" placeholder="Documento" required class="input-field"></div>
                <div class="form-group"><input type="text" name="grado" placeholder="Grado" required class="input-field"></div>
                <div class="form-group"><input type="text" name="grupo" placeholder="Grupo" required class="input-field"></div>

                <div class="actions-row">
                    <a href="dashboard.php" class="btn-back" data-tip="Volver al panel de coordinación">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    </a>
                    <button type="submit" class="btn-primary" data-tip="Guardar los datos y pasar al registro de huella">Siguiente</button>
                </div>
            </form>

        <?php elseif ($paso === 2): ?>
            <h1 class="page-title">¡Por favor ingrese la huella!</h1>

            <?php if (!$modo_sensor): ?>
            <form action="agregar.php" method="POST" class="center-content">
                <input type="hidden" name="accion" value="guardar_huella">
                <div class="fingerprint-scan-frame" role="button" tabindex="0" data-tip="Simular la captura de la huella del estudiante" aria-label="Capturar huella" onclick="this.closest('form').submit()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.closest('form').submit();}">
                    <svg viewBox="0 0 24 24" class="fingerprint-svg-lg" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 8V5.5A1.5 1.5 0 0 1 5.5 4H8"/>
                        <path d="M16 4h2.5A1.5 1.5 0 0 1 20 5.5V8"/>
                        <path d="M20 16v2.5a1.5 1.5 0 0 1-1.5 1.5H16"/>
                        <path d="M8 20H5.5A1.5 1.5 0 0 1 4 18.5V16"/>
                        <path d="M12 8a4 4 0 0 0-4 4v1.5c0 1.2-.2 2-.5 2.8"/>
                        <path d="M12 8a4 4 0 0 1 4 4v.8"/>
                        <path d="M9 6.8a5 5 0 0 1 6 0"/>
                        <path d="M12 11a1.6 1.6 0 0 0-1.6 1.6v1.2c0 1.8-.4 3-1 4.1"/>
                        <path d="M12 11a1.6 1.6 0 0 1 1.6 1.6v.7"/>
                    </svg>
                </div>
                <p class="scan-help-text">Haga clic en la huella para simular la captura biométrica</p>
            </form>

            <?php elseif ($id_comando === 0): ?>
            <form action="agregar.php" method="POST" class="center-content">
                <input type="hidden" name="accion" value="iniciar_captura">
                <div class="fingerprint-scan-frame">
                    <svg viewBox="0 0 24 24" class="fingerprint-svg-lg" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 8V5.5A1.5 1.5 0 0 1 5.5 4H8"/>
                        <path d="M16 4h2.5A1.5 1.5 0 0 1 20 5.5V8"/>
                        <path d="M20 16v2.5a1.5 1.5 0 0 1-1.5 1.5H16"/>
                        <path d="M8 20H5.5A1.5 1.5 0 0 1 4 18.5V16"/>
                        <path d="M12 8a4 4 0 0 0-4 4v1.5c0 1.2-.2 2-.5 2.8"/>
                        <path d="M12 8a4 4 0 0 1 4 4v.8"/>
                        <path d="M9 6.8a5 5 0 0 1 6 0"/>
                        <path d="M12 11a1.6 1.6 0 0 0-1.6 1.6v1.2c0 1.8-.4 3-1 4.1"/>
                        <path d="M12 11a1.6 1.6 0 0 1 1.6 1.6v.7"/>
                    </svg>
                </div>
                <p class="scan-help-text">Pulsa el botón y luego sigue las instrucciones en el lector.</p>
                <div class="actions-row" style="margin-top:20px;">
                    <a href="agregar.php" class="btn-back" data-tip="Volver a los datos del estudiante">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    </a>
                    <button type="submit" class="btn-primary" data-tip="Pedirle al lector que registre la huella">Iniciar captura</button>
                </div>
            </form>

            <?php else: ?>
            <div class="center-content">
                <div class="fingerprint-scan-frame">
                    <svg viewBox="0 0 24 24" class="fingerprint-svg-lg" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 8V5.5A1.5 1.5 0 0 1 5.5 4H8"/>
                        <path d="M16 4h2.5A1.5 1.5 0 0 1 20 5.5V8"/>
                        <path d="M20 16v2.5a1.5 1.5 0 0 1-1.5 1.5H16"/>
                        <path d="M8 20H5.5A1.5 1.5 0 0 1 4 18.5V16"/>
                        <path d="M12 8a4 4 0 0 0-4 4v1.5c0 1.2-.2 2-.5 2.8"/>
                        <path d="M12 8a4 4 0 0 1 4 4v.8"/>
                        <path d="M9 6.8a5 5 0 0 1 6 0"/>
                        <path d="M12 11a1.6 1.6 0 0 0-1.6 1.6v1.2c0 1.8-.4 3-1 4.1"/>
                        <path d="M12 11a1.6 1.6 0 0 1 1.6 1.6v.7"/>
                    </svg>
                </div>
                <p class="scan-help-text" id="estadoSensor" role="status" aria-live="polite">Esperando al lector...</p>
                <div class="actions-row" id="accionesError" style="margin-top:20px; display:none;">
                    <a href="agregar.php" class="btn-primary" data-tip="Volver a intentarlo">Reintentar</a>
                </div>
            </div>
            <script>
            (function () {
                var id = <?php echo (int)$id_comando; ?>;
                var el = document.getElementById('estadoSensor');
                function consultar() {
                    fetch('../api/huella_estado.php?accion=comando&id=' + id, { cache: 'no-store' })
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            if (d.mensaje) { el.textContent = d.mensaje; }
                            if (d.estado === 'ok') {
                                window.location = 'agregar.php?listo=1';
                            } else if (d.estado === 'error' || d.estado === 'expirado') {
                                document.getElementById('accionesError').style.display = 'flex';
                            } else {
                                setTimeout(consultar, 1000);
                            }
                        })
                        .catch(function () { setTimeout(consultar, 2000); });
                }
                consultar();
            })();
            </script>
            <?php endif; ?>

        <?php elseif ($paso === 3): ?>
            <h1 class="page-title center">Su huella ha sido registrada exitosamente</h1>
            <a href="dashboard.php" class="btn-primary" style="margin-top:30px" data-tip="Volver al panel de coordinación">Inicio</a>
        <?php endif; ?>
    </main>
</body>
</html>
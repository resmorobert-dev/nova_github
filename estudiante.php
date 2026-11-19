<?php
require_once 'config/db.php';
require_once 'auth/session.php';
require_once 'config/hardware.php';

$mensaje = '';
$alerta = '';
$paso = 1; // 1: Huella/Doc, 2: Pregunta Excusa, 3: Confirmacion / Alerta Compromiso
$estudiante_encontrado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $documento = trim($_POST['documento'] ?? '');

    if ($accion === 'buscar_huella' && !HUELLA_PERMITIR_DOCUMENTO) {
        $mensaje = "Identifícate poniendo el dedo en el lector de huella.";
    } else if ($accion === 'huella_sensor') {
        // El lector (ESP32) reconoció una huella: tomamos el evento más reciente sin usar
        $stmtEv = $pdo->query(
            "SELECT ev.id AS id_evento, s.* FROM huella_evento ev
             INNER JOIN estudiante s ON s.huella_id = ev.slot
             WHERE ev.usado = 0 AND ev.creado >= (NOW() - INTERVAL 20 SECOND)
             ORDER BY ev.id DESC LIMIT 1"
        );
        $fila = $stmtEv->fetch();
        // Consumir todos los eventos pendientes para que no se repitan
        $pdo->exec("UPDATE huella_evento SET usado = 1 WHERE usado = 0");

        if ($fila) {
            unset($fila['id_evento']);
            $_SESSION['kiosco_estudiante'] = $fila;
            $paso = 2;
        } else {
            $mensaje = "No se pudo identificar la huella. Intenta de nuevo.";
        }
    } else if ($accion === 'buscar_huella') {
        // RF-01, RF-02, RF-08: Validar documento y huella
        $stmt = $pdo->prepare("SELECT * FROM estudiante WHERE documento = :doc LIMIT 1");
        $stmt->execute(['doc' => $documento]);
        $estudiante_encontrado = $stmt->fetch();

        if ($estudiante_encontrado) {
            $_SESSION['kiosco_estudiante'] = $estudiante_encontrado;
            $paso = 2;
        } else {
            $mensaje = "Estudiante no registrado con el documento: " . htmlspecialchars($documento);
        }
    } else if ($accion === 'registrar_tardanza') {
        if (isset($_SESSION['kiosco_estudiante'])) {
            $est = $_SESSION['kiosco_estudiante'];
            $tiene_excusa = ($_POST['excusa'] ?? 'no') === 'si' ? 1 : 0;
            $fecha_actual = date('Y-m-d');
            $hora_actual = date('H:i:s');

            // Insertar llegada tarde (RF-04, RF-05, RF-11)
            $stmtInsert = $pdo->prepare("INSERT INTO llegada_tarde (id_estudiante, fecha, hora, excusa) VALUES (:id, :fecha, :hora, :excusa)");
            $stmtInsert->execute([
                'id' => $est['id_estudiante'],
                'fecha' => $fecha_actual,
                'hora' => $hora_actual,
                'excusa' => $tiene_excusa
            ]);

            // Contar tardanzas acumuladas SIN excusa (RF-12, RF-13)
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM llegada_tarde WHERE id_estudiante = :id AND excusa = 0");
            $stmtCount->execute(['id' => $est['id_estudiante']]);
            $total_sin_excusa = $stmtCount->fetchColumn();

            // Verificar si tiene compromiso firmado
            $stmtComp = $pdo->prepare("SELECT * FROM compromisos WHERE id_estudiante = :id AND estado = 1");
            $stmtComp->execute(['id' => $est['id_estudiante']]);
            $compromiso_firmado = $stmtComp->fetch();

            $paso = 3;
            if ($tiene_excusa == 0) {
                // Solo evaluamos alertas de compromiso cuando el estudiante NO presenta excusa
                if ($total_sin_excusa == 3) {
                    // RF-13, RF-16: Alerta de compromiso al llegar a 3 tardanzas sin excusa
                    $alerta = "Alerta Especial: Has superado las 3 llegadas tarde sin excusa. Debes firmar un compromiso en coordinación.";
                    $stmtAlert = $pdo->prepare("INSERT INTO alertas (id_estudiante, tipo) VALUES (:id, '3_tardanzas')");
                    $stmtAlert->execute(['id' => $est['id_estudiante']]);
                } else if ($total_sin_excusa > 3 && $compromiso_firmado) {
                    // RF-15, RF-17: Reincidencia post-compromiso
                    $alerta = "Alerta Especial: Reincidencia registrada después de firmar el compromiso.";
                    $stmtAlert = $pdo->prepare("INSERT INTO alertas (id_estudiante, tipo) VALUES (:id, 'reincidencia')");
                    $stmtAlert->execute(['id' => $est['id_estudiante']]);
                }
            } else {
                // Aviso simple cuando el estudiante SÍ tiene excusa
                $alerta = "Presentar excusa a coordinación";
            }
            unset($_SESSION['kiosco_estudiante']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Kiosco de Registro de Tardanzas</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <script>document.documentElement.classList.add(window.CSSViewTransitionRule ? 'vt' : 'no-vt');</script>
    <script src="assets/js/nova.js?v=3" defer></script>
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <header class="header-nova">
        <div class="header-container">
            <span class="nova-text">NOVA</span>
            <div class="logo-nova">
                <img src="assets/escudo.png" alt="Escudo" class="shield-img" onerror="this.style.display='none'">
                <span class="andes-text">LOS ANDES</span>
            </div>
            <div class="header-right-group">
                <div class="header-icon">
                <img src="assets/huella_icon.png" alt="" class="header-icon-img" width="48" height="48">
            </div>
            </div>
        </div>
        <div class="yellow-bar"></div>
    </header>

    <main id="contenido" class="main-container center-content">
        <?php if ($paso === 1): ?>
            <div class="nova-clock" data-nova-clock data-server-ts="<?php echo time() * 1000; ?>" data-tz="America/Bogota" role="timer" aria-label="Fecha y hora actuales"><span data-clock-date><?php echo date('d/m/Y'); ?></span><span class="sep" aria-hidden="true"></span><span data-clock-time><?php echo date('H:i:s'); ?></span></div>
            <h1 class="page-title">¡Por favor ingrese la huella!</h1>
            <?php if (!empty($mensaje)): ?>
                <div class="alert-error"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <form action="estudiante.php" method="POST" class="form-kiosco">
                <input type="hidden" name="accion" value="buscar_huella">
                <div class="fingerprint-box" role="button" tabindex="0" data-tip="Escribir tu número de documento o usar el sensor" onclick="var d=document.getElementById('inputDoc'); if(d){d.focus();}" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();var d=document.getElementById('inputDoc'); if(d){d.focus();}}">
                    <svg viewBox="0 0 24 24" class="fingerprint-svg" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
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
                    <p>Toque el sensor biométrico o ingrese su documento</p>
                </div>
                <?php if (HUELLA_PERMITIR_DOCUMENTO): ?>
                <div class="form-group" style="margin-top:20px;">
                    <input type="text" id="inputDoc" name="documento" placeholder="Número de documento del estudiante" required class="input-field">
                </div>
                <?php endif; ?>
                <div class="actions-row" style="margin-top:20px;">
                    <a href="acceso.php" class="btn-back" data-tip="Volver a las opciones de acceso">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    </a>
                    <?php if (HUELLA_PERMITIR_DOCUMENTO): ?>
                    <button type="submit" class="btn-primary" data-tip="Identificarte con tu número de documento">Siguiente</button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (HUELLA_MODO === 'sensor'): ?>
            <form id="formSensor" action="estudiante.php" method="POST" style="display:none">
                <input type="hidden" name="accion" value="huella_sensor">
            </form>
            <script>
            (function () {
                var enviando = false;
                setInterval(function () {
                    if (enviando) { return; }
                    fetch('api/huella_estado.php?accion=hay_evento', { cache: 'no-store' })
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            if (d && d.hay) {
                                enviando = true;
                                document.getElementById('formSensor').submit();
                            }
                        })
                        .catch(function () {});
                }, 1000);
            })();
            </script>
            <?php endif; ?>

        <?php elseif ($paso === 2): ?>
            <h1 class="page-title">Estudiante: <?php echo htmlspecialchars($_SESSION['kiosco_estudiante']['nombre']); ?></h1>
            <h2 class="subtitle-question">¿Tienes excusa?</h2>

            <form action="estudiante.php" method="POST" class="form-excusa-choice">
                <input type="hidden" name="accion" value="registrar_tardanza">
                <button type="submit" name="excusa" value="no" class="btn-no" data-tip="Registrar la llegada tarde sin excusa">No</button>
                <button type="submit" name="excusa" value="si" class="btn-yes" data-tip="Registrar la llegada tarde indicando que tienes excusa">Sí</button>
            </form>

        <?php elseif ($paso === 3): ?>
            <?php if (!empty($alerta)): ?>
                <div class="alert-banner">
                    <h2><?php echo htmlspecialchars($alerta); ?></h2>
                </div>
            <?php endif; ?>

            <h1 class="page-title">!Registro Completado con Exito¡</h1>
            <a href="estudiante.php" class="btn-primary" style="margin-top:30px" data-tip="Volver a la pantalla de huella">Inicio</a>
        <?php endif; ?>
    </main>
</body>
</html>
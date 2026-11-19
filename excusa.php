<?php
require_once '../config/db.php';
require_once '../auth/session.php';
require_auth();

$paso = 1;
$mensaje = '';
$estudiante = null;

/*
 * REGLA: solo se puede subir una excusa cuando el estudiante declaró en el kiosco
 * que SÍ tiene excusa (llegada_tarde.excusa = 1) y todavía no existe un archivo
 * cargado para esa llegada (tabla excusas). Si respondió "No", no se permite subirla.
 */
function buscar_llegada_pendiente(PDO $pdo, int $id_estudiante) {
    $stmt = $pdo->prepare(
        "SELECT ll.* FROM llegada_tarde ll
         WHERE ll.id_estudiante = :id
           AND ll.excusa = 1
           AND NOT EXISTS (SELECT 1 FROM excusas ex WHERE ex.id_llegada = ll.id_llegada)
         ORDER BY ll.fecha DESC, ll.hora DESC
         LIMIT 1"
    );
    $stmt->execute(['id' => $id_estudiante]);
    return $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'buscar_excusa') {
        // Buscar estudiante por número de documento
        $doc = trim($_POST['documento'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM estudiante WHERE documento = :doc LIMIT 1");
        $stmt->execute(['doc' => $doc]);
        $estudiante = $stmt->fetch();

        unset($_SESSION['excusa_id_llegada'], $_SESSION['excusa_estudiante_nombre'], $_SESSION['excusa_id_estudiante'], $_SESSION['excusa_fecha']);

        if ($estudiante) {
            $llegada = buscar_llegada_pendiente($pdo, (int)$estudiante['id_estudiante']);

            if ($llegada) {
                $_SESSION['excusa_id_llegada'] = $llegada['id_llegada'];
                $_SESSION['excusa_id_estudiante'] = $estudiante['id_estudiante'];
                $_SESSION['excusa_estudiante_nombre'] = $estudiante['nombre'];
                $_SESSION['excusa_fecha'] = date('d/m/Y', strtotime($llegada['fecha'])) . ' ' . date('H:i', strtotime($llegada['hora']));
                $paso = 2;
            } else {
                // Explicar por qué no se puede subir
                $stmtTot = $pdo->prepare("SELECT COUNT(*) FROM llegada_tarde WHERE id_estudiante = :id");
                $stmtTot->execute(['id' => $estudiante['id_estudiante']]);
                $total = (int)$stmtTot->fetchColumn();

                $stmtCon = $pdo->prepare("SELECT COUNT(*) FROM llegada_tarde WHERE id_estudiante = :id AND excusa = 1");
                $stmtCon->execute(['id' => $estudiante['id_estudiante']]);
                $con_excusa = (int)$stmtCon->fetchColumn();

                if ($total === 0) {
                    $mensaje = "Este estudiante no tiene llegadas tarde registradas.";
                } else if ($con_excusa === 0) {
                    $mensaje = "Este estudiante no ha presentado excusa. Solo se puede subir la excusa cuando el estudiante indicó que sí la tiene al registrar su llegada tarde.";
                } else {
                    $mensaje = "Las excusas de este estudiante ya fueron subidas. No hay excusas pendientes.";
                }
            }
        } else {
            $mensaje = "No se encontró ningún estudiante con ese documento.";
        }
    } else if (isset($_FILES['archivo_excusa']) && $_FILES['archivo_excusa']['error'] === UPLOAD_ERR_OK) {
        $id_llegada = intval($_SESSION['excusa_id_llegada'] ?? 0);
        $id_est = intval($_SESSION['excusa_id_estudiante'] ?? 0);
        $fileName = $_FILES['archivo_excusa']['name'];
        $fileTmp = $_FILES['archivo_excusa']['tmp_name'];

        // Volver a validar en el servidor: la llegada debe seguir teniendo excusa declarada y sin archivo
        $stmtOk = $pdo->prepare(
            "SELECT ll.id_llegada FROM llegada_tarde ll
             WHERE ll.id_llegada = :ll AND ll.id_estudiante = :est AND ll.excusa = 1
               AND NOT EXISTS (SELECT 1 FROM excusas ex WHERE ex.id_llegada = ll.id_llegada)
             LIMIT 1"
        );
        $stmtOk->execute(['ll' => $id_llegada, 'est' => $id_est]);
        $permitida = $id_llegada > 0 && $stmtOk->fetch();

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $extPermitidas = ['pdf', 'png', 'jpg', 'jpeg'];

        if (!$permitida) {
            unset($_SESSION['excusa_id_llegada'], $_SESSION['excusa_estudiante_nombre'], $_SESSION['excusa_id_estudiante'], $_SESSION['excusa_fecha']);
            $mensaje = "No se puede subir la excusa: el estudiante no ha presentado excusa o ya fue cargada. Busca de nuevo al estudiante.";
            $paso = 1;
        } else if (!in_array($ext, $extPermitidas, true)) {
            $mensaje = "Formato no permitido. Sube la excusa en PDF, PNG o JPG.";
            $paso = 2;
        } else {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $safeName = time() . '_' . preg_replace("/[^a-zA-Z0-9._-]/", "", $fileName);
            $destPath = $uploadDir . $safeName;

            if (move_uploaded_file($fileTmp, $destPath)) {
                // Guardar en la base de datos (RF-06). La llegada ya figura con excusa = 1.
                $stmtIns = $pdo->prepare("INSERT INTO excusas (id_llegada, archivo, estado) VALUES (:id_llegada, :archivo, 'aprobada')");
                $stmtIns->execute(['id_llegada' => $id_llegada, 'archivo' => $safeName]);

                unset($_SESSION['excusa_id_llegada'], $_SESSION['excusa_estudiante_nombre'], $_SESSION['excusa_id_estudiante'], $_SESSION['excusa_fecha']);
                $paso = 3; // Éxito
            } else {
                $mensaje = "Error al mover el archivo adjunto.";
                $paso = 2;
            }
        }
    } else {
        $mensaje = "Por favor seleccione un archivo válido.";
        // Si ya no hay una llegada válida en sesión, se vuelve a la búsqueda
        $paso = isset($_SESSION['excusa_id_llegada']) ? 2 : 1;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Subir Excusa</title>
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
            <h1 class="page-title">¿Que estudiante deseas excusar?</h1>

            <?php if (!empty($mensaje)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <form action="excusa.php" method="POST" class="form-container">
                <input type="hidden" name="accion" value="buscar_excusa">
                <div class="form-group">
                    <input type="text" name="documento" placeholder="Numero de documento del estudiantes" required class="input-field">
                </div>

                <div class="actions-row">
                    <a href="dashboard.php" class="btn-back" data-tip="Volver al panel de coordinación">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    </a>
                    <button type="submit" class="btn-primary" data-tip="Buscar al estudiante">Siguiente</button>
                </div>
            </form>

        <?php elseif ($paso === 2): ?>
            <h1 class="page-title">¡Por favor subir la excusa del estudiante!</h1>
            <p class="subtitle-who">
                <strong><?php echo htmlspecialchars($_SESSION['excusa_estudiante_nombre'] ?? ''); ?></strong>
                presentó excusa en su llegada tarde del <?php echo htmlspecialchars($_SESSION['excusa_fecha'] ?? ''); ?>
            </p>

            <?php if (!empty($mensaje)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <form action="excusa.php" method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="dropzone-box" role="button" tabindex="0" data-tip="Elegir el archivo de la excusa (PDF, PNG o JPG)" onclick="document.getElementById('fileInput').click()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                    <svg viewBox="0 0 24 24" width="64" height="64" fill="currentColor" aria-hidden="true">
                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 14h-3v3h-2v-3H8v-2h3v-3h2v3h3v2zm-3-7V3.5L18.5 9H13z"/>
                    </svg>
                    <p id="fileNameDisplay">Haga clic o arrastre el archivo aquí (PDF, PNG, JPG)</p>
                    <input type="file" id="fileInput" name="archivo_excusa" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg" style="display:none;" onchange="document.getElementById('fileNameDisplay').innerText = this.files[0].name" required>
                </div>

                <div class="actions-bar-split">
                    <a href="dashboard.php" class="btn-cancel" data-tip="Cancelar y volver al panel de coordinación">Cancelar</a>
                    <button type="submit" class="btn-primary" data-tip="Subir la excusa seleccionada">Siguiente</button>
                </div>
            </form>

        <?php elseif ($paso === 3): ?>
            <h1 class="page-title center">¡Se ha subido la excusa exitosamente!</h1>
            <a href="dashboard.php" class="btn-primary" style="margin-top:30px" data-tip="Volver al panel de coordinación">Inicio</a>
        <?php endif; ?>
    </main>
</body>
</html>
<?php
require_once '../config/db.php';
require_once '../auth/session.php';
require_auth();

$paso = 1;
$mensaje = '';
$estudiante = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'buscar_cambiar') {
        $doc = trim($_POST['documento'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM estudiante WHERE documento = :doc LIMIT 1");
        $stmt->execute(['doc' => $doc]);
        $estudiante = $stmt->fetch();

        if ($estudiante) {
            $_SESSION['edit_estudiante_id'] = $estudiante['id_estudiante'];
            $paso = 2;
        } else {
            $mensaje = "No se encontró ningún estudiante con ese documento.";
        }
    } else if ($accion === 'guardar_cambios') {
        $id = $_SESSION['edit_estudiante_id'] ?? 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $doc = trim($_POST['documento'] ?? '');
        $grado = trim($_POST['grado'] ?? '');
        $grupo = trim($_POST['grupo'] ?? '');

        $stmtUp = $pdo->prepare("UPDATE estudiante SET nombre = :nombre, documento = :doc, grado = :grado, grupo = :grupo WHERE id_estudiante = :id");
        $stmtUp->execute([
            'nombre' => $nombre,
            'doc' => $doc,
            'grado' => $grado,
            'grupo' => $grupo,
            'id' => $id
        ]);
        $paso = 3; // Éxito
    } else if ($accion === 'eliminar_estudiante') {
        $id = $_SESSION['edit_estudiante_id'] ?? 0;

        if ($id > 0) {
            $stmtDel = $pdo->prepare("DELETE FROM estudiante WHERE id_estudiante = :id");
            $stmtDel->execute(['id' => $id]);
            unset($_SESSION['edit_estudiante_id']);
        }
        $paso = 4; // Eliminado con éxito
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Cambiar Estudiante</title>
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
            <h1 class="page-title">¿Que estudiante quieres cambiar?</h1>

            <?php if (!empty($mensaje)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <form action="cambiar.php" method="POST" class="form-container">
                <input type="hidden" name="accion" value="buscar_cambiar">
                <div class="form-group">
                    <input type="text" name="documento" placeholder="Numero de documento del estudiantes" required class="input-field">
                </div>

                <div class="actions-row">
                    <a href="dashboard.php" class="btn-back" data-tip="Volver al panel de coordinación">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    </a>
                    <button type="submit" class="btn-primary" data-tip="Buscar al estudiante para editarlo">Siguiente</button>
                </div>
            </form>

        <?php elseif ($paso === 2): ?>
            <h1 class="page-title center">Editar estudiante</h1>

            <form action="cambiar.php" method="POST" class="edit-card">
                <input type="hidden" name="accion" value="guardar_cambios">

                <div class="form-row-edit">
                    <label>Estudiante:</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($estudiante['nombre']); ?>" required class="input-clean">
                </div>
                <div class="form-row-edit">
                    <label>Documento:</label>
                    <input type="text" name="documento" value="<?php echo htmlspecialchars($estudiante['documento']); ?>" required class="input-clean">
                </div>
                <div class="form-row-edit">
                    <label>Grado:</label>
                    <input type="text" name="grado" value="<?php echo htmlspecialchars($estudiante['grado']); ?>" required class="input-clean">
                </div>
                <div class="form-row-edit">
                    <label>Grupo:</label>
                    <input type="text" name="grupo" value="<?php echo htmlspecialchars($estudiante['grupo']); ?>" required class="input-clean">
                </div>

                <div class="actions-row" style="margin-top:30px;">
                    <a href="dashboard.php" class="btn-back" data-tip="Volver al panel de coordinación">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    </a>
                    <button type="submit" class="btn-primary" data-tip="Guardar los cambios del estudiante">Guardar</button>
                </div>
            </form>

            <div id="deleteInitial" style="margin-top:24px; text-align:center;">
                <button type="button"
                    class="btn-delete" data-tip="Eliminar al estudiante (pedirá confirmación)" onclick="document.getElementById('deleteInitial').style.display='none'; document.getElementById('deleteConfirm').style.display='block';">
                    Eliminar estudiante
                </button>
            </div>

            <div id="deleteConfirm" style="display:none; margin-top:24px; text-align:center;">
                <p style="font-size:1.2rem; font-weight:700; margin-bottom:18px;">¿Deseas confirmar la eliminación del estudiante?</p>
                <div style="display:flex; gap:20px; justify-content:center; flex-wrap:wrap;">
                    <button type="button"
                        class="btn-neutral" data-tip="No eliminar y seguir editando" onclick="document.getElementById('deleteConfirm').style.display='none'; document.getElementById('deleteInitial').style.display='block';">
                        Cancelar
                    </button>
                    <form action="cambiar.php" method="POST" style="margin:0;">
                        <input type="hidden" name="accion" value="eliminar_estudiante">
                        <button type="submit" class="btn-delete btn-sm" data-tip="Eliminar definitivamente al estudiante">
                            Confirmar
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($paso === 3): ?>
            <h1 class="page-title center">El cambio de estudiante ha sido exitoso</h1>
            <a href="dashboard.php" class="btn-primary" style="margin-top:30px" data-tip="Volver al panel de coordinación">Inicio</a>

        <?php elseif ($paso === 4): ?>
            <h1 class="page-title center">El estudiante ha sido eliminado exitosamente</h1>
            <a href="dashboard.php" class="btn-primary" style="margin-top:30px" data-tip="Volver al panel de coordinación">Inicio</a>
        <?php endif; ?>
    </main>
</body>
</html>
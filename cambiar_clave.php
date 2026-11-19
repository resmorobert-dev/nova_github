<?php
require_once '../config/db.php';
require_once '../auth/session.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? 'admin');
    $nueva_clave = trim($_POST['nueva_clave'] ?? '');
    $confirmar_clave = trim($_POST['confirmar_clave'] ?? '');

    if (empty($nueva_clave) || empty($confirmar_clave)) {
        $mensaje = 'Por favor complete todos los campos requeridos.';
        $tipo_mensaje = 'error';
    } else if ($nueva_clave !== $confirmar_clave) {
        $mensaje = 'La nueva contraseña y su confirmación no coinciden.';
        $tipo_mensaje = 'error';
    } else if (strlen($nueva_clave) < 4) {
        $mensaje = 'La contraseña debe tener al menos 4 caracteres.';
        $tipo_mensaje = 'error';
    } else {
        // Buscar usuario en BD
        $stmt = $pdo->prepare("SELECT * FROM coordinadora WHERE usuario = :usuario LIMIT 1");
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch();

        $hash_nuevo = password_hash($nueva_clave, PASSWORD_DEFAULT);

        if ($user) {
            $stmt_update = $pdo->prepare("UPDATE coordinadora SET contraseña = :hash WHERE id_usuario = :id");
            $stmt_update->execute(['hash' => $hash_nuevo, 'id' => $user['id_usuario']]);
            $mensaje = '¡Contraseña actualizada exitosamente! Ya puede iniciar sesión con la nueva clave.';
            $tipo_mensaje = 'exito';
        } else {
            $stmt_insert = $pdo->prepare("INSERT INTO coordinadora (usuario, contraseña) VALUES (:usuario, :hash)");
            $stmt_insert->execute(['usuario' => $usuario, 'hash' => $hash_nuevo]);
            $mensaje = '¡Usuario y contraseña creados y guardados correctamente!';
            $tipo_mensaje = 'exito';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Actualizar Contraseña</title>
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
        <h1 class="page-title">Actualizar Contraseña</h1>

        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo $tipo_mensaje === 'exito' ? 'alert-success' : 'alert-error'; ?>" role="status">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form action="cambiar_clave.php" method="POST" class="form-login">
            <div class="form-group">
                <label class="field-label">Usuario Administrador</label>
                <input type="text" name="usuario" value="admin" required class="input-field">
            </div>

            <div class="form-group">
                <label class="field-label">Nueva Contraseña *</label>
                <input type="password" name="nueva_clave" placeholder="Mínimo 4 caracteres" required class="input-field">
            </div>

            <div class="form-group">
                <label class="field-label">Confirmar Nueva Contraseña *</label>
                <input type="password" name="confirmar_clave" placeholder="Repita la nueva contraseña" required class="input-field">
            </div>

            <div class="actions-bar-split" style="margin-top:20px;">
                <a href="../login.php" class="btn-neutral" data-tip="Volver al inicio de sesión">Volver al Login</a>
                <button type="submit" class="btn-primary" data-tip="Guardar la nueva contraseña">Guardar Contraseña</button>
            </div>
        </form>
    </main>
</body>
</html>
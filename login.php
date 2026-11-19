<?php
require_once 'config/db.php';
require_once 'auth/session.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $contraseña = trim($_POST['contraseña'] ?? '');

    if (!empty($usuario) && !empty($contraseña)) {
        // Consulta preparada para prevenir Inyección SQL
        $stmt = $pdo->prepare("SELECT * FROM coordinadora WHERE usuario = :usuario LIMIT 1");
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch();

        if ($user) {
            $es_valido = false;
            // Solo se aceptan contraseñas almacenadas con hash seguro.
            if (password_verify($contraseña, $user['contraseña'])) {
                $es_valido = true;
            }

            if ($es_valido) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $user['id_usuario'];
                $_SESSION['usuario_nombre'] = $user['usuario'];
                header("Location: coordinacion/dashboard.php");
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } else {
            $error = 'Usuario no encontrado.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - LOS ANDES | Iniciar Sesión</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <script>document.documentElement.classList.add(window.CSSViewTransitionRule ? 'vt' : 'no-vt');</script>
    <script src="assets/js/nova.js?v=3" defer></script>
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <header class="header-nova">
        <div class="header-container">
            <span class="nova-text">NOVA</span>
            <div class="header-icon">
                <img src="assets/huella_icon.png" alt="" class="header-icon-img" width="48" height="48">
            </div>
        </div>
        <div class="yellow-bar"></div>
    </header>

    <div class="brand-strip-left">
        <div class="logo-nova">
            <img src="assets/escudo.png" alt="Escudo" class="shield-img" onerror="this.style.display='none'">
            <span class="andes-text">LOS ANDES</span>
        </div>
    </div>

    <main id="contenido" class="main-container center-content">
        <h1 class="page-title">iniciar sesión</h1>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="form-login">
            <div class="form-group">
                <input type="text" name="usuario" placeholder="Usuario" required class="input-field">
            </div>
            <div class="form-group">
                <input type="password" name="contraseña" placeholder="Contraseña" required class="input-field">
            </div>
            <div class="actions-bar-split" style="justify-content:center;">
                <button type="submit" class="btn-primary" data-tip="Iniciar sesión con tu usuario y contraseña">Continuar</button>
                <a href="estudiante.php" class="btn-primary" data-tip="Ir al kiosco de registro de llegadas tarde">Estudiante</a>
            </div>
        </form>

        <div style="margin-top:24px; text-align:center;">
            <a href="coordinacion/cambiar_clave.php" class="btn-soft" data-tip="Actualizar o restablecer la contraseña"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="M10.8 12.2 20 3m-4 4 3 3m-6-1 2 2"/></svg><span>¿Desea actualizar o restablecer la contraseña?</span></a>
        </div>
    </main>
</body>
</html>
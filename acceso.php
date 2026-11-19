<?php
/* Elección de acceso: Administrador (login) o Estudiante (huella) */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Los Andes | Elige tu acceso</title>
    <link rel="stylesheet" href="assets/css/style.css?v=4">
    <script>document.documentElement.classList.add(window.CSSViewTransitionRule ? 'vt' : 'no-vt');</script>
    <script src="assets/js/nova.js?v=3" defer></script>
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <div class="yellow-bar"></div>

    <main id="contenido" class="main-container center-content landing">
        <div class="logo-hero">
            <img src="assets/huella_icon.png" alt="Logo NOVA" class="logo-hero-img" style="width:clamp(90px,20vw,120px)">
            <span class="logo-hero-name" style="font-size:clamp(2.2rem,9vw,3.2rem)">NOVA</span>
        </div>

        <h1 class="page-title">¿Cómo deseas ingresar?</h1>

        <div class="access-grid">
            <a href="login.php" class="access-card" data-tip="Iniciar sesión con usuario y contraseña">
                <span class="ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v6c0 4.5 3.2 7.8 8 9 4.8-1.2 8-4.5 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/></svg>
                </span>
                <span>Administrador</span>
                <small>Iniciar sesión</small>
            </a>
            <a href="estudiante.php" class="access-card" data-tip="Registrar tu llegada con la huella">
                <span class="ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8a4 4 0 0 0-4 4v1.5c0 1.2-.2 2-.5 2.8"/><path d="M12 8a4 4 0 0 1 4 4v.8"/><path d="M9 6.8a5 5 0 0 1 6 0"/><path d="M12 11a1.6 1.6 0 0 0-1.6 1.6v1.2c0 1.8-.4 3-1 4.1"/><path d="M12 11a1.6 1.6 0 0 1 1.6 1.6v.7"/></svg>
                </span>
                <span>Estudiante</span>
                <small>Ingresar con huella</small>
            </a>
        </div>

        <a href="index.php" class="btn-soft access-back" data-tip="Volver a la portada">← Volver</a>
    </main>
</body>
</html>

<?php
require_once '../config/db.php';
require_once '../auth/session.php';
require_auth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Panel Coordinador/a</title>
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
        <div class="nova-clock" data-nova-clock data-server-ts="<?php echo time() * 1000; ?>" data-tz="America/Bogota" role="timer" aria-label="Fecha y hora actuales"><span data-clock-date><?php echo date('d/m/Y'); ?></span><span class="sep" aria-hidden="true"></span><span data-clock-time><?php echo date('H:i:s'); ?></span></div>
        <h1 class="welcome-title">Bienvenido/a Administrador/a</h1>
        <div style="margin-bottom:24px;">
            <a href="cambiar_clave.php" class="btn-soft" data-tip="Cambiar la contraseña de coordinación"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="M10.8 12.2 20 3m-4 4 3 3m-6-1 2 2"/></svg><span>Actualizar Contraseña</span></a>
        </div>
        <p class="subtitle-action">¿Que deseas hacer?</p>

        <div class="grid-dashboard">
            <a href="buscar.php" class="card-btn" data-tip="Consultar la ficha y el historial de un estudiante"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span><span>Buscar</span></a>
            <a href="agregar.php" class="card-btn" data-tip="Registrar un estudiante nuevo y vincular su huella"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1M19 8v6M16 11h6"/></svg></span><span>Agregar</span></a>
            <a href="cambiar.php" class="card-btn" data-tip="Editar los datos de un estudiante o eliminarlo"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4zM13.5 6.5l4 4"/></svg></span><span>Cambiar</span></a>
            <a href="excusa.php" class="card-btn" data-tip="Subir la excusa de un estudiante"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5zM14 3v5h5M12 17v-6m-3 3 3-3 3 3"/></svg></span><span>Excusa</span></a>
            <a href="../estudiante.php" class="card-btn" style="grid-column: 1 / -1;" data-tip="Abrir el kiosco de registro de llegadas tarde"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9.5 12 5l10 4.5L12 14 2 9.5zM6 11.8V16c0 1.4 2.7 3 6 3s6-1.6 6-3v-4.2"/></svg></span><span>Estudiante</span></a>
        </div>

        <a href="../auth/logout.php" class="btn-logout" data-tip="Cerrar la sesión" aria-label="Cerrar sesión">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        </a>
    </main>
</body>
</html>
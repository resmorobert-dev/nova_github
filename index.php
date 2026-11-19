<?php
/* Portada de NOVA: logo, descripción del proyecto y botón Iniciar */
require_once 'auth/session.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Los Andes | Bienvenido</title>
    <link rel="stylesheet" href="assets/css/style.css?v=4">
    <script>document.documentElement.classList.add(window.CSSViewTransitionRule ? 'vt' : 'no-vt');</script>
    <script src="assets/js/nova.js?v=3" defer></script>
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <div class="yellow-bar"></div>

    <main id="contenido" class="main-container center-content landing">
        <div class="logo-hero">
            <img src="assets/huella_icon.png" alt="Logo NOVA" class="logo-hero-img">
            <span class="logo-hero-name">NOVA</span>
            <div class="logo-hero-school">
                <img src="assets/escudo.png" alt="Escudo" onerror="this.style.display='none'">
                <span>INSTITUCIÓN EDUCATIVA LOS ANDES</span>
            </div>
        </div>

        <section class="landing-desc" aria-labelledby="desc-titulo">
            <h2 id="desc-titulo">¿En qué se basa el proyecto?</h2>
            <p>
                NOVA es un sistema web para controlar las llegadas tarde de los estudiantes de la
                Institución Educativa Los Andes. Cada estudiante se identifica con su huella dactilar
                en un lector biométrico, y el sistema registra automáticamente la fecha y la hora.
            </p>
            <ul>
                <li>Registro de llegadas tarde con huella, con o sin excusa.</li>
                <li>Alerta automática al acumular 3 llegadas tarde sin excusa (compromiso).</li>
                <li>Historial por estudiante y gestión de excusas por parte de coordinación.</li>
                <li>Reportes exportables en PDF y Excel.</li>
            </ul>
        </section>

        <a href="acceso.php" class="btn-primary btn-start" data-tip="Elegir cómo quieres ingresar">Iniciar</a>
    </main>
</body>
</html>

<?php
/* Barra superior, subencabezado institucional. Requiere $base ('' en raíz, '../' en coordinacion/) */
$base   = $base ?? '';
$logged = isset($_SESSION['usuario_id']);
$nombre = $_SESSION['usuario_nombre'] ?? 'admin';
$pagina = basename($_SERVER['SCRIPT_NAME']);
$activo = function (array $paginas) use ($pagina) { return in_array($pagina, $paginas) ? ' is-active' : ''; };
?>
<nav class="topnav">
    <div class="topnav-inner">
        <a href="<?php echo $base; ?>index.php" class="topnav-brand">COLEGIO NOVA</a>
        <div class="topnav-links">
            <a href="<?php echo $base; ?>estudiante.php" class="<?php echo trim($activo(['estudiante.php'])); ?>">Kiosco Estudiantil</a>
            <a href="<?php echo $base; ?>coordinacion/dashboard.php" class="<?php echo trim($activo(['dashboard.php'])); ?>">Panel Coordinación</a>
            <a href="<?php echo $base; ?>coordinacion/buscar.php" class="<?php echo trim($activo(['buscar.php', 'ficha.php', 'historial.php'])); ?>">Buscar Estudiante</a>
            <a href="<?php echo $base; ?>coordinacion/excusa.php" class="<?php echo trim($activo(['excusa.php'])); ?>">Gestión Excusas</a>
        </div>
        <div class="topnav-right">
            <?php if ($logged): ?>
                <a href="<?php echo $base; ?>auth/logout.php" class="btn-nav-yellow" data-tip="Cerrar sesión">Cerrar Sesión (<?php echo htmlspecialchars($nombre); ?>)</a>
            <?php else: ?>
                <a href="<?php echo $base; ?>login.php" class="btn-nav-yellow" data-tip="Ir al acceso de coordinación">Acceso Coordinación</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="yellow-bar"></div>
</nav>
<header class="subheader">
    <div class="subheader-inner">
        <img src="<?php echo $base; ?>assets/escudo.png" alt="Escudo" class="subheader-shield" onerror="this.style.display='none'">
        <div>
            <h2 class="subheader-title">Institución Educativa Los Andes · Plataforma Institucional NOVA</h2>
            <p class="subheader-sub">Sistema de Control de Llegadas Tarde, Gestión de Excusas, Compromisos Disciplinarios y Reportes Oficiales</p>
        </div>
        <img src="<?php echo $base; ?>assets/huella_icon.png" alt="Huella" class="subheader-huella" width="44" height="44">
    </div>
</header>

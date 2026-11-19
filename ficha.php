<?php
require_once '../config/db.php';
require_once '../auth/session.php';
require_auth();

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM estudiante WHERE id_estudiante = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$estudiante = $stmt->fetch();

if (!$estudiante) {
    header("Location: buscar.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Ficha Estudiante</title>
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
        <h1 class="page-title center">Ficha del estudiante</h1>

        <div class="profile-card">
            <p><strong class="label-bold">Estudiante:</strong> <span class="val-text"><?php echo htmlspecialchars($estudiante['nombre']); ?></span></p>
            <p><strong class="label-bold">Grado:</strong> <span class="val-text"><?php echo htmlspecialchars($estudiante['grado']); ?></span></p>
            <p><strong class="label-bold">Grupo:</strong> <span class="val-text"><?php echo htmlspecialchars($estudiante['grupo']); ?></span></p>
        </div>

        <a href="historial.php?id=<?php echo $estudiante['id_estudiante']; ?>" class="btn-primary" style="margin-top:10px;" data-tip="Ver las llegadas tarde y las excusas del estudiante">Historial de llegadas tarde</a>

        <a href="buscar.php" class="btn-back" style="margin-top: 40px" data-tip="Volver a la búsqueda de estudiantes">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
    </main>
</body>
</html>
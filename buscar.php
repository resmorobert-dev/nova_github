<?php
require_once '../config/db.php';
require_once '../auth/session.php';
require_auth();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documento = trim($_POST['documento'] ?? '');
    if (!empty($documento)) {
        // RF-09: Buscar estudiante por número de documento o nombre con consulta preparada
        $stmt = $pdo->prepare("SELECT * FROM estudiante WHERE documento = :doc OR nombre LIKE :nombre LIMIT 1");
        $stmt->execute([
            'doc' => $documento,
            'nombre' => '%' . $documento . '%'
        ]);
        $estudiante = $stmt->fetch();

        if ($estudiante) {
            header("Location: ficha.php?id=" . $estudiante['id_estudiante']);
            exit();
        } else {
            $error = 'No se encontró ningún estudiante con los datos ingresados.';
        }
    } else {
        $error = 'Por favor ingrese el número de documento o nombre.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Buscar Estudiante</title>
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
        <h1 class="page-title">¿Que estudiante deseas buscar?</h1>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="buscar.php" method="POST" class="form-container">
            <div class="form-group">
                <input type="text" name="documento" placeholder="Numero de documento del estudiantes" required class="input-field">
            </div>

            <div class="actions-row">
                <a href="dashboard.php" class="btn-back" data-tip="Volver al panel de coordinación">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </a>
                <button type="submit" class="btn-primary" data-tip="Buscar al estudiante y abrir su ficha">Siguiente</button>
            </div>
        </form>
    </main>
</body>
</html>
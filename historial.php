<?php
require_once '../config/db.php';
require_once '../auth/session.php';
require_auth();

$id = intval($_GET['id'] ?? 0);

// Obtener información del estudiante
$stmtEst = $pdo->prepare("SELECT * FROM estudiante WHERE id_estudiante = :id LIMIT 1");
$stmtEst->execute(['id' => $id]);
$estudiante = $stmtEst->fetch();

if (!$estudiante) {
    header("Location: buscar.php");
    exit();
}

// Obtener historial de llegadas tarde (RF-11)
$stmtLlegadas = $pdo->prepare("SELECT * FROM llegada_tarde WHERE id_estudiante = :id ORDER BY fecha DESC, hora DESC");
$stmtLlegadas->execute(['id' => $id]);
$llegadas = $stmtLlegadas->fetchAll();

// RF-12: Contador visible de tardanzas acumuladas
$total_tardanzas = count($llegadas);

// Contador de tardanzas que tienen excusa registrada
$total_con_excusa = 0;
foreach ($llegadas as $item) {
    if ($item['excusa']) {
        $total_con_excusa++;
    }
}

// RF-13 & RF-14: Verificar si requiere o ya firmó compromiso
$stmtComp = $pdo->prepare("SELECT * FROM compromisos WHERE id_estudiante = :id LIMIT 1");
$stmtComp->execute(['id' => $id]);
$compromiso = $stmtComp->fetch();

// Firma de compromiso POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firmar_compromiso'])) {
    $fecha_firma = date('Y-m-d');
    if ($compromiso) {
        $stmtUp = $pdo->prepare("UPDATE compromisos SET fecha_firma = :fecha, estado = 1 WHERE id_estudiante = :id");
        $stmtUp->execute(['fecha' => $fecha_firma, 'id' => $id]);
    } else {
        $stmtIns = $pdo->prepare("INSERT INTO compromisos (id_estudiante, fecha_firma, estado) VALUES (:id, :fecha, 1)");
        $stmtIns->execute(['id' => $id, 'fecha' => $fecha_firma]);
    }
    header("Location: historial.php?id=" . $id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOVA - Historial del estudiante</title>
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

    <main id="contenido" class="main-container">
        <div class="top-nav-bar">
            <a href="dashboard.php" class="home-icon" data-tip="Volver al panel de coordinación" aria-label="Panel de coordinación">
                <svg viewBox="0 0 24 24" width="44" height="44" fill="currentColor">
                    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                </svg>
            </a>
            <div class="export-actions">
                <a href="exportar.php?id=<?php echo $id; ?>&fmt=pdf" target="_blank" class="export-icon pdf" data-tip="Exportar el historial en PDF" aria-label="Exportar PDF">
                    <img src="../assets/icon_pdf.png" alt="" width="44" height="44" class="export-icon-img">
                </a>
                <a href="exportar.php?id=<?php echo $id; ?>&fmt=excel" target="_blank" class="export-icon excel" data-tip="Exportar el historial en Excel" aria-label="Exportar Excel">
                    <img src="../assets/icon_excel.png" alt="" width="44" height="44" class="export-icon-img">
                </a>
            </div>
        </div>

        <h1 class="page-title center">Historial del estudiante</h1>

        <div class="table-card">
            <table class="table-history">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Excusa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($llegadas)): ?>
                        <tr><td colspan="3" class="center">No registra tardanzas acumuladas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($llegadas as $item): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($item['fecha'])); ?></td>
                                <td><?php echo date('H:i', strtotime($item['hora'])); ?></td>
                                <td><?php echo $item['excusa'] ? 'Sí' : 'No'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- RF-12 & RF-13: Contador -->
        <div class="summary-badge">
            <span>Veces que llego tarde: <strong><?php echo $total_tardanzas; ?></strong></span>
            <span>Con excusa: <strong><?php echo $total_con_excusa; ?></strong></span>
        </div>

        <?php if ($total_tardanzas >= 3): ?>
            <div class="alert-compromiso-box">
                <?php if ($compromiso && $compromiso['estado']): ?>
                    <p class="text-success">Compromiso firmado el: <?php echo date('d/m/Y', strtotime($compromiso['fecha_firma'])); ?></p>
                <?php else: ?>
                    <p class="text-danger">El estudiante ha alcanzado 3 tardanzas. Requiere firma de compromiso.</p>
                    <form action="historial.php?id=<?php echo $id; ?>" method="POST">
                        <button type="submit" name="firmar_compromiso" class="btn-primary" data-tip="Guardar que el estudiante ya firmó el compromiso">Registrar Compromiso Firmado</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
<?php
require_once '../config/db.php';
require_once '../auth/session.php';
require_auth();

$id_estudiante = intval($_GET['id'] ?? 0);
$formato = $_GET['fmt'] ?? 'pdf';

// Obtener datos del estudiante e historial
$stmtEst = $pdo->prepare("SELECT * FROM estudiante WHERE id_estudiante = :id");
$stmtEst->execute(['id' => $id_estudiante]);
$estudiante = $stmtEst->fetch();

if (!$estudiante) {
    die("Estudiante no encontrado.");
}

$stmtLlegadas = $pdo->prepare("SELECT * FROM llegada_tarde WHERE id_estudiante = :id ORDER BY fecha DESC");
$stmtLlegadas->execute(['id' => $id_estudiante]);
$llegadas = $stmtLlegadas->fetchAll();

// Registrar log de exportación
$stmtExp = $pdo->prepare("INSERT INTO exportacion (id_usuario, formato) VALUES (:usr, :fmt)");
$stmtExp->execute(['usr' => $_SESSION['usuario_id'], 'fmt' => $formato === 'excel' ? 'excel' : 'pdf']);

if ($formato === 'excel') {
    // Exportación a Excel (.csv / .xlsx compatible)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=historial_' . $estudiante['documento'] . '.csv');

    $output = fopen('php://output', 'w');
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); // UTF-8 BOM
    fputcsv($output, ['INSTITUCION EDUCATIVA LOS ANDES - SISTEMA NOVA']);
    fputcsv($output, ['Reporte de Llegadas Tarde']);
    fputcsv($output, ['Estudiante:', $estudiante['nombre']]);
    fputcsv($output, ['Documento:', $estudiante['documento']]);
    fputcsv($output, ['Grado:', $estudiante['grado'] . ' - ' . $estudiante['grupo']]);
    fputcsv($output, []);
    fputcsv($output, ['Fecha', 'Hora', 'Excusa', 'Observacion']);

    foreach ($llegadas as $l) {
        fputcsv($output, [
            $l['fecha'],
            $l['hora'],
            $l['excusa'] ? 'Si' : 'No',
            $l['observacion'] ?? ''
        ]);
    }
    fclose($output);
    exit();
} else {
    // Exportación a PDF (HTML Imprimible estandarizado)
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Reporte PDF - <?php echo htmlspecialchars($estudiante['nombre']); ?></title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
            .header { text-align: center; border-bottom: 3px solid #10b981; padding-bottom: 10px; }
            .header h1 { color: #047857; margin: 0; }
            .info-box { margin: 20px 0; background: #f3f4f6; padding: 15px; border-radius: 8px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #d1d5db; padding: 10px; text-align: left; }
            th { background: #10b981; color: white; }
            .total { margin-top: 20px; font-weight: bold; font-size: 1.1em; color: #b91c1c; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="header">
            <h1>INSTITUCIÓN EDUCATIVA LOS ANDES</h1>
            <h2>Sistema NOVA - Reporte de Llegadas Tarde</h2>
        </div>
        <div class="info-box">
            <p><strong>Estudiante:</strong> <?php echo htmlspecialchars($estudiante['nombre']); ?></p>
            <p><strong>Documento:</strong> <?php echo htmlspecialchars($estudiante['documento']); ?></p>
            <p><strong>Grado y Grupo:</strong> <?php echo htmlspecialchars($estudiante['grado'] . ' ' . $estudiante['grupo']); ?></p>
            <p><strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Con Excusa</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($llegadas as $l): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($l['fecha'])); ?></td>
                        <td><?php echo date('H:i', strtotime($l['hora'])); ?></td>
                        <td><?php echo $l['excusa'] ? 'Sí' : 'No'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="total">Total de tardanzas acumuladas: <?php echo count($llegadas); ?></p>
    </body>
    </html>
    <?php
}
?>
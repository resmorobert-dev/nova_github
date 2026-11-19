<?php
/**
 * Herramienta de emergencia para restablecer la contraseña a admin / admin123
 */
require_once '../config/db.php';

try {
    $usuario = 'admin';
    $nueva_clave = 'admin123';
    $hash = password_hash($nueva_clave, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id_usuario FROM coordinadora WHERE usuario = :u");
    $stmt->execute(['u' => $usuario]);
    $user = $stmt->fetch();

    if ($user) {
        $update = $pdo->prepare("UPDATE coordinadora SET contraseña = :h WHERE id_usuario = :id");
        $update->execute(['h' => $hash, 'id' => $user['id_usuario']]);
    } else {
        $insert = $pdo->prepare("INSERT INTO coordinadora (usuario, contraseña) VALUES (:u, :h)");
        $insert->execute(['u' => $usuario, 'h' => $hash]);
    }

    echo "<div style='max-width:600px; margin:50px auto; padding:30px; font-family:sans-serif; background:#d1e7dd; color:#0f5132; border-radius:12px; text-align:center; box-shadow:0 4px 6px rgba(0,0,0,0.1);'>";
    echo "<h2 style='margin-top:0;'>¡Contraseña de Administrador Restablecida!</h2>";
    echo "<p style='font-size:16px;'>Las credenciales de acceso predeterminadas son:</p>";
    echo "<p style='background:#fff; padding:12px; border-radius:8px; display:inline-block; font-size:18px;'><strong>Usuario:</strong> admin &nbsp;|&nbsp; <strong>Contraseña:</strong> admin123</p><br><br>";
    echo "<a href='../login.php' style='background:#10b981; color:#000; font-weight:bold; padding:12px 24px; text-decoration:none; border-radius:20px; display:inline-block;'>Ir a Iniciar Sesión</a>";
    echo "</div>";
} catch (Exception $e) {
    echo "Error al restablecer la contraseña: " . $e->getMessage();
}
?>
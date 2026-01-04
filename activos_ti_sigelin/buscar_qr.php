<?php
require_once 'includes/auth_check.php';
require_once 'classes/database.php';
$db = new Database();
$conn = $db->dbConnection();

$qr_code = $_GET['qr'] ?? '';

if (!empty($qr_code)) {
    // Buscar el ID del equipo asociado al QR (Tabla en minúsculas)
    $stmt = $conn->prepare("SELECT id_equipo FROM qr WHERE codigo_qr = :qr");
    $stmt->execute([':qr' => $qr_code]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        // Si existe, redirigir a la edición
        header("Location: editar_equipo.php?id=" . $resultado['id_equipo']);
        exit;
    }
}

// Si no encuentra nada, volver al inventario con error
header("Location: equipos.php?error=qr_no_encontrado");
exit;
?>
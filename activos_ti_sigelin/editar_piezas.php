<?php
// 1. GUARDIA DE AUTENTICACIÓN (¿Estás logueado?)
require_once 'includes/auth_check.php'; 

// 2. Cargar Clases (Necesarias para RBAC y Conexión)
require_once 'classes/user.php'; 
require_once 'classes/database.php';

// 3. Instanciar Clases
$objUsuarioSistema = new UsuarioSistema(); 
$db = new Database();
$conn = $db->dbConnection();

// 4. GUARDIA DE AUTORIZACIÓN (RBAC)
// (Según la matriz, solo 'administrador' y 'tecnico' pueden editar piezas)
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

$id_pieza = $_GET['id'] ?? null;
if (!$id_pieza) {
    // Usar redirect()
    $objUsuarioSistema->redirect("piezas.php");
    exit;
}

// Obtener datos actuales del registro
// 'PiezaHardware' -> 'piezahardware' 
$stmt = $conn->prepare("SELECT * FROM piezahardware WHERE id_pieza = :id");
$stmt->execute([':id' => $id_pieza]);
$pieza = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pieza) {
    echo "Pieza no encontrada.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $cantidad = $_POST['cantidad'];
    $estado = $_POST['estado'];

    try {
        // Actualizar la tabla PiezaHardware
        // 'PiezaHardware' -> 'piezahardware' 
        $stmtPieza = $conn->prepare("
            UPDATE piezahardware SET nombre = :nombre, cantidad = :cantidad, estado = :estado
            WHERE id_pieza = :id
        ");
        $stmtPieza->execute([
            ':nombre' => $nombre,
            ':cantidad' => $cantidad,
            ':estado' => $estado,
            ':id' => $id_pieza
        ]);

        // Usar redirect()
        $objUsuarioSistema->redirect("piezas.php?updated");
        exit;
    } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<?php require_once 'includes/head.php'; ?>
<body>
<?php require_once 'includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-4">
            <nav aria-label="breadcrumb" class="mt-3">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="asignaciones.php">Asignaciones</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
              </ol>
            </nav>
            <h2 class="mt-3">Editar Pieza de Hardware (ID: <?= $id_pieza ?>)</h2>
            
            <form method="post">
                <div class="form-group mb-3">
                    <label>Nombre de la Pieza</label>
                    <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($pieza['nombre']); ?>" maxlength="200">
                </div>
                <div class="form-group mb-3">
                    <label>Cantidad en Stock</label>
                    <input type="number" name="cantidad" class="form-control" required value="<?= $pieza['cantidad']; ?>" min="0">
                </div>
                
                <div class="form-group mb-3">
                    <label>Estado</label>
                    <select name="estado" class="form-control" required>
                        <option value="Nuevo" <?= $pieza['estado'] == 'Nuevo' ? 'selected' : '' ?>>Nuevo</option>
                        <option value="Usado" <?= $pieza['estado'] == 'Usado' ? 'selected' : '' ?>>Usado (Para Reciclaje)</option>
                        <option value="Agotado" <?= $pieza['estado'] == 'Agotado' ? 'selected' : '' ?>>Agotado</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
                <a href="piezas.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
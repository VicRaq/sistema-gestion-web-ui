<?php
// 1. GUARDIA DE AUTENTICACIÓN (¿Estás logueado?)
require_once 'includes/auth_check.php'; 

// 2. NUEVO: GUARDIA DE AUTORIZACIÓN (RBAC)
// (Según la matriz, solo 'administrador' y 'tecnico' pueden gestionar piezas)
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
    require_once 'classes/user.php'; 
    $objUsuarioSistema = new UsuarioSistema(); 
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

// 3. Cargar el resto (Si pasó los dos guardias)
require_once 'classes/database.php';
require_once 'classes/user.php'; // Necesario para el redirect
$db = new Database();
$conn = $db->dbConnection();
$objUsuarioSistema = new UsuarioSistema(); // Para usar la función redirect

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $cantidad = $_POST['cantidad'];
    $estado = $_POST['estado'];

    // 'PiezaHardware' -> 'piezahardware' (minúsculas)
    $stmtPieza = $conn->prepare("
        INSERT INTO piezahardware (nombre, cantidad, estado)
        VALUES (:nombre, :cantidad, :estado)
    ");
    
    try {
        $stmtPieza->execute([
            ':nombre' => $nombre,
            ':cantidad' => $cantidad,
            ':estado' => $estado
        ]);

        // Usar el método redirect()
        $objUsuarioSistema->redirect("piezas.php?inserted");
        // exit; // (redirect() ya incluye el exit)
    } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
    }
}
?>

<!doctype html>
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
                <li class="breadcrumb-item active" aria-current="page">Asignaciones</li>
              </ol>
            </nav>
            <h1 class="mt-3">Registrar Nueva Pieza de Hardware</h1>

            <form method="post" class="mt-4">
                <div class="form-group mb-3">
                    <label>Nombre de la Pieza (ej. Módulo RAM DDR4, Disco SSD 500GB)</label>
                    <input type="text" name="nombre" class="form-control" required maxlength="200">
                </div>
                <div class="form-group mb-3">
                    <label>Cantidad Inicial en Stock</label>
                    <input type="number" name="cantidad" class="form-control" required min="0">
                </div>
                
                <div class="form-group mb-3">
                    <label>Estado Inicial</label>
                    <select name="estado" class="form-control" required>
                        <option value="Nuevo">Nuevo</option>
                        <option value="Usado">Usado (Para Reciclaje)</option>
                        <option value="Agotado">Agotado</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Guardar Pieza</button>
                <a href="piezas.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>

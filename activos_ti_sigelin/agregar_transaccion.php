<?php
// 1. GUARDIA DE AUTENTICACIÓN (¿Estás logueado?)
require_once 'includes/auth_check.php'; 

// 2. Cargar Clases
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'classes/user.php'; 
require_once 'classes/database.php';

$objUsuarioSistema = new UsuarioSistema(); 
$conn = $objUsuarioSistema->getConnection(); 

// 3. GUARDIA DE AUTORIZACIÓN (RBAC)
// (Solo admin y compras pueden registrar transacciones)
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'compras') {
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario_sistema = $_SESSION['user_id']; 
    $tipo_transaccion = $_POST['tipo_transaccion']; 
    $nombre_item = trim($_POST['nombre_item']); 
    $cantidad = (int) $_POST['cantidad'];
    $precio_unitario = (float) $_POST['precio_unitario'];
    $total = $cantidad * $precio_unitario;

    if ($cantidad <= 0 || $precio_unitario < 0) {
        $mensaje = "Error de validación: La cantidad debe ser mayor a cero y el precio no puede ser negativo.";
    } elseif ($id_usuario_sistema === null) {
        $mensaje = "Error de autenticación: El ID del usuario no fue proporcionado.";
    } else {
        try {
            $stmtTransaccion = $conn->prepare("INSERT INTO transaccion (fecha, total, id_usuario_sistema, tipo_transaccion) 
                                              VALUES (NOW(), :total, :usuario_sistema, :tipo)");
            $stmtTransaccion->execute([
                ':total' => $total,
                ':usuario_sistema' => $id_usuario_sistema,
                ':tipo' => $tipo_transaccion
            ]);

            $id_transaccion = $conn->lastInsertId();

            $stmtDetalle = $conn->prepare("INSERT INTO detalletransaccion (id_transaccion, nombre_item, cantidad, precio_unitario)
                                          VALUES (:id_transaccion, :item, :cantidad, :precio)");
            $stmtDetalle->execute([
                ':id_transaccion' => $id_transaccion,
                ':item' => $nombre_item,
                ':cantidad' => $cantidad,
                ':precio' => $precio_unitario
            ]);

            $objUsuarioSistema->redirect("transacciones.php?inserted");
        } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
      }
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
          <li class="breadcrumb-item"><a href="transacciones.php">Transacciones</a></li>
          <li class="breadcrumb-item active" aria-current="page">Registrar</li>
        </ol>
      </nav>
      <h1 class="mt-3">Registrar Nueva Transacción/Compra</h1>

      <?php if ($mensaje): ?>
        <div class="alert alert-danger"><?= $mensaje; ?></div>
      <?php endif; ?>

      <form method="post" class="mt-4">
        
        <input type="hidden" name="id_usuario_sistema" value="<?= htmlspecialchars($_SESSION['user_id']); ?>">

        <div class="mb-3">
          <label for="tipo_transaccion" class="form-label">Tipo de Transacción *</label>
          <select name="tipo_transaccion" id="tipo_transaccion" class="form-control" required>
            <option value="Compra_Repuesto">Compra de Repuesto</option>
            <option value="Servicio_Externo">Contratación de Servicio Externo</option>
            <option value="Baja_Contable">Baja Contable</option>
            <option value="Otro">Otro</option>
          </select>
        </div>
        
        <div class="mb-3">
          <label for="nombre_item" class="form-label">Descripción del Item/Servicio *</label>
          <input type="text" name="nombre_item" id="nombre_item" class="form-control" required maxlength="100">
        </div>

        <div class="mb-3">
          <label for="cantidad" class="form-label">Cantidad *</label>
          <input type="number" name="cantidad" id="cantidad" class="form-control" required min="1">
        </div>

        <div class="mb-3">
          <label for="precio_unitario" class="form-label">Precio Unitario *</label>
          <input type="number" name="precio_unitario" id="precio_unitario" class="form-control" required step="0.01" min="0">
        </div>

        <button type="submit" class="btn btn-primary">Registrar Transacción</button>
        <a href="transacciones.php" class="btn btn-secondary ms-2">Volver</a>
      </form>
    </main>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
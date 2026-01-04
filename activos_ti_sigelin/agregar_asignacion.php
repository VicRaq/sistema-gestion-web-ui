<?php
// 1. ESTABLECER ZONA HORARIA DE CHILE
date_default_timezone_set('America/Santiago');

// 2. GUARDIAS DE AUTENTICACIÓN Y AUTORIZACIÓN (RBAC)
require_once 'includes/auth_check.php'; 
if (!in_array($CURRENT_USER_ROL, ['administrador', 'tecnico', 'compras'])) {
    require_once 'classes/user.php'; 
    $objUsuarioSistema = new UsuarioSistema(); 
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

// 3. Cargar el resto
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'classes/user.php'; 
$objUsuarioSistema = new UsuarioSistema(); 

require_once 'classes/database.php';
$db = new Database();
$conn = $db->dbConnection(); 

$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // OBTENCIÓN Y VALIDACIÓN DE DATOS
    $id_cliente = intval($_POST['id_cliente'] ?? 0); 
    $id_activo = intval($_POST['id_activo'] ?? 0); 
    $fecha_inicio = $_POST['fecha_inicio']; 
    $fecha_fin_estimada = $_POST['fecha_fin_estimada']; 
    $tipo_servicio = $_POST['tipo_servicio']; // Campo presente en el formulario abajo

    if ($id_cliente === 0 || $id_activo === 0) {
        $mensaje_error = "Error de validación: Debe seleccionar un Cliente y un Activo válidos.";
    } 
    elseif (empty($fecha_inicio) || empty($fecha_fin_estimada)) {
         $mensaje_error = "Error de validación: Las fechas de inicio y fin son obligatorias.";
    }
    elseif ($fecha_fin_estimada < $fecha_inicio) {
        $mensaje_error = "Error de Lógica: La fecha de finalización no puede ser anterior a la fecha de inicio.";
    }
    
    // Si no hay errores, procedemos a insertar
    if (empty($mensaje_error)) {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("INSERT INTO asignacion (fecha_inicio, fecha_fin_estimada, estado, id_cliente, tipo_servicio)
                                             VALUES (:fi, :fe, :es, :idc, :ts)");
            $stmt->execute([
                ':fi' => $fecha_inicio,
                ':fe' => $fecha_fin_estimada,
                ':es' => 'Activo', 
                ':idc' => $id_cliente,
                ':ts' => $tipo_servicio 
            ]);

            $lastInsertId = $conn->lastInsertId(); 

            $stmt2 = $conn->prepare("INSERT INTO detalleasignacion (id_asignacion, id_activo) VALUES (:ida, :idac)");
            $stmt2->execute([
                ':ida' => $lastInsertId,
                ':idac' => $id_activo
            ]);

            $stmt3 = $conn->prepare("UPDATE equipo SET estado = 'Asignado' WHERE id_equipo = :idac");
            $stmt3->execute([':idac' => $id_activo]);


            $conn->commit();
            
            $objUsuarioSistema->redirect("asignaciones.php?inserted&mail_sent=true"); 
            
        } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
      }
    }
}

// Obtener datos para el formulario
$clientes = $conn->query("SELECT id_cliente, nombre FROM cliente"); 
$clientes = $clientes->fetchAll(PDO::FETCH_ASSOC);

$activos = $conn->query("SELECT id_equipo, modelo, marca, tipo FROM equipo WHERE estado = 'Disponible'");
$activos = $activos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<?php require_once 'includes/head.php'; ?>
<body>
<?php require_once 'includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php require_once 'includes/sidebar.php'; ?>
    <main role="main" class="col-md-9 ms-sm-auto col-lg-10 px-4">
      <nav aria-label="breadcrumb" class="mt-3">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Asignaciones</li>
              </ol>
            </nav>
      <h1 class="mt-3">Registrar Asignación de Activo</h1>
      
      <?php if (!empty($mensaje_error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($mensaje_error); ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label for="id_cliente">Cliente (Usuario) *</label>
          <select name="id_cliente" id="id_cliente" class="form-control" required>
            <option value="">Seleccione un cliente</option>
            <?php foreach ($clientes as $row): ?>
              <option value="<?= $row['id_cliente']; ?>"><?= $row['nombre']; ?></option> 
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="id_activo">Activo / Equipo *</label>
          <select name="id_activo" id="id_activo" class="form-control" required>
            <option value="">Seleccione un activo</option>
            <?php foreach ($activos as $row): ?>
              <option value="<?= $row['id_equipo']; ?>">
                <?= $row['tipo'] . " - " . $row['marca'] . " " . $row['modelo']; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="tipo_servicio">Tipo de Servicio *</label>
          <select name="tipo_servicio" id="tipo_servicio" class="form-control" required>
            <option value="Prestamo_Interno">Préstamo Interno</option>
            <option value="Soporte_Tecnico">Asignación de Soporte</option>
            <option value="Asignacion_Fija">Asignación Fija</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="fecha_inicio">Fecha de Inicio de Asignación *</label>
          <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="fecha_fin_estimada">Fecha de Finalización Estimada *</label>
          <input type="date" name="fecha_fin_estimada" id="fecha_fin_estimada" class="form-control" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Registrar Asignación</button>
      </form>
    </main>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin_estimada');

    function actualizarFechaFinMin() {
        if (fechaInicio.value) {
            fechaFin.min = fechaInicio.value;
        }
    }

    fechaInicio.addEventListener('change', actualizarFechaFinMin);
    actualizarFechaFinMin();
});
</script>

</body>
</html>
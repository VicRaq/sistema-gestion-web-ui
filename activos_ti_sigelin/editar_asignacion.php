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

$id_asignacion = $_GET['id'] ?? null;
if (!$id_asignacion) {
    $objUsuarioSistema->redirect("asignaciones.php"); 
    exit;
}

// --- INICIO DEL BLOQUE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = intval($_POST['id_cliente'] ?? 0);
    $id_activo = intval($_POST['id_activo'] ?? 0);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin_estimada = $_POST['fecha_fin_estimada'];
    $fecha_fin_real = $_POST['fecha_fin_real'] ?: null;
    $estado = $_POST['estado']; 
    $tipo_servicio = $_POST['tipo_servicio']; 

    // 5. VALIDACIONES DE LÓGICA (BACKEND)
    
    // Validar Fechas (Asegurarse de que no estén vacías)
    if (empty($fecha_inicio) || empty($fecha_fin_estimada)) {
         $mensaje_error = "Error de validación: Las fechas de inicio y fin estimada son obligatorias.";
    }
    // Validar Fecha Estimada vs Inicio
    elseif ($fecha_fin_estimada < $fecha_inicio) {
         $mensaje_error = "Error de Lógica: La fecha de finalización estimada no puede ser anterior a la fecha de inicio.";
    }
    // Validar Fecha Real vs Inicio (solo si existe fecha real)
    elseif (!empty($fecha_fin_real) && $fecha_fin_real < $fecha_inicio) {
        $mensaje_error = "Error de Lógica: La fecha de devolución REAL no puede ser anterior a la fecha de inicio.";
    }

    // Si las validaciones básicas pasan, proceder a la lógica de negocio
    if (empty($mensaje_error)) {
        
        // Lógica de Devolución Real (Automatización de Estado)
        if (!empty($fecha_fin_real)) {
            $estado = 'Finalizado'; 
        }

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                UPDATE asignacion
                SET fecha_inicio = :fi, fecha_fin_estimada = :ffe, fecha_fin_real = :ffr, estado = :es, id_cliente = :idc, tipo_servicio = :ts
                WHERE id_asignacion = :ida
            ");
            $stmt->execute([
                ':fi' => $fecha_inicio,
                ':ffe' => $fecha_fin_estimada,
                ':ffr' => $fecha_fin_real,
                ':es' => $estado,
                ':idc' => $id_cliente,
                ':ts' => $tipo_servicio,
                ':ida' => $id_asignacion
            ]);

            $stmt2 = $conn->prepare("UPDATE detalleasignacion SET id_activo = :idac WHERE id_asignacion = :ida");
            $stmt2->execute([
                ':idac' => $id_activo,
                ':ida' => $id_asignacion
            ]);

            // Lógica de Negocio (Actualizar estado del equipo)
            if ($estado == 'Finalizado') {
                 $stmt3 = $conn->prepare("UPDATE equipo SET estado = 'Disponible' WHERE id_equipo = :idac");
                 $stmt3->execute([':idac' => $id_activo]);
            } elseif ($estado == 'Reparacion') {
                 // (Esta lógica se mantiene por si el estado se cambia manualmente a Reparación)
                 $stmt3 = $conn->prepare("UPDATE equipo SET estado = 'Reparacion' WHERE id_equipo = :idac");
                 $stmt3->execute([':idac' => $id_activo]);
            }

            $conn->commit();
            $objUsuarioSistema->redirect("asignaciones.php?updated");
        } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
    }
    }
}
// --- FIN DEL BLOQUE POST CORREGIDO ---


// (La lógica de OBTENER DATOS se mantiene igual)
$stmt = $conn->prepare("
    SELECT a.*, da.id_activo, a.id_cliente
    FROM asignacion a
    JOIN detalleasignacion da ON a.id_asignacion = da.id_asignacion
    WHERE a.id_asignacion = :ida
");
$stmt->execute([':ida' => $id_asignacion]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$clientes = $conn->query("SELECT id_cliente, nombre FROM cliente");
$clientes = $clientes->fetchAll(PDO::FETCH_ASSOC);

$activos = $conn->query("SELECT id_equipo, modelo, marca, tipo FROM equipo");
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
        <main class="col-md-9 ms-sm-auto col-lg-10 px-4">
            
            <nav aria-label="breadcrumb" class="mt-3">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="asignaciones.php">Asignaciones</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
              </ol>
            </nav>

            <h2 class="mt-3">Editar Asignación de Activo (ID: <?= $id_asignacion ?>)</h2>
            
            <?php if (!empty($mensaje_error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($mensaje_error); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                  <label for="id_cliente">Cliente *</label>
                  <select name="id_cliente" class="form-control" required>
                      <?php foreach ($clientes as $row): ?>
                          <option value="<?= $row['id_cliente']; ?>" <?= $data['id_cliente'] == $row['id_cliente'] ? 'selected' : '' ?>>
                              <?= $row['nombre']; ?>
                          </option>
                      <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label for="id_activo">Activo / Equipo *</label>
                  <select name="id_activo" class="form-control" required>
                      <?php foreach ($activos as $row): ?>
                           <option value="<?= $row['id_equipo']; ?>" <?= $data['id_activo'] == $row['id_equipo'] ? 'selected' : '' ?>>
                               <?= $row['tipo'] . " - " . $row['marca'] . " " . $row['modelo']; ?>
                          </option>
                      <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="tipo_servicio">Tipo de Servicio *</label>
                  <select name="tipo_servicio" class="form-control" required>
                       <option value="Prestamo_Interno" <?= $data['tipo_servicio'] == 'Prestamo_Interno' ? 'selected' : '' ?>>Préstamo Interno</option>
                       <option value="Soporte_Tecnico" <?= $data['tipo_servicio'] == 'Soporte_Tecnico' ? 'selected' : '' ?>>Asignación de Soporte</option>
                       <option value="Asignacion_Fija" <?= $data['tipo_servicio'] == 'Asignacion_Fija' ? 'selected' : '' ?>>Asignación Fija</option>
                  </select>
                </div>

                <div class="form-group">
                    <label>Fecha Solicitud *</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required value="<?= $data['fecha_inicio']; ?>">
                </div>

                <div class="form-group">
                    <label>Fecha Devolución Estimada *</label>
                    <input type="date" name="fecha_fin_estimada" id="fecha_fin_estimada" class="form-control" required value="<?= $data['fecha_fin_estimada']; ?>">
                </div>

                <div class="form-group">
                    <label>Fecha Devolución Real</label>
                    <input type="date" name="fecha_fin_real" id="fecha_fin_real" class="form-control" value="<?= $data['fecha_fin_real']; ?>">
                </div>

                <div class="form-group">
                    <label>Estado *</label>
                    <select name="estado" id="estado" class="form-control" required>
                        <option value="Activo" <?= $data['estado'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Finalizado" <?= $data['estado'] == 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
                        <option value="Pendiente" <?= $data['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
                <a href="asignaciones.php" class="btn btn-secondary mt-3">Volver</a>
            </form>
        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFinEstimada = document.getElementById('fecha_fin_estimada');
    const fechaFinReal = document.getElementById('fecha_fin_real');
    const estadoSelect = document.getElementById('estado');

    function actualizarFechasMin() {
        if (fechaInicio.value) {
            fechaFinEstimada.min = fechaInicio.value;
            fechaFinReal.min = fechaInicio.value; 
        }
    }

    fechaFinReal.addEventListener('change', function() {
        if (this.value !== '' && this.value >= fechaInicio.value) {
            alert('Al registrar una Fecha de Devolución Real, el estado de la asignación se cambiará automáticamente a "Finalizado".');
            estadoSelect.value = 'Finalizado';
        }
    });

    fechaInicio.addEventListener('change', actualizarFechasMin);
    actualizarFechasMin(); 
});
</script>

</body>
</html>
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
// (Según la matriz, solo 'administrador' y 'tecnico' pueden editar reparaciones)
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

$id_reparacion = $_GET['id'] ?? null;
if (!$id_reparacion) {
    // Usar redirect()
    $objUsuarioSistema->redirect("reparaciones.php");
    exit;
}

// OBTENER EQUIPOS 
$stmtEquipos = $conn->query("SELECT id_equipo, modelo, tipo, marca FROM equipo ORDER BY modelo");
$equipos = $stmtEquipos->fetchAll(PDO::FETCH_ASSOC);

// OBTENER ENCARGADOS 
$stmtEncargados = $conn->query("SELECT id_usuario AS id_encargado, nombre FROM usuariosistema WHERE rol IN ('tecnico', 'administrador') ORDER BY nombre");
$encargados = $stmtEncargados->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos actuales del registro 
$stmt = $conn->prepare("SELECT * FROM reparacion WHERE id_reparacion = :id");
$stmt->execute([':id' => $id_reparacion]);
$reparacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reparacion) {
    echo "Registro de reparación no encontrado.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_equipo = intval($_POST['id_equipo'] ?? 0);
    $id_encargado = intval($_POST['id_encargado'] ?? 0); // Este es el id_usuario_sistema
    
    if ($id_equipo === 0 || $id_encargado === 0) {
        die("Error de validación: ID de Equipo o Encargado inválido.");
    }

    $fecha = $_POST['fecha'];
    $tipo_falla = $_POST['tipo_falla'];
    $estado = $_POST['estado'];
    $descripcion = $_POST['descripcion'];

    try {
        $conn->beginTransaction(); 

        // 1. Actualizar la tabla Reparacion 
        $stmtReparacion = $conn->prepare("
            UPDATE reparacion SET id_equipo = :id_equipo, id_encargado = :id_encargado, fecha = :fecha, tipo_falla = :tipo_falla, estado = :estado, descripcion = :descripcion
            WHERE id_reparacion = :id
        ");
        $stmtReparacion->execute([
            ':id_equipo' => $id_equipo,
            ':id_encargado' => $id_encargado, // Usamos el ID del usuario del sistema
            ':fecha' => $fecha,
            ':tipo_falla' => $tipo_falla,
            ':estado' => $estado,
            ':descripcion' => $descripcion,
            ':id' => $id_reparacion
        ]);

        // 2. Lógica de Negocio 
        if ($estado == 'Finalizada') {
            $stmtUpdate = $conn->prepare("UPDATE equipo SET estado = 'Disponible' WHERE id_equipo = :id_equipo");
            $stmtUpdate->execute([':id_equipo' => $id_equipo]);
        } elseif ($estado == 'En Progreso') {
            $stmtUpdate = $conn->prepare("UPDATE equipo SET estado = 'Reparacion' WHERE id_equipo = :id_equipo");
            $stmtUpdate->execute([':id_equipo' => $id_equipo]);
        }
        
        $conn->commit();
        
        // Usar redirect()
        $objUsuarioSistema->redirect("reparaciones.php?updated");
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
            <h2 class="mt-3">Editar Registro de Reparación (ID: <?= $id_reparacion ?>)</h2>
            
            <form method="post">
                <div class="form-group mb-3">
                    <label>Equipo Afectado *</label>
                    <select name="id_equipo" class="form-control" required>
                        <?php foreach ($equipos as $e): ?>
                            <option value="<?= $e['id_equipo']; ?>" <?= $reparacion['id_equipo'] == $e['id_equipo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['tipo'] . ' - ' . $e['marca'] . ' ' . $e['modelo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>Encargado de la Reparación *</label>
                    <select name="id_encargado" class="form-control" required>
                        <?php foreach ($encargados as $r): ?>
                            <option value="<?= $r['id_encargado']; ?>" <?= $reparacion['id_encargado'] == $r['id_encargado'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label>Fecha Solicitud *</label>
                    <input type="date" name="fecha" class="form-control" required value="<?= $reparacion['fecha']; ?>">
                </div>
                
                <div class="form-group mb-3">
                    <label>Tipo de Falla *</label>
                    <input type="text" name="tipo_falla" class="form-control" required value="<?= htmlspecialchars($reparacion['tipo_falla']); ?>" maxlength="100">
                </div>

                <div class="form-group mb-3">
                    <label>Estado *</label>
                    <select name="estado" class="form-control" required>
                        <option value="Pendiente" <?= $reparacion['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="En Progreso" <?= $reparacion['estado'] == 'En Progreso' ? 'selected' : '' ?>>En Progreso</option>
                        <option value="Finalizada" <?= $reparacion['estado'] == 'Finalizada' ? 'selected' : '' ?>>Finalizada</option>
                        <option value="Cancelada" <?= $reparacion['estado'] == 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>Descripción y Observaciones</label>
                    <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($reparacion['descripcion']); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
                <a href="reparaciones.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
<?php
// 1. GUARDIA DE AUTENTICACIÓN (¿Estás logueado?)
require_once 'includes/auth_check.php'; 

// 2. NUEVO: GUARDIA DE AUTORIZACIÓN (¿Tienes el ROL correcto?)
// Según la matriz, solo 'administrador' y 'tecnico' pueden ver esto.
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
    // Si no es admin o tecnico, lo expulsamos.
    require_once 'classes/user.php'; 
    $objUsuarioSistema = new UsuarioSistema(); 
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado');
    exit;
}

// 3. Cargar el resto (Clases necesarias)
require_once 'classes/database.php';
require_once 'classes/user.php'; 

$db = new Database();
$conn = $db->dbConnection();
$objUsuarioSistema = new UsuarioSistema(); // <-- Necesario para el redirect()

// Eliminar Reparación 
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM reparacion WHERE id_reparacion = :id");
        $stmt->execute([':id' => $id]);
        
        $objUsuarioSistema->redirect('reparaciones.php?deleted');
    } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
    }
}

// Obtener Reparaciones actuales
$stmt = $conn->prepare("
    SELECT
        r.id_reparacion,
        r.fecha,
        r.tipo_falla,
        r.estado,
        e.modelo AS nombre_equipo, 
        ac.nombre AS nombre_encargado
    FROM reparacion r 
    JOIN equipo e ON r.id_equipo = e.id_equipo 
    JOIN usuariosistema ac ON r.id_encargado = ac.id_usuario
    ORDER BY r.fecha DESC
");
$stmt->execute();
$reparaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h1 class="mt-3">Historial de Reparaciones / Incidentes</h1>
            <a href="agregar_reparacion.php" class="btn btn-success mb-3">Registrar Nueva Reparación</a>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Equipo</th> 
                            <th>Encargado</th>
                            <th>Tipo de Falla</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reparaciones as $r): ?>
                            <tr>
                                <td><?= $r['id_reparacion']; ?></td>
                                <td><?= $r['fecha']; ?></td>
                                <td><?= htmlspecialchars($r['nombre_equipo']); ?></td>
                                <td><?= htmlspecialchars($r['nombre_encargado']); ?></td>
                                <td><?= htmlspecialchars($r['tipo_falla']); ?></td>
                                <td>
                                    <span class="badge bg-<?= $r['estado'] == 'Finalizada' ? 'success' : ($r['estado'] == 'En Progreso' ? 'warning text-dark' : 'danger'); ?>">
                                        <?= $r['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="editar_reparacion.php?id=<?= $r['id_reparacion']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="reparaciones.php?delete_id=<?= $r['id_reparacion']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('¿Seguro que deseas eliminar esta reparación?')">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
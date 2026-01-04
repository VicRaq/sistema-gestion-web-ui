<?php
// 1. GUARDIA DE AUTENTICACIÓN
require_once 'includes/auth_check.php';

// 2. GUARDIA DE AUTORIZACIÓN (Solo Admin)
// (Este archivo es exclusivo para la gestión de usuarios y vista global)
if ($CURRENT_USER_ROL != 'administrador') {
    require_once 'classes/user.php';
    $objUsuarioSistema = new UsuarioSistema();
    $objUsuarioSistema->redirect('equipos.php?error=acceso_denegado');
    exit;
}

// 3. Cargar Clases
require_once 'classes/user.php'; 
require_once 'classes/database.php';
$db = new Database();
$conn = $db->dbConnection(); 
$objUsuarioSistema = new UsuarioSistema(); 

// Lógica de Eliminar Usuario 
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        if ($id != null) {
            if ($objUsuarioSistema->delete($id)) { 
                $objUsuarioSistema->redirect('index.php?deleted');
            }
        }
    } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
    }
}

// --- NUEVO: OBTENER ESTADÍSTICAS RÁPIDAS (KPIs) ---
// tablas en minúsculas para evitar errores

// 1. Total Equipos en Inventario
$stmt = $conn->query("SELECT COUNT(*) FROM equipo");
$total_equipos = $stmt->fetchColumn();

// 2. Préstamos Activos (Equipos que están fuera)
$stmt = $conn->query("SELECT COUNT(*) FROM asignacion WHERE estado = 'Activo'");
$total_asignaciones = $stmt->fetchColumn();

// 3. Reparaciones en Progreso (Equipos en mantenimiento)
$stmt = $conn->query("SELECT COUNT(*) FROM reparacion WHERE estado = 'En Progreso'");
$total_reparaciones = $stmt->fetchColumn();
// --------------------------------------------------

// Obtener lista de usuarios del sistema 
$query = "SELECT id_usuario, nombre, correo_electronico, rol FROM usuariosistema";
$stmt = $conn->prepare($query); 
$stmt->execute();
?>

<!doctype html>
<html lang="es">
<?php require_once 'includes/head.php'; ?>
<body>
<?php require_once 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>

        <main role="main" class="col-md-9 ms-sm-auto col-lg-10 px-4">
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Panel de Control (Dashboard)</h1>
            </div>
            
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">Inicio</li>
              </ol>
            </nav>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3 shadow-sm">
                        <div class="card-header">Equipos en Inventario</div>
                        <div class="card-body">
                            <h1 class="card-title display-4"><?= $total_equipos; ?></h1>
                            <p class="card-text">Total de activos registrados en el sistema.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3 shadow-sm">
                        <div class="card-header">Préstamos Activos</div>
                        <div class="card-body">
                            <h1 class="card-title display-4"><?= $total_asignaciones; ?></h1>
                            <p class="card-text">Equipos actualmente prestados a clientes.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-white bg-warning mb-3 shadow-sm">
                        <div class="card-header text-dark">Mantenimiento</div>
                        <div class="card-body text-dark">
                            <h1 class="card-title display-4"><?= $total_reparaciones; ?></h1>
                            <p class="card-text">Equipos actualmente en reparación.</p>
                        </div>
                    </div>
                </div>
            </div>
            <h2 class="mt-5">Gestión de Usuarios del Sistema</h2>
            <p class="text-muted">Administración de cuentas de acceso al backend (Técnicos, Compras, etc.)</p>

            <?php
            if (isset($_GET['updated'])) echo '<div class="alert alert-info">Usuario actualizado.</div>';
            elseif (isset($_GET['deleted'])) echo '<div class="alert alert-info">Usuario eliminado.</div>';
            elseif (isset($_GET['inserted'])) echo '<div class="alert alert-success">Usuario agregado.</div>';
            elseif (isset($_GET['error'])) echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($_GET['error']) . '</div>';
            ?>

            <a href="form.php" class="btn btn-outline-primary mb-3">Agregar Nuevo Usuario</a>

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th> 
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <tr>
                                <td><?= $row['id_usuario']; ?></td>
                                <td><?= htmlspecialchars($row['nombre']); ?></td>
                                <td><?= htmlspecialchars($row['correo_electronico']); ?></td>
                                <td>
                                    <span class="badge bg-<?= ($row['rol'] == 'administrador') ? 'dark' : 'secondary'; ?>">
                                        <?= htmlspecialchars($row['rol']); ?>
                                    </span>
                                </td> 
                                <td>
                                    <a href="form.php?edit_id=<?= $row['id_usuario']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a class="confirmation btn btn-sm btn-danger" href="index.php?delete_id=<?= $row['id_usuario']; ?>">Eliminar</a>
                                </td>
                            </tr>
                        <?php }
                    } else {
                        echo '<tr><td colspan="5" class="text-center">No hay usuarios registrados.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    document.querySelectorAll('.confirmation').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm('¿Deseas eliminar este usuario?')) {
                e.preventDefault();
            }
        });
    });
</script>

</body>
</html>
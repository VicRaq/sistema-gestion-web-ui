<?php
// 1. GUARDIA DE AUTENTICACIÓN
require_once 'includes/auth_check.php'; 

// 2. GUARDIA DE AUTORIZACIÓN (RBAC)
// (Permitimos admin, tecnico, y compras VER el listado)
if (!in_array($CURRENT_USER_ROL, ['administrador', 'tecnico', 'compras'])) {
    require_once 'classes/user.php'; 
    $objUsuarioSistema = new UsuarioSistema(); 
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado');
    exit;
}

// 3. Cargar el resto
require_once 'classes/database.php';
require_once 'classes/user.php'; 
$db = new Database();
$conn = $db->dbConnection();
$objUsuarioSistema = new UsuarioSistema(); 

// Eliminar cliente
if (isset($_GET['delete_id'])) {
    
    // 4. Guardia RBAC para la ACCIÓN de eliminar
    // (Solo admin y tecnico pueden eliminar)
    if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
        $objUsuarioSistema->redirect('clientes.php?error=acceso_denegado_accion');
        exit;
    }

    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM cliente WHERE id_cliente = :id");
    $stmt->execute([':id' => $id]);
    $objUsuarioSistema->redirect("clientes.php?deleted"); 
    exit;
}

// (La consulta SELECT se mantiene igual, ya está corregida)
$stmt = $conn->prepare("
    SELECT id_cliente, nombre, tipo_cliente, direccion, telefono, rut 
    FROM cliente
    ORDER BY nombre
");
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h1 class="mt-3">Gestión de Clientes</h1>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'acceso_denegado_accion'): ?>
                <div class="alert alert-danger">No tiene permisos para realizar esa acción.</div>
            <?php endif; ?>
            
            <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'tecnico'): ?>
                <a href="agregar_cliente.php" class="btn btn-success mb-3">Registrar Nuevo Cliente</a>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo de Cliente</th> 
                            <th>RUT</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td><?= $c['id_cliente']; ?></td>
                                <td><?= htmlspecialchars($c['nombre'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($c['tipo_cliente'] ?? ''); ?></td> 
                                <td><?= htmlspecialchars($c['rut'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($c['direccion'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($c['telefono'] ?? ''); ?></td>
                                <td>
                                    <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'tecnico'): ?>
                                        <a href="editar_cliente.php?id=<?= $c['id_cliente']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <a href="clientes.php?delete_id=<?= $c['id_cliente']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('¿Eliminar este cliente?')">Eliminar</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Solo lectura</span>
                                    <?php endif; ?>
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
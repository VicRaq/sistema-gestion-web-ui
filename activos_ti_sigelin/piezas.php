<?php
// 1. GUARDIA DE AUTENTICACIÓN
require_once 'includes/auth_check.php'; 

// 2. Cargar Clases
require_once 'classes/database.php';
require_once 'classes/user.php'; 
$db = new Database();
$conn = $db->dbConnection();
$objUsuarioSistema = new UsuarioSistema(); 

// Eliminar Pieza 
if (isset($_GET['delete_id'])) {
    
    // 3. Guardia RBAC para la ACCIÓN de eliminar
    if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
        $objUsuarioSistema->redirect('piezas.php?error=acceso_denegado_accion');
        exit;
    }

    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM piezahardware WHERE id_pieza = :id");
        $stmt->execute([':id' => $id]);
        $objUsuarioSistema->redirect("piezas.php?deleted"); 
        exit;
    } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
    }
}

// --- LÓGICA DE BÚSQUEDA Y FILTRADO (NUEVO) ---

$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';

// Construcción dinámica de la consulta SQL
$sql = "
    SELECT id_pieza, nombre, cantidad, estado
    FROM piezahardware
    WHERE 1=1
";

$params = [];

// Filtro por Nombre (Búsqueda de texto)
if (!empty($filtro_busqueda)) {
    $sql .= " AND nombre LIKE :busqueda";
    $params[':busqueda'] = "%" . $filtro_busqueda . "%";
}

// Filtro por Estado
if (!empty($filtro_estado)) {
    $sql .= " AND estado = :estado";
    $params[':estado'] = $filtro_estado;
}

$sql .= " ORDER BY nombre";

// Ejecutar consulta con filtros
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$piezas = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h1 class="mt-3">Inventario de Piezas de Hardware</h1>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Pieza eliminada correctamente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['inserted'])): ?>
                <div class="alert alert-success">Pieza agregada correctamente.</div>
            <?php endif; ?>
             <?php if (isset($_GET['error']) && $_GET['error'] == 'acceso_denegado_accion'): ?>
                <div class="alert alert-danger">No tiene permisos para realizar esa acción.</div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                
                <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'tecnico'): ?>
                    <a href="agregar_pieza.php" class="btn btn-success me-2">Registrar Nueva Pieza</a>
                <?php else: ?>
                    <div></div> <?php endif; ?>

                <form class="d-flex" method="GET">
                    <input class="form-control me-2" type="search" name="busqueda" placeholder="Buscar pieza..." aria-label="Search" value="<?= htmlspecialchars($filtro_busqueda) ?>">
                    
                    <select class="form-select me-2" name="estado" style="width: auto;">
                        <option value="">-- Estado --</option>
                        <option value="Nuevo" <?= $filtro_estado == 'Nuevo' ? 'selected' : '' ?>>Nuevo</option>
                        <option value="Usado" <?= $filtro_estado == 'Usado' ? 'selected' : '' ?>>Usado</option>
                        <option value="Agotado" <?= $filtro_estado == 'Agotado' ? 'selected' : '' ?>>Agotado</option>
                    </select>

                    <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                    
                    <?php if($filtro_busqueda || $filtro_estado): ?>
                        <a href="piezas.php" class="btn btn-outline-secondary ms-1">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Cantidad en Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($piezas) > 0): ?>
                            <?php foreach ($piezas as $p): ?>
                                <tr>
                                    <td><?= $p['id_pieza']; ?></td>
                                    <td><?= htmlspecialchars($p['nombre'] ?? ''); ?></td>
                                    <td><?= $p['cantidad']; ?></td>
                                    <td>
                                        <span class="badge bg-<?= $p['estado'] == 'Nuevo' ? 'success' : ($p['estado'] == 'Usado' ? 'info' : 'danger'); ?>">
                                            <?= $p['estado']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'tecnico'): ?>
                                            <a href="editar_piezas.php?id=<?= $p['id_pieza']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <a href="piezas.php?delete_id=<?= $p['id_pieza']; ?>" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="return confirm('¿Seguro que deseas eliminar esta pieza?')">Eliminar</a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Solo lectura</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    No se encontraron piezas con los criterios de búsqueda.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
<?php
// 1. GUARDIA DE AUTENTICACIÓN
require_once 'includes/auth_check.php'; 

// 2. Cargar Clases
require_once 'classes/database.php';
require_once 'classes/user.php'; 
$db = new Database();
$conn = $db->dbConnection();
$objUsuarioSistema = new UsuarioSistema(); 

// Lógica de Eliminar 
if (isset($_GET['delete_id'])) {
    if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
        $objUsuarioSistema->redirect('equipos.php?error=acceso_denegado_accion');
        exit;
    }
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM equipo WHERE id_equipo = :id");
        $stmt->execute([':id' => $id]);
        $objUsuarioSistema->redirect("equipos.php?deleted");
        exit;
    } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
    }
}

// --- LÓGICA DE BÚSQUEDA Y FILTRADO ---
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';

$sql = "
    SELECT
        e.id_equipo, e.tipo, e.modelo, e.marca, e.estado,
        qr.codigo_qr,
        u.laboratorio, u.sala
    FROM equipo e
    LEFT JOIN qr qr ON e.id_equipo = qr.id_equipo
    LEFT JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
    WHERE 1=1 
";

$params = [];

if (!empty($filtro_busqueda)) {
    $sql .= " AND (e.modelo LIKE :busqueda OR e.marca LIKE :busqueda OR e.tipo LIKE :busqueda OR qr.codigo_qr LIKE :busqueda)";
    $params[':busqueda'] = "%" . $filtro_busqueda . "%";
}

if (!empty($filtro_estado)) {
    $sql .= " AND e.estado = :estado";
    $params[':estado'] = $filtro_estado;
}

$sql .= " ORDER BY e.id_equipo DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <li class="breadcrumb-item active" aria-current="page">Inventario de Equipos</li>
              </ol>
            </nav>

            <h1 class="mt-3">Inventario de Activos y Equipos</h1>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Equipo eliminado correctamente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['inserted'])): ?>
                <div class="alert alert-success">Equipo agregado correctamente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] == 'acceso_denegado_accion'): ?>
                <div class="alert alert-danger">No tiene permisos para realizar esa acción.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] == 'qr_no_encontrado'): ?>
                <div class="alert alert-warning">
                    <strong><i data-feather="alert-circle"></i> QR no encontrado:</strong> El código escaneado no corresponde a ningún equipo registrado.
                </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                
                <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'tecnico'): ?>
                    <a href="agregar_equipo.php" class="btn btn-success me-2">Registrar Nuevo Activo</a>
                <?php else: ?>
                    <div></div> 
                <?php endif; ?>

                <form class="d-flex" method="GET">
                    <input class="form-control me-2" type="search" name="busqueda" placeholder="Buscar modelo, marca, QR..." aria-label="Search" value="<?= htmlspecialchars($filtro_busqueda) ?>">
                    
                    <select class="form-select me-2" name="estado" style="width: auto;">
                        <option value="">-- Todos --</option>
                        <option value="Disponible" <?= $filtro_estado == 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="Asignado" <?= $filtro_estado == 'Asignado' ? 'selected' : '' ?>>Asignado</option>
                        <option value="Reparacion" <?= $filtro_estado == 'Reparacion' ? 'selected' : '' ?>>En Reparación</option>
                        <option value="Baja" <?= $filtro_estado == 'Baja' ? 'selected' : '' ?>>Baja</option>
                    </select>

                    <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                    <?php if($filtro_busqueda || $filtro_estado): ?>
                        <a href="equipos.php" class="btn btn-outline-secondary ms-1">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Ubicación</th>
                            <th>Cód. QR</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($equipos) > 0): ?>
                            <?php foreach ($equipos as $e): ?>
                                <tr>
                                    <td><?= $e['id_equipo']; ?></td>
                                    <td><?= htmlspecialchars($e['tipo'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars(($e['marca'] ?? '') . ' / ' . ($e['modelo'] ?? '')); ?></td>
                                    <td><?= htmlspecialchars(($e['laboratorio'] ?? '') . ' - ' . ($e['sala'] ?? '')); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i data-feather="maximize"></i> <?= htmlspecialchars($e['codigo_qr'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $e['estado'] == 'Disponible' ? 'success' : ($e['estado'] == 'Reparacion' ? 'warning text-dark' : ($e['estado'] == 'Asignado' ? 'primary' : 'danger')); ?>">
                                            <?= $e['estado']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="ver_equipo.php?id=<?= $e['id_equipo']; ?>" class="btn btn-sm btn-info text-white" title="Ver Ficha y QR">
                                                <span data-feather="eye"></span>
                                            </a>

                                            <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'tecnico'): ?>
                                                <a href="editar_equipo.php?id=<?= $e['id_equipo']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                    <span data-feather="edit"></span>
                                                </a>
                                                <a href="equipos.php?delete_id=<?= $e['id_equipo']; ?>" 
                                                class="btn btn-sm btn-danger" 
                                                onclick="return confirm('⚠️ Advertencia: ¿Eliminar este equipo y toda su trazabilidad?')" title="Eliminar">
                                                    <span data-feather="trash-2"></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    No se encontraron equipos con los criterios de búsqueda.
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
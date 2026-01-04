<?php
// 1. GUARDIA DE AUTENTICACIÓN
require_once 'includes/auth_check.php'; 

// 2. GUARDIA DE AUTORIZACIÓN (RBAC)
if (!in_array($CURRENT_USER_ROL, ['administrador', 'tecnico', 'compras'])) {
    require_once 'classes/user.php'; 
    $objUsuarioSistema = new UsuarioSistema(); 
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado');
    exit;
}

// 3. Cargar el resto
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'classes/database.php';
$db = new Database();
$conn = $db->dbConnection(); // Objeto de conexión PDO

require_once 'classes/user.php'; 
$objUsuarioSistema = new UsuarioSistema(); 


// --- LÓGICA DE BÚSQUEDA Y FILTRADO ---

$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';

// Construcción dinámica de la consulta SQL
$sql = "
    SELECT
        a.id_asignacion,
        c.nombre AS nombre_cliente,
        ac.modelo AS nombre_activo, 
        a.fecha_inicio,
        a.fecha_fin_estimada,
        a.fecha_fin_real,
        a.estado,
        a.tipo_servicio
    FROM asignacion a 
    JOIN cliente c ON a.id_cliente = c.id_cliente 
    JOIN detalleasignacion da ON a.id_asignacion = da.id_asignacion 
    JOIN equipo ac ON da.id_activo = ac.id_equipo 
    WHERE 1=1
";

$params = [];

// Filtros
if (!empty($filtro_busqueda)) {
    $sql .= " AND (c.nombre LIKE :busqueda OR ac.modelo LIKE :busqueda OR a.tipo_servicio LIKE :busqueda)";
    $params[':busqueda'] = "%" . $filtro_busqueda . "%";
}

if (!empty($filtro_estado)) {
    $sql .= " AND a.estado = :estado";
    $params[':estado'] = $filtro_estado;
}

$sql .= " ORDER BY a.fecha_inicio DESC";

// Ejecutar consulta
$stmt = $conn->prepare($sql); 
$stmt->execute($params);
$asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h1 class="mt-3">Registro de Asignaciones de Equipos</h1>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                
                <a href="agregar_asignacion.php" class="btn btn-success me-2">Registrar Asignación</a>

                <form class="d-flex" method="GET">
                    <input class="form-control me-2" type="search" name="busqueda" placeholder="Cliente, Equipo, Servicio..." aria-label="Search" value="<?= htmlspecialchars($filtro_busqueda) ?>">
                    
                    <select class="form-select me-2" name="estado" style="width: auto;">
                        <option value="">-- Estado --</option>
                        <option value="Activo" <?= $filtro_estado == 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Pendiente" <?= $filtro_estado == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="Finalizado" <?= $filtro_estado == 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
                    </select>

                    <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                    
                    <?php if($filtro_busqueda || $filtro_estado): ?>
                        <a href="asignaciones.php" class="btn btn-outline-secondary ms-1">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (isset($_GET['mail_sent'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong><i data-feather="mail"></i> Notificación Enviada:</strong> 
                    Se ha enviado un comprobante digital al correo institucional del cliente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Asignación actualizada correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Activo / Modelo</th> 
                            <th>Tipo Servicio</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin Est.</th>
                            <th>Fecha Fin Real</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($asignaciones) > 0): ?>
                            <?php foreach ($asignaciones as $row): ?>
                                <?php
                                // Atenuar las filas finalizadas (Borrado Lógico)
                                $clase_css = ($row['estado'] == 'Finalizado') ? 'text-muted' : '';
                                ?>
                                <tr class="<?= $clase_css; ?>">
                                    <td><?= $row['id_asignacion']; ?></td>
                                    <td><?= htmlspecialchars($row['nombre_cliente'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($row['nombre_activo'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($row['tipo_servicio'] ?? ''); ?></td>
                                    <td><?= $row['fecha_inicio']; ?></td>
                                    <td><?= $row['fecha_fin_estimada']; ?></td>
                                    <td><?= $row['fecha_fin_real'] ?? '-'; ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['estado'] == 'Finalizado' ? 'secondary' : ($row['estado'] == 'Activo' ? 'success' : 'warning text-dark'); ?>">
                                            <?= $row['estado']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['estado'] != 'Finalizado'): ?>
                                            <a href="editar_asignacion.php?id=<?= $row['id_asignacion']; ?>" class="btn btn-sm btn-warning">Editar / Finalizar</a>
                                        <?php else: ?>
                                            <span class="badge bg-dark">Cerrado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-4">No se encontraron asignaciones con los criterios de búsqueda.</td></tr>
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
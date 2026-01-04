<?php
// 1. SEGURIDAD
require_once 'includes/auth_check.php';
require_once 'classes/database.php';
require_once 'classes/user.php';

$db = new Database();
$conn = $db->dbConnection();
$objUsuarioSistema = new UsuarioSistema();

// 2. VALIDAR ID
$id_equipo = $_GET['id'] ?? null;
if (!$id_equipo) {
    $objUsuarioSistema->redirect("equipos.php");
    exit;
}

// 3. OBTENER DATOS DEL EQUIPO (JOIN con Ubicación y QR)
$stmt = $conn->prepare("
    SELECT e.*, qr.codigo_qr, u.laboratorio, u.sala
    FROM equipo e
    LEFT JOIN qr qr ON e.id_equipo = qr.id_equipo
    LEFT JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
    WHERE e.id_equipo = :id
");
$stmt->execute([':id' => $id_equipo]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    echo "Equipo no encontrado.";
    exit;
}

// 4. OBTENER HISTORIAL DE REPARACIONES
$stmtRep = $conn->prepare("
    SELECT r.*, us.nombre as nombre_tecnico
    FROM reparacion r
    LEFT JOIN usuariosistema us ON r.id_encargado = us.id_usuario
    WHERE r.id_equipo = :id
    ORDER BY r.fecha DESC
");
$stmtRep->execute([':id' => $id_equipo]);
$historial = $stmtRep->fetchAll(PDO::FETCH_ASSOC);

// 5. GENERAR URL PARA EL QR
$base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$url_ficha = $base_url . "/ver_equipo.php?id=" . $id_equipo;

// API para generar imagen QR (Google Charts o QRServer)
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($url_ficha);
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
                <li class="breadcrumb-item"><a href="equipos.php">Inventario</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ficha Técnica</li>
              </ol>
            </nav>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <?= htmlspecialchars($equipo['tipo'] . ' ' . $equipo['marca']); ?> 
                    <span class="text-muted small">(<?= htmlspecialchars($equipo['modelo']); ?>)</span>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="badge bg-<?= $equipo['estado'] == 'Disponible' ? 'success' : 'warning text-dark'; ?> fs-6 me-2">
                        <?= $equipo['estado']; ?>
                    </span>
                    <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'tecnico'): ?>
                        <a href="editar_equipo.php?id=<?= $id_equipo; ?>" class="btn btn-sm btn-outline-secondary">
                            <span data-feather="edit"></span> Editar
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card mb-4 text-center shadow-sm">
                        <div class="card-header bg-light">Etiqueta de Inventario</div>
                        <div class="card-body">
                            <img src="<?= $qr_image_url; ?>" alt="Código QR" class="img-fluid mb-2" style="max-width: 150px;">
                            <h5 class="card-title"><?= htmlspecialchars($equipo['codigo_qr'] ?? 'S/N'); ?></h5>
                            <p class="card-text small text-muted">Escanea para ver ficha digital</p>
                            <button onclick="window.print()" class="btn btn-sm btn-primary no-print">
                                <span data-feather="printer"></span> Imprimir Ficha
                            </button>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header">Ubicación y Detalles</div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Laboratorio:</strong> <?= htmlspecialchars($equipo['laboratorio'] ?? 'N/A'); ?></li>
                            <li class="list-group-item"><strong>Sala:</strong> <?= htmlspecialchars($equipo['sala'] ?? 'N/A'); ?></li>
                            <li class="list-group-item"><strong>Asignación:</strong> <?= htmlspecialchars($equipo['lugar_asignado'] ?? 'Sin asignar'); ?></li>
                            <li class="list-group-item"><strong>ID Interno:</strong> #<?= $equipo['id_equipo']; ?></li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><span data-feather="activity"></span> Historial de Reparaciones</span>
                            <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'tecnico'): ?>
                                <a href="agregar_reparacion.php" class="btn btn-sm btn-primary">+ Nueva Falla</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Falla</th>
                                            <th>Técnico</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($historial) > 0): ?>
                                            <?php foreach ($historial as $h): ?>
                                                <tr>
                                                    <td><?= $h['fecha']; ?></td>
                                                    <td><?= htmlspecialchars($h['tipo_falla']); ?></td>
                                                    <td><?= htmlspecialchars($h['nombre_tecnico'] ?? 'Desconocido'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $h['estado'] == 'Finalizada' ? 'success' : 'warning text-dark'; ?>">
                                                            <?= $h['estado']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-3">Sin historial registrado.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<style>
@media print {
    .sidebar, .navbar, .breadcrumb, .no-print, .btn { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; }
    .card { border: 1px solid #ddd; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
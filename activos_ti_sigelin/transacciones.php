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
// (Según la matriz, solo 'administrador' y 'compras' pueden ver esta página)
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'compras') {
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado');
    exit;
}

// Eliminar Transacción si viene por GET
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // 1. Obtener datos antes de borrar 
    $stmtDatos = $conn->prepare("
        SELECT dt.*, t.total, t.id_transaccion
        FROM detalletransaccion dt
        JOIN transaccion t ON dt.id_transaccion = t.id_transaccion
        WHERE dt.id_detalle_transaccion = :id
    ");
    $stmtDatos->execute([':id' => $delete_id]);
    $transaccion = $stmtDatos->fetch(PDO::FETCH_ASSOC);

    if ($transaccion) {
        // 2. Insertar en el Log de Transacciones 
        $stmtLog = $conn->prepare("
            INSERT INTO logeliminaciontransaccion
            (id_transaccion, nombre_item, cantidad, precio_unitario, total, motivo, eliminado_por)
            VALUES (:id_transaccion, :item, :cantidad, :precio, :total, :motivo, :usuario)
        ");
        $stmtLog->execute([
            ':id_transaccion' => $transaccion['id_transaccion'],
            ':item'           => $transaccion['nombre_item'], 
            ':cantidad'       => $transaccion['cantidad'],
            ':precio'         => $transaccion['precio_unitario'],
            ':total'          => $transaccion['total'],
            ':motivo'         => 'Eliminado manualmente desde transacciones.php',
            ':usuario'        => $_SESSION['user_nombre'] 
        ]);
    }

    // 3. Eliminar detalle 
    $stmtDetalle = $conn->prepare("DELETE FROM detalletransaccion WHERE id_detalle_transaccion = :id");
    $stmtDetalle->execute([':id' => $delete_id]);

    // 4. Eliminar transacción sin detalles 
    $stmtTransaccion = $conn->prepare("
        DELETE FROM transaccion
        WHERE id_transaccion NOT IN (SELECT DISTINCT id_transaccion FROM detalletransaccion)
    ");
    $stmtTransaccion->execute();

    // CORRECCIÓN: Usar redirect()
    $objUsuarioSistema->redirect("transacciones.php?deleted");
    exit;
}

// Obtener transacciones actuales 
$stmt = $conn->prepare("
    SELECT
        dt.id_detalle_transaccion, t.fecha, dt.nombre_item, dt.cantidad, dt.precio_unitario, t.total, t.tipo_transaccion
    FROM
        detalletransaccion dt
    INNER JOIN transaccion t ON dt.id_transaccion = t.id_transaccion
    ORDER BY t.fecha DESC
");
$stmt->execute();
$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h1 class="mt-3">Registro de Transacciones y Compras</h1>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Transacción eliminada correctamente y registrada en historial.</div>
            <?php endif; ?>
             <?php if (isset($_GET['inserted'])): ?>
                <div class="alert alert-success">Transacción registrada correctamente.</div>
            <?php endif; ?>
             <?php if (isset($_GET['error']) && $_GET['error'] == 'acceso_denegado_accion'): ?>
                <div class="alert alert-danger">No tiene permisos para realizar esa acción.</div>
            <?php endif; ?>

            <a href="agregar_transaccion.php" class="btn btn-success mb-3">Registrar Transacción</a>
            <a href="exportar_transacciones.php" class="btn btn-info mb-3 ms-2">⬇️ Exportar Datos (Cloud)</a>

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Item/Servicio</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Total Transacción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transacciones as $t): ?>
                            <tr>
                                <td><?= $t['id_detalle_transaccion']; ?></td>
                                <td><?= $t['fecha']; ?></td>
                                <td><?= htmlspecialchars($t['tipo_transaccion'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($t['nombre_item'] ?? ''); ?></td>
                                <td><?= $t['cantidad']; ?></td>
                                <td>$<?= number_format($t['precio_unitario'], 2); ?></td>
                                <td>$<?= number_format($t['total'], 2); ?></td>
                                <td>
                                    <?php if ($CURRENT_USER_ROL == 'administrador'): ?>
                                        <a href="transacciones.php?delete_id=<?= $t['id_detalle_transaccion']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('⚠️ Advertencia: Se procederá a borrar este registro.\n\nEl registro quedará en el historial de auditoría.\n\n¿Desea continuar?')">
                                            Eliminar
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <hr>
            <h3 class="mt-5">Historial de Eliminaciones</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-danger">
                        <?php
                        // 5. Obtener logs de eliminación 
                        $stmtLog = $conn->prepare("SELECT * FROM logeliminaciontransaccion ORDER BY fecha_eliminacion DESC");
                        $stmtLog->execute();
                        $logs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <tr>
                            <th>ID Log</th>
                            <th>ID Trans.</th>
                            <th>Item/Servicio</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Motivo</th>
                            <th>Eliminado por</th>
                            <th>Fecha de Eliminación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($logs):
                            foreach ($logs as $log):
                        ?>
                            <tr>
                                <td><?= $log['id_log']; ?></td>
                                <td><?= $log['id_transaccion']; ?></td>
                                <td><?= htmlspecialchars($log['nombre_item'] ?? ''); ?></td>
                                <td><?= $log['cantidad']; ?></td>
                                <td>$<?= number_format($log['total'], 2); ?></td>
                                <td><?= htmlspecialchars($log['motivo'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($log['eliminado_por'] ?? ''); ?></td>
                                <td><?= $log['fecha_eliminacion']; ?></td>
                            </tr>
                        <?php
                            endforeach;
                        else:
                            echo "<tr><td colspan='8' class='text-center'>No hay eliminaciones registradas.</td></tr>";
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
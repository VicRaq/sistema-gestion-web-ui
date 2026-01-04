<?php
// 1. GUARDIA DE AUTENTICACIÓN
require_once 'includes/auth_check.php'; 

// 2. Cargar Clases
require_once 'classes/user.php'; 
require_once 'classes/database.php';

// 3. Instanciar Clases
$objUsuarioSistema = new UsuarioSistema(); 
$db = new Database();
$conn = $db->dbConnection();

// 4. GUARDIA DE AUTORIZACIÓN (RBAC)
// (solo admin y tecnico pueden editar)
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

if (!isset($_GET['id'])) {
    $objUsuarioSistema->redirect("clientes.php"); 
    exit;
}

$id = $_GET['id'];

// (La consulta SELECT ya está en minúsculas)
$stmt = $conn->prepare("SELECT * FROM cliente WHERE id_cliente = :id");
$stmt->execute([':id' => $id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "Cliente no encontrado.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar el tipo_cliente
    $tipo_cliente = $_POST['tipo_cliente'];
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono']; 
    $rut = $_POST['rut']; 

    // (Saneamiento)
    $nombre = htmlspecialchars(strip_tags(trim($nombre)));
    $direccion = htmlspecialchars(strip_tags(trim($direccion)));
    $telefono = htmlspecialchars(strip_tags(trim($telefono)));
    $rut = htmlspecialchars(strip_tags(trim($rut)));
    $tipo_cliente = htmlspecialchars(strip_tags(trim($tipo_cliente)));

    // CORRECCIÓN: Actualizar la columna 'tipo_cliente'
    $stmt = $conn->prepare("UPDATE cliente SET nombre = :nombre, tipo_cliente = :tipo_cliente, direccion = :direccion, telefono = :telefono, rut = :rut WHERE id_cliente = :id");
    $stmt->execute([
        ':nombre' => $nombre,
        ':tipo_cliente' => $tipo_cliente, 
        ':direccion' => $direccion,
        ':telefono' => $telefono,
        ':rut' => $rut,
        ':id' => $id
    ]);

    $objUsuarioSistema->redirect("clientes.php?updated"); 
    exit;
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
            <h1 class="mt-3">Editar Cliente (ID: <?= $id ?>)</h1>
            <form method="post">
                <div class="form-group">
                    <label>Nombre:</label>
                    <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($cliente['nombre']); ?>">
                </div>

                <div class="form-group">
                    <label for="tipo_cliente">Tipo de Cliente *</label>
                    <select class="form-control" name="tipo_cliente" id="tipo_cliente" required>
                        <option value="Docente" <?= ($cliente['tipo_cliente'] == 'Docente' ? 'selected' : '') ?>>Docente / Coordinador</option>
                        <option value="Estudiante" <?= ($cliente['tipo_cliente'] == 'Estudiante' ? 'selected' : '') ?>>Estudiante</option>
                        <option value="Sede" <?= ($cliente['tipo_cliente'] == 'Sede' ? 'selected' : '') ?>>Sede / Laboratorio</option>
                        <option value="Externo" <?= ($cliente['tipo_cliente'] == 'Externo' ? 'selected' : '') ?>>Externo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>RUT:</label>
                    <input type="text" name="rut" class="form-control" required value="<?= htmlspecialchars($cliente['rut']); ?>">
                </div>
                <div class="form-group">
                    <label>Dirección:</label>
                    <input type="text" name="direccion" class="form-control" required value="<?= htmlspecialchars($cliente['direccion']); ?>">
                </div>
                <div class="form-group">
                    <label>Teléfono de Contacto:</label>
                    <input type="text" name="telefono" class="form-control" required value="<?= htmlspecialchars($cliente['telefono']); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
                <a href="clientes.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
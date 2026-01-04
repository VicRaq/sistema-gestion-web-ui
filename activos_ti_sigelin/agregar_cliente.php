<?php
// 1. GUARDIA DE AUTENTICACIÓN
require_once 'includes/auth_check.php'; 

// 2. GUARDIA DE AUTORIZACIÓN (RBAC)
// (Según la matriz, solo admin y tecnico pueden agregar clientes)
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
    require_once 'classes/user.php'; 
    $objUsuarioSistema = new UsuarioSistema(); 
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

// 3. Cargar el resto
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'classes/database.php';
require_once 'classes/user.php'; 
$db = new Database();
$conn = $db->dbConnection();
$objUsuarioSistema = new UsuarioSistema(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir datos
    $tipo_cliente = $_POST['tipo_cliente'];
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $contacto = $_POST['telefono']; 
    $rut = $_POST['rut']; 

    // Saneamiento
    $nombre = htmlspecialchars(strip_tags(trim($nombre)));
    $direccion = htmlspecialchars(strip_tags(trim($direccion)));
    $contacto = htmlspecialchars(strip_tags(trim($contacto)));
    $rut = htmlspecialchars(strip_tags(trim($rut)));
    $tipo_cliente = htmlspecialchars(strip_tags(trim($tipo_cliente)));

    // Consulta INSERT (Tabla en minúsculas)
    $stmt = $conn->prepare("
        INSERT INTO cliente (nombre, tipo_cliente, direccion, telefono, rut) 
        VALUES (:nombre, :tipo_cliente, :direccion, :telefono, :rut)
    ");
    
    try {
        $stmt->execute([
            ':nombre' => $nombre,
            ':tipo_cliente' => $tipo_cliente, 
            ':direccion' => $direccion,
            ':telefono' => $contacto, 
            ':rut' => $rut
        ]);
        
        $objUsuarioSistema->redirect("clientes.php?inserted"); 
    } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
    }
}
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
            <h1 class="mt-3">Registrar Nuevo Cliente</h1>

            <form method="post" class="mt-4">
                <div class="form-group mb-3">
                    <label>Nombre del Cliente</label>
                    <input type="text" name="nombre" class="form-control" required maxlength="200">
                </div>

                <div class="form-group mb-3">
                    <label for="tipo_cliente">Tipo de Cliente *</label>
                    <select class="form-control" name="tipo_cliente" id="tipo_cliente" required>
                        <option value="Docente">Docente / Coordinador</option>
                        <option value="Estudiante">Estudiante</option>
                        <option value="Sede">Sede / Laboratorio</option>
                        <option value="Externo">Externo</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>RUT (Identificación Única)</label>
                    <input type="text" name="rut" class="form-control" required maxlength="12">
                </div>
                
                <div class="form-group mb-3">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" required maxlength="200">
                </div>
                
                <div class="form-group mb-3">
                    <label>Teléfono de Contacto</label>
                    <input type="text" name="telefono" class="form-control" required maxlength="100">
                </div>

                <button type="submit" class="btn btn-primary mt-3">Guardar Cliente</button>
                <a href="clientes.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
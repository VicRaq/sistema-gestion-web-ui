<?php
// 1. GUARDIA DE AUTENTICACIÓN (¿Estás logueado?)
require_once 'includes/auth_check.php'; 

// 2. NUEVO: GUARDIA DE AUTORIZACIÓN (RBAC)
// (Según la matriz, solo 'administrador' y 'tecnico' pueden agregar equipos)
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
    require_once 'classes/user.php'; 
    $objUsuarioSistema = new UsuarioSistema(); 
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

// 3. Cargar el resto (Si pasó los dos guardias)
require_once 'classes/database.php';
require_once 'classes/user.php'; // Necesario para el redirect
$db = new Database();
$conn = $db->dbConnection();
$objUsuarioSistema = new UsuarioSistema(); // Para usar la función redirect

// Obtener ubicaciones disponibles 
$stmtUbicaciones = $conn->query("SELECT id_ubicacion, laboratorio, sala FROM ubicacion ORDER BY laboratorio, sala");
$ubicaciones = $stmtUbicaciones->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $modelo = $_POST['modelo'];
    $marca = $_POST['marca'];
    $lugar_asignado = $_POST['lugar_asignado']; 
    $id_ubicacion = $_POST['id_ubicacion'] ?: null;
    $codigo_qr = $_POST['codigo_qr'];

    // Insertar equipo 
    $stmtEquipo = $conn->prepare("
        INSERT INTO equipo (tipo, modelo, marca, estado, lugar_asignado, id_ubicacion)
        VALUES (:tipo, :modelo, :marca, 'Disponible', :lugar, :id_ubicacion)
    ");
    
    try {
        $conn->beginTransaction(); 

        $stmtEquipo->execute([
            ':tipo' => $tipo,
            ':modelo' => $modelo,
            ':marca' => $marca,
            ':lugar' => $lugar_asignado,
            ':id_ubicacion' => $id_ubicacion
        ]);

        $id_equipo = $conn->lastInsertId();

        // Insertar el Código QR asociado 
        $stmtQR = $conn->prepare("
            INSERT INTO qr (id_equipo, codigo_qr)
            VALUES (:id_equipo, :codigo_qr)
        ");
        $stmtQR->execute([
            ':id_equipo' => $id_equipo,
            ':codigo_qr' => $codigo_qr
        ]);

        $conn->commit(); 
        
        // CORRECCIÓN: Usar el método redirect()
        $objUsuarioSistema->redirect("equipos.php?inserted");
        // exit; // (redirect() ya incluye el exit)
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
            <h1 class="mt-3">Registrar Nuevo Activo / Equipo</h1>

            <form method="post" class="mt-4">
                <div class="form-group mb-3">
                    <label>Tipo (ej. Computador, Router, Monitor)</label>
                    <input type="text" name="tipo" class="form-control" required maxlength="100">
                </div>
                <div class="form-group mb-3">
                    <label>Marca</label>
                    <input type="text" name="marca" class="form-control" required maxlength="100">
                </div>
                <div class="form-group mb-3">
                    <label>Modelo</label>
                    <input type="text" name="modelo" class="form-control" required maxlength="100">
                </div>
                
                <hr>

                <div class="form-group mb-3">
                    <label>Ubicación Física (Laboratorio/Sala)</label>
                    <select name="id_ubicacion" class="form-control">
                        <option value="">-- Seleccionar Ubicación (Opcional) --</option>
                        <?php foreach ($ubicaciones as $u): ?>
                            <option value="<?= $u['id_ubicacion']; ?>">
                                <?= htmlspecialchars($u['laboratorio'] . ' - Sala: ' . $u['sala']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Asignación Manual (Texto de referencia)</label>
                    <input type="text" name="lugar_asignado" class="form-control" placeholder="Ej: Oficina 301 - Mesa de Juan">
                </div>
                <div class="form-group mb-3">
                    <label>Código QR Único</label>
                    <input type="text" name="codigo_qr" class="form-control" required placeholder="Ej: UUID-123456" maxlength="255">
                </div>

                <button type="submit" class="btn btn-primary mt-3">Guardar Equipo</button>
                <a href="equipos.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
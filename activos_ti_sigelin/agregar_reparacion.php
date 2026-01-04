<?php
// 1. GUARDIA DE AUTENTICACIÓN (¿Estás logueado?)
// (Inicia la sesión y define $CURRENT_USER_ROL)
require_once 'includes/auth_check.php'; 

// 2. NUEVO: GUARDIA DE AUTORIZACIÓN (¿Tienes el ROL correcto?)
// Según la matriz, solo 'administrador' y 'tecnico' pueden agregar reparaciones.
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
    require_once 'classes/user.php'; 
    $objUsuarioSistema = new UsuarioSistema(); 
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

// 3. Cargar el resto (Si pasó los dos guardias)
require_once 'classes/database.php';
$db = new Database();
$conn = $db->dbConnection();

// Obtener datos para selectores (Equipos y Técnicos)
$stmtEquipos = $conn->query("SELECT id_equipo, modelo, tipo, marca FROM equipo ORDER BY modelo");
$equipos = $stmtEquipos->fetchAll(PDO::FETCH_ASSOC);

$stmtEncargados = $conn->query("
    SELECT id_usuario, nombre 
    FROM usuariosistema 
    WHERE rol IN ('tecnico', 'administrador') 
    ORDER BY nombre
");
$encargados = $stmtEncargados->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. OBTENCIÓN Y VALIDACIÓN DE DATOS
    $id_equipo = intval($_POST['id_equipo'] ?? 0); 
    $id_encargado = intval($_POST['id_encargado'] ?? 0); 
    
    if ($id_equipo === 0 || $id_encargado === 0) {
        die("Error de validación: Debe seleccionar un Equipo y un Encargado (Usuario) válidos.");
    }

    $fecha = $_POST['fecha'];
    $tipo_falla = $_POST['tipo_falla'];
    $estado = $_POST['estado'];
    $descripcion = $_POST['descripcion'] ?? null; 

    try {
        $conn->beginTransaction(); 

        // 2. INSERCIÓN: Se utiliza la columna EXISTENTE 'id_encargado'
        $stmtReparacion = $conn->prepare("
            INSERT INTO reparacion (id_equipo, id_encargado, fecha, tipo_falla, estado, descripcion)
            VALUES (:id_equipo, :id_encargado, :fecha, :tipo_falla, :estado, :descripcion)
        ");
        $stmtReparacion->execute([
            ':id_equipo' => $id_equipo,
            ':id_encargado' => $id_encargado, 
            ':fecha' => $fecha,
            ':tipo_falla' => $tipo_falla,
            ':estado' => $estado,
            ':descripcion' => $descripcion
        ]);

        // 3. Lógica de Negocio: Actualizar el estado del Equipo
        if ($estado == 'En Progreso') {
            $stmtUpdate = $conn->prepare("UPDATE equipo SET estado = 'Reparacion' WHERE id_equipo = :id_equipo");
            $stmtUpdate->execute([':id_equipo' => $id_equipo]);
        }
        
        $conn->commit();
        header("Location: reparaciones.php?inserted");
        exit;
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
            <h1 class="mt-3">Registrar Reparación / Incidente</h1>

            <form method="post" class="mt-4">
                <div class="form-group mb-3">
                    <label>Equipo Afectado *</label>
                    <select name="id_equipo" class="form-control" required>
                        <option value="">-- Seleccionar Equipo --</option>
                        <?php foreach ($equipos as $e): ?>
                            <option value="<?= $e['id_equipo']; ?>">
                                <?= htmlspecialchars($e['tipo'] . ' - ' . $e['marca'] . ' ' . $e['modelo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>Encargado de la Reparación *</label>
                    <select name="id_encargado" class="form-control" required>
                        <option value="">-- Seleccionar Técnico/Administrador --</option>
                        <?php foreach ($encargados as $r): ?>
                            <option value="<?= $r['id_usuario']; ?>">
                                <?= htmlspecialchars($r['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label>Fecha del Incidente/Registro *</label>
                    <input type="date" name="fecha" class="form-control" required>
                </div>
                
                <div class="form-group mb-3">
                    <label>Tipo de Falla *</label>
                    <input type="text" name="tipo_falla" class="form-control" required maxlength="100" placeholder="Ej: Falla de hardware, Error de software">
                </div>

                <div class="form-group mb-3">
                    <label>Estado *</label>
                    <select name="estado" class="form-control" required>
                        <option value="Pendiente">Pendiente</option>
                        <option value="En Progreso">En Progreso</option>
                        <option value="Finalizada">Finalizada</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>Descripción y Observaciones</label>
                    <textarea name="descripcion" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Guardar Reparación</button>
                <a href="reparaciones.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
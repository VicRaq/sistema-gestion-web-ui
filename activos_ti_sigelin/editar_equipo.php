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
if ($CURRENT_USER_ROL != 'administrador' && $CURRENT_USER_ROL != 'tecnico') {
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

$id_equipo = $_GET['id'] ?? null;
if (!$id_equipo) {
    $objUsuarioSistema->redirect("equipos.php");
    exit;
}

// --- LÓGICA PARA ELIMINAR DOCUMENTO (Si se solicita por GET) ---
if (isset($_GET['delete_doc'])) {
    $uuid_doc = $_GET['delete_doc'];
    try {
        // 1. Obtener ruta para borrar archivo físico
        $stmtDoc = $conn->prepare("SELECT ruta_storage FROM documento WHERE uuid = :uuid");
        $stmtDoc->execute([':uuid' => $uuid_doc]);
        $docData = $stmtDoc->fetch(PDO::FETCH_ASSOC);
        
        // 2. Borrar registro de BD
        $stmtDel = $conn->prepare("DELETE FROM documento WHERE uuid = :uuid");
        $stmtDel->execute([':uuid' => $uuid_doc]);

        // 3. Borrar archivo físico
        if ($docData && file_exists($docData['ruta_storage'])) {
            unlink($docData['ruta_storage']);
        }
        
        $objUsuarioSistema->redirect("editar_equipo.php?id=$id_equipo&doc_deleted=true");
        exit;
    } catch (PDOException $e) {
        error_log("Fallo crítico al eliminar documento (ID Equipo: $id_equipo): " . $e->getMessage());
        
        die("Error del Sistema: No se pudo eliminar el documento debido a un problema técnico. Por favor, contacte a soporte.");
    }
}

// --- FUNCIÓN AUXILIAR PARA GENERAR UUID v4 ---
function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

// Obtener listas
$stmtUbicaciones = $conn->query("SELECT id_ubicacion, laboratorio, sala FROM ubicacion ORDER BY laboratorio, sala");
$ubicaciones = $stmtUbicaciones->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos del equipo
$stmt = $conn->prepare("
    SELECT e.*, qr.codigo_qr
    FROM equipo e
    LEFT JOIN qr qr ON e.id_equipo = qr.id_equipo
    WHERE e.id_equipo = :id
");
$stmt->execute([':id' => $id_equipo]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    echo "Equipo no encontrado.";
    exit;
}

// Obtener documentos asociados 
$stmtDocs = $conn->prepare("SELECT * FROM documento WHERE entidad_relacionada_tipo = 'equipo' AND entidad_relacionada_id = :id");
$stmtDocs->execute([':id' => $id_equipo]);
$documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);


// --- PROCESAR FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $modelo = $_POST['modelo'];
    $marca = $_POST['marca'];
    $estado = $_POST['estado'];
    $lugar_asignado = $_POST['lugar_asignado'];
    $id_ubicacion = $_POST['id_ubicacion'] ?: null;
    $codigo_qr = $_POST['codigo_qr'];

    try {
        $conn->beginTransaction(); 

        // 1. Actualizar Equipo
        $stmtEquipo = $conn->prepare("
            UPDATE equipo SET tipo = :tipo, modelo = :modelo, marca = :marca, estado = :estado, lugar_asignado = :lugar, id_ubicacion = :id_ubicacion
            WHERE id_equipo = :id
        ");
        $stmtEquipo->execute([
            ':tipo' => $tipo,
            ':modelo' => $modelo,
            ':marca' => $marca,
            ':estado' => $estado,
            ':lugar' => $lugar_asignado,
            ':id_ubicacion' => $id_ubicacion,
            ':id' => $id_equipo
        ]);

        // 2. Actualizar QR
        $stmtQR = $conn->prepare("
            INSERT INTO qr (id_equipo, codigo_qr) VALUES (:id_equipo, :codigo_qr)
            ON DUPLICATE KEY UPDATE codigo_qr = VALUES(codigo_qr)
        ");
        $stmtQR->execute([
            ':id_equipo' => $id_equipo,
            ':codigo_qr' => $codigo_qr
        ]);

        // 3. PROCESAR SUBIDA DE DOCUMENTO 
        if (isset($_FILES['documento_adjunto']) && $_FILES['documento_adjunto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['documento_adjunto']['tmp_name'];
            $fileName = $_FILES['documento_adjunto']['name'];
            $fileSize = $_FILES['documento_adjunto']['size'];
            $fileType = $_FILES['documento_adjunto']['type'];
            
            // Generar nombre único y ruta
            $uuid = gen_uuid();
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = $uuid . '.' . $extension;
            $uploadFileDir = 'uploads/';
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // Insertar en tabla documento
                $stmtDoc = $conn->prepare("
                    INSERT INTO documento (uuid, nombre_original, ruta_storage, tipo_mime, tamano_bytes, entidad_relacionada_tipo, entidad_relacionada_id, creado_por)
                    VALUES (:uuid, :nombre, :ruta, :mime, :tamano, 'equipo', :id_entidad, :creador)
                ");
                $stmtDoc->execute([
                    ':uuid' => $uuid,
                    ':nombre' => $fileName,
                    ':ruta' => $dest_path,
                    ':mime' => $fileType,
                    ':tamano' => $fileSize,
                    ':id_entidad' => $id_equipo,
                    ':creador' => $_SESSION['user_id']
                ]);
            }
        }

        $conn->commit();
        
        $objUsuarioSistema->redirect("equipos.php?updated");
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error al editar equipo: " . $e->getMessage());
        echo "Error al editar el equipo: " . $e->getMessage();
        exit;
    }
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
            <h2 class="mt-3">Editar Activo/Equipo (ID: <?= $id_equipo ?>)</h2>
            
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-header">Datos del Equipo</div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <label>Tipo</label>
                                    <input type="text" name="tipo" class="form-control" required value="<?= htmlspecialchars($equipo['tipo'] ?? ''); ?>">
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <label>Marca</label>
                                        <input type="text" name="marca" class="form-control" required value="<?= htmlspecialchars($equipo['marca'] ?? ''); ?>">
                                    </div>
                                    <div class="col">
                                        <label>Modelo</label>
                                        <input type="text" name="modelo" class="form-control" required value="<?= htmlspecialchars($equipo['modelo'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-group mt-3">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control" required>
                                        <option value="Disponible" <?= $equipo['estado'] == 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                                        <option value="Asignado" <?= $equipo['estado'] == 'Asignado' ? 'selected' : '' ?>>Asignado</option>
                                        <option value="Reparacion" <?= $equipo['estado'] == 'Reparacion' ? 'selected' : '' ?>>En Reparación</option>
                                        <option value="Baja" <?= $equipo['estado'] == 'Baja' ? 'selected' : '' ?>>Dado de Baja</option>
                                    </select>
                                </div>
                                <div class="form-group mt-3">
                                    <label>Ubicación Física</label>
                                    <select name="id_ubicacion" class="form-control">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($ubicaciones as $u): ?>
                                            <option value="<?= $u['id_ubicacion']; ?>" <?= $equipo['id_ubicacion'] == $u['id_ubicacion'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($u['laboratorio'] . ' - Sala: ' . $u['sala']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mt-3">
                                    <label>Asignación Manual</label>
                                    <input type="text" name="lugar_asignado" class="form-control" value="<?= htmlspecialchars($equipo['lugar_asignado'] ?? ''); ?>">
                                </div>
                                <div class="form-group mt-3">
                                    <label>Código QR</label>
                                    <input type="text" name="codigo_qr" class="form-control" required value="<?= htmlspecialchars($equipo['codigo_qr'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">Documentación Digital</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Adjuntar Nuevo (PDF/Img)</label>
                                    <input type="file" name="documento_adjunto" class="form-control">
                                    <small class="text-muted">Manuales, garantías, fotos.</small>
                                </div>
                                <hr>
                                <h6>Documentos Existentes:</h6>
                                <?php if (count($documentos) > 0): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($documentos as $doc): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <a href="<?= $doc['ruta_storage']; ?>" target="_blank" class="text-decoration-none text-truncate" style="max-width: 150px;">
                                                    📄 <?= htmlspecialchars($doc['nombre_original']); ?>
                                                </a>
                                                <a href="editar_equipo.php?id=<?= $id_equipo; ?>&delete_doc=<?= $doc['uuid']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('¿Borrar este documento?')">×</a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted small">No hay documentos adjuntos.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 mb-5">
                    <button type="submit" class="btn btn-primary">Guardar Cambios y Subir</button>
                    <a href="equipos.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
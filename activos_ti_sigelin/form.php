<?php
// 1. GUARDIA DE AUTENTICACIÓN (DEBE SER LA PRIMERA LÍNEA)
require_once 'includes/auth_check.php';

// 2. NUEVO: GUARDIA DE AUTORIZACIÓN (RBAC)
// (Según la matriz, solo 'administrador' puede gestionar usuarios)
if ($CURRENT_USER_ROL != 'administrador') {
    require_once 'classes/user.php';
    $objUsuarioSistema = new UsuarioSistema();
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

// 3. Cargar el resto (Si pasó los dos guardias)
require_once 'classes/user.php';
require_once 'classes/database.php';
$objUsuarioSistema = new UsuarioSistema(); 

if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    // 'UsuarioSistema' -> 'usuariosistema' 
    $stmt = $objUsuarioSistema->runQuery("SELECT id_usuario, nombre, correo_electronico, rol FROM usuariosistema WHERE id_usuario = :id");
    $stmt->execute([":id" => $id]);
    $rowUser = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $id = null;
    $rowUser = ['id_usuario' => '', 'nombre' => '', 'correo_electronico' => '', 'rol' => '']; 
}

if (isset($_POST['btn_save'])) {
    $nombre = strip_tags($_POST['nombre']);
    $correo = strip_tags($_POST['correo_electronico']);
    $rol = strip_tags($_POST['rol']); 
    $contrasena = $_POST['contrasena'] ?? null; 

    try {
        if ($id !== null) {
            // (La lógica de update/insert ya llama a la clase UsuarioSistema,
            $contrasena_to_update = (!empty($contrasena) ? $contrasena : null); 
            
            if ($objUsuarioSistema->update($nombre, $correo, $rol, $id, $contrasena_to_update)) {
                $objUsuarioSistema->redirect('index.php?updated');
            }
        } else {
            if (empty($contrasena)) {
                throw new Exception("La contraseña es obligatoria para nuevos usuarios.");
            }
            if ($objUsuarioSistema->insert($nombre, $correo, $rol, $contrasena)) {
                $objUsuarioSistema->redirect('index.php?inserted');
            } else {
                $objUsuarioSistema->redirect('index.php?error');
            }
        }
    } catch (Exception $e) {
        echo $e->getMessage();
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

        <main role="main" class="col-md-9 ms-sm-auto col-lg-10 px-4">
            <h1 class="mt-3"><?= ($id !== null ? 'Editar' : 'Agregar') ?> Usuario del Sistema</h1>
            
            <form method="post">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input class="form-control" type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($rowUser['nombre']); ?>" required maxlength="200">
                </div>
                <div class="form-group">
                    <label for="correo_electronico">Correo Electrónico *</label>
                    <input class="form-control" type="email" name="correo_electronico" id="correo_electronico" value="<?= htmlspecialchars($rowUser['correo_electronico']); ?>" required maxlength="200">
                </div>
                
                <div class="form-group">
                    <label for="contrasena">Contraseña <?= ($id !== null ? '(Dejar vacío para no cambiar)' : '*') ?></label>
                    <input class="form-control" type="password" name="contrasena" id="contrasena" <?= ($id === null ? 'required' : ''); ?>>
                    <small class="form-text text-muted">La contraseña se almacenará de forma segura (hashing).</small>
                </div>

                <div class="form-group">
                    <label for="rol">Rol (Permisos) *</label>
                    <select class="form-control" name="rol" id="rol" required>
                        <option value="administrador" <?= ($rowUser['rol'] == 'administrador' ? 'selected' : '') ?>>Administrador</option>
                        <option value="tecnico" <?= ($rowUser['rol'] == 'tecnico' ? 'selected' : '') ?>>Técnico/Encargado</option>
                        
                        <option value="compras" <?= ($rowUser['rol'] == 'compras' ? 'selected' : '') ?>>Compras</option>
                        <option value="devqa" <?= ($rowUser['rol'] == 'devqa' ? 'selected' : '') ?>>Equipo de Desarrollo (Dev/QA)</option>
                        
                        <option value="auditor" <?= ($rowUser['rol'] == 'auditor' ? 'selected' : '') ?>>Auditor (Solo lectura)</option>
                    </select>
                </div>
                
                <input class="btn btn-primary mb-2 mt-3" type="submit" name="btn_save" value="Guardar">
                <a href="index.php" class="btn btn-secondary mb-2 mt-3">Cancelar</a>
            </form>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
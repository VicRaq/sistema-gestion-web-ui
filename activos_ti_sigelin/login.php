<?php
session_start();
// Si el usuario ya está logueado, redirigir al index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Solo necesitamos 'user.php' (que ya incluye 'database.php')
require_once 'classes/user.php';
$objUsuarioSistema = new UsuarioSistema();

$error = '';

if (isset($_POST['btn_login'])) {
    $correo = strip_tags($_POST['correo']);
    $contrasena = strip_tags($_POST['contrasena']);

    // 1. $userRow AHORA ES EL ARRAY COMPLETO (o false si falla)
    $userRow = $objUsuarioSistema->login($correo, $contrasena);

    if ($userRow) {
        
        // 2. ELIMINAMOS LA CONSULTA REDUNDANTE
        // (Ya no necesitamos $stmt = $objUsuarioSistema->runQuery(...) )

        // 3. GUARDAMOS LOS DATOS EN LA SESIÓN 
        // Obtenemos los datos directamente del array $userRow
        $_SESSION['user_id'] = $userRow['id_usuario'];
        $_SESSION['user_nombre'] = $userRow['nombre'];
        $_SESSION['user_rol'] = $userRow['rol']; 
        
        $objUsuarioSistema->redirect('index.php');

    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>

<!doctype html>
<html lang="es">
<?php require_once 'includes/head.php'; ?>
<body class="text-center">
    <main class="form-signin w-100 m-auto" style="max-width: 400px; padding: 15px;">
        <form method="post">
            <h1 class="h3 mb-3 fw-normal">SIGELIN - Acceso</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error; ?></div>
            <?php endif; ?>

            <div class="form-floating">
                <input type="email" name="correo" class="form-control" id="floatingInput" placeholder="nombre@ejemplo.com" required>
                <label for="floatingInput">Correo Electrónico</label>
            </div>
            <div class="form-floating mt-2">
                <input type="password" name="contrasena" class="form-control" id="floatingPassword" placeholder="Contraseña" required>
                <label for="floatingPassword">Contraseña</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary mt-3" type="submit" name="btn_login">Ingresar</button>
        </form>
    </main>
</body>
</html>
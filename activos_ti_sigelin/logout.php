<?php
// 1. Iniciar la sesión
session_start();

// 2. Destruir todas las variables de sesión
session_unset();
session_destroy();

// 3. Redirigir al login
// (Cargamos la clase solo para usar la función redirect)
require_once 'classes/user.php';
$objUser = new UsuarioSistema();
$objUser->redirect('login.php?logout=true');
exit;
?>
<?php
session_start();

// Tiempo de expiración (15 minutos = 900 segundos)
$timeout_duration = 900; 

// Verificar si existe una marca de tiempo de "última actividad"
if (isset($_SESSION['LAST_ACTIVITY'])) {
    // Calcular el tiempo transcurrido
    if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        // Si pasó más de 15 min, destruir sesión y redirigir
        session_unset();
        session_destroy();
        // Redirigir al login con mensaje de timeout
        header("Location: login.php?error=timeout");
        exit;
    }
}

// Actualizar la marca de tiempo de la última actividad
$_SESSION['LAST_ACTIVITY'] = time();

// --- RESTO DE CÓDIGO DE SEGURIDAD ---
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    if (!class_exists('UsuarioSistema')) {
        require_once 'classes/user.php';
    }
    $objUser = new UsuarioSistema();
    $objUser->redirect('login.php');
    exit;
}

$CURRENT_USER_ROL = $_SESSION['user_rol'];
?>
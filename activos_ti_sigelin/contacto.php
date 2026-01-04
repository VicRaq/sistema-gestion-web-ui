<?php
// 1. GUARDIA DE AUTENTICACIÓN (Accesible para todos los roles logueados)
require_once 'includes/auth_check.php'; 
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
            <nav aria-label="breadcrumb" class="mt-3">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Asignaciones</li>
              </ol>
            </nav>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Centro de Ayuda y Contacto</h1>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="my-0 fw-normal"><i data-feather="tool"></i> Soporte Técnico IT</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Para problemas con el sistema, errores de software o fallas en equipos.</p>
                            <ul class="list-unstyled mt-3 mb-4">
                                <li class="mb-2"><strong>Encargado:</strong> Carlos Martínez</li>
                                <li class="mb-2"><strong>Correo:</strong> soporte.ti@sigelin.cl</li>
                                <li class="mb-2"><strong>Anexo:</strong> 4500</li>
                                <li class="mb-2"><strong>Ubicación:</strong> Edificio B, Piso 2 (Lab Computación)</li>
                            </ul>
                            <a href="mailto:soporte.ti@sigelin.cl" class="btn btn-outline-primary w-100">Enviar Correo</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="my-0 fw-normal"><i data-feather="users"></i> Administración de Recursos</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Para consultas sobre stock, nuevas adquisiciones o permisos de usuario.</p>
                            <ul class="list-unstyled mt-3 mb-4">
                                <li class="mb-2"><strong>Jefa de Gestión:</strong> María López</li>
                                <li class="mb-2"><strong>Correo:</strong> administracion@sigelin.cl</li>
                                <li class="mb-2"><strong>WhatsApp:</strong> +56 9 1234 5678</li>
                                <li class="mb-2"><strong>Horario:</strong> Lunes a Viernes, 09:00 - 18:00</li>
                            </ul>
                            <button type="button" class="btn btn-outline-success w-100" disabled>Chat WhatsApp (Próximamente)</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-secondary mt-4" role="alert">
                <h4 class="alert-heading"><i data-feather="info"></i> Información del Sistema</h4>
                <p>Estás utilizando <strong>SIGELIN v1.0 (MVP)</strong>.</p>
                <hr>
                <p class="mb-0">Si encuentras un error crítico, por favor contacta al equipo de desarrollo (DevQA) inmediatamente.</p>
            </div>

        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
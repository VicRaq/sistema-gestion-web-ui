<?php
// 1. SEGURIDAD
require_once 'includes/auth_check.php';
?>
<!doctype html>
<html lang="es">
<?php require_once 'includes/head.php'; ?>
<body onload="document.getElementById('qr_input').focus()"> <?php require_once 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-4">
            
            <nav aria-label="breadcrumb" class="mt-3">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Escáner</li>
              </ol>
            </nav>

            <div class="text-center mt-5">
                <i data-feather="maximize" style="width: 64px; height: 64px; color: #6c757d;"></i>
                <h1 class="h2 mt-3">Escanear Código QR</h1>
                <p class="lead text-muted">Utilice el lector de códigos o ingrese el ID manual.</p>
            </div>

            <div class="row justify-content-center mt-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form action="buscar_qr.php" method="GET">
                                <div class="form-group">
                                    <label for="qr_input" class="form-label fw-bold">Código del Activo:</label>
                                    <input type="text" name="qr" id="qr_input" class="form-control form-control-lg text-center" placeholder="Esperando lectura..." required autocomplete="off">
                                </div>
                                <div class="d-grid gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary btn-lg">Buscar / Ir a Ficha</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">El sistema redirigirá automáticamente a la ficha del equipo encontrado.</small>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
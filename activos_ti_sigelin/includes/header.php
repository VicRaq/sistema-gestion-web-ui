<!-- includes/header.php -->
<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    
    <button class="navbar-toggler px-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">SIGELIN (Gestión TI)</a>

    <form action="buscar_qr.php" method="GET" class="d-flex w-100 px-3">
        <input class="form-control form-control-dark w-100" type="text" name="qr" placeholder="Escanear/Ingresar Código QR rápido..." aria-label="Search">
    </form>

    <div class="navbar-nav">
        <div class="nav-item text-nowrap">
            <a class="nav-link px-3" href="logout.php">Salir</a>
        </div>
    </div>
</header>
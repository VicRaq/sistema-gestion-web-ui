<?php
/*
  Este archivo asume que 'includes/auth_check.php' ya se ejecutó
  y definió la variable $CURRENT_USER_ROL.
*/
?>
<nav id="sidebarMenu" class="bg-light sidebar collapse show">
    <div class="sidebar-sticky pt-3">
        
        <ul class="nav flex-column">

            <?php if ($CURRENT_USER_ROL == 'administrador'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <span data-feather="users"></span>
                        Gestión de Usuarios
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'tecnico'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="clientes.php">
                        <span data-feather="briefcase"></span>
                        Gestión de Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reparaciones.php">
                        <span data-feather="tool"></span>
                        Historial de Reparaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="piezas.php">
                        <span data-feather="settings"></span>
                        Piezas de Hardware
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if (in_array($CURRENT_USER_ROL, ['administrador', 'tecnico', 'compras', 'devqa'])): ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="escanear.php">
                        <span data-feather="maximize"></span>
                        Escanear Activo (QR)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="equipos.php">
                        <span data-feather="hard-drive"></span>
                        Inventario de Equipos
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($CURRENT_USER_ROL, ['administrador', 'tecnico', 'compras'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="asignaciones.php">
                        <span data-feather="send"></span>
                        Asignaciones
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($CURRENT_USER_ROL == 'administrador' || $CURRENT_USER_ROL == 'compras'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="transacciones.php">
                        <span data-feather="shopping-cart"></span>
                        Transacciones / Compras
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($CURRENT_USER_ROL, ['administrador', 'tecnico', 'compras', 'devqa'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="exportar_transacciones.php">
                        <span data-feather="download"></span>
                        Exportar Datos (CSV)
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item mt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Enlaces Externos</span>
                </h6>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="https://www.microsoft.com/es-cl/microsoft-365" target="_blank">
                    <span data-feather="external-link"></span>
                    Portal de Licencias / Software
                </a>
            </li>
            
            <hr class="my-2">
            
            <li class="nav-item">
                <a class="nav-link" href="contacto.php">
                    <span data-feather="help-circle"></span>
                    Ayuda y Contacto
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <span data-feather="log-out"></span>
                    Cerrar Sesión (<?= htmlspecialchars($_SESSION['user_nombre']); ?>)
                </a>
            </li>

        </ul>
    </div>
</nav>
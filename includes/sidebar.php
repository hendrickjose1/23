<?php
// Sidebar del sistema
$currentUser = currentUser();
$currentPath = $_SERVER['PHP_SELF'];
?>
<aside class="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item <?= strpos($currentPath, 'index.php') !== false ? 'active' : '' ?>">
                <a href="<?= baseUrl() ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <?php if (hasRole(['admin', 'recepcion'])): ?>
            <li class="nav-item <?= strpos($currentPath, 'modules/recepcion') !== false ? 'active' : '' ?>">
                <a href="<?= baseUrl('modules/recepcion/') ?>">
                    <i class="fas fa-truck"></i>
                    <span>Recepción</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole(['admin', 'supervisor', 'tecnico'])): ?>
            <li class="nav-item <?= strpos($currentPath, 'modules/taller') !== false ? 'active' : '' ?>">
                <a href="<?= baseUrl('modules/taller/') ?>">
                    <i class="fas fa-tools"></i>
                    <span>Taller</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole(['admin', 'recepcion'])): ?>
            <li class="nav-item <?= strpos($currentPath, 'modules/cotizaciones') !== false ? 'active' : '' ?>">
                <a href="<?= baseUrl('modules/cotizaciones/') ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Cotizaciones</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole(['admin', 'supervisor'])): ?>
            <li class="nav-item <?= strpos($currentPath, 'modules/inventario') !== false ? 'active' : '' ?>">
                <a href="<?= baseUrl('modules/inventario/') ?>">
                    <i class="fas fa-boxes"></i>
                    <span>Inventario</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole(['admin', 'supervisor'])): ?>
            <li class="nav-item <?= strpos($currentPath, 'modules/reportes') !== false ? 'active' : '' ?>">
                <a href="<?= baseUrl('modules/reportes/') ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole(['admin'])): ?>
            <li class="nav-item <?= strpos($currentPath, 'modules/usuarios') !== false ? 'active' : '' ?>">
                <a href="<?= baseUrl('modules/usuarios/') ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="system-info">
            <p><i class="fas fa-circle <?= $config['system_status'] === 'online' ? 'online' : 'offline' ?>"></i>
               <?= e(ucfirst($config['system_status'] ?? 'online')) ?></p>
            <p><small><?= date('d/m/Y H:i:s') ?></small></p>
        </div>
    </div>
</aside>
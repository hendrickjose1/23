<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la página
$pageTitle = $pageTitle ?? 'Morismetal';
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Morismetal</title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/main.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?= baseUrl('assets/img/favicon.ico') ?>" type="image/x-icon">
</head>
<body>
    <!-- Barra superior -->
    <header class="main-header">
        <div class="header-left">
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <a href="<?= baseUrl() ?>" class="logo">
                <img src="<?= baseUrl('assets/img/logo_morismetal.png') ?>" alt="Morismetal">
            </a>
        </div>

        <div class="header-right">
            <div class="user-dropdown">
                <button class="user-btn">
                    <i class="fas fa-user-circle"></i>
                    <span><?= e($currentUser['nombre'] ?? 'Usuario') ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-content">
                    <a href="<?= baseUrl('perfil.php') ?>"><i class="fas fa-user"></i> Mi Perfil</a>
                    <a href="<?= baseUrl('logout.php') ?>"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenedor principal -->
    <div class="main-container">
        <!-- Sidebar se incluirá aquí -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Contenido principal -->
        <main class="main-content">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message <?= e($_SESSION['flash_message']['type']) ?>">
                    <?= e($_SESSION['flash_message']['text']) ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
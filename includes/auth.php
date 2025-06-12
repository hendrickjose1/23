<?php
session_start();

// Verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirigir si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Verificar permisos de usuario
function hasRole($role) {
    if (!isLoggedIn()) return false;
    return $_SESSION['user_rol'] === $role || $_SESSION['user_rol'] === 'admin';
}

// Obtener información del usuario actual
function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nombre' => $_SESSION['user_nombre'],
        'email' => $_SESSION['user_email'],
        'rol' => $_SESSION['user_rol']
    ];
}
?>
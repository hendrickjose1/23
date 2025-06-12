<?php
// Funciones útiles para todo el sistema

/**
 * Convierte un valor numérico a formato de dinero
 */
function formatMoney($amount, $decimal = 0) {
    return '$' . number_format($amount, $decimal, ',', '.');
}

/**
 * Redirecciona a una URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Obtiene el nombre del mes en español
 */
function getMonthName($month) {
    $months = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $months[(int)$month];
}

/**
 * Escapa output para prevenir XSS
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica si un archivo es una imagen válida
 */
function isImage($file) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return in_array($ext, $allowed);
}

/**
 * Sube un archivo al servidor
 */
function uploadFile($file, $directory, $allowedExtensions = []) {
    $filename = uniqid() . '_' . basename($file['name']);
    $target = rtrim($directory, '/') . '/' . $filename;

    if (!empty($allowedExtensions)) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            throw new Exception("Tipo de archivo no permitido");
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception("Error al subir el archivo");
    }

    return $filename;
}

/**
 * Obtiene la URL base del sitio
 */
function baseUrl($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/' . ltrim($path, '/');
}

/**
 * Genera un token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
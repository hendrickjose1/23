<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'moris_user');
define('DB_PASS', '25789849');
define('DB_NAME', 'moris_admin');

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Configuración del sistema
define('SITE_NAME', 'Morismetal');
define('SITE_URL', 'http://localhost/morismetal');
define('UPLOAD_DIR', __DIR__.'/../assets/uploads/');
?>
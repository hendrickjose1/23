<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $query = $_GET['q'] ?? '';

    if (empty($query)) {
        echo json_encode([]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id, codigo, nombre, precio_unitario
                          FROM materiales
                          WHERE nombre LIKE ? OR codigo LIKE ?
                          LIMIT 10");

    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm]);

    $materiales = $stmt->fetchAll();

    echo json_encode($materiales);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
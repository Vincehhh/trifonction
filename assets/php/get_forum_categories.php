<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'db_connect.php';

try {
    $query = "SELECT id, name, slug FROM forum_categories ORDER BY id ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);

} catch (PDOException $e) { 

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des catégories.'
    ]);
}
exit();
?>
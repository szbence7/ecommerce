<?php
// Hibakezelés bekapcsolása a fájl legelején
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once '../includes/db.php';

    // Session ellenőrzése
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
        http_response_code(403);
        exit(json_encode(['error' => 'Unauthorized']));
    }

    // JSON header beállítása
    header('Content-Type: application/json; charset=utf-8');

    if (isset($_GET['search']) && strlen($_GET['search']) >= 3) {
        $search = '%' . $_GET['search'] . '%';
        
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id LIKE ? 
            OR p.name LIKE ? 
            OR p.description LIKE ? 
            OR c.name LIKE ?
        ");
        
        $stmt->execute([$search, $search, $search, $search]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug információk
        error_log("Search query: " . $_GET['search']);
        error_log("Number of results: " . count($products));
        
        // Konvertáljuk a numerikus értékeket stringgé a biztonság kedvéért
        foreach ($products as &$product) {
            $product['id'] = (string)$product['id'];
            $product['price'] = (string)$product['price'];
            if (isset($product['discount_price'])) {
                $product['discount_price'] = (string)$product['discount_price'];
            }
        }
        
        exit(json_encode($products));
    } else {
        exit(json_encode([]));
    }
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    http_response_code(500);
    exit(json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]));
}

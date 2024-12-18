<?php
require_once __DIR__ . '/../includes/db.php';

// Get current datetime
$currentDateTime = date('Y-m-d H:i:s');

// Find products with expired discounts
$stmt = $pdo->prepare("
    UPDATE products 
    SET is_on_sale = 0, 
        discount_price = NULL, 
        discount_end_time = NULL 
    WHERE is_on_sale = 1 
    AND discount_end_time IS NOT NULL 
    AND discount_end_time <= ?
");

$stmt->execute([$currentDateTime]);

// Optional: Log the number of updated products
$updatedCount = $stmt->rowCount();
error_log("Updated $updatedCount products with expired discounts at $currentDateTime");

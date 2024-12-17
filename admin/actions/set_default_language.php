<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkAdmin();

header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception('Language ID is required');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Remove default flag from all languages
    $stmt = $pdo->prepare("UPDATE languages SET is_default = 0");
    $stmt->execute();

    // Set new default language and ensure it's active
    $stmt = $pdo->prepare("UPDATE languages SET is_default = 1, is_active = 1 WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

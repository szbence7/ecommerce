<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkAdmin();

header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;
    $active = $_POST['active'] ?? null;

    if (!$id || !isset($active)) {
        throw new Exception('Missing required parameters');
    }

    // Cannot deactivate default language
    $stmt = $pdo->prepare("SELECT is_default FROM languages WHERE id = ?");
    $stmt->execute([$id]);
    $isDefault = $stmt->fetchColumn();

    if ($isDefault && !$active) {
        throw new Exception('Cannot deactivate default language');
    }

    // Update language status
    $stmt = $pdo->prepare("UPDATE languages SET is_active = ? WHERE id = ?");
    $stmt->execute([$active, $id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

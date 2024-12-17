<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkAdmin();

header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $code = $_POST['code'] ?? '';

    if (empty($name) || empty($code)) {
        throw new Exception('Name and code are required');
    }

    // Validate language code (2-5 characters)
    if (!preg_match('/^[a-z]{2,5}$/', strtolower($code))) {
        throw new Exception('Invalid language code format');
    }

    if ($id) {
        // Update existing language
        $stmt = $pdo->prepare("UPDATE languages SET name = ?, code = ? WHERE id = ?");
        $stmt->execute([$name, strtolower($code), $id]);
    } else {
        // Check if language code already exists
        $stmt = $pdo->prepare("SELECT id FROM languages WHERE code = ?");
        $stmt->execute([strtolower($code)]);
        if ($stmt->fetch()) {
            throw new Exception('Language code already exists');
        }

        // Insert new language
        $stmt = $pdo->prepare("INSERT INTO languages (name, code) VALUES (?, ?)");
        $stmt->execute([$name, strtolower($code)]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

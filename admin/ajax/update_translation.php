<?php
require_once '../../includes/db.php';
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['key'] ?? '';
    $value = $_POST['value'] ?? '';
    $language = $_POST['language'] ?? '';
    $context = $_POST['context'] ?? '';

    if (empty($key) || empty($language) || empty($context)) {
        die(json_encode(['success' => false, 'message' => 'Missing required fields']));
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE translations 
            SET translation_value = ? 
            WHERE translation_key = ? 
            AND language_code = ? 
            AND context = ?
        ");
        
        $success = $stmt->execute([$value, $key, $language, $context]);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Translation updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update translation'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>

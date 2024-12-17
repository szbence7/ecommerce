<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkAdmin();

header('Content-Type: application/json');

try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }

    // Check file type
    $mimeType = mime_content_type($_FILES['file']['tmp_name']);
    if (!in_array($mimeType, ['text/csv', 'text/plain', 'application/vnd.ms-excel'])) {
        throw new Exception('Invalid file type. Please upload a CSV file.');
    }

    // Open file
    $handle = fopen($_FILES['file']['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception('Could not open file');
    }

    // Skip BOM if present
    $bom = fread($handle, 3);
    if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
        // If not BOM, go back to start
        rewind($handle);
    }

    // Read header
    $header = fgetcsv($handle);
    if ($header !== ['Key', 'Context', 'Language Code', 'Translation']) {
        throw new Exception('Invalid CSV format. Please use the exported format.');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Prepare statement
    $stmt = $pdo->prepare("
        INSERT INTO translations (translation_key, context, language_code, translation_value) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE translation_value = VALUES(translation_value)
    ");

    // Read and import data
    $imported = 0;
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== 4) {
            continue; // Skip invalid rows
        }

        // Verify language exists and is active
        $langStmt = $pdo->prepare("SELECT code FROM languages WHERE code = ? AND is_active = 1");
        $langStmt->execute([$row[2]]);
        if (!$langStmt->fetch()) {
            continue; // Skip inactive or non-existent languages
        }

        $stmt->execute([
            $row[0], // key
            $row[1], // context
            $row[2], // language_code
            $row[3]  // translation
        ]);
        $imported++;
    }

    fclose($handle);
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Successfully imported $imported translations"
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

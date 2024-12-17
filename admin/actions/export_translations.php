<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

// Check if user is admin
checkAdmin();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="translations_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header
fputcsv($output, ['Key', 'Context', 'Language Code', 'Translation']);

// Get all translations
$stmt = $pdo->query("
    SELECT t.translation_key, t.context, t.language_code, t.translation_value 
    FROM translations t 
    JOIN languages l ON t.language_code = l.code 
    WHERE l.is_active = 1 
    ORDER BY t.translation_key, t.context, t.language_code
");

// Write data
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['translation_key'],
        $row['context'],
        $row['language_code'],
        $row['translation_value']
    ]);
}

fclose($output);

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (isset($data['points_to_redeem'])) {
        $_SESSION['points_to_redeem'] = intval($data['points_to_redeem']);
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Points value not provided');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 
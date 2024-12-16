<?php
session_start();
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'add') {
    $id = (int)$_GET['id'];
    
    if (!isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = 0;
    }
    $_SESSION['cart'][$id]++;
    
    $response['success'] = true;
}

header('Content-Type: application/json');
echo json_encode($response); 
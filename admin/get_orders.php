<?php
include '../includes/header.php';
require_once '../includes/functions.php';

$search = $_GET['search'] ?? '';
$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE o.id LIKE ? 
              OR u.name LIKE ? 
              OR u.email LIKE ?
              OR o.total LIKE ? 
              OR o.status LIKE ?
              OR DATE_FORMAT(o.created_at, '%Y-%m-%d') LIKE ?";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
}

$stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      $where
                      ORDER BY o.created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$html = '';
if (empty($orders)) {
    $html = '<tr><td colspan="7" class="text-center">No orders found</td></tr>';
} else {
    foreach ($orders as $order) {
        $html .= '<tr>';
        $html .= '<td>#' . $order['id'] . '</td>';
        $html .= '<td>' . htmlspecialchars($order['customer_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($order['customer_email']) . '</td>';
        $html .= '<td>' . date('Y-m-d H:i', strtotime($order['created_at'])) . '</td>';
        $html .= '<td>' . formatPrice($order['total']) . '</td>';
        $html .= '<td><span class="badge bg-' . getStatusBadgeClass($order['status']) . '">' 
              . ucfirst($order['status']) . '</span></td>';
        $html .= '<td><a href="?id=' . $order['id'] . '" class="btn btn-sm btn-primary">View Details</a></td>';
        $html .= '</tr>';
    }
}

header('Content-Type: application/json');
echo json_encode([
    'html' => $html,
    'count' => count($orders)
]); 
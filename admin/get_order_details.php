<?php
require_once 'auth_check.php';
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    die('Unauthorized access');
}

if (!isset($_GET['id'])) {
    die('No order ID provided');
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, 
           CONCAT(o.firstname, ' ', o.lastname) as customer_name,
           o.email as customer_email
    FROM orders o 
    WHERE o.id = ?
");
$stmt->execute([$_GET['id']]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$_GET['id']]);
$items = $stmt->fetchAll();

// Payment method translations
$payment_methods = [
    'card' => 'Bankkártya',
    'transfer' => 'Átutalás',
    'cash_on_delivery' => 'Utánvét'
];

// Payment status translations
$payment_statuses = [
    'paid' => 'Fizetve',
    'pending_payment' => 'Fizetésre vár',
    'cash_on_delivery' => 'Utánvét'
];

// Order status translations
$order_statuses = [
    'pending' => 'Függőben',
    'processing' => 'Feldolgozás alatt',
    'shipped' => 'Kiszállítva',
    'delivered' => 'Kézbesítve',
    'cancelled' => 'Törölve'
];
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h4>Rendelési adatok</h4>
            <table class="table table-sm">
                <tr>
                    <th>Rendelési szám:</th>
                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                </tr>
                <tr>
                    <th>Dátum:</th>
                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>Státusz:</th>
                    <td><?php echo $order_statuses[$order['status']] ?? $order['status']; ?></td>
                </tr>
                <tr>
                    <th>Fizetési mód:</th>
                    <td><?php echo $payment_methods[$order['payment_method']] ?? $order['payment_method']; ?></td>
                </tr>
                <tr>
                    <th>Fizetési státusz:</th>
                    <td><?php echo $payment_statuses[$order['payment_status']] ?? $order['payment_status']; ?></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h4>Szállítási adatok</h4>
            <table class="table table-sm">
                <tr>
                    <th>Név:</th>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                </tr>
                <tr>
                    <th>Cím:</th>
                    <td>
                        <?php echo htmlspecialchars($order['street_address']); ?><br>
                        <?php echo htmlspecialchars($order['postal_code'] . ' ' . $order['city']); ?><br>
                        <?php echo htmlspecialchars($order['country']); ?>
                    </td>
                </tr>
                <tr>
                    <th>Szállítási mód:</th>
                    <td><?php echo htmlspecialchars($order['shipping_method']); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <h4>Rendelt termékek</h4>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Termék</th>
                    <th>Mennyiség</th>
                    <th>Egységár</th>
                    <th>Összesen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($item['image']): ?>
                                    <img src="../uploads/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </div>
                        </td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo formatPrice($item['price']); ?></td>
                        <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="text-end"><strong>Végösszeg:</strong></td>
                    <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

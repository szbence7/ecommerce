<?php
require_once 'auth_check.php';
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: /login.php');
    exit();
}

include 'layout/header.php';

// Update order status if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    header('Location: orders.php');
    exit();
}

// Get all orders with payment and shipping details
$stmt = $pdo->prepare("
    SELECT o.*, 
           CONCAT(o.firstname, ' ', o.lastname) as customer_name,
           o.email as customer_email,
           o.payment_method,
           o.payment_status,
           o.shipping_method,
           o.order_number
    FROM orders o 
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute();
$orders = $stmt->fetchAll();

// Helper function to get payment status badge class
function getPaymentStatusBadgeClass($status) {
    switch ($status) {
        case 'paid':
            return 'bg-success';
        case 'pending_payment':
            return 'bg-warning text-dark';
        case 'cash_on_delivery':
            return 'bg-info text-dark';
        default:
            return 'bg-secondary';
    }
}

// Helper function to get order status badge class
function getOrderStatusBadgeClass($status) {
    switch ($status) {
        case 'processing':
            return 'bg-primary';
        case 'shipped':
            return 'bg-info';
        case 'delivered':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-warning text-dark';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'layout/sidebar.php'; ?>
        
        <div class="col-md-10" id="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?php echo __t('admin.orders.title', 'admin'); ?></h2>
            </div>

            <!-- Search Bar -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="<?php echo __t('admin.orders.search.placeholder', 'admin'); ?>">
                                <button class="btn btn-outline-secondary" type="button" onclick="searchOrders()">
                                    <?php echo __t('admin.orders.search.button', 'admin'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Rendelési szám</th>
                            <th>Vásárló</th>
                            <th>Fizetési mód</th>
                            <th>Fizetési státusz</th>
                            <th>Rendelés státusza</th>
                            <th>Összeg</th>
                            <th>Dátum</th>
                            <th>Műveletek</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </td>
                                <td><?php 
                                    $payment_methods = [
                                        'card' => 'Bankkártya',
                                        'transfer' => 'Átutalás',
                                        'cash_on_delivery' => 'Utánvét'
                                    ];
                                    echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                ?></td>
                                <td><span class="badge <?php echo getPaymentStatusBadgeClass($order['payment_status']); ?>">
                                    <?php 
                                        $payment_statuses = [
                                            'paid' => 'Fizetve',
                                            'pending_payment' => 'Fizetésre vár',
                                            'cash_on_delivery' => 'Utánvét'
                                        ];
                                        echo $payment_statuses[$order['payment_status']] ?? $order['payment_status'];
                                    ?>
                                </span></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Függőben</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Feldolgozás alatt</option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Kiszállítva</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Kézbesítve</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Törölve</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo formatPrice($order['total_amount']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                        Részletek
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rendelés részletei</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    // Load order details via AJAX
    fetch(`get_order_details.php?id=${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
        });
}

function searchOrders() {
    const searchTerm = document.getElementById('searchInput').value;
    // Implement search functionality
}
</script>

<?php include 'layout/footer.php'; ?>
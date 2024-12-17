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

// Get initial orders
$stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC
                      LIMIT 10");
$stmt->execute();
$orders = $stmt->fetchAll();
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
                            <th><?php echo __t('admin.orders.table.order_id', 'admin'); ?></th>
                            <th><?php echo __t('admin.orders.table.customer', 'admin'); ?></th>
                            <th><?php echo __t('admin.orders.table.total', 'admin'); ?></th>
                            <th><?php echo __t('admin.orders.table.status', 'admin'); ?></th>
                            <th><?php echo __t('admin.orders.table.date', 'admin'); ?></th>
                            <th><?php echo __t('admin.orders.table.actions', 'admin'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </td>
                                <td><?php echo formatPrice($order['total']); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>><?php echo __t('admin.orders.status.pending', 'admin'); ?></option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>><?php echo __t('admin.orders.status.processing', 'admin'); ?></option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>><?php echo __t('admin.orders.status.shipped', 'admin'); ?></option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>><?php echo __t('admin.orders.status.delivered', 'admin'); ?></option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>><?php echo __t('admin.orders.status.cancelled', 'admin'); ?></option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                        <?php echo __t('admin.orders.view_details', 'admin'); ?>
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
                <h5 class="modal-title"><?php echo __t('admin.orders.modal.title', 'admin'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <?php echo __t('admin.orders.modal.loading', 'admin'); ?>
            </div>
        </div>
    </div>
</div>

<script>
function searchOrders() {
    const searchTerm = document.getElementById('searchInput').value;
    fetch(`get_orders.php?search=${encodeURIComponent(searchTerm)}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('ordersTableBody').innerHTML = html;
        });
}

function viewOrderDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    modal.show();
    
    // Here you would fetch the order details from a new endpoint
    // For now, we'll just show a placeholder
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="text-center">
            <h4>Order #${orderId}</h4>
            <p>Order details functionality coming soon...</p>
        </div>
    `;
}

// Enable search on Enter key
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchOrders();
    }
});
</script>

<?php include 'layout/footer.php'; ?>
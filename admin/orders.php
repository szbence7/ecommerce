<?php
include 'layout/header.php';
require_once '../includes/functions.php';

// Order statuses
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

// Single order view
if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    
    // Update order status
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $order_id]);
        header('Location: orders.php?id=' . $order_id);
        exit;
    }
    
    // Get order details with customer info
    $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email 
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if ($order) {
        // Get order items with product details
        $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();
        ?>
        
        <div class="container-fluid">
            <div class="row">
                <?php include 'layout/sidebar.php'; ?>
                
                <div class="col-md-10" id="content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Order #<?php echo $order_id; ?></h2>
                        <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Order Details</h5>
                                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                    <p><strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
                                    <p><strong>Total:</strong> <?php echo formatPrice($order['total']); ?></p>
                                    
                                    <form method="POST" class="mt-3">
                                        <div class="input-group">
                                            <select name="status" class="form-select">
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?php echo $status; ?>" 
                                                            <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($status); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Order Items</h5>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if ($item['image']): ?>
                                                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                                         alt="" style="width: 50px; height: 50px; object-fit: cover"
                                                                         class="me-2">
                                                                <?php endif; ?>
                                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo formatPrice($item['price']); ?></td>
                                                        <td><?php echo $item['quantity']; ?></td>
                                                        <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    // Search functionality
    $search = $_GET['search'] ?? '';
    $where = '';
    $params = [];
    
    if ($search) {
        $where = "WHERE o.id LIKE ? 
                  OR u.name LIKE ? 
                  OR u.email LIKE ?
                  OR o.total LIKE ? 
                  OR o.status LIKE ?
                  OR DATE_FORMAT(o.created_at, '%Y-%m-%d') LIKE ?";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
    }

    // Orders list view with search
    $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email 
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          $where
                          ORDER BY o.created_at DESC");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'layout/sidebar.php'; ?>
            
            <div class="col-md-10" id="content">
                <h2>Orders</h2>
                
                <!-- Search Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <div class="input-group">
                                    <input type="text" 
                                           name="search" 
                                           class="form-control" 
                                           placeholder="Search by order ID, customer name, email, total, status, or date (YYYY-MM-DD)"
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">Search</button>
                                    <?php if ($search): ?>
                                        <a href="orders.php" class="btn btn-secondary">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results count -->
                <?php if ($search): ?>
                    <div class="alert alert-info">
                        Found <?php echo count($orders); ?> orders matching "<?php echo htmlspecialchars($search); ?>"
                    </div>
                <?php endif; ?>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No orders found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo formatPrice($order['total']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                        View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Helper function for status badge colors
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

include 'layout/footer.php';
?>
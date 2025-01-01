<?php
require_once 'auth_check.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/language.php';

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
           o.email as customer_email,
           COALESCE(
               (SELECT SUM(oi.quantity * oi.price)
                FROM order_items oi
                WHERE oi.order_id = o.id), 0
           ) + 
           CASE 
               WHEN o.shipping_method = 'personal' THEN 0
               ELSE 5.99
           END as total_amount
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

// Get current language
$language = getCurrentLanguage();

// Function to get translation
function getTranslation($key, $language, $pdo) {
    $stmt = $pdo->prepare("SELECT translation_value FROM translations WHERE language_code = ? AND translation_key = ? AND context = 'admin' LIMIT 1");
    $stmt->execute([$language, $key]);
    return $stmt->fetchColumn() ?: $key;
}

?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-6">
            <h4><?php echo getTranslation('admin.orders.title', $language, $pdo); ?></h4>
            <table class="table table-sm">
                <tr>
                    <th><?php echo getTranslation('admin.orders.table.customer', $language, $pdo); ?>:</th>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                </tr>
                <tr>
                    <th><?php echo getTranslation('admin.orders.table.email', $language, $pdo); ?>:</th>
                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                </tr>
                <tr>
                    <th><?php echo getTranslation('admin.orders.table.phone', $language, $pdo); ?>:</th>
                    <td><?php echo isset($order['phone']) ? htmlspecialchars($order['phone']) : '-'; ?></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h4><?php echo getTranslation('admin.orders.shipping_info', $language, $pdo); ?></h4>
            <table class="table table-sm">
                <tr>
                    <th><?php echo getTranslation('admin.orders.table.address', $language, $pdo); ?>:</th>
                    <td>
                        <?php echo htmlspecialchars($order['street_address']); ?><br>
                        <?php echo htmlspecialchars($order['postal_code'] . ' ' . $order['city']); ?><br>
                        <?php echo htmlspecialchars($order['country']); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo getTranslation('admin.orders.table.shipping_method', $language, $pdo); ?>:</th>
                    <td><?php echo getTranslation('admin.orders.shipping.' . $order['shipping_method'], $language, $pdo); ?></td>
                </tr>
                <tr>
                    <th><?php echo getTranslation('admin.orders.table.payment_method', $language, $pdo); ?>:</th>
                    <td><?php echo getTranslation('admin.orders.payment.' . $order['payment_method'], $language, $pdo); ?></td>
                </tr>
                <tr>
                    <th><?php echo getTranslation('admin.orders.table.payment_status', $language, $pdo); ?>:</th>
                    <td><?php echo getTranslation('admin.orders.payment_status.' . $order['payment_status'], $language, $pdo); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <h4 class="mt-4"><?php echo getTranslation('admin.orders.products', $language, $pdo); ?></h4>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo getTranslation('admin.orders.table.product', $language, $pdo); ?></th>
                    <th><?php echo getTranslation('admin.orders.table.quantity', $language, $pdo); ?></th>
                    <th><?php echo getTranslation('admin.orders.table.price', $language, $pdo); ?></th>
                    <th><?php echo getTranslation('admin.orders.table.total', $language, $pdo); ?></th>
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
                    <td colspan="3" class="text-end"><strong><?php echo getTranslation('admin.orders.table.total', $language, $pdo); ?>:</strong></td>
                    <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
// Debug információk
echo "<!--\n";
echo "Order data:\n";
var_dump($order);
echo "\nOrder items:\n";
var_dump($items);
echo "\n-->";

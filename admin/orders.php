<?php
require_once 'auth_check.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/language.php';

// Get current language
$language = getCurrentLanguage();

// Function to get translation
function getTranslation($key, $language, $pdo) {
    $stmt = $pdo->prepare("SELECT translation_value FROM translations WHERE language_code = ? AND translation_key = ? AND context = 'admin' LIMIT 1");
    $stmt->execute([$language, $key]);
    return $stmt->fetchColumn() ?: $key;
}

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

// Get all orders without pagination
$stmt = $pdo->prepare("
    SELECT o.*, 
           CONCAT(o.firstname, ' ', o.lastname) as customer_name,
           o.email as customer_email,
           o.payment_method,
           o.payment_status,
           o.shipping_method,
           o.order_number,
           COALESCE(
               (SELECT SUM(oi.quantity * oi.price)
                FROM order_items oi
                WHERE oi.order_id = o.id), 0
           ) as subtotal,
           CASE 
               WHEN o.shipping_method = 'personal' THEN 0
               ELSE 5.99
           END as shipping_cost,
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
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

$orders_per_page = 10;
$total_orders = count($orders);
$total_pages = ceil($total_orders / $orders_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages) $current_page = $total_pages;

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
                <h2><?php echo getTranslation('admin.orders.title', $language, $pdo); ?></h2>
            </div>

            <!-- Search Bar -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" id="searchInput" class="form-control" placeholder="<?php echo getTranslation('admin.orders.search.placeholder', $language, $pdo); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo getTranslation('admin.orders.table.order_number', $language, $pdo); ?></th>
                            <th><?php echo getTranslation('admin.orders.table.customer', $language, $pdo); ?></th>
                            <th><?php echo getTranslation('admin.orders.table.shipping_method', $language, $pdo); ?></th>
                            <th><?php echo getTranslation('admin.orders.table.payment_method', $language, $pdo); ?></th>
                            <th><?php echo getTranslation('admin.orders.table.payment_status', $language, $pdo); ?></th>
                            <th><?php echo getTranslation('admin.orders.table.order_status', $language, $pdo); ?></th>
                            <th><?php echo getTranslation('admin.orders.table.total', $language, $pdo); ?></th>
                            <th><?php echo getTranslation('admin.orders.table.date', $language, $pdo); ?></th>
                            <th><?php echo getTranslation('admin.orders.table.actions', $language, $pdo); ?></th>
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
                                <td><?php echo getTranslation('admin.orders.shipping.' . $order['shipping_method'], $language, $pdo); ?></td>
                                <td><?php echo getTranslation('admin.orders.payment.' . $order['payment_method'], $language, $pdo); ?></td>
                                <td><span class="badge <?php echo getPaymentStatusBadgeClass($order['payment_status']); ?>">
                                    <?php echo getTranslation('admin.orders.payment_status.' . $order['payment_status'], $language, $pdo); ?></span></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>><?php echo getTranslation('admin.orders.status.pending', $language, $pdo); ?></option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>><?php echo getTranslation('admin.orders.status.processing', $language, $pdo); ?></option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>><?php echo getTranslation('admin.orders.status.shipped', $language, $pdo); ?></option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>><?php echo getTranslation('admin.orders.status.delivered', $language, $pdo); ?></option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>><?php echo getTranslation('admin.orders.status.cancelled', $language, $pdo); ?></option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo formatPrice($order['total_amount']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                        <?php echo getTranslation('admin.orders.button.details', $language, $pdo); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav aria-label="Orders pagination" class="mt-4">
                <ul class="pagination justify-content-center" id="pagination">
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo getTranslation('admin.orders.modal.title', $language, $pdo); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('#ordersTableBody tr');
    const itemsPerPage = <?php echo $orders_per_page; ?>;
    let currentPage = 1;
    let filteredRows = Array.from(rows);

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Update pagination
    function updatePagination() {
        const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        // Previous button
        if (totalPages > 1) {
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" ${currentPage === 1 ? 'tabindex="-1"' : ''}>«</a>`;
            if (currentPage !== 1) {
                prevLi.onclick = () => {
                    currentPage--;
                    updateDisplay();
                };
            }
            pagination.appendChild(prevLi);
        }

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${currentPage === i ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            li.onclick = () => {
                currentPage = i;
                updateDisplay();
            };
            pagination.appendChild(li);
        }

        // Next button
        if (totalPages > 1) {
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" ${currentPage === totalPages ? 'tabindex="-1"' : ''}>»</a>`;
            if (currentPage !== totalPages) {
                nextLi.onclick = () => {
                    currentPage++;
                    updateDisplay();
                };
            }
            pagination.appendChild(nextLi);
        }
    }

    // Update display
    function updateDisplay() {
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        
        rows.forEach(row => row.style.display = 'none');
        filteredRows.slice(start, end).forEach(row => row.style.display = '');
        
        updatePagination();
    }

    // Filter function
    const filterRows = debounce(function(searchTerm) {
        searchTerm = searchTerm.toLowerCase();
        
        if (searchTerm.length < 3) {
            filteredRows = Array.from(rows);
        } else {
            filteredRows = Array.from(rows).filter(row => {
                const orderNumber = row.cells[0].textContent.toLowerCase();
                const customerInfo = row.cells[1].textContent.toLowerCase();
                const shippingMethod = row.cells[2].textContent.toLowerCase();
                const paymentMethod = row.cells[3].textContent.toLowerCase();

                return orderNumber.includes(searchTerm) || 
                       customerInfo.includes(searchTerm) || 
                       shippingMethod.includes(searchTerm) || 
                       paymentMethod.includes(searchTerm);
            });
        }

        // Reset to first page when filtering
        currentPage = 1;
        
        // Show no results message if needed
        if (filteredRows.length === 0 && searchTerm.length >= 3) {
            const tbody = document.getElementById('ordersTableBody');
            const noResultsRow = document.createElement('tr');
            noResultsRow.id = 'noResults';
            noResultsRow.innerHTML = '<td colspan="9" class="text-center">No orders found</td>';
            tbody.appendChild(noResultsRow);
        } else {
            const noResultsRow = document.getElementById('noResults');
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }

        updateDisplay();
    }, 300);

    // Add event listener
    searchInput.addEventListener('input', function(e) {
        filterRows(this.value.trim());
    });

    // Initial display
    updateDisplay();
});

function viewOrderDetails(orderId) {
    fetch(`get_order_details.php?id=${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
        });
}
</script>

<?php include 'layout/footer.php'; ?>
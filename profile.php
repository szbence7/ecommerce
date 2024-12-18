<?php
require_once 'includes/header.php';
require_once 'includes/components/alert.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_address'])) {
        // Update address logic
        $stmt = $pdo->prepare("UPDATE users SET 
            street_address = ?,
            city = ?,
            country = ?,
            postal_code = ?,
            district = ?
            WHERE id = ?");
        $stmt->execute([
            $_POST['street_address'],
            $_POST['city'],
            $_POST['country'],
            $_POST['postal_code'],
            $_POST['district'],
            $_SESSION['user_id']
        ]);
        set_alert(__t('profile.address_updated'), 'success');
    } elseif (isset($_POST['change_password'])) {
        // Change password logic
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                set_alert(__t('profile.password_changed'), 'success');
            } else {
                set_alert(__t('profile.passwords_mismatch'), 'error');
            }
        } else {
            set_alert(__t('profile.current_password_incorrect'), 'error');
        }
    } elseif (isset($_POST['delete_account'])) {
        // Delete account logic
        $password = $_POST['confirm_delete_password'];
        
        // Verify password before deletion
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['password'])) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            session_destroy();
            header('Location: index.php');
            exit();
        } else {
            set_alert(__t('profile.password_incorrect'), 'error');
        }
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           COALESCE(SUM(oi.quantity * oi.price), 0) as total_amount
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container py-5">
    <?php display_alert(); ?>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __t('profile.information'); ?></h5>
                </div>
                <div class="card-body">
                    <p><strong><?php echo __t('common.name'); ?>:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong><?php echo __t('common.email'); ?>:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong><?php echo __t('common.phone'); ?>:</strong> <?php echo htmlspecialchars($user['phone'] ?? __t('common.not_provided')); ?></p>
                </div>
            </div>

            <!-- Address Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __t('profile.shipping_address'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><?php echo __t('address.street'); ?></label>
                                <input type="text" class="form-control" name="street_address" value="<?php echo htmlspecialchars($user['street_address'] ?? ''); ?>" placeholder="<?php echo __t('address.enter_street'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __t('address.city'); ?></label>
                                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="<?php echo __t('address.enter_city'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __t('address.district'); ?></label>
                                <input type="text" class="form-control" name="district" value="<?php echo htmlspecialchars($user['district'] ?? ''); ?>" placeholder="<?php echo __t('address.enter_district'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __t('address.postal_code'); ?></label>
                                <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" placeholder="<?php echo __t('address.enter_postal_code'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo __t('address.country'); ?></label>
                                <select class="form-select" name="country">
                                    <option value=""><?php echo __t('address.select_country'); ?></option>
                                    <option value="HU" <?php echo ($user['country'] ?? '') === 'HU' ? 'selected' : ''; ?>><?php echo __t('country.hungary'); ?></option>
                                    <option value="AT" <?php echo ($user['country'] ?? '') === 'AT' ? 'selected' : ''; ?>><?php echo __t('country.austria'); ?></option>
                                    <option value="DE" <?php echo ($user['country'] ?? '') === 'DE' ? 'selected' : ''; ?>><?php echo __t('country.germany'); ?></option>
                                    <option value="SK" <?php echo ($user['country'] ?? '') === 'SK' ? 'selected' : ''; ?>><?php echo __t('country.slovakia'); ?></option>
                                    <option value="RO" <?php echo ($user['country'] ?? '') === 'RO' ? 'selected' : ''; ?>><?php echo __t('country.romania'); ?></option>
                                    <option value="HR" <?php echo ($user['country'] ?? '') === 'HR' ? 'selected' : ''; ?>><?php echo __t('country.croatia'); ?></option>
                                    <option value="SI" <?php echo ($user['country'] ?? '') === 'SI' ? 'selected' : ''; ?>><?php echo __t('country.slovenia'); ?></option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="update_address" class="btn btn-primary"><?php echo __t('profile.update_address'); ?></button>
                    </form>
                </div>
            </div>

            <!-- Change Password Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __t('profile.change_password'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __t('profile.current_password'); ?></label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __t('profile.new_password'); ?></label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __t('profile.confirm_new_password'); ?></label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning"><?php echo __t('profile.change_password'); ?></button>
                    </form>
                </div>
            </div>

            <!-- Delete Account -->
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><?php echo __t('profile.delete_account'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return confirm('<?php echo __t('profile.confirm_delete'); ?>');">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __t('profile.enter_password_confirm'); ?></label>
                            <input type="password" class="form-control" name="confirm_delete_password" required>
                        </div>
                        <button type="submit" name="delete_account" class="btn btn-danger"><?php echo __t('profile.delete_account'); ?></button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Orders -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __t('orders.my_orders'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#pending"><?php echo __t('orders.pending'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#fulfilled"><?php echo __t('orders.fulfilled'); ?></a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pending">
                            <?php 
                            $pending_orders = false;
                            foreach ($orders as $order) {
                                if ($order['status'] != 'fulfilled') {
                                    $pending_orders = true;
                                    ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo __t('orders.order_number'); ?>: #<?php echo $order['order_number']; ?></h6>
                                            <p class="card-text">
                                                <strong><?php echo __t('common.date'); ?>:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?><br>
                                                <strong><?php echo __t('common.total'); ?>:</strong> <?php echo formatPrice($order['total_amount']); ?><br>
                                                <strong><?php echo __t('common.status'); ?>:</strong> 
                                                <span class="badge bg-warning"><?php echo __t($order['status']); ?></span>
                                            </p>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary"><?php echo __t('orders.view_details'); ?></a>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            if (!$pending_orders) {
                                echo '<p class="text-muted">' . __t('orders.no_pending') . '</p>';
                            }
                            ?>
                        </div>
                        <div class="tab-pane fade" id="fulfilled">
                            <?php 
                            $fulfilled_orders = false;
                            foreach ($orders as $order) {
                                if ($order['status'] == 'fulfilled') {
                                    $fulfilled_orders = true;
                                    ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo __t('orders.order_number'); ?>: #<?php echo $order['order_number']; ?></h6>
                                            <p class="card-text">
                                                <strong><?php echo __t('common.date'); ?>:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?><br>
                                                <strong><?php echo __t('common.total'); ?>:</strong> <?php echo formatPrice($order['total_amount']); ?><br>
                                                <strong><?php echo __t('common.status'); ?>:</strong> 
                                                <span class="badge bg-success"><?php echo __t($order['status']); ?></span>
                                            </p>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary"><?php echo __t('orders.view_details'); ?></a>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            if (!$fulfilled_orders) {
                                echo '<p class="text-muted">' . __t('orders.no_fulfilled') . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<?php require_once 'includes/footer.php'; ?>

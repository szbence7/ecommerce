<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_address'])) {
        // Update address logic
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $query = "UPDATE users SET address = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $address, $_SESSION['user_id']);
        $stmt->execute();
        $_SESSION['success_message'] = translate('address_updated_successfully');
    } elseif (isset($_POST['change_password'])) {
        // Change password logic
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                $stmt->execute();
                $_SESSION['success_message'] = translate('password_changed_successfully');
            } else {
                $_SESSION['error_message'] = translate('new_passwords_do_not_match');
            }
        } else {
            $_SESSION['error_message'] = translate('current_password_incorrect');
        }
    } elseif (isset($_POST['delete_account'])) {
        // Delete account logic
        $password = $_POST['confirm_delete_password'];
        
        // Verify password before deletion
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            session_destroy();
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['error_message'] = translate('password_incorrect');
        }
    }
}

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user orders
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result();
?>

<div class="container py-5">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo translate('profile_information'); ?></h5>
                </div>
                <div class="card-body">
                    <p><strong><?php echo translate('name'); ?>:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong><?php echo translate('email'); ?>:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong><?php echo translate('phone'); ?>:</strong> <?php echo htmlspecialchars($user['phone'] ?? translate('not_provided')); ?></p>
                </div>
            </div>

            <!-- Address Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo translate('shipping_address'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <textarea class="form-control" name="address" rows="3" placeholder="<?php echo translate('enter_your_address'); ?>"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="update_address" class="btn btn-primary"><?php echo translate('update_address'); ?></button>
                    </form>
                </div>
            </div>

            <!-- Change Password Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo translate('change_password'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?php echo translate('current_password'); ?></label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo translate('new_password'); ?></label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo translate('confirm_new_password'); ?></label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning"><?php echo translate('change_password'); ?></button>
                    </form>
                </div>
            </div>

            <!-- Delete Account -->
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><?php echo translate('delete_account'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return confirm('<?php echo translate('confirm_delete_account'); ?>');">
                        <div class="mb-3">
                            <label class="form-label"><?php echo translate('enter_password_to_confirm'); ?></label>
                            <input type="password" class="form-control" name="confirm_delete_password" required>
                        </div>
                        <button type="submit" name="delete_account" class="btn btn-danger"><?php echo translate('delete_account'); ?></button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Orders -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo translate('my_orders'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#pending"><?php echo translate('pending_orders'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#fulfilled"><?php echo translate('fulfilled_orders'); ?></a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pending">
                            <?php 
                            $pending_orders = false;
                            while ($order = $orders->fetch_assoc()) {
                                if ($order['status'] != 'fulfilled') {
                                    $pending_orders = true;
                                    ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo translate('order_id'); ?>: #<?php echo $order['id']; ?></h6>
                                            <p class="card-text">
                                                <strong><?php echo translate('date'); ?>:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?><br>
                                                <strong><?php echo translate('total'); ?>:</strong> $<?php echo number_format($order['total'], 2); ?><br>
                                                <strong><?php echo translate('status'); ?>:</strong> 
                                                <span class="badge bg-warning"><?php echo translate($order['status']); ?></span>
                                            </p>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary"><?php echo translate('view_details'); ?></a>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            if (!$pending_orders) {
                                echo '<p class="text-muted">' . translate('no_pending_orders') . '</p>';
                            }
                            $orders->data_seek(0);
                            ?>
                        </div>
                        <div class="tab-pane fade" id="fulfilled">
                            <?php 
                            $fulfilled_orders = false;
                            while ($order = $orders->fetch_assoc()) {
                                if ($order['status'] == 'fulfilled') {
                                    $fulfilled_orders = true;
                                    ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo translate('order_id'); ?>: #<?php echo $order['id']; ?></h6>
                                            <p class="card-text">
                                                <strong><?php echo translate('date'); ?>:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?><br>
                                                <strong><?php echo translate('total'); ?>:</strong> $<?php echo number_format($order['total'], 2); ?><br>
                                                <strong><?php echo translate('status'); ?>:</strong> 
                                                <span class="badge bg-success"><?php echo translate($order['status']); ?></span>
                                            </p>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary"><?php echo translate('view_details'); ?></a>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            if (!$fulfilled_orders) {
                                echo '<p class="text-muted">' . translate('no_fulfilled_orders') . '</p>';
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

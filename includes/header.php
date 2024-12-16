<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="js/cart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/index.php">Store</a>
            
            <!-- Search Bar -->
            <div class="position-relative">
                <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
                <div id="searchResults" class="position-absolute w-100 bg-white shadow-sm" style="display:none; z-index:1000;"></div>
            </div>

            <div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['is_admin']): ?>
                        <a href="/admin/index.php" class="btn btn-primary">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-primary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary">Login</a>
                    <a href="register.php" class="btn btn-outline-primary">Register</a>
                <?php endif; ?>
                <button onclick="toggleCart()" class="btn btn-primary">
                    Cart <span id="cart-count">(<?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : '0'; ?>)</span>
                </button>
            </div>
        </div>
    </nav>

    <?php include 'cart-drawer.php'; ?>

    <div class="container" style="margin-top: 80px;">
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="js/cart.js"></script>
    <script src="js/search.js"></script>
    <style>
        #searchResults {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 0 0 4px 4px;
            margin-top: 2px;
        }
        #searchResults .list-group-item {
            border-left: none;
            border-right: none;
            padding: 10px;
        }
        #searchResults .list-group-item:first-child {
            border-top: none;
        }
        #searchResults .list-group-item:last-child {
            border-bottom: none;
        }
        .search-container {
            position: relative;
            width: 300px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/index.php">Store</a>
            
            <!-- Search Bar -->
            <div class="search-container">
                <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
                <div id="searchResults" class="position-absolute w-100 bg-white shadow-sm" style="display:none; z-index:1000;"></div>
            </div>

            <div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="/admin/index.php" class="btn btn-primary me-2">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-primary me-2">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="register.php" class="btn btn-outline-primary me-2">Register</a>
                <?php endif; ?>
                <button onclick="toggleCart()" class="btn btn-primary">
                    Cart <span id="cart-count">(<?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : '0'; ?>)</span>
                </button>
            </div>
        </div>
    </nav>

    <?php include 'cart-drawer.php'; ?>

    <div class="container mt-5 pt-5">
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';
require_once 'language.php';

// Update session with latest user data
updateUserSession();
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
            <a class="navbar-brand" href="/index.php"><?= __t('nav.home') ?></a>
            
            <!-- Search Bar -->
            <div class="search-container">
                <input type="text" id="searchInput" class="form-control" placeholder="<?= __t('nav.search') ?>">
                <div id="searchResults" class="position-absolute w-100 bg-white shadow-sm" style="display:none; z-index:1000;"></div>
            </div>

            <div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <?php if(isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], [1, 2])): ?>
                        <a href="/ecommerce/admin/index.php" class="btn btn-primary me-2">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-primary me-2"><?= __t('nav.logout') ?></a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary me-2"><?= __t('nav.login') ?></a>
                    <a href="register.php" class="btn btn-outline-primary me-2"><?= __t('nav.register') ?></a>
                <?php endif; ?>
                <button onclick="toggleCart()" class="btn btn-primary">
                    <?= __t('nav.cart') ?> (<?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?>)
                </button>
            </div>
        </div>
    </nav>

    <?php include 'cart-drawer.php'; ?>

    <div class="container mt-5 pt-5">
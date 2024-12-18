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
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="js/cart.js?v=<?= time() ?>"></script>
    <script src="js/search.js?v=<?= time() ?>"></script>
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
        .cart-button {
            position: relative;
            padding: 0.5rem;
            border: none;
            background: none;
            color: var(--bs-primary);
            cursor: pointer;
        }
        .cart-button:hover {
            color: var(--bs-primary-dark);
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.75rem;
            min-width: 1.5rem;
            height: 1.5rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bs-primary);
            color: white;
            padding: 0 0.4rem;
            transition: transform 0.3s ease;
        }
        
        @keyframes cartBadgePop {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.5);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .cart-badge-pop {
            animation: cartBadgePop 0.5s ease;
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
                <button onclick="toggleCart()" class="cart-button">
                    <i data-lucide="shopping-cart" width="24" height="24"></i>
                    <span class="cart-badge" id="cart-count"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?></span>
                </button>
            </div>
        </div>
    </nav>

    <?php include 'cart-drawer.php'; ?>

    <div class="container mt-5 pt-5">
    
    <script>
        // Lucide ikonok inicializálása
        lucide.createIcons();
    </script>
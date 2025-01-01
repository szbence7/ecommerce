<?php
require_once __DIR__ . '/../../includes/language.php';
?>
<div class="col-md-2" id="sidebar">
    <h4 class="p-3"><?= __t('admin.panel', 'admin') ?></h4>
    
    <!-- Return to shop button at the top -->
    <a href="/ecommerce" class="sidebar-link">
        <i data-lucide="store"></i>
        <?= __t('admin.return_to_shop', 'admin') ?>
    </a>

    <!-- Regular menu items -->
    <a href="index.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i data-lucide="layout-dashboard"></i>
        <?= __t('admin.dashboard', 'admin') ?>
    </a>
    <a href="products.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
        <i data-lucide="package"></i>
        <?= __t('admin.products', 'admin') ?>
    </a>
    <a href="categories.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
        <i data-lucide="list"></i>
        <?= __t('admin.categories', 'admin') ?>
    </a>
    <a href="orders.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
        <i data-lucide="shopping-cart"></i>
        <?= __t('admin.orders', 'admin') ?>
    </a>
    <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i data-lucide="settings"></i>
        <?= __t('admin.settings', 'admin') ?>
    </a>

    <!-- Logout at the bottom -->
    <div style="position: fixed; bottom: 20px; left: 0; width: inherit; padding: 0 15px;">
        <a href="logout.php" class="sidebar-link text-danger">
            <i data-lucide="log-out"></i>
            <?= __t('admin.logout', 'admin') ?>
        </a>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();
</script>
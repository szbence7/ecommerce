<div class="col-md-2" id="sidebar">
    <h4 class="p-3">Admin Panel</h4>
    
    <!-- Return to shop button at the top -->
    <a href="/ecommerce" class="sidebar-link">
        <i data-lucide="store"></i>
        Return to Shop
    </a>

    <!-- Regular menu items -->
    <a href="/ecommerce/admin/index.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i data-lucide="layout-dashboard"></i>
        Dashboard
    </a>
    <a href="/ecommerce/admin/products.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
        <i data-lucide="package"></i>
        Products
    </a>
    <a href="/ecommerce/admin/categories.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
        <i data-lucide="list"></i>
        Categories
    </a>
    <a href="/ecommerce/admin/orders.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
        <i data-lucide="shopping-cart"></i>
        Orders
    </a>
    <a href="/ecommerce/admin/settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i data-lucide="settings"></i>
        Settings
    </a>

    <!-- Logout at the bottom -->
    <div style="position: absolute; bottom: 20px; width: 100%;">
        <a href="/ecommerce/logout.php" class="sidebar-link text-danger">
            <i data-lucide="log-out"></i>
            Logout
        </a>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();
</script>
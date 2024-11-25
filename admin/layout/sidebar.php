<div class="col-md-2" id="sidebar">
    <h4 class="p-3">Admin Panel</h4>
    
    <!-- Return to shop button at the top -->
    <a href="/" class="sidebar-link">
        Return to Shop
    </a>

    <!-- Regular menu items -->
    <a href="/admin/index.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        Dashboard
    </a>
    <a href="/admin/settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        Settings
    </a>

    <!-- Logout at the bottom -->
    <div style="position: absolute; bottom: 20px; width: 100%;">
        <a href="/logout.php" class="sidebar-link text-danger">
            Logout
        </a>
    </div>
</div> 
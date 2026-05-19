<?php
// =============================================================================
// includes/sidebar.php — Main navigation sidebar
// Reads role from session. Marks the active link by comparing script filename.
// =============================================================================

$user        = current_user();
$user_role   = $user['role'] ?? 'staff';
$current     = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Helper: return 'active' class string when the link matches the current page.
function nav_active(string $dir, string $file, string $current_dir, string $current): string
{
    return ($current_dir === $dir && $current === $file) ? ' active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <img
            src="<?= htmlspecialchars(app_url('assets/images/material-management.png'), ENT_QUOTES, 'UTF-8') ?>"
            alt="InvenTrack logo"
            class="app-logo app-logo--sidebar"
            width="40"
            height="40"
        >
        <span>InvenTrack</span>
    </div>

    <nav class="sidebar-nav">

        <!-- INVENTORY group -->
        <div class="nav-group-label">Inventory</div>

        <a href="<?= htmlspecialchars(app_url('pages/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link<?= ($current === 'dashboard.php') ? ' active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= htmlspecialchars(app_url('pages/items/index.php'), ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link<?= nav_active('items', 'index.php', $current_dir, $current) ?>">
            <i class="fa-solid fa-box"></i>
            <span>Items</span>
        </a>

        <a href="<?= htmlspecialchars(app_url('pages/categories/index.php'), ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link<?= nav_active('categories', 'index.php', $current_dir, $current) ?>">
            <i class="fa-solid fa-tags"></i>
            <span>Categories</span>
        </a>

        <a href="<?= htmlspecialchars(app_url('pages/suppliers/index.php'), ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link<?= nav_active('suppliers', 'index.php', $current_dir, $current) ?>">
            <i class="fa-solid fa-truck"></i>
            <span>Suppliers</span>
        </a>

        <!-- TRANSACTIONS group -->
        <div class="nav-group-label">Transactions</div>

        <a href="<?= htmlspecialchars(app_url('pages/purchases/index.php'), ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link<?= nav_active('purchases', 'index.php', $current_dir, $current) ?>">
            <i class="fa-solid fa-cart-arrow-down"></i>
            <span>Purchases</span>
        </a>

        <a href="<?= htmlspecialchars(app_url('pages/sales/index.php'), ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link<?= nav_active('sales', 'index.php', $current_dir, $current) ?>">
            <i class="fa-solid fa-receipt"></i>
            <span>Sales</span>
        </a>

        <!-- REPORTS group -->
        <div class="nav-group-label">Reports</div>

        <a href="<?= htmlspecialchars(app_url('pages/reports/index.php'), ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link<?= nav_active('reports', 'index.php', $current_dir, $current) ?>">
            <i class="fa-solid fa-chart-bar"></i>
            <span>Reports</span>
        </a>

        <!-- ADMIN group — visible to admin only -->
        <?php if ($user_role === 'admin'): ?>
        <div class="nav-group-label">Admin</div>

        <a href="<?= htmlspecialchars(app_url('pages/users/index.php'), ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link<?= nav_active('users', 'index.php', $current_dir, $current) ?>">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
        </a>

        <a href="<?= htmlspecialchars(app_url('pages/audit-logs/index.php'), ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link<?= nav_active('audit-logs', 'index.php', $current_dir, $current) ?>">
            <i class="fa-solid fa-clipboard-list"></i>
            <span>Audit Logs</span>
        </a>
        <?php endif; ?>

    </nav>

    <!-- Sidebar footer: logged-in user info -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <i class="fa-solid fa-circle-user"></i>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="sidebar-user-role"><?= htmlspecialchars(ucfirst($user_role), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
        <a href="<?= htmlspecialchars(app_url('pages/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-logout" title="Sign Out">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</aside>

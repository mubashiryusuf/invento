<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'manager', 'staff']);
$user = current_user();

// Stat counts
$total_items     = $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
$total_suppliers = $pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();
$today_sales     = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(sale_date)=CURDATE()")->fetchColumn();
$low_stock_count = $pdo->query('SELECT COUNT(*) FROM items WHERE stock_qty <= reorder_level')->fetchColumn();

// Low stock items
$low_stock_items = $pdo->query(
    'SELECT id, item_code, name, stock_qty, reorder_level FROM items WHERE stock_qty <= reorder_level ORDER BY stock_qty ASC LIMIT 5'
)->fetchAll();

// Recent sales
$recent_sales = $pdo->query(
    'SELECT s.id, s.customer_name, s.sale_date, s.total_amount, u.name AS created_by
     FROM sales s LEFT JOIN users u ON u.id = s.created_by
     ORDER BY s.sale_date DESC, s.id DESC LIMIT 5'
)->fetchAll();

// Recent purchases
$recent_purchases = $pdo->query(
    'SELECT p.id, p.purchase_date, p.total_amount, su.name AS supplier_name, u.name AS created_by
     FROM purchases p
     LEFT JOIN suppliers su ON su.id = p.supplier_id
     LEFT JOIN users u ON u.id = p.created_by
     ORDER BY p.purchase_date DESC, p.id DESC LIMIT 5'
)->fetchAll();

$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Dashboard</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>

    <main class="main-content">
        <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?= sanitize($flash['type']) ?>">
            <i class="fa-solid <?= $flash['type']==='success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <?= sanitize($flash['message']) ?>
        </div>
        <?php endif; ?>

        <!-- Low stock warning -->
        <?php if ($low_stock_count > 0): ?>
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <strong><?= (int)$low_stock_count ?> item(s)</strong> are at or below reorder level:
            <?php foreach ($low_stock_items as $i => $ls): ?>
                <?= $i > 0 ? ', ' : '' ?>
                <a href="<?= htmlspecialchars(app_url('pages/items/index.php'), ENT_QUOTES, 'UTF-8') ?>"><strong><?= sanitize($ls['name']) ?></strong></a>
                (<?= (int)$ls['stock_qty'] ?>/<?= (int)$ls['reorder_level'] ?>)
            <?php endforeach; ?>
            <?php if ($low_stock_count > 5): ?>&hellip;<?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Stat cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-label">Total Items</span>
                    <div class="stat-icon stat-icon--blue"><i class="fa-solid fa-box"></i></div>
                </div>
                <div class="stat-value"><?= (int)$total_items ?></div>
                <div class="stat-sub">Products in inventory</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-label">Suppliers</span>
                    <div class="stat-icon stat-icon--green"><i class="fa-solid fa-truck"></i></div>
                </div>
                <div class="stat-value"><?= (int)$total_suppliers ?></div>
                <div class="stat-sub">Active suppliers</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-label">Today's Sales</span>
                    <div class="stat-icon stat-icon--yellow"><i class="fa-solid fa-receipt"></i></div>
                </div>
                <div class="stat-value"><?= format_currency((float)$today_sales) ?></div>
                <div class="stat-sub">Revenue today</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-label">Low Stock</span>
                    <div class="stat-icon stat-icon--red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
                <div class="stat-value"><?= (int)$low_stock_count ?></div>
                <div class="stat-sub">Items need restocking</div>
            </div>
        </div>

        <!-- Recent tables -->
        <div class="dashboard-panel-grid">
            <section class="section-panel">
                <div class="section-panel__header">
                    <h2 class="section-panel__title">Recent Sales</h2>
                    <a href="<?= htmlspecialchars(app_url('pages/sales/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm">View All</a>
                </div>
                <div class="section-panel__body">
                    <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_sales)): ?>
                            <tr><td colspan="3" class="table-empty">No sales yet</td></tr>
                            <?php else: ?>
                            <?php foreach ($recent_sales as $s): ?>
                            <tr>
                                <td><a href="<?= htmlspecialchars(app_url('pages/sales/view.php?id=' . (int)$s['id']), ENT_QUOTES, 'UTF-8') ?>"><?= sanitize($s['customer_name']) ?></a></td>
                                <td><?= sanitize($s['sale_date']) ?></td>
                                <td class="price-cell"><?= format_currency((float)$s['total_amount']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </section>

            <section class="section-panel">
                <div class="section-panel__header">
                    <h2 class="section-panel__title">Recent Purchases</h2>
                    <a href="<?= htmlspecialchars(app_url('pages/purchases/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm">View All</a>
                </div>
                <div class="section-panel__body">
                    <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_purchases)): ?>
                            <tr><td colspan="3" class="table-empty">No purchases yet</td></tr>
                            <?php else: ?>
                            <?php foreach ($recent_purchases as $p): ?>
                            <tr>
                                <td><a href="<?= htmlspecialchars(app_url('pages/purchases/view.php?id=' . (int)$p['id']), ENT_QUOTES, 'UTF-8') ?>"><?= sanitize($p['supplier_name'] ?? 'N/A') ?></a></td>
                                <td><?= sanitize($p['purchase_date']) ?></td>
                                <td class="price-cell"><?= format_currency((float)$p['total_amount']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

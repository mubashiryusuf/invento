<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager']);
$user = current_user();

// Default: last 30 days
$date_to   = trim($_GET['date_to']   ?? date('Y-m-d'));
$date_from = trim($_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')));

// Summary stats
$sales_summary = $pdo->prepare(
    'SELECT COALESCE(SUM(total_amount),0) AS revenue, COUNT(*) AS cnt
     FROM sales WHERE sale_date BETWEEN :df AND :dt'
);
$sales_summary->execute([':df' => $date_from, ':dt' => $date_to]);
$ss = $sales_summary->fetch();

$purchases_summary = $pdo->prepare(
    'SELECT COALESCE(SUM(total_amount),0) AS cost, COUNT(*) AS cnt
     FROM purchases WHERE purchase_date BETWEEN :df AND :dt'
);
$purchases_summary->execute([':df' => $date_from, ':dt' => $date_to]);
$ps = $purchases_summary->fetch();

// Daily sales for chart
$daily_stmt = $pdo->prepare(
    'SELECT sale_date AS date, SUM(total_amount) AS amount
     FROM sales WHERE sale_date BETWEEN :df AND :dt
     GROUP BY sale_date ORDER BY sale_date ASC'
);
$daily_stmt->execute([':df' => $date_from, ':dt' => $date_to]);
$daily = $daily_stmt->fetchAll();
$chart_labels  = array_column($daily, 'date');
$chart_amounts = array_map('floatval', array_column($daily, 'amount'));

// Top 10 selling items
$top_items = $pdo->prepare(
    'SELECT i.name, SUM(si.qty) AS total_qty, SUM(si.qty * si.unit_price) AS total_revenue
     FROM sale_items si
     JOIN items i ON i.id = si.item_id
     JOIN sales s ON s.id = si.sale_id
     WHERE s.sale_date BETWEEN :df AND :dt
     GROUP BY si.item_id, i.name
     ORDER BY total_qty DESC LIMIT 10'
);
$top_items->execute([':df' => $date_from, ':dt' => $date_to]);
$top_items = $top_items->fetchAll();

// Low stock items
$low_stock = $pdo->query(
    'SELECT item_code, name, stock_qty, reorder_level, unit FROM items WHERE stock_qty <= reorder_level ORDER BY stock_qty ASC'
)->fetchAll();

$page_title = 'Reports';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Reports</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content reports-page">
        <div class="page-header">
            <h2 class="page-title">Reports &amp; Analytics</h2>
        </div>

        <!-- Date filter -->
        <form method="GET" class="reports-filter">
            <label style="font-size:13px;color:var(--muted)">From</label>
            <input type="date" name="date_from" value="<?= sanitize($date_from) ?>">
            <label style="font-size:13px;color:var(--muted)">To</label>
            <input type="date" name="date_to" value="<?= sanitize($date_to) ?>">
            <button type="submit" class="btn-primary btn-sm">Apply</button>
        </form>

        <!-- Summary stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-label">Sales Revenue</span>
                    <div class="stat-icon stat-icon--green"><i class="fa-solid fa-arrow-trend-up"></i></div>
                </div>
                <div class="stat-value"><?= format_currency((float)$ss['revenue']) ?></div>
                <div class="stat-sub"><?= (int)$ss['cnt'] ?> sales in period</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-label">Purchase Cost</span>
                    <div class="stat-icon stat-icon--yellow"><i class="fa-solid fa-cart-arrow-down"></i></div>
                </div>
                <div class="stat-value"><?= format_currency((float)$ps['cost']) ?></div>
                <div class="stat-sub"><?= (int)$ps['cnt'] ?> purchases in period</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-label">Net Profit</span>
                    <div class="stat-icon stat-icon--blue"><i class="fa-solid fa-scale-balanced"></i></div>
                </div>
                <?php $net = (float)$ss['revenue'] - (float)$ps['cost']; ?>
                <div class="stat-value <?= $net >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_currency($net) ?></div>
                <div class="stat-sub">Revenue minus purchases</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-label">Low Stock Items</span>
                    <div class="stat-icon stat-icon--red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
                <div class="stat-value"><?= count($low_stock) ?></div>
                <div class="stat-sub">Items need restocking</div>
            </div>
        </div>

        <!-- Sales chart -->
        <div class="form-card reports-chart-card">
            <div class="form-card-header">
                <h3 class="form-card-title">Daily Sales Revenue</h3>
            </div>
            <?php if (empty($daily)): ?>
            <p class="text-center text-muted mt-24 mb-24">No sales data for this period.</p>
            <?php else: ?>
            <div class="chart-shell">
                <canvas id="salesChart"></canvas>
            </div>
            <?php endif; ?>
        </div>

        <div class="analytics-grid">
            <section class="section-panel">
                <div class="section-panel__header">
                    <h3 class="section-panel__title">Top Selling Items</h3>
                </div>
                <div class="section-panel__body">
                    <div class="table-container">
                    <table>
                        <thead><tr><th>Item</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
                        <tbody>
                            <?php if (empty($top_items)): ?>
                            <tr><td colspan="3" class="table-empty">No data.</td></tr>
                            <?php else: ?>
                            <?php foreach ($top_items as $ti): ?>
                            <tr>
                                <td><?= sanitize($ti['name']) ?></td>
                                <td><?= (float)$ti['total_qty'] ?></td>
                                <td class="price-cell"><?= format_currency((float)$ti['total_revenue']) ?></td>
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
                    <h3 class="section-panel__title">Low Stock Alert</h3>
                </div>
                <div class="section-panel__body">
                    <div class="table-container">
                    <table>
                        <thead><tr><th>Code</th><th>Item</th><th>Stock</th><th>Reorder</th></tr></thead>
                        <tbody>
                            <?php if (empty($low_stock)): ?>
                            <tr><td colspan="4" class="table-empty">All items well stocked!</td></tr>
                            <?php else: ?>
                            <?php foreach ($low_stock as $ls): ?>
                            <tr>
                                <td><span class="item-code"><?= sanitize($ls['item_code']) ?></span></td>
                                <td><?= sanitize($ls['name']) ?></td>
                                <td>
                                    <span class="badge <?= (int)$ls['stock_qty']===0 ? 'badge-danger' : 'badge-warning' ?>"><?= (int)$ls['stock_qty'] ?></span>
                                </td>
                                <td><?= (int)$ls['reorder_level'] ?></td>
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

<?php if (!empty($daily)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Sales Revenue',
            data: <?= json_encode($chart_amounts) ?>,
            backgroundColor: 'rgba(37,99,235,0.75)',
            borderColor: '#2563eb',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => 'Rs ' + v.toLocaleString() } }
        }
    }
});
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

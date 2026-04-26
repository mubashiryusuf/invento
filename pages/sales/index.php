<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager', 'staff']);
$user = current_user();

$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to']   ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per       = 15;

$where  = ['1=1'];
$params = [];
if ($date_from !== '') { $where[] = 's.sale_date >= :df'; $params[':df'] = $date_from; }
if ($date_to   !== '') { $where[] = 's.sale_date <= :dt'; $params[':dt'] = $date_to; }
$where_sql = implode(' AND ', $where);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM sales s WHERE $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pag  = paginate($total, $per, $page);
$stmt = $pdo->prepare(
    "SELECT s.id, s.customer_name, s.sale_date, s.total_amount, u.name AS created_by_name
     FROM sales s
     LEFT JOIN users u ON u.id = s.created_by
     WHERE $where_sql
     ORDER BY s.sale_date DESC, s.id DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit',  $per,          PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$page_title = 'Sales';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Sales</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content">
        <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?= sanitize($flash['type']) ?>" style="margin-bottom:16px">
            <i class="fa-solid <?= $flash['type']==='success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <?= sanitize($flash['message']) ?>
        </div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h2 class="page-title">Sales</h2>
                <p class="page-subtitle"><?= $total ?> record(s)</p>
            </div>
            <div style="display:flex;gap:8px">
                <a href="<?= htmlspecialchars(app_url('api/export_csv.php?type=sales'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm"><i class="fa-solid fa-download"></i> Export</a>
                <a href="<?= htmlspecialchars(app_url('pages/sales/add.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-primary"><i class="fa-solid fa-plus"></i> New Sale</a>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <label style="font-size:13px;color:var(--muted)">From</label>
            <input type="date" name="date_from" value="<?= sanitize($date_from) ?>">
            <label style="font-size:13px;color:var(--muted)">To</label>
            <input type="date" name="date_to" value="<?= sanitize($date_to) ?>">
            <button type="submit" class="btn-primary btn-sm">Filter</button>
            <?php if ($date_from || $date_to): ?>
            <a href="<?= htmlspecialchars(app_url('pages/sales/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Recorded By</th>
                        <th class="col-actions">View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="table-empty">No sales found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= sanitize($r['customer_name']) ?></td>
                        <td><?= sanitize($r['sale_date']) ?></td>
                        <td class="price-cell"><?= format_currency((float)$r['total_amount']) ?></td>
                        <td><?= sanitize($r['created_by_name'] ?? 'N/A') ?></td>
                        <td class="col-actions">
                            <a href="<?= htmlspecialchars(app_url('pages/sales/view.php?id=' . (int)$r['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn-action btn-edit" title="View"><i class="fa-solid fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($pag['total_pages'] > 1): ?>
            <div class="pagination">
                <span class="pagination-info">Page <?= $pag['current_page'] ?> of <?= $pag['total_pages'] ?></span>
                <?php
                $base = app_url('pages/sales/index.php') . '?date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to) . '&page=';
                for ($i = 1; $i <= $pag['total_pages']; $i++):
                ?>
                <a href="<?= $base.$i ?>" class="page-btn <?= $i===$pag['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

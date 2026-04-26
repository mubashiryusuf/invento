<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager', 'staff']);
$user      = current_user();
$is_editor = in_array($user['role'], ['admin', 'manager'], true);
$csrf_token = generate_csrf();

$q      = trim($_GET['q']      ?? '');
$cat_id = (int)($_GET['cat_id'] ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 15;

// Build WHERE
$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $where[]         = '(i.name LIKE :q OR i.item_code LIKE :q)';
    $params[':q']    = '%' . $q . '%';
}
if ($cat_id > 0) {
    $where[]          = 'i.category_id = :cat_id';
    $params[':cat_id'] = $cat_id;
}
$where_sql = implode(' AND ', $where);

$count_sql = "SELECT COUNT(*) FROM items i LEFT JOIN categories c ON c.id=i.category_id WHERE $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pag    = paginate($total, $per, $page);
$offset = $pag['offset'];

$data_sql = "SELECT i.*, c.name AS category_name
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE $where_sql
             ORDER BY i.id DESC
             LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($data_sql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit',  $per,    PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$page_title = 'Items';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Items</h1>
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
                <h2 class="page-title">Inventory Items</h2>
                <p class="page-subtitle"><?= $total ?> item(s) found</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <a href="<?= htmlspecialchars(app_url('api/export_csv.php?type=items'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm">
                    <i class="fa-solid fa-download"></i> Export CSV
                </a>
                <?php if ($is_editor): ?>
                <a href="<?= htmlspecialchars(app_url('pages/items/add.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">
                    <i class="fa-solid fa-plus"></i> Add Item
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_editor): ?>
        <div class="form-card" style="margin-bottom:16px;max-width:100%">
            <form method="POST" action="<?= htmlspecialchars(app_url('api/import_csv.php'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="filter-bar">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="type" value="items">
                <label style="font-size:13px;color:var(--muted)">Import Items CSV</label>
                <input type="file" name="csv_file" accept=".csv,text/csv" required>
                <button type="submit" class="btn-primary btn-sm">Import CSV</button>
                <span class="field-hint">Columns: item_code, name, category, unit, price, stock_qty, reorder_level, expiry_date</span>
            </form>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <form method="GET" class="filter-bar">
            <div class="search-wrapper">
                <i class="fa-solid fa-search"></i>
                <input type="search" name="q" placeholder="Search by name or code…" value="<?= sanitize($q) ?>">
            </div>
            <select name="cat_id">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= $cat_id===$cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary btn-sm">Filter</button>
            <?php if ($q || $cat_id): ?>
            <a href="<?= htmlspecialchars(app_url('pages/items/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Reorder</th>
                        <th>Expiry</th>
                        <?php if ($is_editor): ?><th class="col-actions">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr><td colspan="<?= $is_editor ? 9 : 8 ?>" class="table-empty">No items found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <?php
                        $stock = (int)$item['stock_qty'];
                        $reorder = (int)$item['reorder_level'];
                        if ($stock === 0) { $badge = 'badge-danger'; $badge_text = 'Out'; }
                        elseif ($stock <= $reorder) { $badge = 'badge-warning'; $badge_text = 'Low'; }
                        else { $badge = 'badge-success'; $badge_text = 'OK'; }
                    ?>
                    <tr>
                        <td><span class="item-code"><?= sanitize($item['item_code']) ?></span></td>
                        <td><?= sanitize($item['name']) ?></td>
                        <td><?= sanitize($item['category_name'] ?? '—') ?></td>
                        <td><?= sanitize($item['unit']) ?></td>
                        <td class="price-cell"><?= format_currency((float)$item['price']) ?></td>
                        <td>
                            <div class="stock-cell">
                                <?= $stock ?>
                                <span class="badge <?= $badge ?>"><?= $badge_text ?></span>
                            </div>
                        </td>
                        <td><?= $reorder ?></td>
                        <td><?= $item['expiry_date'] ? format_date($item['expiry_date']) : '—' ?></td>
                        <?php if ($is_editor): ?>
                        <td class="col-actions">
                            <a href="<?= htmlspecialchars(app_url('pages/items/edit.php?id=' . (int)$item['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn-action btn-edit" title="Edit"><i class="fa-solid fa-pencil"></i></a>
                            <form method="POST" action="<?= htmlspecialchars(app_url('pages/items/delete.php'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn-action btn-delete" title="Delete" data-confirm-delete data-item-name="<?= sanitize($item['name']) ?>"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($pag['total_pages'] > 1): ?>
            <div class="pagination">
                <span class="pagination-info">Page <?= $pag['current_page'] ?> of <?= $pag['total_pages'] ?></span>
                <?php
                $base = app_url('pages/items/index.php') . '?q=' . urlencode($q) . '&cat_id=' . $cat_id . '&page=';
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

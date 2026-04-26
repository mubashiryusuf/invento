<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager', 'staff']);
$user      = current_user();
$is_editor = in_array($user['role'], ['admin', 'manager'], true);
$csrf_token = generate_csrf();

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 20;

$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $where[]      = '(name LIKE :q OR email LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
$where_sql = implode(' AND ', $where);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pag  = paginate($total, $per, $page);
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE $where_sql ORDER BY id DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit',  $per,          PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$page_title = 'Suppliers';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Suppliers</h1>
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
                <h2 class="page-title">Suppliers</h2>
                <p class="page-subtitle"><?= $total ?> supplier(s)</p>
            </div>
            <div style="display:flex;gap:8px">
                <a href="<?= htmlspecialchars(app_url('api/export_csv.php?type=suppliers'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm"><i class="fa-solid fa-download"></i> Export CSV</a>
                <?php if ($is_editor): ?>
                <a href="<?= htmlspecialchars(app_url('pages/suppliers/add.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-primary"><i class="fa-solid fa-plus"></i> Add Supplier</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_editor): ?>
        <div class="form-card" style="margin-bottom:16px;max-width:100%">
            <form method="POST" action="<?= htmlspecialchars(app_url('api/import_csv.php'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="filter-bar">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="type" value="suppliers">
                <label style="font-size:13px;color:var(--muted)">Import Suppliers CSV</label>
                <input type="file" name="csv_file" accept=".csv,text/csv" required>
                <button type="submit" class="btn-primary btn-sm">Import CSV</button>
                <span class="field-hint">Columns: name, contact, email, address</span>
            </form>
        </div>
        <?php endif; ?>

        <form method="GET" class="filter-bar">
            <div class="search-wrapper">
                <i class="fa-solid fa-search"></i>
                <input type="search" name="q" placeholder="Search by name or email…" value="<?= sanitize($q) ?>">
            </div>
            <button type="submit" class="btn-primary btn-sm">Search</button>
            <?php if ($q): ?><a href="<?= htmlspecialchars(app_url('pages/suppliers/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm">Clear</a><?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Address</th>
                        <?php if ($is_editor): ?><th class="col-actions">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= $is_editor ? 6 : 5 ?>" class="table-empty">No suppliers found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($rows as $s): ?>
                    <tr>
                        <td><?= (int)$s['id'] ?></td>
                        <td><?= sanitize($s['name']) ?></td>
                        <td><?= sanitize($s['contact'] ?? '—') ?></td>
                        <td><?= sanitize($s['email'] ?? '—') ?></td>
                        <td><?= sanitize($s['address'] ?? '—') ?></td>
                        <?php if ($is_editor): ?>
                        <td class="col-actions">
                            <a href="<?= htmlspecialchars(app_url('pages/suppliers/edit.php?id=' . (int)$s['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn-action btn-edit" title="Edit"><i class="fa-solid fa-pencil"></i></a>
                            <form method="POST" action="<?= htmlspecialchars(app_url('pages/suppliers/delete.php'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                <button type="submit" class="btn-action btn-delete" title="Delete" data-confirm-delete data-item-name="<?= sanitize($s['name']) ?>"><i class="fa-solid fa-trash"></i></button>
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
                <?php for ($i = 1; $i <= $pag['total_pages']; $i++): ?>
                <a href="<?= htmlspecialchars(app_url('pages/suppliers/index.php?q=' . urlencode($q) . '&page=' . $i), ENT_QUOTES, 'UTF-8') ?>" class="page-btn <?= $i===$pag['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

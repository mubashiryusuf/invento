<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager', 'staff']);
$user      = current_user();
$is_editor = in_array($user['role'], ['admin', 'manager'], true);

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 20;

$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $where[]      = 'c.name LIKE :q';
    $params[':q'] = '%' . $q . '%';
}
$where_sql = implode(' AND ', $where);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM categories c WHERE $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pag    = paginate($total, $per, $page);
$offset = $pag['offset'];

$stmt = $pdo->prepare(
    "SELECT c.*, (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id) AS item_count
     FROM categories c WHERE $where_sql
     ORDER BY c.id DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit',  $per,    PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$page_title = 'Categories';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Categories</h1>
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
                <h2 class="page-title">Categories</h2>
                <p class="page-subtitle"><?= $total ?> category/ies found</p>
            </div>
            <?php if ($is_editor): ?>
            <a href="<?= htmlspecialchars(app_url('pages/categories/add.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-primary"><i class="fa-solid fa-plus"></i> Add Category</a>
            <?php endif; ?>
        </div>

        <form method="GET" class="filter-bar">
            <div class="search-wrapper">
                <i class="fa-solid fa-search"></i>
                <input type="search" name="q" placeholder="Search categories…" value="<?= sanitize($q) ?>">
            </div>
            <button type="submit" class="btn-primary btn-sm">Search</button>
            <?php if ($q): ?><a href="<?= htmlspecialchars(app_url('pages/categories/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm">Clear</a><?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Items</th>
                        <?php if ($is_editor): ?><th class="col-actions">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= $is_editor ? 5 : 4 ?>" class="table-empty">No categories found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($rows as $cat): ?>
                    <tr>
                        <td><?= (int)$cat['id'] ?></td>
                        <td><?= sanitize($cat['name']) ?></td>
                        <td><?= sanitize($cat['description'] ?? '—') ?></td>
                        <td><span class="badge badge-info"><?= (int)$cat['item_count'] ?></span></td>
                        <?php if ($is_editor): ?>
                        <td class="col-actions">
                            <a href="<?= htmlspecialchars(app_url('pages/categories/edit.php?id=' . (int)$cat['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn-action btn-edit" title="Edit"><i class="fa-solid fa-pencil"></i></a>
                            <form method="POST" action="<?= htmlspecialchars(app_url('pages/categories/delete.php'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                <button type="submit" class="btn-action btn-delete" title="Delete" data-confirm-delete data-item-name="<?= sanitize($cat['name']) ?>"><i class="fa-solid fa-trash"></i></button>
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
                <a href="<?= htmlspecialchars(app_url('pages/categories/index.php?q=' . urlencode($q) . '&page=' . $i), ENT_QUOTES, 'UTF-8') ?>" class="page-btn <?= $i===$pag['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

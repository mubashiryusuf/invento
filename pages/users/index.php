<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);
$user = current_user();

$q    = trim($_GET['q']    ?? '');
$role = trim($_GET['role'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 20;

$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $where[]           = '(name LIKE :q_name OR email LIKE :q_email)';
    $params[':q_name'] = '%' . $q . '%';
    $params[':q_email'] = '%' . $q . '%';
}
if (in_array($role, ['admin','manager','staff'], true)) {
    $where[]        = 'role = :role';
    $params[':role'] = $role;
}
$where_sql = implode(' AND ', $where);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pag  = paginate($total, $per, $page);
$stmt = $pdo->prepare("SELECT id, name, email, role, status, created_at FROM users WHERE $where_sql ORDER BY id DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit',  $per,          PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$page_title = 'Users';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Users</h1>
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
                <h2 class="page-title">User Management</h2>
                <p class="page-subtitle"><?= $total ?> user(s)</p>
            </div>
            <a href="<?= htmlspecialchars(app_url('pages/users/add.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-primary"><i class="fa-solid fa-plus"></i> Add User</a>
        </div>

        <form method="GET" class="filter-bar">
            <div class="search-wrapper">
                <i class="fa-solid fa-search"></i>
                <input type="search" name="q" placeholder="Search by name or email…" value="<?= sanitize($q) ?>">
            </div>
            <select name="role">
                <option value="">All Roles</option>
                <option value="admin"   <?= $role==='admin'   ? 'selected' : '' ?>>Admin</option>
                <option value="manager" <?= $role==='manager' ? 'selected' : '' ?>>Manager</option>
                <option value="staff"   <?= $role==='staff'   ? 'selected' : '' ?>>Staff</option>
            </select>
            <button type="submit" class="btn-primary btn-sm">Filter</button>
            <?php if ($q || $role): ?><a href="<?= htmlspecialchars(app_url('pages/users/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm">Clear</a><?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="table-empty">No users found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($rows as $u): ?>
                    <?php
                        $role_badge  = ['admin'=>'badge-danger','manager'=>'badge-warning','staff'=>'badge-info'][$u['role']] ?? 'badge-muted';
                        $status_badge = $u['status']==='active' ? 'badge-success' : 'badge-danger';
                        $is_self     = (int)$u['id'] === (int)$user['id'];
                    ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= sanitize($u['name']) ?> <?= $is_self ? '<span class="badge badge-info">You</span>' : '' ?></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td><span class="badge <?= $role_badge ?>"><?= ucfirst(sanitize($u['role'])) ?></span></td>
                        <td><span class="badge <?= $status_badge ?>"><?= ucfirst(sanitize($u['status'])) ?></span></td>
                        <td><?= format_date($u['created_at']) ?></td>
                        <td class="col-actions">
                            <a href="<?= htmlspecialchars(app_url('pages/users/edit.php?id=' . (int)$u['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn-action btn-edit" title="Edit"><i class="fa-solid fa-pencil"></i></a>
                            <?php if (!$is_self): ?>
                            <form method="POST" action="<?= htmlspecialchars(app_url('pages/users/toggle.php'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn-action btn-toggle" title="<?= $u['status']==='active' ? 'Deactivate' : 'Activate' ?>">
                                    <i class="fa-solid fa-power-off"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= htmlspecialchars(app_url('pages/users/delete.php'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn-action btn-delete" title="Delete" data-confirm-delete data-item-name="<?= sanitize($u['name']) ?>"><i class="fa-solid fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($pag['total_pages'] > 1): ?>
            <div class="pagination">
                <span class="pagination-info">Page <?= $pag['current_page'] ?> of <?= $pag['total_pages'] ?></span>
                <?php for ($i = 1; $i <= $pag['total_pages']; $i++): ?>
                <a href="<?= htmlspecialchars(app_url('pages/users/index.php?q=' . urlencode($q) . '&role=' . urlencode($role) . '&page=' . $i), ENT_QUOTES, 'UTF-8') ?>" class="page-btn <?= $i===$pag['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

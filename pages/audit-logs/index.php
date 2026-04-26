<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['admin']);

$user = current_user();
$filter_user = (int)($_GET['user_id'] ?? 0);
$filter_action = trim($_GET['action'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 25;

$where = ['1=1'];
$params = [];

if ($filter_user > 0) {
    $where[] = 'a.user_id = :uid';
    $params[':uid'] = $filter_user;
}

if ($filter_action !== '') {
    $where[] = 'a.action LIKE :act';
    $params[':act'] = '%' . $filter_action . '%';
}

if ($date_from !== '') {
    $where[] = 'a.created_at >= :df';
    $params[':df'] = $date_from;
}

if ($date_to !== '') {
    $where[] = 'a.created_at <= :dt';
    $params[':dt'] = $date_to . ' 23:59:59';
}

$where_sql = implode(' AND ', $where);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a WHERE $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pag = paginate($total, $per, $page);
$stmt = $pdo->prepare(
    "SELECT a.*, u.name AS user_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     WHERE $where_sql
     ORDER BY a.id DESC
     LIMIT :limit OFFSET :offset"
);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$all_users = $pdo->query('SELECT id, name FROM users ORDER BY name')->fetchAll();
$action_colors = [
    'CREATE' => 'badge-success',
    'UPDATE' => 'badge-info',
    'DELETE' => 'badge-danger',
    'LOGIN' => 'badge-info',
    'LOGIN_FAILED' => 'badge-danger',
    'TOGGLE_STATUS' => 'badge-warning',
    'IMPORT' => 'badge-info',
];

$page_title = 'Audit Logs';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Audit Logs</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Audit Logs</h2>
                <p class="page-subtitle"><?= $total ?> entries</p>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <select name="user_id">
                <option value="0">All Users</option>
                <?php foreach ($all_users as $filter_user_row): ?>
                <option value="<?= (int)$filter_user_row['id'] ?>" <?= $filter_user === (int)$filter_user_row['id'] ? 'selected' : '' ?>>
                    <?= sanitize($filter_user_row['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="action" placeholder="Action (CREATE, DELETE...)" value="<?= sanitize($filter_action) ?>" class="filter-input">
            <label for="date_from">From</label>
            <input type="date" id="date_from" name="date_from" value="<?= sanitize($date_from) ?>">
            <label for="date_to">To</label>
            <input type="date" id="date_to" name="date_to" value="<?= sanitize($date_to) ?>">
            <button type="submit" class="btn-primary btn-sm">Filter</button>
            <a href="<?= htmlspecialchars(app_url('pages/audit-logs/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary btn-sm">Clear</a>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>Details</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="table-empty">No log entries found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($rows as $log): ?>
                    <tr>
                        <td><?= (int)$log['id'] ?></td>
                        <td><?= sanitize($log['user_name'] ?? 'System') ?></td>
                        <td>
                            <span class="badge <?= $action_colors[$log['action']] ?? 'badge-info' ?>">
                                <?= sanitize($log['action']) ?>
                            </span>
                        </td>
                        <td><span class="item-code"><?= sanitize($log['table_name']) ?></span></td>
                        <td><?= $log['record_id'] !== null ? (int)$log['record_id'] : '-' ?></td>
                        <td class="table-cell-truncate" title="<?= sanitize($log['details']) ?>"><?= sanitize($log['details']) ?></td>
                        <td class="text-nowrap"><?= sanitize($log['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($pag['total_pages'] > 1): ?>
            <div class="pagination">
                <span class="pagination-info">Page <?= $pag['current_page'] ?> of <?= $pag['total_pages'] ?></span>
                <?php
                $base = app_url('pages/audit-logs/index.php') . '?user_id=' . $filter_user .
                    '&action=' . urlencode($filter_action) .
                    '&date_from=' . urlencode($date_from) .
                    '&date_to=' . urlencode($date_to) .
                    '&page=';
                for ($i = 1; $i <= $pag['total_pages']; $i++):
                ?>
                <a href="<?= $base . $i ?>" class="page-btn <?= $i === $pag['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

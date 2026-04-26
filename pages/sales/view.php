<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager', 'staff']);
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error', 'Invalid sale.'); redirect('pages/sales/index.php'); }

$s_stmt = $pdo->prepare(
    'SELECT s.*, u.name AS created_by_name
     FROM sales s
     LEFT JOIN users u ON u.id = s.created_by
     WHERE s.id = :id LIMIT 1'
);
$s_stmt->execute([':id' => $id]);
$sale = $s_stmt->fetch();
if (!$sale) { flash('error', 'Sale not found.'); redirect('pages/sales/index.php'); }

$si_stmt = $pdo->prepare(
    'SELECT si.*, i.name AS item_name, i.item_code, i.unit
     FROM sale_items si
     LEFT JOIN items i ON i.id = si.item_id
     WHERE si.sale_id = :sid'
);
$si_stmt->execute([':sid' => $id]);
$line_items = $si_stmt->fetchAll();

$page_title = 'Sale #' . $id;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Sale Details</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Sale #<?= (int)$sale['id'] ?></h2>
                <p class="page-subtitle"><?= format_date($sale['sale_date']) ?></p>
            </div>
            <a href="<?= htmlspecialchars(app_url('pages/sales/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <div class="form-card">
            <div class="record-summary">
                <div class="record-summary__item">
                    <span class="record-summary__label">Customer</span>
                    <div class="record-summary__value"><?= sanitize($sale['customer_name']) ?></div>
                </div>
                <div class="record-summary__item">
                    <span class="record-summary__label">Date</span>
                    <div class="record-summary__value"><?= sanitize($sale['sale_date']) ?></div>
                </div>
                <div class="record-summary__item">
                    <span class="record-summary__label">Recorded By</span>
                    <div class="record-summary__value"><?= sanitize($sale['created_by_name'] ?? 'N/A') ?></div>
                </div>
                <div class="record-summary__item">
                    <span class="record-summary__label">Total Amount</span>
                    <div class="record-summary__value record-summary__value--accent record-summary__value--success"><?= format_currency((float)$sale['total_amount']) ?></div>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($line_items)): ?>
                        <tr><td colspan="6" class="table-empty">No items.</td></tr>
                        <?php else: ?>
                        <?php foreach ($line_items as $li): ?>
                        <tr>
                            <td><span class="item-code"><?= sanitize($li['item_code'] ?? '') ?></span></td>
                            <td><?= sanitize($li['item_name'] ?? 'Deleted Item') ?></td>
                            <td><?= sanitize($li['unit'] ?? '') ?></td>
                            <td><?= (float)$li['qty'] ?></td>
                            <td class="price-cell"><?= format_currency((float)$li['unit_price']) ?></td>
                            <td class="price-cell"><?= format_currency((float)$li['qty'] * (float)$li['unit_price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="5" class="text-right"><strong>Total:</strong></td>
                            <td class="price-cell"><?= format_currency((float)$sale['total_amount']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

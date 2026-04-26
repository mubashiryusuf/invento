<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager', 'staff']);
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error', 'Invalid purchase.'); redirect('pages/purchases/index.php'); }

$p_stmt = $pdo->prepare(
    'SELECT p.*, s.name AS supplier_name, s.contact AS supplier_contact,
            u.name AS created_by_name
     FROM purchases p
     LEFT JOIN suppliers s ON s.id = p.supplier_id
     LEFT JOIN users u ON u.id = p.created_by
     WHERE p.id = :id LIMIT 1'
);
$p_stmt->execute([':id' => $id]);
$purchase = $p_stmt->fetch();
if (!$purchase) { flash('error', 'Purchase not found.'); redirect('pages/purchases/index.php'); }

$pi_stmt = $pdo->prepare(
    'SELECT pi.*, i.name AS item_name, i.item_code, i.unit
     FROM purchase_items pi
     LEFT JOIN items i ON i.id = pi.item_id
     WHERE pi.purchase_id = :pid'
);
$pi_stmt->execute([':pid' => $id]);
$line_items = $pi_stmt->fetchAll();

$page_title = 'Purchase #' . $id;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Purchase Details</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Purchase #<?= (int)$purchase['id'] ?></h2>
                <p class="page-subtitle"><?= format_date($purchase['purchase_date']) ?></p>
            </div>
            <a href="<?= htmlspecialchars(app_url('pages/purchases/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <div class="form-card">
            <div class="record-summary">
                <div class="record-summary__item">
                    <span class="record-summary__label">Supplier</span>
                    <div class="record-summary__value"><?= sanitize($purchase['supplier_name'] ?? 'N/A') ?></div>
                    <?php if ($purchase['supplier_contact']): ?>
                    <div class="field-hint"><?= sanitize($purchase['supplier_contact']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="record-summary__item">
                    <span class="record-summary__label">Date</span>
                    <div class="record-summary__value"><?= sanitize($purchase['purchase_date']) ?></div>
                </div>
                <div class="record-summary__item">
                    <span class="record-summary__label">Recorded By</span>
                    <div class="record-summary__value"><?= sanitize($purchase['created_by_name'] ?? 'N/A') ?></div>
                </div>
                <div class="record-summary__item">
                    <span class="record-summary__label">Total Amount</span>
                    <div class="record-summary__value record-summary__value--accent"><?= format_currency((float)$purchase['total_amount']) ?></div>
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
                        <tr><td colspan="6" class="table-empty">No items found.</td></tr>
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
                            <td class="price-cell"><?= format_currency((float)$purchase['total_amount']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

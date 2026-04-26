<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['admin', 'manager', 'staff']);

$user = current_user();
$items_list = $pdo->query('SELECT id, name, price, unit, stock_qty FROM items ORDER BY name')->fetchAll();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $old = $_POST;
    $customer_name = trim($_POST['customer_name'] ?? '');
    $sale_date = trim($_POST['sale_date'] ?? '');
    $item_ids = $_POST['item_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $unit_prices = $_POST['unit_price'] ?? [];

    if ($customer_name === '') {
        $errors['customer_name'] = 'Customer name is required.';
    }
    if ($sale_date === '') {
        $errors['sale_date'] = 'Sale date is required.';
    }

    $line_items = [];
    foreach ($item_ids as $idx => $iid) {
        $iid = (int)$iid;
        $qty = (float)($qtys[$idx] ?? 0);
        $unit_price = (float)($unit_prices[$idx] ?? 0);

        if ($iid < 1 || $qty <= 0) {
            continue;
        }

        $line_items[] = [
            'item_id' => $iid,
            'qty' => $qty,
            'unit_price' => max(0, $unit_price),
        ];
    }

    if (empty($line_items)) {
        $errors['items'] = 'At least one valid item row is required.';
    }

    if (empty($errors)) {
        $total = array_sum(array_map(static fn(array $row): float => $row['qty'] * $row['unit_price'], $line_items));

        try {
            $pdo->beginTransaction();

            $sale_stmt = $pdo->prepare(
                'INSERT INTO sales (customer_name, sale_date, total_amount, created_by) VALUES (:customer_name, :sale_date, :total_amount, :created_by)'
            );
            $sale_stmt->execute([
                ':customer_name' => $customer_name,
                ':sale_date' => $sale_date,
                ':total_amount' => $total,
                ':created_by' => $user['id'],
            ]);

            $sale_id = (int)$pdo->lastInsertId();

            $line_stmt = $pdo->prepare(
                'INSERT INTO sale_items (sale_id, item_id, qty, unit_price) VALUES (:sale_id, :item_id, :qty, :unit_price)'
            );

            foreach ($line_items as $line_item) {
                $line_stmt->execute([
                    ':sale_id' => $sale_id,
                    ':item_id' => $line_item['item_id'],
                    ':qty' => $line_item['qty'],
                    ':unit_price' => $line_item['unit_price'],
                ]);
            }

            $pdo->commit();
            log_action($pdo, $user['id'], 'CREATE', 'sales', $sale_id, 'Sale to ' . $customer_name . ', total: ' . format_currency($total));
            flash('success', 'Sale recorded successfully.');
            redirect('pages/sales/index.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = $e->getMessage();
            if (stripos($message, 'stock') !== false) {
                $errors['items'] = 'Insufficient stock for one or more items. Please review quantities.';
            } else {
                $errors['items'] = 'Database error: ' . $message;
            }
        }
    }
}

$initial_rows = [];
foreach (($old['item_id'] ?? []) as $index => $item_id) {
    $qty = $old['qty'][$index] ?? '';
    $unit_price = $old['unit_price'][$index] ?? '';

    if (trim((string)$item_id) === '' && trim((string)$qty) === '' && trim((string)$unit_price) === '') {
        continue;
    }

    $initial_rows[] = [
        'item_id' => (int)$item_id,
        'qty' => (float)($qty === '' ? 1 : $qty),
        'unit_price' => (float)($unit_price === '' ? 0 : $unit_price),
    ];
}

$can_submit = !empty($items_list);

$page_title = 'New Sale';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">New Sale</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Record Sale</h2>
                <p class="page-subtitle">Capture customer sales with live totals and stock hints.</p>
            </div>
            <a href="<?= htmlspecialchars(app_url('pages/sales/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= implode(' ', array_map('sanitize', $errors)) ?>
        </div>
        <?php endif; ?>

        <?php if (empty($items_list)): ?>
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Add at least one inventory item before recording a sale.
        </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" id="saleForm" class="needs-validation">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">

                <div class="form-section">
                    <div class="form-section-title">Sale Details</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="customer_name">Customer Name <span class="required">*</span></label>
                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                value="<?= sanitize($old['customer_name'] ?? '') ?>"
                                class="<?= isset($errors['customer_name']) ? 'invalid' : '' ?>"
                                required
                            >
                            <?php if (isset($errors['customer_name'])): ?><span class="field-error"><?= sanitize($errors['customer_name']) ?></span><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="sale_date">Sale Date <span class="required">*</span></label>
                            <input
                                type="date"
                                id="sale_date"
                                name="sale_date"
                                value="<?= sanitize($old['sale_date'] ?? date('Y-m-d')) ?>"
                                class="<?= isset($errors['sale_date']) ? 'invalid' : '' ?>"
                                required
                            >
                            <?php if (isset($errors['sale_date'])): ?><span class="field-error"><?= sanitize($errors['sale_date']) ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Items</div>

                    <?php if (isset($errors['items'])): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?= sanitize($errors['items']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="transaction-table-wrap">
                        <div class="table-container">
                            <table class="transaction-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th class="text-right">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows"></tbody>
                            </table>
                        </div>
                    </div>

                    <button type="button" class="btn-secondary btn-sm" id="addRowBtn" <?= $can_submit ? '' : 'disabled' ?>>
                        <i class="fa-solid fa-plus"></i> Add Item
                    </button>
                </div>

                <div class="transaction-total">
                    <span class="transaction-total__label">Sale Total</span>
                    <span class="transaction-total__value" id="grandTotal">Rs 0.00</span>
                </div>

                <div class="form-footer">
                    <a href="<?= htmlspecialchars(app_url('pages/sales/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary" id="saveSaleBtn" <?= $can_submit ? '' : 'disabled' ?>>
                        <i class="fa-solid fa-floppy-disk"></i> Save Sale
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
const itemsData = <?= json_encode(array_map(static fn(array $item): array => [
    'id' => (int)$item['id'],
    'name' => $item['name'],
    'price' => (float)$item['price'],
    'unit' => $item['unit'],
    'stock' => (int)$item['stock_qty'],
], $items_list), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const initialRows = <?= json_encode($initial_rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const itemRows = document.getElementById('itemRows');
const addRowBtn = document.getElementById('addRowBtn');
const grandTotal = document.getElementById('grandTotal');
const hasInventoryData = itemsData.length > 0;

function buildItemSelect(selected) {
    let html = '<option value="0">- Select Item -</option>';

    itemsData.forEach(function (item) {
        const isSelected = Number(selected) === Number(item.id) ? ' selected' : '';
        html += '<option value="' + item.id + '" data-price="' + item.price + '" data-stock="' + item.stock + '"' + isSelected + '>' +
            item.name + ' (' + item.unit + ') - Stock: ' + item.stock +
            '</option>';
    });

    return html;
}

function updateGrandTotal() {
    let total = 0;

    itemRows.querySelectorAll('.row-subtotal').forEach(function (cell) {
        total += parseFloat(cell.dataset.value || '0');
    });

    grandTotal.textContent = 'Rs ' + total.toFixed(2);
}

function updateStockHint(row) {
    const select = row.querySelector('select');
    const hint = row.querySelector('.stock-hint');
    const selectedOption = select.options[select.selectedIndex];
    const stock = parseInt(selectedOption.dataset.stock || '0', 10);

    hint.textContent = stock > 0 ? 'Available: ' + stock : 'Out of stock';
    hint.className = 'field-hint stock-hint ' + (stock > 0 ? '' : 'text-danger');
}

function updateRow(row) {
    const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
    const unitPrice = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
    const subtotal = qty * unitPrice;
    const subtotalCell = row.querySelector('.row-subtotal');

    subtotalCell.dataset.value = subtotal.toFixed(2);
    subtotalCell.textContent = 'Rs ' + subtotal.toFixed(2);
    updateStockHint(row);
    updateGrandTotal();
}

function addRow(itemId, qty, unitPrice) {
    if (!hasInventoryData) {
        return;
    }

    const row = document.createElement('tr');
    row.innerHTML = '' +
        '<td><select name="item_id[]">' + buildItemSelect(itemId) + '</select><span class="field-hint stock-hint"></span></td>' +
        '<td><input type="number" name="qty[]" min="1" step="any" value="' + (qty || 1) + '"></td>' +
        '<td><input type="number" name="unit_price[]" min="0" step="0.01" value="' + Number(unitPrice || 0).toFixed(2) + '"></td>' +
        '<td class="price-cell row-subtotal" data-value="0">Rs 0.00</td>' +
        '<td class="text-center"><button type="button" class="transaction-row-remove" aria-label="Remove row"><i class="fa-solid fa-xmark"></i></button></td>';

    itemRows.appendChild(row);

    const select = row.querySelector('select');
    const qtyInput = row.querySelector('input[name="qty[]"]');
    const priceInput = row.querySelector('input[name="unit_price[]"]');
    const removeBtn = row.querySelector('.transaction-row-remove');

    select.addEventListener('change', function () {
        const selectedOption = select.options[select.selectedIndex];
        const selectedPrice = parseFloat(selectedOption.dataset.price || priceInput.value || '0');
        priceInput.value = selectedPrice.toFixed(2);
        updateRow(row);
    });

    qtyInput.addEventListener('input', function () {
        updateRow(row);
    });

    priceInput.addEventListener('input', function () {
        updateRow(row);
    });

    removeBtn.addEventListener('click', function () {
        if (itemRows.querySelectorAll('tr').length <= 1) {
            return;
        }

        row.remove();
        updateGrandTotal();
    });

    updateRow(row);
}

addRowBtn.addEventListener('click', function () {
    addRow(0, 1, 0);
});

if (initialRows.length > 0) {
    initialRows.forEach(function (row) {
        addRow(row.item_id || 0, row.qty || 1, row.unit_price || 0);
    });
} else if (hasInventoryData) {
    addRow(0, 1, 0);
}

updateGrandTotal();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

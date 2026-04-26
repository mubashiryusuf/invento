<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager']);
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error', 'Invalid item.'); redirect('pages/items/index.php'); }

$item_row = $pdo->prepare('SELECT * FROM items WHERE id = :id LIMIT 1');
$item_row->execute([':id' => $id]);
$item_row = $item_row->fetch();
if (!$item_row) { flash('error', 'Item not found.'); redirect('pages/items/index.php'); }

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors = [];
$old    = $item_row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $old = $_POST;
    $item_code   = trim($_POST['item_code']    ?? '');
    $name        = trim($_POST['name']         ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $unit        = trim($_POST['unit']         ?? '');
    $price       = (float)($_POST['price']     ?? 0);
    $stock_qty   = (int)($_POST['stock_qty']   ?? 0);
    $reorder     = (int)($_POST['reorder_level'] ?? 0);
    $expiry      = trim($_POST['expiry_date']  ?? '') ?: null;

    if ($item_code === '') $errors['item_code'] = 'Item code is required.';
    if ($name === '')      $errors['name']      = 'Name is required.';
    if ($category_id < 1)  $errors['category_id'] = 'Category is required.';
    if ($unit === '')      $errors['unit']      = 'Unit is required.';

    if ($item_code !== '') {
        $ck = $pdo->prepare('SELECT id FROM items WHERE item_code = :c AND id != :id LIMIT 1');
        $ck->execute([':c' => $item_code, ':id' => $id]);
        if ($ck->fetchColumn()) $errors['item_code'] = 'Item code already used by another item.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE items SET item_code=:ic, name=:nm, category_id=:ca, unit=:un,
             price=:pr, stock_qty=:sq, reorder_level=:rl, expiry_date=:ex
             WHERE id=:id'
        );
        $stmt->execute([
            ':ic' => $item_code, ':nm' => $name, ':ca' => $category_id,
            ':un' => $unit, ':pr' => $price, ':sq' => $stock_qty,
            ':rl' => $reorder, ':ex' => $expiry, ':id' => $id,
        ]);
        log_action($pdo, $user['id'], 'UPDATE', 'items', $id, "Updated item: $name");
        flash('success', "Item \"$name\" updated.");
        redirect('pages/items/index.php');
    }
}

$page_title = 'Edit Item';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Edit Item</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Edit Item</h2>
                <p class="page-subtitle"><?= sanitize($item_row['name']) ?></p>
            </div>
            <a href="<?= htmlspecialchars(app_url('pages/items/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <h3 class="form-card-title">Item Details</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Item Code <span class="required">*</span></label>
                        <input type="text" name="item_code" value="<?= sanitize($old['item_code'] ?? '') ?>" class="<?= isset($errors['item_code']) ? 'invalid' : '' ?>" required>
                        <?php if (isset($errors['item_code'])): ?><span class="field-error"><?= sanitize($errors['item_code']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" value="<?= sanitize($old['name'] ?? '') ?>" class="<?= isset($errors['name']) ? 'invalid' : '' ?>" required>
                        <?php if (isset($errors['name'])): ?><span class="field-error"><?= sanitize($errors['name']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Category <span class="required">*</span></label>
                        <select name="category_id" class="<?= isset($errors['category_id']) ? 'invalid' : '' ?>" required>
                            <option value="0">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= (int)($old['category_id'] ?? 0)===(int)$cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?><span class="field-error"><?= sanitize($errors['category_id']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Unit <span class="required">*</span></label>
                        <input type="text" name="unit" value="<?= sanitize($old['unit'] ?? '') ?>" class="<?= isset($errors['unit']) ? 'invalid' : '' ?>" required>
                    </div>
                    <div class="form-group">
                            <label>Price (Rs) <span class="required">*</span></label>
                        <input type="number" name="price" value="<?= sanitize($old['price'] ?? '0') ?>" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock_qty" value="<?= sanitize($old['stock_qty'] ?? '0') ?>" min="0">
                        <span class="field-hint">Usually managed via purchases/sales</span>
                    </div>
                    <div class="form-group">
                        <label>Reorder Level <span class="required">*</span></label>
                        <input type="number" name="reorder_level" value="<?= sanitize($old['reorder_level'] ?? '0') ?>" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date" value="<?= sanitize($old['expiry_date'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-footer">
                    <a href="<?= htmlspecialchars(app_url('pages/items/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update Item</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

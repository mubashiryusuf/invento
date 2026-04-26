<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager']);
$user = current_user();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $old = $_POST;
    $item_code    = trim($_POST['item_code']    ?? '');
    $name         = trim($_POST['name']         ?? '');
    $category_id  = (int)($_POST['category_id'] ?? 0);
    $unit         = trim($_POST['unit']         ?? '');
    $price        = (float)($_POST['price']     ?? 0);
    $stock_qty    = (int)($_POST['stock_qty']   ?? 0);
    $reorder      = (int)($_POST['reorder_level'] ?? 0);
    $expiry       = trim($_POST['expiry_date']  ?? '') ?: null;

    if ($item_code === '') $errors['item_code'] = 'Item code is required.';
    if ($name === '')      $errors['name']      = 'Name is required.';
    if ($category_id < 1)  $errors['category_id'] = 'Category is required.';
    if ($unit === '')      $errors['unit']      = 'Unit is required.';
    if ($price < 0)        $errors['price']     = 'Price cannot be negative.';

    if ($item_code !== '') {
        $ck = $pdo->prepare('SELECT id FROM items WHERE item_code = :c LIMIT 1');
        $ck->execute([':c' => $item_code]);
        if ($ck->fetchColumn()) $errors['item_code'] = 'Item code already exists.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO items (item_code, name, category_id, unit, price, stock_qty, reorder_level, expiry_date)
             VALUES (:ic, :nm, :ca, :un, :pr, :sq, :rl, :ex)'
        );
        $stmt->execute([
            ':ic' => $item_code, ':nm' => $name, ':ca' => $category_id,
            ':un' => $unit, ':pr' => $price, ':sq' => $stock_qty,
            ':rl' => $reorder, ':ex' => $expiry,
        ]);
        $new_id = (int)$pdo->lastInsertId();
        log_action($pdo, $user['id'], 'CREATE', 'items', $new_id, "Created item: $name");
        flash('success', "Item \"$name\" created successfully.");
        redirect('pages/items/index.php');
    }
}

$page_title = 'Add Item';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Add Item</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title">Add New Item</h2>
                <p class="page-subtitle">Add a product to inventory</p>
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
                        <input type="text" name="item_code" value="<?= sanitize($old['item_code'] ?? '') ?>" placeholder="e.g. ITM-001" class="<?= isset($errors['item_code']) ? 'invalid' : '' ?>" required>
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
                        <input type="text" name="unit" value="<?= sanitize($old['unit'] ?? '') ?>" placeholder="pcs / kg / litre / box" class="<?= isset($errors['unit']) ? 'invalid' : '' ?>" required>
                    </div>
                    <div class="form-group">
                            <label>Price (Rs) <span class="required">*</span></label>
                        <input type="number" name="price" value="<?= sanitize($old['price'] ?? '0') ?>" min="0" step="0.01" class="<?= isset($errors['price']) ? 'invalid' : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity <span class="required">*</span></label>
                        <input type="number" name="stock_qty" value="<?= sanitize($old['stock_qty'] ?? '0') ?>" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Reorder Level <span class="required">*</span></label>
                        <input type="number" name="reorder_level" value="<?= sanitize($old['reorder_level'] ?? '0') ?>" min="0" required>
                        <span class="field-hint">Alert when stock falls to this number</span>
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date" value="<?= sanitize($old['expiry_date'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-footer">
                    <a href="<?= htmlspecialchars(app_url('pages/items/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Item</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

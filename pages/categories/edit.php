<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager']);
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error', 'Invalid category.'); redirect('pages/categories/index.php'); }

$cat_stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
$cat_stmt->execute([':id' => $id]);
$cat = $cat_stmt->fetch();
if (!$cat) { flash('error', 'Category not found.'); redirect('pages/categories/index.php'); }

$errors = [];
$old    = $cat;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $old  = $_POST;
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors['name'] = 'Name is required.';
    } else {
        $ck = $pdo->prepare('SELECT id FROM categories WHERE name = :n AND id != :id LIMIT 1');
        $ck->execute([':n' => $name, ':id' => $id]);
        if ($ck->fetchColumn()) $errors['name'] = 'Category name already used.';
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE categories SET name=:n, description=:d WHERE id=:id')
            ->execute([':n' => $name, ':d' => $desc ?: null, ':id' => $id]);
        log_action($pdo, $user['id'], 'UPDATE', 'categories', $id, "Updated category: $name");
        flash('success', "Category \"$name\" updated.");
    redirect('pages/categories/index.php');
    }
}

$page_title = 'Edit Category';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Edit Category</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content">
        <div class="page-header">
            <h2 class="page-title">Edit Category</h2>
            <a href="<?= htmlspecialchars(app_url('pages/categories/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="form-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
                <div class="form-grid">
                    <div class="form-group form-grid-full">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" value="<?= sanitize($old['name'] ?? '') ?>" class="<?= isset($errors['name']) ? 'invalid' : '' ?>" required>
                        <?php if (isset($errors['name'])): ?><span class="field-error"><?= sanitize($errors['name']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group form-grid-full">
                        <label>Description</label>
                        <textarea name="description"><?= sanitize($old['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="form-footer">
                    <a href="<?= htmlspecialchars(app_url('pages/categories/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

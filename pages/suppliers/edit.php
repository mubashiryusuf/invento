<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager']);
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error', 'Invalid supplier.'); redirect('pages/suppliers/index.php'); }

$sup_stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = :id LIMIT 1');
$sup_stmt->execute([':id' => $id]);
$sup = $sup_stmt->fetch();
if (!$sup) { flash('error', 'Supplier not found.'); redirect('pages/suppliers/index.php'); }

$errors = [];
$old    = $sup;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $old     = $_POST;
    $name    = trim($_POST['name']    ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email   = trim($_POST['email']   ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') $errors['name'] = 'Name is required.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email.';
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE suppliers SET name=:n, contact=:c, email=:e, address=:a WHERE id=:id')
            ->execute([':n' => $name, ':c' => $contact ?: null, ':e' => $email ?: null, ':a' => $address ?: null, ':id' => $id]);
        log_action($pdo, $user['id'], 'UPDATE', 'suppliers', $id, "Updated supplier: $name");
        flash('success', "Supplier \"$name\" updated.");
    redirect('pages/suppliers/index.php');
    }
}

$page_title = 'Edit Supplier';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Edit Supplier</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content">
        <div class="page-header">
            <h2 class="page-title">Edit Supplier</h2>
            <a href="<?= htmlspecialchars(app_url('pages/suppliers/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="form-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" value="<?= sanitize($old['name'] ?? '') ?>" class="<?= isset($errors['name']) ? 'invalid' : '' ?>" required>
                        <?php if (isset($errors['name'])): ?><span class="field-error"><?= sanitize($errors['name']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Contact</label>
                        <input type="text" name="contact" value="<?= sanitize($old['contact'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= sanitize($old['email'] ?? '') ?>" class="<?= isset($errors['email']) ? 'invalid' : '' ?>">
                        <?php if (isset($errors['email'])): ?><span class="field-error"><?= sanitize($errors['email']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group form-grid-full">
                        <label>Address</label>
                        <textarea name="address"><?= sanitize($old['address'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="form-footer">
                    <a href="<?= htmlspecialchars(app_url('pages/suppliers/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

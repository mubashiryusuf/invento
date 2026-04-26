<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);
$user   = current_user();
$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $old      = $_POST;
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_val = trim($_POST['role']     ?? 'staff');
    $status   = trim($_POST['status']   ?? 'active');

    if ($name === '')     $errors['name']     = 'Name is required.';
    if ($email === '')    $errors['email']    = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
    if ($password === '') $errors['password'] = 'Password is required.';
    elseif (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if (!in_array($role_val, ['admin','manager','staff'], true)) $errors['role'] = 'Invalid role.';

    if (empty($errors['email'])) {
        $ck = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
        $ck->execute([':e' => $email]);
        if ($ck->fetchColumn()) $errors['email'] = 'Email already in use.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, status) VALUES (:n,:e,:h,:r,:s)'
        );
        $stmt->execute([':n'=>$name,':e'=>$email,':h'=>$hash,':r'=>$role_val,':s'=>$status]);
        $new_id = (int)$pdo->lastInsertId();
        log_action($pdo, $user['id'], 'CREATE', 'users', $new_id, "Created user: $name ($role_val)");
        flash('success', "User \"$name\" created.");
        redirect('pages/users/index.php');
    }
}

$page_title = 'Add User';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Add User</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content">
        <div class="page-header">
            <h2 class="page-title">Add User</h2>
            <a href="<?= htmlspecialchars(app_url('pages/users/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="form-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="name" value="<?= sanitize($old['name'] ?? '') ?>" class="<?= isset($errors['name']) ? 'invalid' : '' ?>" required>
                        <?php if (isset($errors['name'])): ?><span class="field-error"><?= sanitize($errors['name']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" value="<?= sanitize($old['email'] ?? '') ?>" class="<?= isset($errors['email']) ? 'invalid' : '' ?>" required>
                        <?php if (isset($errors['email'])): ?><span class="field-error"><?= sanitize($errors['email']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <input type="password" name="password" class="<?= isset($errors['password']) ? 'invalid' : '' ?>" required>
                        <?php if (isset($errors['password'])): ?><span class="field-error"><?= sanitize($errors['password']) ?></span><?php endif; ?>
                        <span class="field-hint">Minimum 8 characters</span>
                    </div>
                    <div class="form-group">
                        <label>Role <span class="required">*</span></label>
                        <select name="role">
                            <option value="staff"   <?= ($old['role'] ?? 'staff')==='staff'   ? 'selected' : '' ?>>Staff</option>
                            <option value="manager" <?= ($old['role'] ?? '')==='manager' ? 'selected' : '' ?>>Manager</option>
                            <option value="admin"   <?= ($old['role'] ?? '')==='admin'   ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active"   <?= ($old['status'] ?? 'active')==='active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($old['status'] ?? '')==='inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-footer">
                    <a href="<?= htmlspecialchars(app_url('pages/users/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Create User</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error', 'Invalid user.'); redirect('pages/users/index.php'); }

$u_stmt = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE id = :id LIMIT 1');
$u_stmt->execute([':id' => $id]);
$target = $u_stmt->fetch();
if (!$target) { flash('error', 'User not found.'); redirect('pages/users/index.php'); }

$errors = [];
$old    = $target;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $old      = $_POST;
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_val = trim($_POST['role']     ?? 'staff');
    $status   = trim($_POST['status']   ?? 'active');

    if ($name === '')  $errors['name']  = 'Name is required.';
    if ($email === '') $errors['email'] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
    if ($password !== '' && strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';

    if (empty($errors['email'])) {
        $ck = $pdo->prepare('SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1');
        $ck->execute([':e' => $email, ':id' => $id]);
        if ($ck->fetchColumn()) $errors['email'] = 'Email already in use.';
    }

    if (empty($errors)) {
        if ($password !== '') {
            $pdo->prepare('UPDATE users SET name=:n,email=:e,password_hash=:h,role=:r,status=:s WHERE id=:id')
                ->execute([':n'=>$name,':e'=>$email,':h'=>password_hash($password,PASSWORD_DEFAULT),':r'=>$role_val,':s'=>$status,':id'=>$id]);
        } else {
            $pdo->prepare('UPDATE users SET name=:n,email=:e,role=:r,status=:s WHERE id=:id')
                ->execute([':n'=>$name,':e'=>$email,':r'=>$role_val,':s'=>$status,':id'=>$id]);
        }
        log_action($pdo, $user['id'], 'UPDATE', 'users', $id, "Updated user: $name");
        flash('success', "User \"$name\" updated.");
        redirect('pages/users/index.php');
    }
}

$page_title = 'Edit User';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="page-wrapper">
    <div class="topbar">
        <button class="btn-hamburger" id="hamburgerBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 class="topbar-title">Edit User</h1>
        <div class="topbar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="user-name"><?= sanitize($user['name']) ?></span>
        </div>
    </div>
    <main class="main-content">
        <div class="page-header">
            <h2 class="page-title">Edit User</h2>
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
                        <label>New Password</label>
                        <input type="password" name="password" class="<?= isset($errors['password']) ? 'invalid' : '' ?>">
                        <?php if (isset($errors['password'])): ?><span class="field-error"><?= sanitize($errors['password']) ?></span><?php endif; ?>
                        <span class="field-hint">Leave blank to keep current password</span>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role">
                            <option value="staff"   <?= ($old['role'] ?? '')==='staff'   ? 'selected' : '' ?>>Staff</option>
                            <option value="manager" <?= ($old['role'] ?? '')==='manager' ? 'selected' : '' ?>>Manager</option>
                            <option value="admin"   <?= ($old['role'] ?? '')==='admin'   ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active"   <?= ($old['status'] ?? '')==='active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($old['status'] ?? '')==='inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-footer">
                    <a href="<?= htmlspecialchars(app_url('pages/users/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update User</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

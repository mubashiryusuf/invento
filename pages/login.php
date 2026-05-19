<?php
// =============================================================================
// pages/login.php - Login form and authentication handler
// =============================================================================

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Already logged in -> go straight to dashboard.
if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $_SESSION['old_login_email'] = $email;

    if ($email === '' || $password === '') {
        flash('error', 'Email and password are required.');
        redirect('pages/login.php');
    }

    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        flash('error', 'Invalid email or password.');
        log_action($pdo, null, 'LOGIN_FAILED', 'users', null, 'Failed login attempt for: ' . $email);
        redirect('pages/login.php');
    }

    if ($user['status'] !== 'active') {
        flash('error', 'Your account is inactive. Contact an administrator.');
        redirect('pages/login.php');
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    log_action(
        $pdo,
        (int)$user['id'],
        'LOGIN',
        'users',
        (int)$user['id'],
        htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ' logged in'
    );

    flash('success', 'Welcome back, ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . '!');
    unset($_SESSION['old_login_email']);

    $dest = $_SESSION['redirect_after_login'] ?? 'pages/dashboard.php';
    unset($_SESSION['redirect_after_login']);
    redirect($dest);
}

$csrf = generate_csrf();
$flash = get_flash();
$old_email = $_SESSION['old_login_email'] ?? '';
unset($_SESSION['old_login_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InvenTrack</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(app_url('assets/images/material-management.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/style.css?v=8'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/responsive.css?v=8'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-shell">
            <section class="login-showcase">
                <div class="login-showcase__badge">
                    <i class="fa-solid fa-bolt"></i>
                    Inventory flow, streamlined
                </div>
                <div class="login-brand">
                    <span class="login-brand-icon" aria-hidden="true">
                        <img
                            src="<?= htmlspecialchars(app_url('assets/images/material-management.png'), ENT_QUOTES, 'UTF-8') ?>"
                            alt=""
                            class="app-logo app-logo--login"
                            width="72"
                            height="72"
                        >
                    </span>
                    <h1 class="login-brand-name">InvenTrack</h1>
                    <p class="login-tagline">Track stock, suppliers, purchases, and sales from one clean workspace.</p>
                </div>
                <div class="login-metrics">
                    <div class="login-metric">
                        <span class="login-metric__label">Fast access</span>
                        <strong class="login-metric__value">Role-based dashboard</strong>
                    </div>
                    <div class="login-metric">
                        <span class="login-metric__label">Built for teams</span>
                        <strong class="login-metric__value">Admin, Manager, Staff</strong>
                    </div>
                    <div class="login-metric">
                        <span class="login-metric__label">Ready on mobile</span>
                        <strong class="login-metric__value">Responsive workflow</strong>
                    </div>
                </div>
            </section>

            <section class="login-card">
                <div class="login-card__header">
                    <span class="login-card__eyebrow">Welcome back</span>
                    <h2 class="login-card__title">Sign in to continue</h2>
                    <p class="login-card__text">Use your account to access inventory, reports, and daily transaction flows.</p>
                </div>

                <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid <?= $flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
                    <span class="alert-message"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= htmlspecialchars(app_url('pages/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="login-form" id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="form-group">
                        <label for="email" class="form-label">Email address</label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="you@example.com"
                                value="<?= htmlspecialchars($old_email, ENT_QUOTES, 'UTF-8') ?>"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-icon-wrap has-toggle">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="input-toggle-pw" id="togglePw" aria-label="Show or hide password">
                                <i class="fa-solid fa-eye" id="togglePwIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block login-submit-btn">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Sign In
                    </button>
                </form>

                <div class="login-credentials">
                    <span class="login-credentials__label">Demo access</span>
                    <p><span>Admin</span><strong>admin@inv.local</strong><em>admin123</em></p>
                    <p><span>Manager</span><strong>manager@inv.local</strong><em>manager123</em></p>
                    <p><span>Staff</span><strong>staff@inv.local</strong><em>staff123</em></p>
                </div>
            </section>
        </div>

        <p class="login-footer-text">
            &copy; <?= date('Y') ?> InvenTrack. All rights reserved.
        </p>
    </div>

    <script>
    document.getElementById('togglePw').addEventListener('click', function () {
        var pw = document.getElementById('password');
        var icon = document.getElementById('togglePwIcon');

        if (pw.type === 'password') {
            pw.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            pw.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
    </script>
</body>
</html>

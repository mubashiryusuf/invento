<?php
// =============================================================================
// pages/logout.php — Terminate session and redirect to login
// =============================================================================

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only log if there is actually a user in session.
if (is_logged_in()) {
    $user = current_user();
    log_action(
        $pdo,
        (int)$user['id'],
        'LOGOUT',
        'users',
        (int)$user['id'],
        htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ' logged out'
    );
}

// Unset all session variables, delete the session cookie, then destroy.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

redirect('pages/login.php');

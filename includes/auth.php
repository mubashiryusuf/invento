<?php
// =============================================================================
// includes/auth.php - Session management, role guards, CSRF helpers
// =============================================================================

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------------------
// require_role - Enforce authentication and role-based access.
// Call at the top of every protected page.
// -----------------------------------------------------------------------------
function require_role(array $allowed_roles): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('pages/login.php');
    }

    $user = current_user();
    if (!in_array($user['role'], $allowed_roles, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
           . '<title>403 - Access Denied</title></head><body>'
           . '<h2>Access Denied</h2>'
           . '<p>You do not have permission to view this page.</p>'
           . '<a href="' . htmlspecialchars(app_url('pages/dashboard.php'), ENT_QUOTES, 'UTF-8') . '">Go to Dashboard</a>'
           . '</body></html>';
        exit;
    }
}

// -----------------------------------------------------------------------------
// is_logged_in - Check whether a user session is active.
// -----------------------------------------------------------------------------
function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

// -----------------------------------------------------------------------------
// current_user - Return the session user array or null.
// Keys: id, name, email, role
// -----------------------------------------------------------------------------
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

// -----------------------------------------------------------------------------
// generate_csrf - Create (or reuse) a CSRF token for the current session.
// Returns the token string to embed in forms.
// -----------------------------------------------------------------------------
function generate_csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// -----------------------------------------------------------------------------
// verify_csrf - Validate the submitted token against the session token.
// On failure: sets a flash error and redirects back to the referrer.
// Returns true on success (so the caller knows it passed).
// -----------------------------------------------------------------------------
function verify_csrf(?string $token): bool
{
    $session_token = $_SESSION['csrf_token'] ?? '';

    if (empty($token) || !hash_equals($session_token, $token)) {
        unset($_SESSION['csrf_token']);

        if (function_exists('flash')) {
            flash('error', 'Invalid form submission. Please try again.');
        }
        $back = $_SERVER['HTTP_REFERER'] ?? app_url('pages/login.php');
        header('Location: ' . $back);
        exit;
    }

    return true;
}

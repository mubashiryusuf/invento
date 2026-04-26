<?php
// =============================================================================
// index.php — Entry point. Redirect to dashboard or login.
// =============================================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
} else {
    redirect('pages/login.php');
}
exit;

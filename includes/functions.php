<?php
// =============================================================================
// includes/functions.php — Shared helper functions
// =============================================================================

declare(strict_types=1);

function app_base_path(): string
{
    static $base_path = null;

    if ($base_path !== null) {
        return $base_path;
    }

    $base_path = '/' . trim(basename(dirname(__DIR__)), '/');
    return $base_path;
}

function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return $path === '' ? app_base_path() . '/' : app_base_path() . '/' . $path;
}

function rewrite_app_output(string $html): string
{
    $base_path = app_base_path();

    return preg_replace_callback(
        '/\b(href|src|action)="([^"]*)"/i',
        static function (array $matches) use ($base_path): string {
            $attribute = $matches[1];
            $url = $matches[2];

            if (
                $url === '' ||
                $url[0] !== '/' ||
                str_starts_with($url, '//') ||
                str_starts_with($url, $base_path . '/') ||
                preg_match('/^(https?:|mailto:|tel:|#)/i', $url)
            ) {
                return $matches[0];
            }

            return sprintf('%s="%s%s"', $attribute, $base_path, $url);
        },
        $html
    ) ?? $html;
}

// -----------------------------------------------------------------------------
// log_action — Write an entry to audit_logs.
// $user_id can be null (e.g., failed login before session is set).
// -----------------------------------------------------------------------------
function log_action(
    PDO $pdo,
    ?int $user_id,
    string $action,
    string $table_name,
    ?int $record_id,
    string $details = ''
): void {
    $sql = 'INSERT INTO audit_logs (user_id, action, table_name, record_id, details)
            VALUES (:user_id, :action, :table_name, :record_id, :details)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id'    => $user_id,
        ':action'     => strtoupper($action),
        ':table_name' => $table_name,
        ':record_id'  => $record_id,
        ':details'    => $details,
    ]);
}

// -----------------------------------------------------------------------------
// flash — Store a one-time message in the session.
// $type: success | error | warning | info
// -----------------------------------------------------------------------------
function flash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// -----------------------------------------------------------------------------
// get_flash — Return and clear the stored flash message.
// Returns null when nothing is queued.
// -----------------------------------------------------------------------------
function get_flash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// -----------------------------------------------------------------------------
// redirect — Send a Location header and stop execution.
// -----------------------------------------------------------------------------
function redirect(string $path): never
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
        exit;
    }

    $base_path = app_base_path();

    if ($path === $base_path || str_starts_with($path, $base_path . '/')) {
        header('Location: ' . $path);
        exit;
    }

    if (str_starts_with($path, '/')) {
        header('Location: ' . $base_path . $path);
        exit;
    }

    header('Location: ' . app_url($path));
    exit;
}

// -----------------------------------------------------------------------------
// sanitize — Trim and escape a value for safe HTML output.
// -----------------------------------------------------------------------------
function sanitize(mixed $value): string
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

// -----------------------------------------------------------------------------
// paginate — Calculate pagination metadata.
// Returns: ['total_pages' => N, 'offset' => N, 'per_page' => N]
// -----------------------------------------------------------------------------
function paginate(int $total, int $per_page, int $current_page): array
{
    $per_page     = max(1, $per_page);
    $total_pages  = (int)ceil($total / $per_page);
    $current_page = max(1, min($current_page, $total_pages ?: 1));
    $offset       = ($current_page - 1) * $per_page;

    return [
        'total_pages'  => $total_pages,
        'offset'       => $offset,
        'per_page'     => $per_page,
        'current_page' => $current_page,
    ];
}

// -----------------------------------------------------------------------------
// format_currency — Format a number as a dollar amount.
// -----------------------------------------------------------------------------
function format_currency(float|int|string $amount): string
{
    return 'Rs ' . number_format((float)$amount, 2);
}

// -----------------------------------------------------------------------------
// format_date — Format a Y-m-d date string as "Mon DD, YYYY".
// Returns '' for empty/null values.
// -----------------------------------------------------------------------------
function format_date(?string $date): string
{
    if (empty($date)) {
        return '';
    }
    $ts = strtotime($date);
    return $ts !== false ? date('M d, Y', $ts) : '';
}

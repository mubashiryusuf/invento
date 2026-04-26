<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('pages/users/index.php'); }
verify_csrf($_POST['csrf_token'] ?? null);

$current_user = current_user();
$id = (int)($_POST['id'] ?? 0);
if ($id < 1 || $id === (int)$current_user['id']) {
    flash('error', 'Cannot toggle your own account.');
    redirect('pages/users/index.php');
}

$u = $pdo->prepare('SELECT name, status FROM users WHERE id = :id LIMIT 1');
$u->execute([':id' => $id]);
$u = $u->fetch();
if (!$u) { flash('error', 'User not found.'); redirect('pages/users/index.php'); }

$new_status = $u['status'] === 'active' ? 'inactive' : 'active';
$pdo->prepare('UPDATE users SET status = :s WHERE id = :id')->execute([':s' => $new_status, ':id' => $id]);
log_action($pdo, (int)$current_user['id'], 'TOGGLE_STATUS', 'users', $id, "Set {$u['name']} to $new_status");
flash('success', "User \"{$u['name']}\" set to $new_status.");
redirect('pages/users/index.php');

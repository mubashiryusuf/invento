<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('pages/items/index.php'); }
verify_csrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) { flash('error', 'Invalid item.'); redirect('pages/items/index.php'); }

$item = $pdo->prepare('SELECT name FROM items WHERE id = :id LIMIT 1');
$item->execute([':id' => $id]);
$item = $item->fetch();
if (!$item) { flash('error', 'Item not found.'); redirect('pages/items/index.php'); }

// Check for existing transactions
$has_sales = $pdo->prepare('SELECT COUNT(*) FROM sale_items WHERE item_id = :id');
$has_sales->execute([':id' => $id]);
$has_purchases = $pdo->prepare('SELECT COUNT(*) FROM purchase_items WHERE item_id = :id');
$has_purchases->execute([':id' => $id]);

if ($has_sales->fetchColumn() > 0 || $has_purchases->fetchColumn() > 0) {
    flash('error', 'Cannot delete "' . $item['name'] . '": it has existing transactions.');
    redirect('pages/items/index.php');
}

$pdo->prepare('DELETE FROM items WHERE id = :id')->execute([':id' => $id]);
log_action($pdo, (int)(current_user()['id']), 'DELETE', 'items', $id, 'Deleted item: ' . $item['name']);
flash('success', 'Item "' . $item['name'] . '" deleted.');
    redirect('pages/items/index.php');

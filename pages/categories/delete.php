<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('pages/categories/index.php'); }
verify_csrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) { flash('error', 'Invalid category.'); redirect('pages/categories/index.php'); }

$cat = $pdo->prepare('SELECT name FROM categories WHERE id = :id LIMIT 1');
$cat->execute([':id' => $id]);
$cat = $cat->fetch();
if (!$cat) { flash('error', 'Category not found.'); redirect('pages/categories/index.php'); }

$item_count = $pdo->prepare('SELECT COUNT(*) FROM items WHERE category_id = :id');
$item_count->execute([':id' => $id]);
if ($item_count->fetchColumn() > 0) {
    flash('error', 'Cannot delete "' . $cat['name'] . '": it has existing items. Reassign items first.');
    redirect('pages/categories/index.php');
}

$pdo->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);
log_action($pdo, (int)(current_user()['id']), 'DELETE', 'categories', $id, 'Deleted category: ' . $cat['name']);
flash('success', 'Category "' . $cat['name'] . '" deleted.');
    redirect('pages/categories/index.php');

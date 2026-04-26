<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('pages/suppliers/index.php'); }
verify_csrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) { flash('error', 'Invalid supplier.'); redirect('pages/suppliers/index.php'); }

$sup = $pdo->prepare('SELECT name FROM suppliers WHERE id = :id LIMIT 1');
$sup->execute([':id' => $id]);
$sup = $sup->fetch();
if (!$sup) { flash('error', 'Supplier not found.'); redirect('pages/suppliers/index.php'); }

$has_purchases = $pdo->prepare('SELECT COUNT(*) FROM purchases WHERE supplier_id = :id');
$has_purchases->execute([':id' => $id]);
if ($has_purchases->fetchColumn() > 0) {
    flash('error', 'Cannot delete "' . $sup['name'] . '": it has existing purchases.');
    redirect('pages/suppliers/index.php');
}

$pdo->prepare('DELETE FROM suppliers WHERE id = :id')->execute([':id' => $id]);
log_action($pdo, (int)(current_user()['id']), 'DELETE', 'suppliers', $id, 'Deleted supplier: ' . $sup['name']);
flash('success', 'Supplier "' . $sup['name'] . '" deleted.');
    redirect('pages/suppliers/index.php');

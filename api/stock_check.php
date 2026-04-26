<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$item_id = (int)($_GET['item_id'] ?? 0);
if ($item_id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid item_id']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, price, stock_qty, unit FROM items WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $item_id]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found']);
    exit;
}

echo json_encode([
    'id'        => (int)$item['id'],
    'name'      => $item['name'],
    'price'     => number_format((float)$item['price'], 2, '.', ''),
    'stock_qty' => (int)$item['stock_qty'],
    'unit'      => $item['unit'],
]);

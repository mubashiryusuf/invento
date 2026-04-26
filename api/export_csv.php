<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!is_logged_in()) {
    http_response_code(401);
    die('Unauthorised');
}

$type = trim($_GET['type'] ?? '');
$allowed = ['items', 'suppliers', 'sales', 'purchases'];
if (!in_array($type, $allowed, true)) {
    http_response_code(400);
    die('Invalid type');
}

$filename = $type . '_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');

$out = fopen('php://output', 'w');

switch ($type) {
    case 'items':
        fputcsv($out, ['item_code', 'name', 'category', 'unit', 'price', 'stock_qty', 'reorder_level', 'expiry_date']);
        $rows = $pdo->query(
            'SELECT i.item_code, i.name, COALESCE(c.name,"") AS category, i.unit,
                    i.price, i.stock_qty, i.reorder_level, COALESCE(i.expiry_date,"") AS expiry_date
             FROM items i LEFT JOIN categories c ON c.id = i.category_id
             ORDER BY i.name'
        )->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, [$r['item_code'], $r['name'], $r['category'], $r['unit'],
                           $r['price'], $r['stock_qty'], $r['reorder_level'], $r['expiry_date']]);
        }
        break;

    case 'suppliers':
        fputcsv($out, ['name', 'contact', 'email', 'address']);
        $rows = $pdo->query('SELECT name, COALESCE(contact,"") AS contact, COALESCE(email,"") AS email, COALESCE(address,"") AS address FROM suppliers ORDER BY name')->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, [$r['name'], $r['contact'], $r['email'], $r['address']]);
        }
        break;

    case 'sales':
        fputcsv($out, ['id', 'customer_name', 'sale_date', 'total_amount', 'created_by']);
        $rows = $pdo->query(
            'SELECT s.id, s.customer_name, s.sale_date, s.total_amount, COALESCE(u.name,"N/A") AS created_by
             FROM sales s LEFT JOIN users u ON u.id = s.created_by
             ORDER BY s.sale_date DESC'
        )->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['customer_name'], $r['sale_date'], $r['total_amount'], $r['created_by']]);
        }
        break;

    case 'purchases':
        fputcsv($out, ['id', 'supplier_name', 'purchase_date', 'total_amount', 'created_by']);
        $rows = $pdo->query(
            'SELECT p.id, COALESCE(s.name,"N/A") AS supplier_name, p.purchase_date, p.total_amount, COALESCE(u.name,"N/A") AS created_by
             FROM purchases p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             LEFT JOIN users u ON u.id = p.created_by
             ORDER BY p.purchase_date DESC'
        )->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['supplier_name'], $r['purchase_date'], $r['total_amount'], $r['created_by']]);
        }
        break;
}

fclose($out);

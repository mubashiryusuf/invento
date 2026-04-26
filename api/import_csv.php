<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/dashboard.php');
}

verify_csrf($_POST['csrf_token'] ?? null);

$user = current_user();
$type = trim($_POST['type'] ?? '');
$allowed_types = ['items', 'suppliers'];
$redirect_map = [
    'items' => 'pages/items/index.php',
    'suppliers' => 'pages/suppliers/index.php',
];

if (!in_array($type, $allowed_types, true)) {
    flash('error', 'Invalid import type.');
    redirect('pages/dashboard.php');
}

if (empty($_FILES['csv_file']) || ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    flash('error', 'Please choose a valid CSV file to import.');
    redirect($redirect_map[$type]);
}

$tmp_name = $_FILES['csv_file']['tmp_name'];
$handle = fopen($tmp_name, 'r');

if ($handle === false) {
    flash('error', 'Could not read the uploaded CSV file.');
    redirect($redirect_map[$type]);
}

function normalize_csv_header(string $header): string
{
    $header = strtolower(trim($header));
    return preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';
}

function row_is_empty(array $row): bool
{
    foreach ($row as $value) {
        if (trim((string)$value) !== '') {
            return false;
        }
    }

    return true;
}

$headers = fgetcsv($handle);
if ($headers === false) {
    fclose($handle);
    flash('error', 'The CSV file is empty.');
    redirect($redirect_map[$type]);
}

$normalized_headers = array_map('normalize_csv_header', $headers);
$required_headers = $type === 'items'
    ? ['item_code', 'name', 'category', 'unit', 'price', 'stock_qty', 'reorder_level']
    : ['name'];

foreach ($required_headers as $required_header) {
    if (!in_array($required_header, $normalized_headers, true)) {
        fclose($handle);
        flash('error', 'Missing required CSV column: ' . $required_header);
        redirect($redirect_map[$type]);
    }
}

$imported = 0;
$skipped = 0;
$errors = [];
$created_categories = [];

try {
    $pdo->beginTransaction();

    if ($type === 'items') {
        $category_stmt = $pdo->query('SELECT id, name FROM categories');
        $categories = [];
        foreach ($category_stmt->fetchAll() as $category) {
            $categories[strtolower(trim($category['name']))] = (int)$category['id'];
        }

        $find_item = $pdo->prepare('SELECT id FROM items WHERE item_code = :item_code LIMIT 1');
        $insert_category = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
        $insert_item = $pdo->prepare(
            'INSERT INTO items (item_code, name, category_id, unit, price, stock_qty, reorder_level, expiry_date)
             VALUES (:item_code, :name, :category_id, :unit, :price, :stock_qty, :reorder_level, :expiry_date)'
        );

        $row_number = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;

            if (row_is_empty($row)) {
                continue;
            }

            $data = [];
            foreach ($normalized_headers as $index => $header) {
                $data[$header] = trim((string)($row[$index] ?? ''));
            }

            $item_code = $data['item_code'] ?? '';
            $name = $data['name'] ?? '';
            $category_name = $data['category'] ?? '';
            $unit = $data['unit'] ?? '';
            $price = is_numeric($data['price'] ?? null) ? (float)$data['price'] : null;
            $stock_qty = filter_var($data['stock_qty'] ?? null, FILTER_VALIDATE_INT);
            $reorder_level = filter_var($data['reorder_level'] ?? null, FILTER_VALIDATE_INT);
            $expiry_date = $data['expiry_date'] ?? '';

            if ($item_code === '' || $name === '' || $category_name === '' || $unit === '') {
                $skipped++;
                $errors[] = "Row {$row_number}: item_code, name, category, and unit are required.";
                continue;
            }

            if ($price === null || $price < 0 || $stock_qty === false || $stock_qty < 0 || $reorder_level === false || $reorder_level < 0) {
                $skipped++;
                $errors[] = "Row {$row_number}: price, stock_qty, and reorder_level must be valid non-negative numbers.";
                continue;
            }

            if ($expiry_date !== '' && strtotime($expiry_date) === false) {
                $skipped++;
                $errors[] = "Row {$row_number}: expiry_date must be a valid date.";
                continue;
            }

            $find_item->execute([':item_code' => $item_code]);
            if ($find_item->fetchColumn()) {
                $skipped++;
                $errors[] = "Row {$row_number}: item code {$item_code} already exists.";
                continue;
            }

            $category_key = strtolower($category_name);
            if (!isset($categories[$category_key])) {
                $insert_category->execute([
                    ':name' => $category_name,
                    ':description' => 'Auto-created from CSV import',
                ]);
                $categories[$category_key] = (int)$pdo->lastInsertId();
                $created_categories[] = $category_name;
            }

            $insert_item->execute([
                ':item_code' => $item_code,
                ':name' => $name,
                ':category_id' => $categories[$category_key],
                ':unit' => $unit,
                ':price' => $price,
                ':stock_qty' => $stock_qty,
                ':reorder_level' => $reorder_level,
                ':expiry_date' => $expiry_date !== '' ? date('Y-m-d', strtotime($expiry_date)) : null,
            ]);
            $imported++;
        }

        if ($created_categories !== []) {
            $created_categories = array_values(array_unique($created_categories));
            log_action(
                $pdo,
                (int)$user['id'],
                'CREATE',
                'categories',
                null,
                'CSV import created categories: ' . implode(', ', $created_categories)
            );
        }
    } else {
        $insert_supplier = $pdo->prepare(
            'INSERT INTO suppliers (name, contact, email, address) VALUES (:name, :contact, :email, :address)'
        );

        $row_number = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;

            if (row_is_empty($row)) {
                continue;
            }

            $data = [];
            foreach ($normalized_headers as $index => $header) {
                $data[$header] = trim((string)($row[$index] ?? ''));
            }

            $name = $data['name'] ?? '';
            $contact = $data['contact'] ?? '';
            $email = $data['email'] ?? '';
            $address = $data['address'] ?? '';

            if ($name === '') {
                $skipped++;
                $errors[] = "Row {$row_number}: supplier name is required.";
                continue;
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                $errors[] = "Row {$row_number}: supplier email is invalid.";
                continue;
            }

            $insert_supplier->execute([
                ':name' => $name,
                ':contact' => $contact !== '' ? $contact : null,
                ':email' => $email !== '' ? $email : null,
                ':address' => $address !== '' ? $address : null,
            ]);
            $imported++;
        }
    }

    $pdo->commit();
    fclose($handle);

    log_action(
        $pdo,
        (int)$user['id'],
        'IMPORT',
        $type,
        null,
        "CSV import completed: {$imported} imported, {$skipped} skipped."
    );

    $message = ucfirst($type) . " import complete: {$imported} imported";
    if ($skipped > 0) {
        $message .= ", {$skipped} skipped.";
        $message .= ' ' . implode(' ', array_slice($errors, 0, 3));
        if (count($errors) > 3) {
            $message .= ' Additional row issues were skipped.';
        }
        flash('warning', $message);
    } else {
        flash('success', $message . '.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fclose($handle);
    flash('error', 'CSV import failed: ' . $e->getMessage());
}

redirect($redirect_map[$type]);

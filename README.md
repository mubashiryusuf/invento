# Inventory Management System

A clean PHP + MySQL inventory system for small and medium businesses, built with custom HTML, CSS, JavaScript, and PDO-based PHP.

## Features

- Role-based login for `Admin`, `Manager`, and `Staff`
- CRUD for items, categories, suppliers, purchases, sales, and users
- Dashboard summary cards with low-stock warnings
- Reports page with date filters and Chart.js sales chart
- CSV export for items, suppliers, purchases, and sales
- CSV import for items and suppliers
- Audit logging for key actions

## Project Location

```powershell
C:\xampp\htdocs\inventory-management-system
```

## Requirements

- XAMPP with Apache and MySQL
- PHP 8.x
- MySQL 8.x

## Setup

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Import the SQL file in `database\inventory_db.sql`.
3. Confirm `config\db.php` matches your local MySQL credentials.
4. Open `http://localhost/inventory-management-system/` in your browser.

## Commands To Start The Project

Start Apache and MySQL from XAMPP, then import the database:

```powershell
cmd /c "\"C:\xampp\mysql\bin\mysql.exe\" -u root < \"C:\xampp\htdocs\inventory-management-system\database\inventory_db.sql\""
```

If your MySQL root user has a password:

```powershell
cmd /c "\"C:\xampp\mysql\bin\mysql.exe\" -u root -p < \"C:\xampp\htdocs\inventory-management-system\database\inventory_db.sql\""
```

Open the project:

```text
http://localhost/inventory-management-system/
```

## Default Login Credentials

```text
Admin   -> admin@inv.local   / admin123
Manager -> manager@inv.local / manager123
Staff   -> staff@inv.local   / staff123
```

## How To Check The Project

Run PHP syntax checks:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & "C:\xampp\php\php.exe" -l $_.FullName }
```

Check that the default passwords match the SQL seed:

```powershell
@'
<?php
echo password_verify('admin123', '$2y$10$caTLyTa8jgAllhnp0ydD8OVxwMb4K5jin3sjvFFQCqql6EqoCry2W') ? 'admin ok' : 'admin fail', PHP_EOL;
echo password_verify('manager123', '$2y$10$o9EkKaDEqu8pTqPNmo..x.W0yJ3VeyIE.5GlG9cw9F0BKUHyOXnsS') ? 'manager ok' : 'manager fail', PHP_EOL;
echo password_verify('staff123', '$2y$10$UalpSZJgAfnm334mqT8JUOXyUwD1RwWLu517zCMD.LGnsbBtWGEqe') ? 'staff ok' : 'staff fail', PHP_EOL;
?>
'@ | & "C:\xampp\php\php.exe"
```

## Quick Manual Test Checklist

1. Log in as `admin@inv.local`.
2. Check dashboard cards and low-stock warning.
3. Add, edit, and delete a category.
4. Add and edit an item with a valid category.
5. Import an items CSV from the Items page.
6. Add and edit a supplier.
7. Import a suppliers CSV from the Suppliers page.
8. Create a purchase and confirm stock increases.
9. Create a sale and confirm stock decreases.
10. Open Reports and Audit Logs.
11. Log in as Manager and Staff to verify role-based access.

## Sample CSV Headers

Items CSV:

```csv
item_code,name,category,unit,price,stock_qty,reorder_level,expiry_date
```

Suppliers CSV:

```csv
name,contact,email,address
```

## Backup Command

```powershell
cmd /c "\"C:\xampp\mysql\bin\mysqldump.exe\" -u root inventory_db > \"C:\xampp\htdocs\inventory-management-system\database\inventory_backup.sql\""
```

If root has a password:

```powershell
cmd /c "\"C:\xampp\mysql\bin\mysqldump.exe\" -u root -p inventory_db > \"C:\xampp\htdocs\inventory-management-system\database\inventory_backup.sql\""
```

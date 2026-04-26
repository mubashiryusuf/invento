-- =============================================================================
-- Inventory Management System — Full Database Schema + Seed Data
-- Engine  : InnoDB
-- Charset : utf8mb4_unicode_ci
-- MySQL   : 8.x
-- Run     : mysql -u root -p < inventory_db.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS inventory_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE inventory_db;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- DROP TABLES (reverse FK order so constraints do not block drops)
-- =============================================================================
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- =============================================================================
-- TABLE: users
-- =============================================================================
CREATE TABLE users (
    id            INT            AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)   NOT NULL,
    email         VARCHAR(150)   NOT NULL,
    password_hash VARCHAR(255)   NOT NULL,
    role          ENUM('admin','manager','staff') NOT NULL DEFAULT 'staff',
    status        ENUM('active','inactive')       NOT NULL DEFAULT 'active',
    created_at    DATETIME       DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: categories
-- =============================================================================
CREATE TABLE categories (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_categories_name UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: items
-- =============================================================================
CREATE TABLE items (
    id            INT            AUTO_INCREMENT PRIMARY KEY,
    item_code     VARCHAR(50)    NOT NULL,
    name          VARCHAR(150)   NOT NULL,
    category_id   INT            NOT NULL,
    unit          VARCHAR(30)    NOT NULL DEFAULT 'pcs',
    price         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    stock_qty     INT            NOT NULL DEFAULT 0,
    reorder_level INT            NOT NULL DEFAULT 10,
    expiry_date   DATE           NULL,
    created_at    DATETIME       DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_items_item_code UNIQUE (item_code),
    CONSTRAINT fk_items_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: suppliers
-- =============================================================================
CREATE TABLE suppliers (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    contact    VARCHAR(100),
    email      VARCHAR(150),
    address    TEXT,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: purchases
-- =============================================================================
CREATE TABLE purchases (
    id            INT           AUTO_INCREMENT PRIMARY KEY,
    supplier_id   INT           NOT NULL,
    purchase_date DATE          NOT NULL,
    total_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes         TEXT,
    created_by    INT           NOT NULL,
    created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchases_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_purchases_user
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: purchase_items
-- =============================================================================
CREATE TABLE purchase_items (
    id          INT           AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT           NOT NULL,
    item_id     INT           NOT NULL,
    qty         INT           NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pitems_purchase
        FOREIGN KEY (purchase_id) REFERENCES purchases (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pitems_item
        FOREIGN KEY (item_id) REFERENCES items (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: sales
-- =============================================================================
CREATE TABLE sales (
    id            INT           AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150)  NOT NULL,
    sale_date     DATE          NOT NULL,
    total_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes         TEXT,
    created_by    INT           NOT NULL,
    created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_user
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: sale_items
-- =============================================================================
CREATE TABLE sale_items (
    id         INT           AUTO_INCREMENT PRIMARY KEY,
    sale_id    INT           NOT NULL,
    item_id    INT           NOT NULL,
    qty        INT           NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    created_at DATETIME      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sitems_sale
        FOREIGN KEY (sale_id) REFERENCES sales (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_sitems_item
        FOREIGN KEY (item_id) REFERENCES items (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: audit_logs
-- =============================================================================
CREATE TABLE audit_logs (
    id         INT         AUTO_INCREMENT PRIMARY KEY,
    user_id    INT         NULL,
    action     VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id  INT         NULL,
    details    TEXT,
    created_at DATETIME    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alogs_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- INDEXES
-- =============================================================================

CREATE INDEX idx_items_category_id  ON items (category_id);
CREATE INDEX idx_items_stock_qty    ON items (stock_qty);
CREATE INDEX idx_items_expiry_date  ON items (expiry_date);

CREATE INDEX idx_purchases_supplier_id ON purchases (supplier_id);
CREATE INDEX idx_purchases_date        ON purchases (purchase_date);
CREATE INDEX idx_purchases_created_by  ON purchases (created_by);

CREATE INDEX idx_pitems_purchase_id ON purchase_items (purchase_id);
CREATE INDEX idx_pitems_item_id     ON purchase_items (item_id);

CREATE INDEX idx_sales_date       ON sales (sale_date);
CREATE INDEX idx_sales_created_by ON sales (created_by);

CREATE INDEX idx_sitems_sale_id  ON sale_items (sale_id);
CREATE INDEX idx_sitems_item_id  ON sale_items (item_id);

CREATE INDEX idx_alogs_user_id    ON audit_logs (user_id);
CREATE INDEX idx_alogs_table_name ON audit_logs (table_name);
CREATE INDEX idx_alogs_created_at ON audit_logs (created_at);

-- =============================================================================
-- TRIGGERS
-- =============================================================================

DELIMITER $$

-- Increase stock when a purchase line item is recorded
CREATE TRIGGER after_purchase_item_insert
AFTER INSERT ON purchase_items
FOR EACH ROW
BEGIN
    UPDATE items
    SET    stock_qty = stock_qty + NEW.qty
    WHERE  id = NEW.item_id;
END$$

-- Decrease stock when a sale line item is recorded; reject if insufficient
CREATE TRIGGER after_sale_item_insert
AFTER INSERT ON sale_items
FOR EACH ROW
BEGIN
    DECLARE v_stock INT DEFAULT 0;

    SELECT stock_qty INTO v_stock
    FROM   items
    WHERE  id = NEW.item_id;

    IF v_stock < NEW.qty THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Insufficient stock for the requested quantity';
    END IF;

    UPDATE items
    SET    stock_qty = stock_qty - NEW.qty
    WHERE  id = NEW.item_id;
END$$

-- Restore stock when a sale line item is deleted (cancellation)
CREATE TRIGGER after_sale_item_delete
AFTER DELETE ON sale_items
FOR EACH ROW
BEGIN
    UPDATE items
    SET    stock_qty = stock_qty + OLD.qty
    WHERE  id = OLD.item_id;
END$$

DELIMITER ;

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- users (passwords: admin123 / manager123 / staff123)
INSERT INTO users (name, email, password_hash, role, status) VALUES
('System Admin',      'admin@inv.local',   '$2y$10$caTLyTa8jgAllhnp0ydD8OVxwMb4K5jin3sjvFFQCqql6EqoCry2W', 'admin',   'active'),
('Inventory Manager', 'manager@inv.local', '$2y$10$o9EkKaDEqu8pTqPNmo..x.W0yJ3VeyIE.5GlG9cw9F0BKUHyOXnsS', 'manager', 'active'),
('Store Staff',       'staff@inv.local',   '$2y$10$UalpSZJgAfnm334mqT8JUOXyUwD1RwWLu517zCMD.LGnsbBtWGEqe', 'staff',   'active');

-- categories
INSERT INTO categories (name, description) VALUES
('Electronics',       'Electronic devices, components, and accessories'),
('Office Supplies',   'Stationery, paper, pens, and general office consumables'),
('Furniture',         'Desks, chairs, shelving, and other office furniture'),
('Food & Beverages',  'Perishable and non-perishable food items and drinks'),
('Cleaning Supplies', 'Cleaning agents, tools, and hygiene products');

-- suppliers
INSERT INTO suppliers (name, contact, email, address) VALUES
('TechWorld Traders',   '+1-555-0142', 'orders@techworld-traders.example.com', '14 Silicon Avenue, Tech Park, San Jose, CA 95110'),
('Office Pro Supplies', '+1-555-0198', 'sales@officeprosupplies.example.com',  '88 Commerce Street, Business District, Chicago, IL 60601');

-- items (stock_qty starts at 0; triggers set real values via purchase_items inserts below)
INSERT INTO items (item_code, name, category_id, unit, price, stock_qty, reorder_level, expiry_date) VALUES
('ELEC-001', 'Wireless USB Keyboard',              1, 'pcs',    29.99,  0, 15, NULL),
('ELEC-002', 'USB-C Hub 7-in-1',                   1, 'pcs',    44.50,  0, 10, NULL),
('ELEC-003', 'LED Desk Lamp',                      1, 'pcs',    18.75,  0,  8, NULL),
('OFFC-001', 'A4 Copy Paper (500 sheets)',          2, 'ream',    6.99,  0, 50, NULL),
('OFFC-002', 'Ballpoint Pen Box (12 pcs)',          2, 'box',     3.49,  0, 30, NULL),
('FURN-001', 'Ergonomic Office Chair',              3, 'pcs',   189.00,  0,  5, NULL),
('FOOD-001', 'Instant Coffee (200 g jar)',          4, 'jar',     8.25,  0, 20, '2026-12-31'),
('FOOD-002', 'Mineral Water Bottle (500 ml)',       4, 'pcs',     0.99,  0,100, '2026-06-30'),
('CLEN-001', 'Multi-Surface Disinfectant (1 L)',   5, 'bottle',  4.75,  0, 25, NULL),
('CLEN-002', 'Microfibre Cleaning Cloth (5-pack)', 5, 'pack',    7.10,  0, 15, NULL);

-- purchase #1 — electronics from TechWorld Traders
INSERT INTO purchases (supplier_id, purchase_date, total_amount, notes, created_by)
VALUES (1, '2026-04-01', 0.00, 'Initial stock order — electronics', 1);

INSERT INTO purchase_items (purchase_id, item_id, qty, unit_price) VALUES
(1, 1, 40, 22.00),
(1, 2, 25, 35.00),
(1, 3, 20, 14.00);

UPDATE purchases SET total_amount = (40*22.00)+(25*35.00)+(20*14.00) WHERE id = 1;

-- purchase #2 — office & cleaning from Office Pro Supplies
INSERT INTO purchases (supplier_id, purchase_date, total_amount, notes, created_by)
VALUES (2, '2026-04-05', 0.00, 'Office consumables and cleaning stock', 2);

INSERT INTO purchase_items (purchase_id, item_id, qty, unit_price) VALUES
(2,  4, 120, 5.50),
(2,  5,  80, 2.80),
(2,  7,  60, 6.50),
(2,  8, 200, 0.75),
(2,  9,  50, 3.80),
(2, 10,  40, 5.60);

UPDATE purchases
SET total_amount = (120*5.50)+(80*2.80)+(60*6.50)+(200*0.75)+(50*3.80)+(40*5.60)
WHERE id = 2;

-- purchase #3 — furniture (only 3 chairs → stock=3, reorder_level=5 → triggers low-stock banner)
INSERT INTO purchases (supplier_id, purchase_date, total_amount, notes, created_by)
VALUES (2, '2026-04-10', 465.00, 'Furniture restock', 1);

INSERT INTO purchase_items (purchase_id, item_id, qty, unit_price) VALUES
(3, 6, 3, 155.00);

-- sale #1 — Acme Corporation
INSERT INTO sales (customer_name, sale_date, total_amount, notes, created_by)
VALUES ('Acme Corporation', '2026-04-12', 0.00, 'Bulk office supply order', 2);

INSERT INTO sale_items (sale_id, item_id, qty, unit_price) VALUES
(1, 1,  5, 29.99),
(1, 4, 20,  6.99),
(1, 5, 10,  3.49);

UPDATE sales SET total_amount = (5*29.99)+(20*6.99)+(10*3.49) WHERE id = 1;

-- sale #2 — BrightPath School
INSERT INTO sales (customer_name, sale_date, total_amount, notes, created_by)
VALUES ('BrightPath School', '2026-04-15', 0.00, 'School supply purchase', 3);

INSERT INTO sale_items (sale_id, item_id, qty, unit_price) VALUES
(2, 3,  4, 18.75),
(2, 8, 50,  0.99),
(2, 9, 10,  4.75);

UPDATE sales SET total_amount = (4*18.75)+(50*0.99)+(10*4.75) WHERE id = 2;

-- audit_logs bootstrap entries
INSERT INTO audit_logs (user_id, action, table_name, record_id, details) VALUES
(1, 'LOGIN',  'users',     1, 'Admin logged in'),
(1, 'CREATE', 'purchases', 1, 'Created purchase order #1 from TechWorld Traders'),
(2, 'LOGIN',  'users',     2, 'Manager logged in'),
(2, 'CREATE', 'purchases', 2, 'Created purchase order #2 from Office Pro Supplies'),
(2, 'CREATE', 'sales',     1, 'Created sale #1 for Acme Corporation'),
(3, 'LOGIN',  'users',     3, 'Staff logged in'),
(3, 'CREATE', 'sales',     2, 'Created sale #2 for BrightPath School'),
(1, 'CREATE', 'purchases', 3, 'Created purchase order #3 — furniture restock');

-- =============================================================================
-- END OF FILE
-- Final stock after seed (trigger-driven):
--   ELEC-001  Wireless USB Keyboard          40 - 5  = 35
--   ELEC-002  USB-C Hub 7-in-1               25 - 0  = 25
--   ELEC-003  LED Desk Lamp                  20 - 4  = 16
--   OFFC-001  A4 Copy Paper                 120 - 20 = 100
--   OFFC-002  Ballpoint Pen Box              80 - 10 = 70
--   FURN-001  Ergonomic Office Chair          3 - 0  =  3  << BELOW reorder_level=5
--   FOOD-001  Instant Coffee                 60 - 0  = 60
--   FOOD-002  Mineral Water Bottle          200 - 50 = 150
--   CLEN-001  Multi-Surface Disinfectant     50 - 10 = 40
--   CLEN-002  Microfibre Cleaning Cloth      40 - 0  = 40
-- =============================================================================

# Requirements — Included, Simplified, Skipped

This document lists every requirement from the original spec and marks what
we are building, simplifying, or skipping. Reason is given for each skip so
the supervisor/reviewer sees the choice was deliberate.

Scope target: **middle-level project** (final-year / junior-dev quality).

---

## ✅ INCLUDED — Built as described

| ID     | Requirement (short)                                 | How we build it |
|--------|------------------------------------------------------|-----------------|
| IM-01  | Role-based access (Admin, Manager, Staff)           | `users.role` column + session check in `auth.php` + menu filter |
| IM-02  | CRUD on inventory items                             | `pages/items/` with add/edit/delete/view + PDO prepared stmts |
| IM-03  | Unique item code + categories                       | `items.item_code UNIQUE`, FK to `categories` |
| IM-07  | Inventory reports (daily/weekly/monthly/custom)     | `pages/reports/` with date-range filter + Chart.js bar/line |
| IM-08  | Supplier + purchase order management                | `suppliers`, `purchases`, `purchase_items` tables + forms |
| IM-09  | Sales & dispatch linked with stock deduction        | `sales`, `sale_items` tables; DB trigger / PHP txn reduces `stock_qty` |
| IM-12  | Audit logs of inventory changes                     | `audit_logs` table + helper `log_action()` called on every write |
| IM-14  | Secure login & role-based access                    | `password_hash()`, PHP sessions, CSRF tokens on forms |
| IM-18  | Responsive UI (web + mobile)                        | Mobile-first CSS, breakpoints at 576 / 768 / 992 px |
| IM-19  | Stock updates within 2 seconds                      | Indexed `item_code`, `category_id`; single-transaction updates |
| IM-20  | Prevent unauthorized stock modification             | Role check + prepared stmts + CSRF + audit log |

---

## ⚠️ INCLUDED BUT SIMPLIFIED — Honest trade-offs

| ID     | Original ask                                    | What we actually do                                                                 | Why simplified |
|--------|-------------------------------------------------|--------------------------------------------------------------------------------------|----------------|
| IM-04  | Track stock in **real-time**                    | Stock refreshes after each CRUD action and on page load. No WebSockets / push.      | True real-time needs Node/Socket.io or Pusher — out of scope for PHP project |
| IM-05  | Alerts for low stock / expiry / reorder         | Dashboard shows a red/amber banner + low-stock table. **No email / SMS**.           | Email service needs SMTP config + cron — beyond mid-level scope |
| IM-06  | Bulk import/export Excel + CSV                  | **CSV only** (import + export). Excel `.xlsx` skipped.                              | Native xlsx in PHP needs `PhpSpreadsheet` library — we keep it simple |
| IM-15  | Data integrity with backup & recovery           | Foreign keys + transactions. Backup = **manual `mysqldump` documented in README**.  | Automated backups need cron + server config |
| IM-16  | High performance for large datasets             | Indexes on FK and search columns + pagination (20/page).                            | No Redis / query cache — not needed at mid-level data sizes |
| IM-21  | Modular architecture for easy updates           | Clean folder separation (`config`, `includes`, `pages`, `api`). **No MVC framework.** | A full framework (Laravel etc.) was explicitly excluded |

---

## ❌ SKIPPED — Too technical / out of scope

These are the ones you should mention in the report as "future scope" so
the examiner knows you understood them, just chose not to build them.

| ID     | Requirement                                          | Why skipped |
|--------|------------------------------------------------------|-------------|
| IM-10  | Multi-location inventory management                  | Needs location table, stock-per-location split, transfer workflows — doubles the schema & UI. Single-location keeps the project clean. |
| IM-11  | Barcode / QR code integration                        | Needs a JS scanner library (`QuaggaJS` / `html5-qrcode`) + camera permission handling + label printing. Marginal value for a demo. |
| IM-13  | Trend analysis & forecasting                         | Needs historical aggregation + statistics (moving averages etc.). Beyond a typical mid-level PHP project. |
| IM-17  | Comply with inventory & financial regulatory standards | Requires legal/tax domain knowledge (GST, GAAP, SOX, etc.). Not implementable in a student project. |
| IM-22  | AI-based demand forecasting                          | Needs ML model (ARIMA / Prophet / regression), Python microservice, training data. Clearly an advanced / optional feature. |

> **Mention in final report:** "Features IM-10, IM-11, IM-13, IM-17, and IM-22
> are documented as **future enhancements**. Their absence does not break any
> core workflow; they extend the system into enterprise / analytics territory
> which was out of scope for this implementation."

---

## Quick numbers

- Total requirements in spec: **22**
- Fully built: **11**
- Simplified but delivered: **6**
- Skipped (documented as future scope): **5**
- **Coverage = 17 / 22 ≈ 77%** — a healthy mid-level target.

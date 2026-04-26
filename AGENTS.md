# Inventory Management System — Project Guide (AGENTS.md)

> This file tells Codex (and its subagents) how to work on this project.
> Keep the tone and scope **middle-level**: clean, working, understandable code —
> not enterprise-grade, not sloppy student code.

---

## 1. Project Summary

A simple but complete **Inventory Management System** for a small/medium business.
Roles: **Admin**, **Manager**, **Staff**. Users can manage items, suppliers,
purchases, sales, and view reports. Responsive layout works on desktop and mobile.

**Intended vibe:** a final-year / mid-level developer project. Reviewer should
feel "yeh bnda samjhta hai kaam" — not "yeh to bilkul beginner hai" and also
not "yeh to senior engineer wali cheez hai".

---

## 2. Tech Stack (fixed — do not change)

| Layer      | Tech                                              |
|------------|---------------------------------------------------|
| Frontend   | HTML5, CSS3 (custom, responsive), Vanilla JS      |
| Backend    | PHP 8.x (procedural + few helper classes)         |
| Database   | MySQL 8.x (InnoDB, utf8mb4)                       |
| Server     | Apache (XAMPP / WAMP) on localhost                |
| Icons      | Font Awesome CDN                                  |
| Charts     | Chart.js CDN (only for reports page)              |

**No frameworks** (no React, Vue, Laravel, Bootstrap). CSS is hand-written so it
looks authored, not templated.

---

## 3. Folder Structure

```
inventory-management-system/
├── AGENTS.md                         ← this file
├── REQUIREMENTS_DECISION.md          ← what is included vs skipped
├── Codex-agents/                    ← subagent definitions
│   ├── frontend-developer.md
│   ├── backend-developer.md
│   ├── database-engineer.md
│   ├── ui-ux-designer.md
│   └── qa-tester.md
├── database/
│   └── inventory_db.sql              ← schema + seed data
├── config/
│   └── db.php                        ← DB connection (PDO)
├── includes/
│   ├── header.php
│   ├── sidebar.php
│   ├── footer.php
│   ├── auth.php                      ← session + role guard
│   └── functions.php                 ← helper functions
├── assets/
│   ├── css/
│   │   ├── style.css                 ← main stylesheet
│   │   └── responsive.css            ← media queries
│   ├── js/
│   │   ├── main.js
│   │   └── validation.js
│   └── images/
├── pages/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── items/          (list, add, edit, delete)
│   ├── categories/
│   ├── suppliers/
│   ├── purchases/
│   ├── sales/
│   ├── users/          (admin only)
│   ├── reports/
│   └── audit-logs/
├── api/                              ← small PHP endpoints for AJAX
│   ├── stock_check.php
│   └── import_csv.php
└── index.php                         ← redirects to login or dashboard
```

---

## 4. Coding Conventions

### PHP
- Use **PDO** with prepared statements. No `mysqli_query` with string concat.
- Start every protected page with `require_once 'includes/auth.php';`.
- Escape output: `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.
- Hash passwords with `password_hash()` / verify with `password_verify()`.
- Keep files under ~200 lines where possible; split if bigger.

### HTML / CSS
- Semantic tags: `<header>`, `<nav>`, `<main>`, `<section>`, `<table>`.
- Class naming: simple kebab-case, e.g. `.card`, `.btn-primary`, `.table-striped`.
- Mobile-first media queries in `responsive.css` at 576px, 768px, 992px.
- No inline styles except tiny one-offs.

### JavaScript
- Vanilla JS only. Use `fetch()` for AJAX.
- Keep event listeners at the bottom of the file or inside `DOMContentLoaded`.
- Form validation on both client and server side.

### Color Theme (light + blue accent)
```
--primary:    #2563eb   /* main blue */
--primary-dk: #1e40af
--bg:         #f7f8fb
--surface:    #ffffff
--text:       #1f2937
--muted:      #6b7280
--border:     #e5e7eb
--success:    #16a34a
--warning:    #f59e0b
--danger:     #dc2626
```

---

## 5. Database Schema (outline)

Main tables (all InnoDB, foreign keys enabled):

- `users` — id, name, email, password_hash, role (admin/manager/staff), status, created_at
- `categories` — id, name, description
- `items` — id, item_code (unique), name, category_id, unit, price, stock_qty, reorder_level, expiry_date, created_at
- `suppliers` — id, name, contact, email, address
- `purchases` — id, supplier_id, purchase_date, total_amount, created_by
- `purchase_items` — id, purchase_id, item_id, qty, unit_price
- `sales` — id, customer_name, sale_date, total_amount, created_by
- `sale_items` — id, sale_id, item_id, qty, unit_price
- `audit_logs` — id, user_id, action, table_name, record_id, details, created_at

Triggers / logic:
- Inserting a `purchase_item` → increase `items.stock_qty`
- Inserting a `sale_item` → decrease `items.stock_qty` (reject if insufficient)
- Every INSERT/UPDATE/DELETE on core tables writes to `audit_logs`

Full SQL will live in `database/inventory_db.sql`.

---

## 6. Requirement Mapping (short — full details in REQUIREMENTS_DECISION.md)

✅ **Included (core features):**
IM-01, IM-02, IM-03, IM-04, IM-05, IM-06, IM-07, IM-08, IM-09, IM-12,
IM-14, IM-15 (basic), IM-18, IM-19, IM-20, IM-21 (basic).

⚠️ **Simplified:**
- IM-04 "real-time" = on page load / after each action (no WebSockets).
- IM-05 alerts = dashboard warning banner + color tag (no email/SMS).
- IM-06 import/export = CSV only (no Excel .xlsx native).
- IM-15 backup = documented manual `mysqldump` (no automated job).
- IM-16 "high performance" = indexed columns + pagination (no caching layer).

❌ **Skipped (too technical for a mid-level project):**
- IM-10 Multi-location inventory
- IM-11 Barcode/QR scanning integration
- IM-13 Trend analysis & forecasting
- IM-17 Financial regulatory compliance
- IM-22 AI-based demand forecasting

See `REQUIREMENTS_DECISION.md` for the reason behind each decision.

---

## 7. How Subagents Should Work

Codex has five subagents defined in `Codex-agents/`. (When you copy this
project into a Codex workspace, rename the folder to `.Codex/agents/`
so Codex auto-loads them.) Use them like this:

| Task                                         | Subagent              |
|----------------------------------------------|------------------------|
| Design MySQL schema, write queries, triggers | database-engineer     |
| Build PHP backend, auth, sessions, APIs      | backend-developer     |
| Write HTML/CSS/JS pages, responsive layout   | frontend-developer    |
| Decide layout, colors, spacing, components   | ui-ux-designer        |
| Test flows, edge cases, role restrictions    | qa-tester             |

**Rule of thumb:** one agent = one concern. Don't let the frontend agent
touch SQL; don't let the DB agent style pages.

---

## 8. Delivery Checklist (definition of done)

- [ ] All three roles can log in and see role-appropriate menus
- [ ] Full CRUD on items, categories, suppliers, purchases, sales
- [ ] Dashboard shows totals + low-stock warnings
- [ ] Reports page with date filter + Chart.js bar/line chart
- [ ] CSV import and export working
- [ ] Audit log page visible to Admin
- [ ] Layout works on 360px, 768px, 1280px
- [ ] No SQL injection (all PDO prepared)
- [ ] No XSS (all echo escaped)
- [ ] README with setup steps (import SQL, edit `config/db.php`, default login)

---

## 9. Default Credentials (for README / testing)

```
Admin   → admin@inv.local   / admin123
Manager → manager@inv.local / manager123
Staff   → staff@inv.local   / staff123
```

(These go into the SQL seed. Change before any real use.)

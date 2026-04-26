# Inventory Management System — UI/UX Design Specification

> Handed to: frontend-developer
> Implement in plain HTML5 + custom CSS3 + Vanilla JS. No Bootstrap, no React.

---

## 0. Design Tokens

Apply as CSS custom properties on `:root` in `style.css`.

```
--primary:    #2563eb
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

**Typography**
- Font stack: `system-ui, -apple-system, "Segoe UI", Roboto, sans-serif`
- Sizes in use: 12 / 14 / 16 / 20 / 24 / 32 px
- Weights: 400 body, 500 labels/nav items, 600 headings and card values
- Base line-height: 1.5

**Spacing scale (no arbitrary values):** 4 / 8 / 12 / 16 / 24 / 32 / 48 px

**Border radius:** Cards, inputs, buttons, modals: 6px. Small badge pills: 4px.

**Shadow token (one only):** `box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);`

**Icons:** Font Awesome 6 Free via CDN. Use `<i class="fa-solid fa-..."></i>`.

---

## 1. Overall Page Layout

Every authenticated page uses a three-zone layout:

```
+------------------+--------------------------------------------+
|                  |  Header bar (56px tall, sticky)            |
|  Sidebar         |--------------------------------------------|
|  (240px fixed    |                                            |
|   left,          |  .main-content                             |
|   full height)   |  padding: 24px; background: var(--bg)      |
+------------------+--------------------------------------------+
```

HTML skeleton:
```html
<body class="layout">
  <aside class="sidebar">…</aside>
  <div class="page-wrapper">
    <header class="topbar">…</header>
    <main class="main-content">…</main>
  </div>
</body>
```

- `body.layout` — `display: flex; height: 100vh; overflow: hidden;`
- `.page-wrapper` — `flex: 1; display: flex; flex-direction: column; overflow-y: auto;`
- `.main-content` — `padding: 24px; background: var(--bg); flex: 1;`

---

## 2. Sidebar

**Dimensions:** 240px wide, full viewport height, fixed position. Does not scroll with content.

**Background:** `var(--surface)`. Right edge: `1px solid var(--border)`.

**Structure top to bottom:**

1. `.sidebar-logo` block — 56px tall, `padding: 0 16px`, vertically centered. App name "InvenTrack" in 18px, weight 600, `var(--primary)`. Prepend `fa-boxes-stacked` icon at 20px.

2. Nav group label — `.nav-group-label`: 10px, weight 600, uppercase, letter-spacing 0.08em, `var(--muted)`, `padding: 16px 16px 4px`.

3. Nav links — `.nav-link`: `height: 40px; padding: 0 16px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; color: var(--text); text-decoration: none;`. Icon: 15px, `var(--muted)`, fixed width 18px.
   - Hover: `background: #f3f5f9; color: var(--primary);`. Icon also shifts to `var(--primary)`.
   - Active (`.nav-link.active`): `color: var(--primary); background: #eff6ff; border-left: 3px solid var(--primary); padding-left: 13px`.

4. Nav groups and links:

   **INVENTORY** — Dashboard (`fa-gauge`), Items (`fa-box`), Categories (`fa-tag`), Suppliers (`fa-truck`)

   **TRANSACTIONS** — Purchases (`fa-cart-arrow-down`), Sales (`fa-receipt`)

   **REPORTS** — Reports (`fa-chart-bar`)

   **ADMIN** *(admin role only)* — Users (`fa-users`), Audit Logs (`fa-shield-halved`)

5. `.sidebar-footer` at bottom: `padding: 16px; font-size: 12px; color: var(--muted);`. Shows "v1.0.0".

---

## 3. Top Header Bar (`.topbar`)

**Height:** 56px. `background: var(--surface); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100;`

**Internal layout:** `display: flex; align-items: center; justify-content: space-between; padding: 0 24px;`

**Left:**
- Desktop (≥ 992px): `.topbar-title` — 20px, weight 600, `var(--text)`.
- Mobile (< 992px): `.btn-hamburger` — 36×36px, no border/background, `fa-bars` at 18px, `var(--text)`.

**Right:** `.topbar-user` — `display: flex; align-items: center; gap: 12px;`
- `.user-name` — 14px, weight 500. Hidden below 480px.
- `.user-avatar` — 32px circle, `background: var(--primary)`, white 13px initials, `border-radius: 50%`.
- `.btn-logout` — styled as `.btn-secondary` but `height: 32px; font-size: 13px; padding: 0 12px`. Prepend `fa-right-from-bracket` icon. Hover: `color: var(--danger); border-color: var(--danger)`.

---

## 4. Stat Cards (Dashboard)

**Container:** `.stats-grid` — `display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;`

**Each card:** `.stat-card` — `background: var(--surface); border: 1px solid var(--border); border-radius: 6px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); display: flex; flex-direction: column; gap: 8px;`

Internal structure:
- `.stat-card-header` — `display: flex; justify-content: space-between; align-items: flex-start;`
  - `.stat-label` — 13px, weight 500, `var(--muted)`, uppercase, letter-spacing 0.05em
  - `.stat-icon` — 36×36px, `border-radius: 8px`, icon centered at 18px
- `.stat-value` — 32px, weight 600, `var(--text)`, line-height 1
- `.stat-sub` — 13px, `var(--muted)`

Four cards:

| Card | Label | Icon | Icon bg | Icon color |
|------|-------|------|---------|------------|
| 1 | Total Items | `fa-box` | `#eff6ff` | `var(--primary)` |
| 2 | Total Sales | `fa-receipt` | `#f0fdf4` | `var(--success)` |
| 3 | Total Purchases | `fa-cart-arrow-down` | `#fffbeb` | `var(--warning)` |
| 4 | Low Stock Items | `fa-triangle-exclamation` | `#fef2f2` | `var(--danger)` |

Responsive: 4-per-row ≥ 992px, 2-per-row 576–991px, 1-per-row below 576px.

---

## 5. Tables

**Wrapper:** `.table-container` — `background: var(--surface); border: 1px solid var(--border); border-radius: 6px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,.08);`

**Table:** `width: 100%; border-collapse: collapse; font-size: 14px;`

**Header (`<thead>`):** `background: var(--primary); color: #ffffff;` Cell height 44px, `padding: 0 16px`, font-size 13px, weight 600, uppercase, letter-spacing 0.04em. `position: sticky; top: 56px;`

**Body rows:**
- Default: `background: var(--surface); padding: 12px 16px; border-bottom: 1px solid var(--border);`
- Even (`:nth-child(even)`): `background: #f9fafb`
- Hover: `background: #f3f5f9`
- Last row: no bottom border

**Action column (last column):**
- Header "Actions", `text-align: center`
- Cell: `text-align: center; white-space: nowrap; padding: 8px 12px;`
- Edit: `.btn-action.btn-edit` — 30×30px, `background: #eff6ff; color: var(--primary); border: 1px solid #bfdbfe; border-radius: 4px; fa-pen-to-square`. Hover: `background: var(--primary); color: #fff`
- Delete: `.btn-action.btn-delete` — same sizing, `background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; fa-trash`. Hover: `background: var(--danger); color: #fff`
- Gap between buttons: 6px

**Empty state:** single `<tr><td colspan="N">` — centered "No records found.", 14px, `var(--muted)`, `padding: 40px 0`.

**Pagination:** `.pagination` — `display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding: 12px 16px; border-top: 1px solid var(--border);`
- Page buttons: 32×32px, `border: 1px solid var(--border); border-radius: 4px; font-size: 13px`
- Active: `background: var(--primary); color: #fff; border-color: var(--primary)`
- Prev/Next: `fa-chevron-left` / `fa-chevron-right`. 20 rows per page.

---

## 6. Forms (Add / Edit Pages)

**Card wrapper:** `.form-card` — `background: var(--surface); border: 1px solid var(--border); border-radius: 6px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.08); max-width: 860px;`

**Card header:** `.form-card-header` — `border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 24px;` Title: 18px, weight 600.

**Form grid:** `.form-grid` — `display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px;`. Single column below 768px. `.form-grid-full` modifier: `grid-column: 1 / -1`.

**Field group:** `.form-group` — `display: flex; flex-direction: column; gap: 6px;`

**Label:** 14px, weight 500, `var(--text)`. Required field: `<span class="required">*</span>` — `color: var(--danger); margin-left: 2px`.

**Input / Select / Textarea:**
- Height 40px (textarea: `min-height: 80px; resize: vertical; height: auto`)
- `border: 1px solid var(--border); border-radius: 6px; padding: 0 12px; font-size: 14px; width: 100%; box-sizing: border-box;`
- Focus: `outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.15);`
- Disabled: `background: #f9fafb; color: var(--muted); cursor: not-allowed;`
- Invalid: `border-color: var(--danger); box-shadow: 0 0 0 3px rgba(220,38,38,0.12);`

**Hint / error text:** `.field-hint` — 12px, `var(--muted)`. `.field-error` — 12px, `var(--danger)`.

**Form footer:** `.form-footer` — `display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);` — "Cancel" `.btn-secondary` + "Save" `.btn-primary`.

---

## 7. Buttons

All buttons: `border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.15s, color 0.15s, border-color 0.15s; white-space: nowrap;`

| Class | Height | Padding | Background | Border | Text |
|-------|--------|---------|------------|--------|------|
| `.btn-primary` | 38px | `0 16px` | `var(--primary)` | none | white |
| `.btn-secondary` | 38px | `0 16px` | `var(--surface)` | `1px solid var(--border)` | `var(--text)` |
| `.btn-danger` | 38px | `0 16px` | `var(--danger)` | none | white |
| `.btn-sm` (modifier) | 30px | `0 10px` | inherits | inherits | inherits |

Hover: `.btn-primary` → `var(--primary-dk)`. `.btn-secondary` → `background: #f9fafb; border-color: #d1d5db`. `.btn-danger` → `#b91c1c`.

Disabled: `opacity: 0.55; cursor: not-allowed; pointer-events: none;`

---

## 8. Page Header Bar (List Pages)

`.page-header` — `display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;`

- Left: `.page-title` — 22px, weight 600, `var(--text)`. Below it: `.page-subtitle` — 13px, `var(--muted)` (e.g. "12 total items").
- Right: `.btn-primary` with `fa-plus` icon.

**Filter bar:** `.filter-bar` — `display: flex; gap: 12px; align-items: center; margin-bottom: 16px; flex-wrap: wrap;`
- Search input: 240px wide, 38px tall. Wrapper position relative; `fa-magnifying-glass` icon positioned left (absolute), input `padding-left: 36px`.
- Category/status select: 180px wide, 38px tall.
- "Search" `.btn-secondary.btn-sm`.
- On mobile all filter items go full-width.

---

## 9. Badges and Alerts

**Badge base:** `.badge` — `display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; line-height: 1.6;`

| Modifier | Background | Text | When |
|----------|------------|------|------|
| `.badge-success` | `#f0fdf4` | `#15803d` | stock > reorder |
| `.badge-warning` | `#fffbeb` | `#b45309` | 0 < stock ≤ reorder |
| `.badge-danger` | `#fef2f2` | `#b91c1c` | stock = 0 |
| `.badge-info` | `#eff6ff` | `#1d4ed8` | neutral / general |

**Flash message banners:** `.alert` — `display: flex; align-items: flex-start; gap: 12px; padding: 12px 16px; border-radius: 6px; font-size: 14px; margin-bottom: 16px; border-left: 4px solid;`

| Modifier | Background | Border | Text | Icon |
|----------|------------|--------|------|------|
| `.alert-success` | `#f0fdf4` | `var(--success)` | `#15803d` | `fa-circle-check` |
| `.alert-error` | `#fef2f2` | `var(--danger)` | `#b91c1c` | `fa-circle-xmark` |
| `.alert-warning` | `#fffbeb` | `var(--warning)` | `#b45309` | `fa-triangle-exclamation` |
| `.alert-info` | `#eff6ff` | `var(--primary)` | `#1d4ed8` | `fa-circle-info` |

Close button inside alert: `fa-xmark` icon right-aligned, `background: none; border: none; cursor: pointer; opacity: 0.6;`. JS sets `display: none` on click.

---

## 10. Dashboard Page

**Low-stock warning banner** (render only when 1+ items at or below reorder level):

`.low-stock-banner` — `background: #fffbeb; border: 1px solid #fcd34d; border-left: 4px solid var(--warning); border-radius: 6px; padding: 12px 16px; display: flex; align-items: center; gap: 12px; margin-bottom: 24px; font-size: 14px; color: #92400e;`
- Left: `fa-triangle-exclamation` at 18px, `color: var(--warning)`.
- Text: "**N items** are at or below reorder level."
- Right: `.btn-sm.btn-secondary` "View All" link to items list filtered to low-stock.

**Stats grid:** Section 4.

**Two-column dashboard bottom section:**

`.dashboard-grid` — `display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;`

- Left: `.card` — low-stock items table. `.card-header`: `padding: 16px; border-bottom: 1px solid var(--border); font-size: 16px; font-weight: 600; display: flex; justify-content: space-between;`. Table columns: Item Name | Stock | Reorder Level | Status. Max 6 rows.
- Right: `.card` — sales chart. `.card-header` title "Sales Overview". `<canvas id="salesChart">` height 280px (Chart.js line chart). Below canvas: tab pills "7 Days" / "30 Days" / "3 Months" — `.tab-btn` `border: 1px solid var(--border); border-radius: 20px; padding: 4px 12px; font-size: 12px; background: var(--surface);`. Active: `background: var(--primary); color: #fff; border-color: var(--primary)`.

Tablet (< 992px): `.dashboard-grid` single column.

---

## 11. Reports Page

**Filter bar:** `.reports-filter` — `background: var(--surface); border: 1px solid var(--border); border-radius: 6px; padding: 16px; margin-bottom: 24px; display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;`
- Fields: Date From, Date To (date inputs), Category select — all 38px tall. Then "Generate Report" `.btn-primary` and "Export CSV" `.btn-secondary` with `fa-download`.

**Summary cards:** same `.stats-grid` layout showing totals for selected range.

**Charts:** `.charts-grid` — `display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;`
- Card 1: Bar chart — "Sales by Category"
- Card 2: Line chart — "Sales Trend"
- Full-width third card below: "Top Selling Items" (horizontal bar)

Collapses to single column below 992px.

---

## 12. Login Page

**Body:** `background: var(--bg); display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh;` No sidebar, no topbar.

**Login card:** `.login-card` — `background: var(--surface); border: 1px solid var(--border); border-radius: 6px; padding: 40px; width: 400px; max-width: calc(100vw - 32px); box-shadow: 0 1px 3px rgba(0,0,0,.08);`

Inside the card:
1. `.login-brand` — centered, `margin-bottom: 32px`. `fa-boxes-stacked` at 40px, `var(--primary)`. App name "InvenTrack" 24px weight 600 below. Tagline "Inventory Management System" 13px `var(--muted)` `margin-top: 4px`.
2. Email input `.form-group`.
3. Password input `.form-group` — toggle button inside (absolute right 12px, `fa-eye`/`fa-eye-slash` at 14px, `var(--muted)`, no border, cursor pointer); input `padding-right: 40px`.
4. Checkbox row — `display: flex; align-items: center; gap: 8px;` — "Remember me".
5. `.alert.alert-error` (hidden by default, shown on failed login).
6. `.btn-primary` full-width — `width: 100%; justify-content: center; height: 42px; font-size: 15px; margin-top: 8px;` — "Sign In".

Below card: 14px `var(--muted)` centered copyright text. `margin-top: 24px`.

---

## 13. Modal Dialogs (Delete Confirmation)

**Backdrop:** `.modal-backdrop` — `position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 200; display: flex; align-items: center; justify-content: center;`

**Modal box:** `.modal` — `background: var(--surface); border-radius: 6px; padding: 24px; width: 460px; max-width: calc(100vw - 32px); box-shadow: 0 8px 24px rgba(0,0,0,0.12);`

Structure:
- `.modal-header` — `display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;`. Title 18px weight 600. Close button: `fa-xmark` at 18px, `var(--muted)`, no border/background. Hover: `var(--text)`.
- `.modal-body` — 14px, `line-height: 1.6`, `margin-bottom: 24px`. `fa-circle-exclamation` at 32px `var(--danger)` centered above message text. Message: "Are you sure you want to delete **[name]**? This action cannot be undone."
- `.modal-footer` — `display: flex; justify-content: flex-end; gap: 12px;`. "Cancel" `.btn-secondary` + "Delete" `.btn-danger`.

JS: on open focus moves to modal; Escape key closes it.

---

## 14. Audit Logs Page (Admin Only)

Standard list page. Filter bar fields: Date From, Date To, User select, Action select (INSERT/UPDATE/DELETE), Table select. "Filter" `.btn-primary` + "Reset" `.btn-secondary`.

Table columns: # | Date & Time | User | Action | Table | Record ID | Details

- Action cell badges: INSERT = `.badge-success`, UPDATE = `.badge-info`, DELETE = `.badge-danger`.
- Details cell: `overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 240px`. Full content in `title` attribute for native tooltip.
- No action buttons column — read-only table.

---

## 15. Users Page (Admin Only)

Table columns: # | Name | Email | Role | Status | Created At | Actions

- Role badges: Admin = `.badge-info`, Manager = `.badge-success`, Staff = plain `var(--muted)` text.
- Status: `.badge-success` Active / `.badge-danger` Inactive.
- Actions: Edit (blue) + Toggle Status (`fa-power-off`, amber hover) + Delete (red). Gap 6px.

**Add / Edit User form (2-column grid):**
- Full Name (`.form-grid-full`)
- Email | Password (on edit: "Leave blank to keep current")
- Role select | Status select

---

## 16. Items Pages

**List table columns:** # | Item Code | Name | Category | Unit | Stock | Reorder Level | Price | Actions

- Stock cell: qty value + stock badge. `display: flex; align-items: center; gap: 8px;`
- Price cell: right-aligned, currency format.
- Item Code: `font-family: "Courier New", monospace; color: var(--muted);`

**Add / Edit form (2-column grid):**
- Row 1: Item Code | Item Name
- Row 2: Category select | Unit select
- Row 3: Sale Price | Reorder Level
- Row 4: Stock Quantity (disabled on Add — set via purchases) | Expiry Date (optional)
- Row 5: Description (`.form-grid-full` textarea)

---

## 17. Purchases Pages

**List columns:** # | Purchase ID | Supplier | Date | Total Amount | Created By | Actions

**Add Purchase form — two sections:**

Section 1 — header: Supplier select | Purchase Date. Notes textarea (full-width).

Section 2 — items: editable table with columns: Item select | Qty | Unit Price | Subtotal (readonly, auto-calculated) | Remove (`fa-trash`). "Add Item Row" `.btn-secondary` below table. Total amount right-aligned 16px weight 600. Form footer: Cancel + Save Purchase.

---

## 18. Sales Pages

**List columns:** # | Sale ID | Customer Name | Date | Total Amount | Created By | Actions

**Add Sale form:** same two-section layout as Purchases. Section 1: Customer Name | Sale Date. If stock insufficient when row added, show `.alert-error` inline (JS check via `api/stock_check.php`).

---

## 19. Categories and Suppliers

**Categories list columns:** # | Name | Description | Items Count | Actions

**Add/Edit Category form:** single-column. Name (required) + Description (textarea, optional).

**Suppliers list columns:** # | Name | Contact | Email | Address | Actions

**Add/Edit Supplier form (2-column):**
- Supplier Name (`.form-grid-full`)
- Contact Number | Email
- Address (`.form-grid-full` textarea)

---

## 20. Responsive Breakpoints (`responsive.css`)

**< 576px:**
- `.stats-grid`, `.form-grid`, `.dashboard-grid`, `.charts-grid`: `grid-template-columns: 1fr`
- `.filter-bar` children: `flex: 1 1 100%`
- `.topbar-user .user-name`: `display: none`
- `.main-content`: `padding: 16px`
- Page title: 18px

**576px – 767px:**
- `.stats-grid`: `repeat(2, 1fr)`
- Sidebar: off-canvas drawer

**768px – 991px:**
- `.stats-grid`: `repeat(2, 1fr)`
- `.form-grid`: `repeat(2, 1fr)`
- `.dashboard-grid`, `.charts-grid`: single column
- Sidebar: off-canvas drawer

**≥ 992px:**
- `.stats-grid`: `repeat(4, 1fr)`
- `.page-wrapper`: `margin-left: 240px`
- Sidebar: fixed visible, `transform: none`
- `.btn-hamburger`: `display: none`

**Sidebar drawer (< 992px):**
- Default: `.sidebar` has `transform: translateX(-240px); transition: transform 0.25s ease; position: fixed; z-index: 100;`
- `.sidebar-open` on `<body>`: sidebar gets `transform: translateX(0)`
- `body.sidebar-open::after`: full-screen overlay `background: rgba(0,0,0,0.35); z-index: 99; position: fixed; inset: 0; content: "";`. Click closes drawer.

Tables: all columns kept, `.table-container` has `overflow-x: auto` for horizontal scroll on mobile.

---

## 21. Utility Classes

```css
.text-muted     { color: var(--muted); }
.text-success   { color: var(--success); }
.text-danger    { color: var(--danger); }
.text-warning   { color: var(--warning); }
.text-primary   { color: var(--primary); }
.text-right     { text-align: right; }
.text-center    { text-align: center; }
.font-mono      { font-family: "Courier New", monospace; font-size: 13px; }
.mt-8           { margin-top: 8px; }
.mt-16          { margin-top: 16px; }
.mt-24          { margin-top: 24px; }
.mb-16          { margin-bottom: 16px; }
.mb-24          { margin-bottom: 24px; }
.d-flex         { display: flex; }
.align-center   { align-items: center; }
.gap-8          { gap: 8px; }
.gap-12         { gap: 12px; }
.w-100          { width: 100%; }
.sr-only        { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }
```

---

## 22. Accessibility Notes

- Every input has a `<label>` with matching `for`/`id`.
- Icon-only buttons carry `aria-label` (e.g. `aria-label="Edit item"`).
- Color is never the sole state indicator — stock badges always include text ("In Stock", "Low", "Out of Stock").
- Contrast: all body text meets 4.5:1. `var(--muted)` (#6b7280) on white is 4.6:1 at 14px — acceptable.
- Focus styles: never suppress `outline` without the custom `box-shadow` focus ring in its place.
- Modal on open: focus moves inside. Escape key closes. Implemented in `main.js`.

---

## 23. File Organization

```
assets/css/style.css       — tokens, reset, all component styles in spec order
assets/css/responsive.css  — @media blocks only, no component definitions
assets/js/main.js          — sidebar toggle, modal open/close, tab switching, alert close
assets/js/validation.js    — form validation helpers
```

`style.css` order: `:root` tokens → CSS reset → layout → sidebar → topbar → cards → tables → forms → buttons → badges → alerts → utilities.

No inline styles in HTML except `style="display:none"` for JS-controlled visibility.

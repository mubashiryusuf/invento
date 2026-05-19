const api = window.inventrack;
const app = document.getElementById('app');

let state = {
  user: null,
  route: 'dashboard',
  search: '',
  filters: {},
  lookups: { categories: [], suppliers: [], items: [], users: [] }
};

const appLogo = '../../assets/images/material-management.png';
const appName = 'Stockly';

const routes = [
  ['dashboard', 'Dashboard', 'layout-dashboard', ['admin', 'manager', 'staff']],
  ['items', 'Items', 'package', ['admin', 'manager', 'staff']],
  ['categories', 'Categories', 'layers', ['admin', 'manager', 'staff']],
  ['suppliers', 'Suppliers', 'truck', ['admin', 'manager', 'staff']],
  ['purchases', 'Purchases', 'download', ['admin', 'manager', 'staff']],
  ['sales', 'Sales', 'upload', ['admin', 'manager', 'staff']],
  ['reports', 'Reports', 'chart', ['admin', 'manager']],
  ['users', 'Users', 'users', ['admin']],
  ['audit', 'Audit Logs', 'clipboard', ['admin']]
];

const iconPaths = {
  'layout-dashboard': '<rect x="3" y="3" width="7" height="8" rx="1.5"></rect><rect x="14" y="3" width="7" height="5" rx="1.5"></rect><rect x="14" y="12" width="7" height="9" rx="1.5"></rect><rect x="3" y="15" width="7" height="6" rx="1.5"></rect>',
  package: '<path d="m3 7 9-4 9 4-9 4-9-4Z"></path><path d="M3 7v10l9 4 9-4V7"></path><path d="M12 11v10"></path>',
  layers: '<path d="m12 3 9 5-9 5-9-5 9-5Z"></path><path d="m3 12 9 5 9-5"></path><path d="m3 17 9 5 9-5"></path>',
  truck: '<path d="M10 17H5V6h10v11h-1"></path><path d="M15 9h4l2 3v5h-2"></path><circle cx="7.5" cy="17.5" r="2"></circle><circle cx="17.5" cy="17.5" r="2"></circle>',
  download: '<path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M4 21h16"></path>',
  upload: '<path d="M12 21V9"></path><path d="m7 14 5-5 5 5"></path><path d="M4 3h16"></path>',
  chart: '<path d="M4 19V5"></path><path d="M4 19h16"></path><rect x="7" y="11" width="3" height="5" rx="1"></rect><rect x="12" y="7" width="3" height="9" rx="1"></rect><rect x="17" y="3" width="3" height="13" rx="1"></rect>',
  users: '<circle cx="9" cy="8" r="3"></circle><path d="M3 20a6 6 0 0 1 12 0"></path><circle cx="17" cy="9" r="2.5"></circle><path d="M15 15a5 5 0 0 1 6 5"></path>',
  clipboard: '<rect x="5" y="4" width="14" height="17" rx="2"></rect><path d="M9 4a3 3 0 0 1 6 0"></path><path d="M9 9h6"></path><path d="M9 13h6"></path>',
  plus: '<path d="M12 5v14"></path><path d="M5 12h14"></path>',
  refresh: '<path d="M21 12a9 9 0 0 1-15.5 6.2"></path><path d="M3 12A9 9 0 0 1 18.5 5.8"></path><path d="M18 2v4h4"></path><path d="M6 22v-4H2"></path>',
  edit: '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"></path>',
  trash: '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M6 6l1 15h10l1-15"></path>',
  close: '<path d="M6 6l12 12"></path><path d="M18 6 6 18"></path>',
  eye: '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle>',
  alert: '<path d="M12 3 2 21h20L12 3Z"></path><path d="M12 9v5"></path><path d="M12 17h.01"></path>',
  logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path>',
  database: '<ellipse cx="12" cy="5" rx="8" ry="3"></ellipse><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"></path><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"></path>',
  fileUp: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M12 17V11"></path><path d="m9 14 3-3 3 3"></path>',
  fileDown: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M12 11v6"></path><path d="m9 14 3 3 3-3"></path>',
  save: '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path><path d="M17 21v-8H7v8"></path><path d="M7 3v5h8"></path>',
  power: '<path d="M12 2v10"></path><path d="M18.4 6.6a9 9 0 1 1-12.8 0"></path>',
  search: '<circle cx="11" cy="11" r="7"></circle><path d="m16 16 5 5"></path>'
};

function icon(name, className = '') {
  return `<svg class="icon ${className}" viewBox="0 0 24 24" aria-hidden="true">${iconPaths[name] || iconPaths.package}</svg>`;
}

function allowed(route) {
  const row = routes.find((item) => item[0] === route);
  return row && state.user && row[3].includes(state.user.role);
}

function fmtMoney(value) {
  return `Rs ${Number(value || 0).toFixed(2)}`;
}

function fmtDate(value) {
  if (!value) return '';
  if (value instanceof Date) return value.toISOString().slice(0, 10);
  const text = String(value);
  if (/^\d{4}-\d{2}-\d{2}/.test(text)) return text.slice(0, 10);
  const parsed = new Date(text);
  return Number.isNaN(parsed.getTime()) ? text.slice(0, 10) : parsed.toISOString().slice(0, 10);
}

function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[char]));
}

function today(offsetDays = 0) {
  const date = new Date();
  date.setDate(date.getDate() + offsetDays);
  return date.toISOString().slice(0, 10);
}

function toast(message, type = 'success') {
  const existing = document.querySelector('.alert');
  if (existing) existing.remove();
  const node = document.createElement('div');
  node.className = `alert ${type}`;
  node.textContent = message;
  document.querySelector('.main')?.prepend(node);
  setTimeout(() => node.remove(), 4200);
}

async function run(action, errorPrefix = '') {
  try {
    return await action();
  } catch (error) {
    toast(`${errorPrefix}${error.message || error}`, 'error');
    throw error;
  }
}

function renderLogin() {
  app.innerHTML = `
    <main class="login-page">
      <section class="login-art">
        <div class="brand-mark brand-mark-large"><img src="${appLogo}" alt=""></div>
        <div>
          <h1>Stockly</h1>
          <p>Manage stock, purchases, sales, suppliers, users, reports, and audit history directly against your MySQL database.</p>
        </div>
      </section>
      <section class="login-panel">
        <h2>Sign in</h2>
        <p class="muted">Use your existing inventory account.</p>
        <form id="loginForm">
          <div class="form-group">
            <label>Email</label>
            <input name="email" type="email" value="admin@inv.local" required>
          </div>
          <div class="form-group" style="margin-top:14px">
            <label>Password</label>
            <input name="password" type="password" value="admin123" required>
          </div>
          <button class="btn btn-primary" style="width:100%;margin-top:18px" type="submit">${icon('database')} Sign in</button>
        </form>
        <p class="muted" style="margin-top:16px">Default users: admin@inv.local / admin123, manager@inv.local / manager123, staff@inv.local / staff123.</p>
      </section>
    </main>`;

  document.getElementById('loginForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    await run(async () => {
      state.user = await api.login({ email: form.get('email'), password: form.get('password') });
      state.route = 'dashboard';
      await renderShell();
    });
  });
}

async function renderShell() {
  if (!state.user) return renderLogin();
  const currentRoute = routes.find((r) => r[0] === state.route) || routes[0];
  app.innerHTML = `
    <div class="app-shell">
      <aside class="sidebar">
        <div class="sidebar-brand"><div class="brand-mark"><img src="${appLogo}" alt=""></div><span>${appName}</span></div>
        <nav class="nav">
          ${routes.filter((r) => r[3].includes(state.user.role)).map((r) => `
            <button data-route="${r[0]}" class="${state.route === r[0] ? 'active' : ''}">${icon(r[2])}<span>${r[1]}</span></button>
          `).join('')}
        </nav>
        <div class="sidebar-footer">
          <div class="user-chip">
            <div class="avatar">${esc(state.user.name[0] || 'U').toUpperCase()}</div>
            <div><strong>${esc(state.user.name)}</strong><div class="muted">${esc(state.user.role)}</div></div>
          </div>
          <button class="btn btn-secondary" id="logoutBtn" style="width:100%">${icon('logout')} Logout</button>
        </div>
      </aside>
      <section class="page">
        <div class="topbar"><h1>${icon(currentRoute[2])}${esc(currentRoute[1])}</h1><div class="db-pill">${icon('database')} MySQL: inventory_db</div></div>
        <main class="main" id="main"></main>
      </section>
    </div>`;

  document.querySelectorAll('[data-route]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      state.route = btn.dataset.route;
      state.search = '';
      state.filters = {};
      await renderShell();
    });
  });
  document.getElementById('logoutBtn').addEventListener('click', async () => {
    await api.logout();
    state.user = null;
    renderLogin();
  });
  await renderRoute();
}

async function renderRoute() {
  if (!allowed(state.route)) state.route = 'dashboard';
  if (state.route === 'dashboard') return renderDashboard();
  if (state.route === 'reports') return renderReports();
  return renderList(state.route);
}

function setMain(html) {
  document.getElementById('main').innerHTML = html;
}

async function renderDashboard() {
  const data = await run(() => api.dashboard());
  setMain(`
    ${Number(data.stats.lowStockCount) ? `<div class="alert warning"><strong>${data.stats.lowStockCount} item(s)</strong> are at or below reorder level: ${data.lowStock.map((i) => `${esc(i.name)} (${i.stock_qty}/${i.reorder_level})`).join(', ')}</div>` : ''}
    <div class="stats-grid">
      ${stat('Total Items', data.stats.totalItems, 'Products in inventory', 'package')}
      ${stat('Suppliers', data.stats.totalSuppliers, 'Active suppliers', 'truck')}
      ${stat("Today's Sales", fmtMoney(data.stats.todaySales), 'Revenue today', 'upload')}
      ${stat('Low Stock', data.stats.lowStockCount, 'Items need restocking', 'alert')}
    </div>
    <div class="grid-2">
      ${panel('Recent Sales', table(['Customer', 'Date', 'Amount'], data.sales.map((s) => [s.customer_name, fmtDate(s.sale_date), fmtMoney(s.total_amount)])), 'sales')}
      ${panel('Recent Purchases', table(['Supplier', 'Date', 'Amount'], data.purchases.map((p) => [p.supplier_name || 'N/A', fmtDate(p.purchase_date), fmtMoney(p.total_amount)])), 'purchases')}
    </div>`);

  document.querySelectorAll('[data-view-all]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      state.route = btn.dataset.viewAll;
      state.search = '';
      state.filters = {};
      await renderShell();
    });
  });
}

function stat(label, value, sub, iconName) {
  return `<div class="stat-card"><div class="stat-head"><span>${esc(label)}</span><span class="stat-icon">${icon(iconName)}</span></div><div class="stat-value">${esc(value)}</div><div class="stat-sub">${esc(sub)}</div></div>`;
}

function panel(title, body, viewAllRoute = '') {
  const action = viewAllRoute ? `<button class="btn btn-secondary btn-sm" data-view-all="${viewAllRoute}">${icon('eye')} View All</button>` : '';
  return `<section class="panel"><div class="panel-header"><h2 class="panel-title">${esc(title)}</h2>${action}</div><div class="panel-body">${body}</div></section>`;
}

function table(headers, rows) {
  return `<table><thead><tr>${headers.map((h) => `<th>${esc(h)}</th>`).join('')}</tr></thead><tbody>${
    rows.length ? rows.map((row) => `<tr>${row.map((tableCell, i) => `<td class="${i === row.length - 1 && /^Rs /.test(String(tableCell)) ? 'price' : ''}">${esc(tableCell)}</td>`).join('')}</tr>`).join('') :
      `<tr><td class="table-empty" colspan="${headers.length}">No records found.</td></tr>`
  }</tbody></table>`;
}

const entityConfig = {
  categories: {
    title: 'Categories',
    singular: 'Category',
    editorRoles: ['admin', 'manager'],
    columns: ['#', 'Name', 'Description', 'Items'],
    row: (r) => [r.id, r.name, r.description || '-', badge(r.item_count, 'info')],
    searchPlaceholder: 'Search categories',
    form: categoryForm
  },
  suppliers: {
    title: 'Suppliers',
    singular: 'Supplier',
    editorRoles: ['admin', 'manager'],
    columns: ['#', 'Name', 'Contact', 'Email', 'Purchases'],
    row: (r) => [r.id, r.name, r.contact || '-', r.email || '-', badge(r.purchase_count, 'info')],
    searchPlaceholder: 'Search by name, email or contact',
    form: supplierForm
  },
  items: {
    title: 'Items',
    singular: 'Item',
    editorRoles: ['admin', 'manager'],
    columns: ['Code', 'Name', 'Category', 'Unit', 'Price', 'Stock', 'Reorder', 'Expiry'],
    row: (r) => [r.item_code, r.name, r.category_name, r.unit, fmtMoney(r.price), stockBadge(r), r.reorder_level, fmtDate(r.expiry_date) || '-'],
    searchPlaceholder: 'Search by name, code or category',
    filters: 'items',
    form: itemForm
  },
  purchases: {
    title: 'Purchases',
    editorRoles: ['admin', 'manager'],
    columns: ['#', 'Supplier', 'Date', 'Total', 'Created By'],
    row: (r) => [r.id, r.supplier_name, fmtDate(r.purchase_date), fmtMoney(r.total_amount), r.created_by_name || '-'],
    searchPlaceholder: 'Search supplier, ID or user',
    filters: 'dates',
    transaction: 'purchase'
  },
  sales: {
    title: 'Sales',
    editorRoles: ['admin', 'manager', 'staff'],
    columns: ['#', 'Customer', 'Date', 'Total', 'Created By'],
    row: (r) => [r.id, r.customer_name, fmtDate(r.sale_date), fmtMoney(r.total_amount), r.created_by_name || '-'],
    searchPlaceholder: 'Search customer, ID or user',
    filters: 'dates',
    transaction: 'sale'
  },
  users: {
    title: 'Users',
    singular: 'User',
    editorRoles: ['admin'],
    columns: ['#', 'Name', 'Email', 'Role', 'Status', 'Created'],
    row: (r) => [r.id, r.name, r.email, badge(r.role, 'info'), badge(r.status, r.status === 'active' ? 'success' : 'warning'), fmtDate(r.created_at)],
    searchPlaceholder: 'Search by name, email or role',
    filters: 'users',
    form: userForm
  },
  audit: {
    title: 'Audit Logs',
    editorRoles: [],
    columns: ['Date', 'User', 'Action', 'Table', 'Record', 'Details'],
    row: (r) => [String(r.created_at).replace('T', ' ').slice(0, 19), r.user_name || '-', badge(r.action, 'info'), r.table_name, r.record_id || '-', r.details || '-'],
    searchPlaceholder: 'Search logs',
    filters: 'audit'
  }
};

function badge(text, type = '') {
  return `<span class="badge ${type}">${esc(text)}</span>`;
}

function cell(value) {
  const html = String(value ?? '');
  return html.startsWith('<span class="badge') ? html : esc(html);
}

function stockBadge(item) {
  const type = Number(item.stock_qty) <= 0 ? 'danger' : Number(item.stock_qty) <= Number(item.reorder_level) ? 'warning' : 'success';
  return badge(item.stock_qty, type);
}

function filterControls(config) {
  if (config.filters === 'items') {
    return `<select name="categoryId">
      <option value="">All Categories</option>
      ${state.lookups.categories.map((cat) => `<option value="${cat.id}" ${String(state.filters.categoryId || '') === String(cat.id) ? 'selected' : ''}>${esc(cat.name)}</option>`).join('')}
    </select>`;
  }
  if (config.filters === 'dates') {
    return `
      <label>From</label><input type="date" name="dateFrom" value="${esc(state.filters.dateFrom || '')}">
      <label>To</label><input type="date" name="dateTo" value="${esc(state.filters.dateTo || '')}">`;
  }
  if (config.filters === 'users') {
    return `<select name="role">
      <option value="">All Roles</option>
      ${['admin', 'manager', 'staff'].map((role) => `<option value="${role}" ${state.filters.role === role ? 'selected' : ''}>${esc(role[0].toUpperCase() + role.slice(1))}</option>`).join('')}
    </select>`;
  }
  if (config.filters === 'audit') {
    return `
      <select name="userId">
        <option value="">All Users</option>
        ${state.lookups.users.map((user) => `<option value="${user.id}" ${String(state.filters.userId || '') === String(user.id) ? 'selected' : ''}>${esc(user.name)}</option>`).join('')}
      </select>
      <input class="filter-input" name="action" type="text" value="${esc(state.filters.action || '')}" placeholder="Action">
      <label>From</label><input type="date" name="dateFrom" value="${esc(state.filters.dateFrom || '')}">
      <label>To</label><input type="date" name="dateTo" value="${esc(state.filters.dateTo || '')}">`;
  }
  return '';
}

async function renderList(entity) {
  const config = entityConfig[entity];
  if (['items', 'audit'].includes(config.filters)) await ensureLookups();
  const rows = await run(() => api.list({ entity, search: state.search, filters: state.filters }));
  const canEdit = config.editorRoles.includes(state.user.role);
  const canImport = ['items', 'suppliers'].includes(entity) && ['admin', 'manager'].includes(state.user.role);
  const canExport = ['items', 'suppliers', 'sales', 'purchases'].includes(entity) && ['admin', 'manager'].includes(state.user.role);
  setMain(`
    <div class="page-header">
      <div><h2 class="page-title">${config.title}</h2><p class="page-subtitle">${rows.length} record(s) found</p></div>
      <div class="actions">
        ${canImport ? `<button class="btn btn-secondary" id="importBtn">${icon('fileUp')} Import CSV</button>` : ''}
        ${canExport ? `<button class="btn btn-secondary" id="exportBtn">${icon('fileDown')} Export CSV</button>` : ''}
        ${config.transaction && canEdit ? `<button class="btn btn-primary" id="addBtn">${icon('plus')} New ${config.transaction === 'purchase' ? 'Purchase' : 'Sale'}</button>` : ''}
        ${config.form && canEdit ? `<button class="btn btn-primary" id="addBtn">${icon('plus')} Add ${config.singular}</button>` : ''}
      </div>
    </div>
    <form class="toolbar filter-form" id="filterForm">
      <input class="search" id="searchInput" name="search" type="search" value="${esc(state.search)}" placeholder="${esc(config.searchPlaceholder || `Search ${config.title.toLowerCase()}`)}">
      ${filterControls(config)}
      <button class="btn btn-primary" type="submit">${icon('search')} Apply</button>
      <button class="btn btn-secondary" type="button" id="clearFiltersBtn">${icon('close')} Clear</button>
      <button class="btn btn-secondary" type="button" id="refreshBtn">${icon('refresh')} Refresh</button>
    </form>
    <div class="table-wrap">
      <table>
        <thead><tr>${config.columns.map((h) => `<th>${esc(h)}</th>`).join('')}${canEdit && !config.transaction ? '<th>Actions</th>' : ''}${config.transaction ? '<th>View</th>' : ''}</tr></thead>
        <tbody>
          ${rows.length ? rows.map((row) => `<tr>${config.row(row).map((value) => `<td>${cell(value)}</td>`).join('')}${
            canEdit && !config.transaction ? `<td class="nowrap"><button class="icon-btn" data-edit="${row.id}" title="Edit">${icon('edit')}</button> ${entity === 'users' && Number(row.id) !== Number(state.user.id) ? `<button class="icon-btn" data-toggle="${row.id}" title="Toggle status">${icon('power')}</button> <button class="icon-btn danger" data-delete="${row.id}" title="Delete">${icon('trash')}</button>` : entity === 'users' ? '' : `<button class="icon-btn danger" data-delete="${row.id}" title="Delete">${icon('trash')}</button>`}</td>` : ''
          }${config.transaction ? `<td><button class="btn btn-secondary btn-sm" data-view="${row.id}">${icon('eye')} Details</button></td>` : ''}</tr>`).join('') :
            `<tr><td class="table-empty" colspan="${config.columns.length + 1}">No records found.</td></tr>`}
        </tbody>
      </table>
    </div>`);

  document.getElementById('filterForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    state.search = String(form.get('search') || '').trim();
    state.filters = Object.fromEntries(form.entries());
    delete state.filters.search;
    await renderList(entity);
  });
  document.getElementById('clearFiltersBtn').addEventListener('click', async () => {
    state.search = '';
    state.filters = {};
    await renderList(entity);
  });
  document.getElementById('refreshBtn').addEventListener('click', () => renderList(entity));
  document.getElementById('addBtn')?.addEventListener('click', async () => {
    if (config.transaction) return openTransactionModal(config.transaction);
    await openFormModal(entity, null);
  });
  document.getElementById('exportBtn')?.addEventListener('click', async () => {
    const result = await run(() => api.exportCsv({ entity }));
    if (!result.canceled) toast(`CSV exported to ${result.path}`);
  });
  document.getElementById('importBtn')?.addEventListener('click', async () => {
    const result = await run(() => api.importCsv({ entity }));
    if (!result.canceled) {
      const skipped = result.skipped ? `, ${result.skipped} skipped` : '';
      toast(`CSV import complete: ${result.imported} imported${skipped}.`);
      await renderList(entity);
    }
  });
  document.querySelectorAll('[data-edit]').forEach((btn) => {
    btn.addEventListener('click', () => openFormModal(entity, rows.find((r) => Number(r.id) === Number(btn.dataset.edit))));
  });
  document.querySelectorAll('[data-delete]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (!confirm('Delete this record?')) return;
      await run(() => api.delete({ entity, id: btn.dataset.delete }));
      toast('Record deleted.');
      await renderList(entity);
    });
  });
  document.querySelectorAll('[data-toggle]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      await run(() => api.toggleUser({ id: btn.dataset.toggle }));
      toast('User status updated.');
      await renderList(entity);
    });
  });
  document.querySelectorAll('[data-view]').forEach((btn) => {
    btn.addEventListener('click', () => openTransactionDetail(config.transaction, btn.dataset.view));
  });
}

function debounce(fn, wait) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), wait);
  };
}

async function ensureLookups() {
  state.lookups = await api.lookups();
}

async function openFormModal(entity, row) {
  await ensureLookups();
  const html = entityConfig[entity].form(row || {});
  showModal(`${row ? 'Edit' : 'Add'} ${entityConfig[entity].singular}`, html, async (form) => {
    const payload = Object.fromEntries(new FormData(form).entries());
    if (row?.id) payload.id = row.id;
    await run(() => api.save({ entity, payload }));
    closeModal();
    toast('Record saved.');
    await renderList(entity);
  });
}

function categoryForm(row) {
  return `<div class="form-grid">${input('name', 'Name', row.name, true)}${textarea('description', 'Description', row.description, 'full')}</div>`;
}

function supplierForm(row) {
  return `<div class="form-grid">
    ${input('name', 'Name', row.name, true)}
    ${input('contact', 'Contact', row.contact)}
    ${input('email', 'Email', row.email, false, 'email')}
    ${textarea('address', 'Address', row.address, 'full')}
  </div>`;
}

function itemForm(row) {
  return `<div class="form-grid">
    ${input('item_code', 'Item Code', row.item_code, true)}
    ${input('name', 'Name', row.name, true)}
    ${select('category_id', 'Category', state.lookups.categories.map((c) => [c.id, c.name]), row.category_id, true)}
    ${input('unit', 'Unit', row.unit || 'pcs', true)}
    ${input('price', 'Price (Rs)', row.price || 0, true, 'number', '0.01')}
    ${input('stock_qty', 'Stock Quantity', row.stock_qty || 0, true, 'number', '1')}
    ${input('reorder_level', 'Reorder Level', row.reorder_level || 10, true, 'number', '1')}
    ${input('expiry_date', 'Expiry Date', fmtDate(row.expiry_date), false, 'date')}
  </div>`;
}

function userForm(row) {
  return `<div class="form-grid">
    ${input('name', 'Full Name', row.name, true)}
    ${input('email', 'Email', row.email, true, 'email')}
    ${input('password', row.id ? 'New Password' : 'Password', '', !row.id, 'password')}
    ${select('role', 'Role', [['staff', 'Staff'], ['manager', 'Manager'], ['admin', 'Admin']], row.role || 'staff', true)}
    ${select('status', 'Status', [['active', 'Active'], ['inactive', 'Inactive']], row.status || 'active', true)}
  </div>`;
}

function input(name, label, value = '', required = false, type = 'text', step = '') {
  return `<div class="form-group"><label>${esc(label)}${required ? ' *' : ''}</label><input name="${name}" type="${type}" value="${esc(value ?? '')}" ${required ? 'required' : ''} ${step ? `step="${step}" min="0"` : ''}></div>`;
}

function textarea(name, label, value = '', cls = '') {
  return `<div class="form-group ${cls}"><label>${esc(label)}</label><textarea name="${name}">${esc(value ?? '')}</textarea></div>`;
}

function select(name, label, options, value, required = false) {
  return `<div class="form-group"><label>${esc(label)}${required ? ' *' : ''}</label><select name="${name}" ${required ? 'required' : ''}>${options.map(([id, text]) => `<option value="${esc(id)}" ${String(id) === String(value) ? 'selected' : ''}>${esc(text)}</option>`).join('')}</select></div>`;
}

function showModal(title, body, onSubmit, size = '') {
  const node = document.createElement('div');
  node.className = 'modal-backdrop';
  node.innerHTML = `<form class="modal ${size}" id="modalForm">
    <div class="modal-header"><h2>${esc(title)}</h2><button type="button" class="icon-btn" data-close>${icon('close')}</button></div>
    <div class="modal-body">${body}</div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-close>Cancel</button><button class="btn btn-primary" type="submit">${icon('save')} Save</button></div>
  </form>`;
  document.body.appendChild(node);
  node.querySelectorAll('[data-close]').forEach((btn) => btn.addEventListener('click', closeModal));
  node.querySelector('#modalForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    await onSubmit(event.currentTarget);
  });
}

function closeModal() {
  document.querySelector('.modal-backdrop')?.remove();
}

async function openTransactionModal(type) {
  await ensureLookups();
  const isPurchase = type === 'purchase';
  const head = isPurchase
    ? `${select('supplier_id', 'Supplier', state.lookups.suppliers.map((s) => [s.id, s.name]), '', true)}${input('purchase_date', 'Purchase Date', today(), true, 'date')}`
    : `${input('customer_name', 'Customer Name', '', true)}${input('sale_date', 'Sale Date', today(), true, 'date')}`;
  const body = `<div class="form-grid">${head}${textarea('notes', 'Notes', '', 'full')}</div>
    <div style="margin-top:18px">
      <label>Items</label>
      <div id="lineRows"></div>
      <button type="button" class="btn btn-secondary btn-sm" id="addLineBtn">${icon('plus')} Add Item</button>
      <div class="total-box"><span>Total</span><span id="txnTotal">Rs 0.00</span></div>
    </div>`;
  showModal(`New ${isPurchase ? 'Purchase' : 'Sale'}`, body, async (form) => {
    const payload = Object.fromEntries(new FormData(form).entries());
    payload.lines = collectLines();
    await run(() => api.createTransaction({ type, payload }));
    closeModal();
    toast(`${isPurchase ? 'Purchase' : 'Sale'} saved.`);
    await renderList(isPurchase ? 'purchases' : 'sales');
  });
  document.getElementById('addLineBtn').addEventListener('click', () => addLineRow());
  addLineRow();
}

function itemOptions() {
  return `<option value="">Select item</option>${state.lookups.items.map((i) => `<option value="${i.id}" data-price="${i.price}" data-stock="${i.stock_qty}">${esc(i.name)} (${esc(i.unit)}) - Stock: ${i.stock_qty}</option>`).join('')}`;
}

function addLineRow() {
  const wrapper = document.getElementById('lineRows');
  const row = document.createElement('div');
  row.className = 'line-editor';
  row.innerHTML = `
    <select class="line-item" required>${itemOptions()}</select>
    <input class="line-qty" type="number" min="1" step="1" value="1" required>
    <input class="line-price" type="number" min="0" step="0.01" value="0.00" required>
    <input class="line-subtotal" readonly value="Rs 0.00">
    <button type="button" class="icon-btn danger">${icon('trash')}</button>`;
  wrapper.appendChild(row);
  const recalc = () => {
    const qty = Number(row.querySelector('.line-qty').value || 0);
    const price = Number(row.querySelector('.line-price').value || 0);
    row.querySelector('.line-subtotal').value = fmtMoney(qty * price);
    updateTransactionTotal();
  };
  row.querySelector('.line-item').addEventListener('change', (event) => {
    const option = event.target.options[event.target.selectedIndex];
    row.querySelector('.line-price').value = Number(option.dataset.price || 0).toFixed(2);
    recalc();
  });
  row.querySelector('.line-qty').addEventListener('input', recalc);
  row.querySelector('.line-price').addEventListener('input', recalc);
  row.querySelector('button').addEventListener('click', () => {
    if (wrapper.children.length > 1) row.remove();
    updateTransactionTotal();
  });
  recalc();
}

function collectLines() {
  return Array.from(document.querySelectorAll('.line-editor')).map((row) => ({
    item_id: row.querySelector('.line-item').value,
    qty: row.querySelector('.line-qty').value,
    unit_price: row.querySelector('.line-price').value
  }));
}

function updateTransactionTotal() {
  const total = collectLines().reduce((sum, line) => sum + Number(line.qty || 0) * Number(line.unit_price || 0), 0);
  const node = document.getElementById('txnTotal');
  if (node) node.textContent = fmtMoney(total);
}

async function openTransactionDetail(type, id) {
  const data = await run(() => api.transactionDetail({ type, id }));
  const h = data.header;
  const isPurchase = type === 'purchase';
  const body = `<div class="grid-2">
      <div><strong>${isPurchase ? 'Supplier' : 'Customer'}</strong><p>${esc(isPurchase ? h.supplier_name : h.customer_name)}</p></div>
      <div><strong>Date</strong><p>${esc(fmtDate(isPurchase ? h.purchase_date : h.sale_date))}</p></div>
      <div><strong>Total</strong><p>${esc(fmtMoney(h.total_amount))}</p></div>
      <div><strong>Created By</strong><p>${esc(h.created_by_name || '-')}</p></div>
    </div>
    <div class="table-wrap" style="margin-top:16px">${table(['Code', 'Item', 'Qty', 'Unit Price', 'Subtotal'], data.lines.map((line) => [
      line.item_code, line.name, line.qty, fmtMoney(line.unit_price), fmtMoney(Number(line.qty) * Number(line.unit_price))
    ]))}</div>`;
  showModal(`${isPurchase ? 'Purchase' : 'Sale'} #${id}`, body, () => {}, 'small');
  const footer = document.querySelector('.modal-footer');
  footer.innerHTML = '<button type="button" class="btn btn-primary" data-close>Close</button>';
  footer.querySelector('[data-close]').addEventListener('click', closeModal);
}

async function renderReports() {
  const dateFrom = today(-30);
  const dateTo = today();
  setMain(`<div class="page-header"><div><h2 class="page-title">Reports & Analytics</h2><p class="page-subtitle">Sales, purchases, profit and stock health.</p></div></div>
    <div class="toolbar">
      <div class="actions">
        <label>From</label><input type="date" id="dateFrom" value="${dateFrom}">
        <label>To</label><input type="date" id="dateTo" value="${dateTo}">
        <button class="btn btn-primary" id="applyReport">${icon('chart')} Apply</button>
      </div>
    </div>
    <div id="reportBody"></div>`);
  document.getElementById('applyReport').addEventListener('click', loadReports);
  await loadReports();
}

async function loadReports() {
  const dateFrom = document.getElementById('dateFrom').value;
  const dateTo = document.getElementById('dateTo').value;
  const data = await run(() => api.reports({ dateFrom, dateTo }));
  const net = Number(data.salesSummary.revenue) - Number(data.purchaseSummary.cost);
  document.getElementById('reportBody').innerHTML = `
    <div class="stats-grid">
      ${stat('Sales Revenue', fmtMoney(data.salesSummary.revenue), `${data.salesSummary.count} sales in period`, 'upload')}
      ${stat('Purchase Cost', fmtMoney(data.purchaseSummary.cost), `${data.purchaseSummary.count} purchases in period`, 'download')}
      ${stat('Net Profit', fmtMoney(net), 'Revenue minus purchases', 'chart')}
      ${stat('Low Stock Items', data.lowStock.length, 'Items need restocking', 'alert')}
    </div>
    ${panel('Daily Sales Revenue', chart(data.dailySales))}
    <div class="grid-2" style="margin-top:16px">
      ${panel('Top Selling Items', table(['Item', 'Qty Sold', 'Revenue'], data.topItems.map((i) => [i.name, i.total_qty, fmtMoney(i.total_revenue)])))}
      ${panel('Low Stock Alert', table(['Code', 'Item', 'Stock', 'Reorder'], data.lowStock.map((i) => [i.item_code, i.name, i.stock_qty, i.reorder_level])))}
    </div>`;
}

function chart(rows) {
  if (!rows.length) return '<p class="table-empty">No sales data for this period.</p>';
  const max = Math.max(...rows.map((r) => Number(r.amount)));
  return `<div class="report-chart">${rows.map((r) => `<div class="bar" title="${fmtMoney(r.amount)}" style="height:${Math.max(6, Number(r.amount) / max * 190)}px"><span>${esc(fmtDate(r.date).slice(5))}</span></div>`).join('')}</div>`;
}

renderLogin();

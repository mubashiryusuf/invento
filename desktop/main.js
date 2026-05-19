const { app, BrowserWindow, ipcMain, dialog } = require('electron');
const path = require('path');
const fs = require('fs/promises');
const bcrypt = require('bcryptjs');
const { createPool } = require('mysql2/promise');

const dbConfig = {
  host: process.env.INVENTRACK_DB_HOST || 'localhost',
  user: process.env.INVENTRACK_DB_USER || 'root',
  password: process.env.INVENTRACK_DB_PASS || '',
  database: process.env.INVENTRACK_DB_NAME || 'inventory_db',
  dateStrings: true,
  waitForConnections: true,
  connectionLimit: 10,
  namedPlaceholders: true,
  multipleStatements: false
};

let mainWindow;
let pool;
let currentUser = null;

function getPool() {
  if (!pool) pool = createPool(dbConfig);
  return pool;
}

function can(roleList) {
  return currentUser && roleList.includes(currentUser.role);
}

function requireUser(roles = ['admin', 'manager', 'staff']) {
  if (!currentUser) throw new Error('Please sign in again.');
  if (!roles.includes(currentUser.role)) throw new Error('You do not have permission for this action.');
}

function money(value) {
  return Number(value || 0).toFixed(2);
}

async function query(sql, params = {}) {
  const [rows] = await getPool().execute(sql, params);
  return rows;
}

async function tx(work) {
  const conn = await getPool().getConnection();
  try {
    await conn.beginTransaction();
    const result = await work(conn);
    await conn.commit();
    return result;
  } catch (error) {
    await conn.rollback();
    throw error;
  } finally {
    conn.release();
  }
}

async function logAction(userId, action, tableName, recordId, details = '') {
  await query(
    'INSERT INTO audit_logs (user_id, action, table_name, record_id, details) VALUES (:userId, :action, :tableName, :recordId, :details)',
    { userId, action: action.toUpperCase(), tableName, recordId, details }
  );
}

function cleanText(value) {
  return String(value ?? '').trim();
}

function cleanDate(value) {
  const text = cleanText(value);
  return text || null;
}

function normalizeHeader(header) {
  return String(header || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '_');
}

function parseCsv(text) {
  const rows = [];
  let row = [];
  let cell = '';
  let quoted = false;
  for (let i = 0; i < text.length; i += 1) {
    const char = text[i];
    const next = text[i + 1];
    if (quoted && char === '"' && next === '"') {
      cell += '"';
      i += 1;
    } else if (char === '"') {
      quoted = !quoted;
    } else if (!quoted && char === ',') {
      row.push(cell);
      cell = '';
    } else if (!quoted && (char === '\n' || char === '\r')) {
      if (char === '\r' && next === '\n') i += 1;
      row.push(cell);
      if (row.some((value) => String(value).trim() !== '')) rows.push(row);
      row = [];
      cell = '';
    } else {
      cell += char;
    }
  }
  row.push(cell);
  if (row.some((value) => String(value).trim() !== '')) rows.push(row);
  return rows;
}

function sqlError(error) {
  if (error && error.code === 'ER_DUP_ENTRY') return 'A record with the same unique value already exists.';
  if (error && /stock/i.test(error.message)) return 'Insufficient stock for one or more selected items.';
  return error.message || 'Unexpected database error.';
}

async function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1280,
    height: 820,
    minWidth: 1080,
    minHeight: 680,
    backgroundColor: '#f7f8fb',
    title: 'Stockly',
    icon: path.join(__dirname, '..', 'assets', 'images', 'material-management.png'),
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false
    }
  });

  await mainWindow.loadFile(path.join(__dirname, 'renderer', 'index.html'));
}

app.whenReady().then(createWindow);
app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});
app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) createWindow();
});

ipcMain.handle('auth:login', async (_event, credentials) => {
  const email = cleanText(credentials.email);
  const password = cleanText(credentials.password);
  if (!email || !password) throw new Error('Email and password are required.');

  const rows = await query(
    'SELECT id, name, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1',
    { email }
  );
  const user = rows[0];
  const hash = user ? String(user.password_hash).replace(/^\$2y\$/, () => '$2a$') : '';
  const ok = user && user.status === 'active' && bcrypt.compareSync(password, hash);
  if (!ok) {
    await logAction(null, 'LOGIN_FAILED', 'users', null, `Failed desktop login attempt for: ${email}`).catch(() => {});
    throw new Error('Invalid email or password.');
  }

  currentUser = { id: user.id, name: user.name, email: user.email, role: user.role };
  await logAction(user.id, 'LOGIN', 'users', user.id, `${user.name} logged in from desktop`);
  return currentUser;
});

ipcMain.handle('auth:logout', async () => {
  currentUser = null;
  return true;
});

ipcMain.handle('app:ping-db', async () => {
  await query('SELECT 1 AS ok');
  return { ok: true, database: dbConfig.database, host: dbConfig.host };
});

ipcMain.handle('data:dashboard', async () => {
  requireUser();
  const [stats] = await query(`
    SELECT
      (SELECT COUNT(*) FROM items) AS totalItems,
      (SELECT COUNT(*) FROM suppliers) AS totalSuppliers,
      (SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(sale_date)=CURDATE()) AS todaySales,
      (SELECT COUNT(*) FROM items WHERE stock_qty <= reorder_level) AS lowStockCount
  `);
  const lowStock = await query('SELECT id, item_code, name, stock_qty, reorder_level FROM items WHERE stock_qty <= reorder_level ORDER BY stock_qty ASC LIMIT 8');
  const sales = await query(`
    SELECT s.id, s.customer_name, s.sale_date, s.total_amount, u.name AS created_by
    FROM sales s LEFT JOIN users u ON u.id = s.created_by
    ORDER BY s.sale_date DESC, s.id DESC LIMIT 8
  `);
  const purchases = await query(`
    SELECT p.id, p.purchase_date, p.total_amount, su.name AS supplier_name, u.name AS created_by
    FROM purchases p
    LEFT JOIN suppliers su ON su.id = p.supplier_id
    LEFT JOIN users u ON u.id = p.created_by
    ORDER BY p.purchase_date DESC, p.id DESC LIMIT 8
  `);
  return { stats, lowStock, sales, purchases };
});

ipcMain.handle('data:list', async (_event, { entity, search = '', filters = {} }) => {
  requireUser();
  const q = `%${cleanText(search)}%`;
  const params = { q };
  const builders = {
    categories: () => ({
      roles: ['admin', 'manager', 'staff'],
      sql: `SELECT c.*, (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id) AS item_count
            FROM categories c WHERE c.name LIKE :q ORDER BY c.id ASC`
    }),
    suppliers: () => ({
      roles: ['admin', 'manager', 'staff'],
      sql: `SELECT s.*, (SELECT COUNT(*) FROM purchases p WHERE p.supplier_id = s.id) AS purchase_count
            FROM suppliers s
            WHERE s.name LIKE :q OR COALESCE(s.email,'') LIKE :q OR COALESCE(s.contact,'') LIKE :q
            ORDER BY s.id ASC`
    }),
    items: () => {
      const where = ['(i.name LIKE :q OR i.item_code LIKE :q OR c.name LIKE :q)'];
      if (Number(filters.categoryId || 0) > 0) {
        where.push('i.category_id = :categoryId');
        params.categoryId = Number(filters.categoryId);
      }
      return {
        roles: ['admin', 'manager', 'staff'],
        sql: `SELECT i.*, c.name AS category_name
              FROM items i LEFT JOIN categories c ON c.id = i.category_id
              WHERE ${where.join(' AND ')} ORDER BY i.id ASC`
      };
    },
    purchases: () => {
      const where = ['(s.name LIKE :q OR CAST(p.id AS CHAR) LIKE :q OR COALESCE(u.name,"") LIKE :q)'];
      if (cleanDate(filters.dateFrom)) {
        where.push('p.purchase_date >= :dateFrom');
        params.dateFrom = cleanDate(filters.dateFrom);
      }
      if (cleanDate(filters.dateTo)) {
        where.push('p.purchase_date <= :dateTo');
        params.dateTo = cleanDate(filters.dateTo);
      }
      return {
        roles: ['admin', 'manager', 'staff'],
        sql: `SELECT p.*, s.name AS supplier_name, u.name AS created_by_name
              FROM purchases p JOIN suppliers s ON s.id = p.supplier_id LEFT JOIN users u ON u.id = p.created_by
              WHERE ${where.join(' AND ')} ORDER BY p.id ASC`
      };
    },
    sales: () => {
      const where = ['(s.customer_name LIKE :q OR CAST(s.id AS CHAR) LIKE :q OR COALESCE(u.name,"") LIKE :q)'];
      if (cleanDate(filters.dateFrom)) {
        where.push('s.sale_date >= :dateFrom');
        params.dateFrom = cleanDate(filters.dateFrom);
      }
      if (cleanDate(filters.dateTo)) {
        where.push('s.sale_date <= :dateTo');
        params.dateTo = cleanDate(filters.dateTo);
      }
      return {
        roles: ['admin', 'manager', 'staff'],
        sql: `SELECT s.*, u.name AS created_by_name
              FROM sales s LEFT JOIN users u ON u.id = s.created_by
              WHERE ${where.join(' AND ')} ORDER BY s.id ASC`
      };
    },
    users: () => {
      const where = ['(name LIKE :q OR email LIKE :q OR role LIKE :q)'];
      if (['admin', 'manager', 'staff'].includes(cleanText(filters.role))) {
        where.push('role = :role');
        params.role = cleanText(filters.role);
      }
      return {
        roles: ['admin'],
        sql: `SELECT id, name, email, role, status, created_at FROM users
              WHERE ${where.join(' AND ')} ORDER BY id ASC`
      };
    },
    audit: () => {
      const where = ['(a.action LIKE :q OR a.table_name LIKE :q OR COALESCE(a.details,"") LIKE :q OR COALESCE(u.name,"") LIKE :q)'];
      if (Number(filters.userId || 0) > 0) {
        where.push('a.user_id = :userId');
        params.userId = Number(filters.userId);
      }
      if (cleanText(filters.action)) {
        where.push('a.action LIKE :action');
        params.action = `%${cleanText(filters.action)}%`;
      }
      if (cleanDate(filters.dateFrom)) {
        where.push('a.created_at >= :dateFrom');
        params.dateFrom = cleanDate(filters.dateFrom);
      }
      if (cleanDate(filters.dateTo)) {
        where.push('a.created_at <= :dateTo');
        params.dateTo = `${cleanDate(filters.dateTo)} 23:59:59`;
      }
      return {
        roles: ['admin'],
        sql: `SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
              WHERE ${where.join(' AND ')}
              ORDER BY a.id ASC LIMIT 300`
      };
    }
  };
  if (!builders[entity]) throw new Error('Unknown list requested.');
  const built = builders[entity]();
  requireUser(built.roles);
  return query(built.sql, params);
});

ipcMain.handle('data:lookups', async () => {
  requireUser();
  const [categories, suppliers, items, users] = await Promise.all([
    query('SELECT id, name FROM categories ORDER BY name'),
    query('SELECT id, name FROM suppliers ORDER BY name'),
    query('SELECT id, item_code, name, unit, price, stock_qty FROM items ORDER BY name'),
    currentUser.role === 'admin' ? query('SELECT id, name FROM users ORDER BY name') : Promise.resolve([])
  ]);
  return { categories, suppliers, items, users };
});

ipcMain.handle('data:save', async (_event, { entity, payload }) => {
  requireUser(entity === 'users' ? ['admin'] : ['admin', 'manager']);
  const p = payload || {};
  try {
    if (entity === 'categories') {
      const name = cleanText(p.name);
      if (!name) throw new Error('Category name is required.');
      if (p.id) {
        await query('UPDATE categories SET name=:name, description=:description WHERE id=:id', { id: p.id, name, description: cleanText(p.description) || null });
        await logAction(currentUser.id, 'UPDATE', 'categories', p.id, `Updated category: ${name}`);
        return { id: p.id };
      }
      const result = await query('INSERT INTO categories (name, description) VALUES (:name, :description)', { name, description: cleanText(p.description) || null });
      await logAction(currentUser.id, 'CREATE', 'categories', result.insertId, `Created category: ${name}`);
      return { id: result.insertId };
    }

    if (entity === 'suppliers') {
      const name = cleanText(p.name);
      if (!name) throw new Error('Supplier name is required.');
      const data = { id: p.id, name, contact: cleanText(p.contact) || null, email: cleanText(p.email) || null, address: cleanText(p.address) || null };
      if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) throw new Error('Invalid email address.');
      if (p.id) {
        await query('UPDATE suppliers SET name=:name, contact=:contact, email=:email, address=:address WHERE id=:id', data);
        await logAction(currentUser.id, 'UPDATE', 'suppliers', p.id, `Updated supplier: ${name}`);
        return { id: p.id };
      }
      const result = await query('INSERT INTO suppliers (name, contact, email, address) VALUES (:name, :contact, :email, :address)', data);
      await logAction(currentUser.id, 'CREATE', 'suppliers', result.insertId, `Created supplier: ${name}`);
      return { id: result.insertId };
    }

    if (entity === 'items') {
      const data = {
        id: p.id,
        item_code: cleanText(p.item_code),
        name: cleanText(p.name),
        category_id: Number(p.category_id || 0),
        unit: cleanText(p.unit),
        price: Number(p.price || 0),
        stock_qty: Number(p.stock_qty || 0),
        reorder_level: Number(p.reorder_level || 0),
        expiry_date: cleanDate(p.expiry_date)
      };
      if (!data.item_code || !data.name || !data.category_id || !data.unit) throw new Error('Item code, name, category and unit are required.');
      if (data.price < 0 || data.stock_qty < 0 || data.reorder_level < 0) throw new Error('Numeric values cannot be negative.');
      if (p.id) {
        await query(`UPDATE items SET item_code=:item_code, name=:name, category_id=:category_id, unit=:unit, price=:price,
          stock_qty=:stock_qty, reorder_level=:reorder_level, expiry_date=:expiry_date WHERE id=:id`, data);
        await logAction(currentUser.id, 'UPDATE', 'items', p.id, `Updated item: ${data.name}`);
        return { id: p.id };
      }
      const result = await query(`INSERT INTO items (item_code, name, category_id, unit, price, stock_qty, reorder_level, expiry_date)
        VALUES (:item_code, :name, :category_id, :unit, :price, :stock_qty, :reorder_level, :expiry_date)`, data);
      await logAction(currentUser.id, 'CREATE', 'items', result.insertId, `Created item: ${data.name}`);
      return { id: result.insertId };
    }

    if (entity === 'users') {
      requireUser(['admin']);
      const name = cleanText(p.name);
      const email = cleanText(p.email);
      const role = cleanText(p.role || 'staff');
      const status = cleanText(p.status || 'active');
      if (!name || !email) throw new Error('Name and email are required.');
      if (!['admin', 'manager', 'staff'].includes(role) || !['active', 'inactive'].includes(status)) throw new Error('Invalid role or status.');
      if (p.id) {
        const params = { id: p.id, name, email, role, status };
        await query('UPDATE users SET name=:name, email=:email, role=:role, status=:status WHERE id=:id', params);
        if (cleanText(p.password)) {
          if (cleanText(p.password).length < 8) throw new Error('Password must be at least 8 characters.');
          const passwordHash = bcrypt.hashSync(cleanText(p.password), 10);
          await query('UPDATE users SET password_hash=:passwordHash WHERE id=:id', { id: p.id, passwordHash });
        }
        await logAction(currentUser.id, 'UPDATE', 'users', p.id, `Updated user: ${name}`);
        return { id: p.id };
      }
      if (!cleanText(p.password) || cleanText(p.password).length < 8) throw new Error('Password must be at least 8 characters.');
      const passwordHash = bcrypt.hashSync(cleanText(p.password), 10);
      const result = await query(
        'INSERT INTO users (name, email, password_hash, role, status) VALUES (:name, :email, :passwordHash, :role, :status)',
        { name, email, passwordHash, role, status }
      );
      await logAction(currentUser.id, 'CREATE', 'users', result.insertId, `Created user: ${name} (${role})`);
      return { id: result.insertId };
    }

    throw new Error('Unknown save target.');
  } catch (error) {
    throw new Error(sqlError(error));
  }
});

ipcMain.handle('data:delete', async (_event, { entity, id }) => {
  requireUser(entity === 'users' ? ['admin'] : ['admin', 'manager']);
  const tables = { categories: 'categories', suppliers: 'suppliers', items: 'items', users: 'users' };
  const table = tables[entity];
  if (!table) throw new Error('Delete is not available for this record type.');
  if (entity === 'users' && Number(id) === Number(currentUser.id)) throw new Error('You cannot delete your own account while signed in.');
  try {
    await query(`DELETE FROM ${table} WHERE id=:id`, { id });
    await logAction(currentUser.id, 'DELETE', table, id, `Deleted ${table.slice(0, -1)} #${id}`);
    return true;
  } catch (error) {
    throw new Error('This record is linked to existing transactions and cannot be deleted.');
  }
});

ipcMain.handle('data:toggle-user', async (_event, { id }) => {
  requireUser(['admin']);
  if (Number(id) === Number(currentUser.id)) throw new Error('You cannot deactivate your own account while signed in.');
  const rows = await query('SELECT id, name, status FROM users WHERE id=:id LIMIT 1', { id });
  if (!rows.length) throw new Error('User not found.');
  const nextStatus = rows[0].status === 'active' ? 'inactive' : 'active';
  await query('UPDATE users SET status=:nextStatus WHERE id=:id', { id, nextStatus });
  await logAction(currentUser.id, 'TOGGLE_STATUS', 'users', id, `Changed ${rows[0].name} to ${nextStatus}`);
  return { id, status: nextStatus };
});

ipcMain.handle('data:create-transaction', async (_event, { type, payload }) => {
  requireUser(type === 'purchase' ? ['admin', 'manager'] : ['admin', 'manager', 'staff']);
  const lines = (payload.lines || [])
    .map((line) => ({ item_id: Number(line.item_id), qty: Number(line.qty), unit_price: Number(line.unit_price || 0) }))
    .filter((line) => line.item_id > 0 && line.qty > 0);
  if (!lines.length) throw new Error('At least one valid item row is required.');
  const total = lines.reduce((sum, line) => sum + line.qty * line.unit_price, 0);

  try {
    return await tx(async (conn) => {
      if (type === 'purchase') {
        const supplierId = Number(payload.supplier_id || 0);
        if (!supplierId || !cleanDate(payload.purchase_date)) throw new Error('Supplier and purchase date are required.');
        const [result] = await conn.execute(
          'INSERT INTO purchases (supplier_id, purchase_date, total_amount, notes, created_by) VALUES (:supplierId, :purchaseDate, :total, :notes, :createdBy)',
          { supplierId, purchaseDate: payload.purchase_date, total, notes: cleanText(payload.notes) || null, createdBy: currentUser.id }
        );
        for (const line of lines) {
          await conn.execute('INSERT INTO purchase_items (purchase_id, item_id, qty, unit_price) VALUES (:id, :item, :qty, :price)', {
            id: result.insertId, item: line.item_id, qty: line.qty, price: line.unit_price
          });
        }
        await conn.execute(
          'INSERT INTO audit_logs (user_id, action, table_name, record_id, details) VALUES (:user, :action, :tableName, :record, :details)',
          { user: currentUser.id, action: 'CREATE', tableName: 'purchases', record: result.insertId, details: `Purchase created, total: Rs ${money(total)}` }
        );
        return { id: result.insertId, total };
      }

      const customer = cleanText(payload.customer_name);
      if (!customer || !cleanDate(payload.sale_date)) throw new Error('Customer name and sale date are required.');
      const [result] = await conn.execute(
        'INSERT INTO sales (customer_name, sale_date, total_amount, notes, created_by) VALUES (:customer, :saleDate, :total, :notes, :createdBy)',
        { customer, saleDate: payload.sale_date, total, notes: cleanText(payload.notes) || null, createdBy: currentUser.id }
      );
      for (const line of lines) {
        await conn.execute('INSERT INTO sale_items (sale_id, item_id, qty, unit_price) VALUES (:id, :item, :qty, :price)', {
          id: result.insertId, item: line.item_id, qty: line.qty, price: line.unit_price
        });
      }
      await conn.execute(
        'INSERT INTO audit_logs (user_id, action, table_name, record_id, details) VALUES (:user, :action, :tableName, :record, :details)',
        { user: currentUser.id, action: 'CREATE', tableName: 'sales', record: result.insertId, details: `Sale to ${customer}, total: Rs ${money(total)}` }
      );
      return { id: result.insertId, total };
    });
  } catch (error) {
    throw new Error(sqlError(error));
  }
});

ipcMain.handle('data:transaction-detail', async (_event, { type, id }) => {
  requireUser();
  if (type === 'purchase') {
    const rows = await query(`
      SELECT p.*, s.name AS supplier_name, u.name AS created_by_name
      FROM purchases p JOIN suppliers s ON s.id = p.supplier_id LEFT JOIN users u ON u.id = p.created_by WHERE p.id=:id
    `, { id });
    const lines = await query(`
      SELECT pi.*, i.item_code, i.name, i.unit FROM purchase_items pi JOIN items i ON i.id = pi.item_id WHERE pi.purchase_id=:id
    `, { id });
    return { header: rows[0], lines };
  }
  const rows = await query(`
    SELECT s.*, u.name AS created_by_name FROM sales s LEFT JOIN users u ON u.id = s.created_by WHERE s.id=:id
  `, { id });
  const lines = await query(`
    SELECT si.*, i.item_code, i.name, i.unit FROM sale_items si JOIN items i ON i.id = si.item_id WHERE si.sale_id=:id
  `, { id });
  return { header: rows[0], lines };
});

ipcMain.handle('data:reports', async (_event, { dateFrom, dateTo }) => {
  requireUser(['admin', 'manager']);
  const date_from = cleanDate(dateFrom);
  const date_to = cleanDate(dateTo);
  const [salesSummary] = await query('SELECT COALESCE(SUM(total_amount),0) AS revenue, COUNT(*) AS count FROM sales WHERE sale_date BETWEEN :date_from AND :date_to', { date_from, date_to });
  const [purchaseSummary] = await query('SELECT COALESCE(SUM(total_amount),0) AS cost, COUNT(*) AS count FROM purchases WHERE purchase_date BETWEEN :date_from AND :date_to', { date_from, date_to });
  const dailySales = await query('SELECT sale_date AS date, SUM(total_amount) AS amount FROM sales WHERE sale_date BETWEEN :date_from AND :date_to GROUP BY sale_date ORDER BY sale_date ASC', { date_from, date_to });
  const topItems = await query(`
    SELECT i.name, SUM(si.qty) AS total_qty, SUM(si.qty * si.unit_price) AS total_revenue
    FROM sale_items si JOIN items i ON i.id = si.item_id JOIN sales s ON s.id = si.sale_id
    WHERE s.sale_date BETWEEN :date_from AND :date_to GROUP BY si.item_id, i.name ORDER BY total_qty DESC LIMIT 10
  `, { date_from, date_to });
  const lowStock = await query('SELECT item_code, name, stock_qty, reorder_level, unit FROM items WHERE stock_qty <= reorder_level ORDER BY stock_qty ASC');
  return { salesSummary, purchaseSummary, dailySales, topItems, lowStock };
});

ipcMain.handle('file:export-csv', async (_event, { entity }) => {
  requireUser(['admin', 'manager']);
  const exports = {
    items: {
      headers: ['item_code', 'name', 'category', 'unit', 'price', 'stock_qty', 'reorder_level', 'expiry_date'],
      sql: `SELECT i.item_code, i.name, COALESCE(c.name,'') AS category, i.unit, i.price, i.stock_qty, i.reorder_level, COALESCE(i.expiry_date,'') AS expiry_date
            FROM items i LEFT JOIN categories c ON c.id = i.category_id ORDER BY i.name`
    },
    suppliers: {
      headers: ['name', 'contact', 'email', 'address'],
      sql: `SELECT name, COALESCE(contact,'') AS contact, COALESCE(email,'') AS email, COALESCE(address,'') AS address FROM suppliers ORDER BY name`
    },
    sales: {
      headers: ['id', 'customer_name', 'sale_date', 'total_amount', 'created_by'],
      sql: `SELECT s.id, s.customer_name, s.sale_date, s.total_amount, COALESCE(u.name,'N/A') AS created_by
            FROM sales s LEFT JOIN users u ON u.id = s.created_by ORDER BY s.sale_date DESC`
    },
    purchases: {
      headers: ['id', 'supplier_name', 'purchase_date', 'total_amount', 'created_by'],
      sql: `SELECT p.id, COALESCE(s.name,'N/A') AS supplier_name, p.purchase_date, p.total_amount, COALESCE(u.name,'N/A') AS created_by
            FROM purchases p LEFT JOIN suppliers s ON s.id = p.supplier_id LEFT JOIN users u ON u.id = p.created_by ORDER BY p.purchase_date DESC`
    }
  };
  if (!exports[entity]) throw new Error('This section cannot be exported.');
  const rows = await query(exports[entity].sql);
  const headers = exports[entity].headers;
  const csv = [headers.join(',')]
    .concat(rows.map((row) => headers.map((h) => `"${String(row[h] ?? '').replace(/"/g, '""')}"`).join(',')))
    .join('\n');
  const result = await dialog.showSaveDialog(mainWindow, {
    defaultPath: `${entity}-${new Date().toISOString().slice(0, 10)}.csv`,
    filters: [{ name: 'CSV Files', extensions: ['csv'] }]
  });
  if (result.canceled || !result.filePath) return { canceled: true };
  await fs.writeFile(result.filePath, csv, 'utf8');
  await logAction(currentUser.id, 'EXPORT', entity, null, `Exported ${entity} CSV from desktop`);
  return { path: result.filePath };
});

ipcMain.handle('file:import-csv', async (_event, { entity }) => {
  requireUser(['admin', 'manager']);
  if (!['items', 'suppliers'].includes(entity)) throw new Error('CSV import is available for items and suppliers.');
  const selected = await dialog.showOpenDialog(mainWindow, {
    filters: [{ name: 'CSV Files', extensions: ['csv'] }],
    properties: ['openFile']
  });
  if (selected.canceled || !selected.filePaths[0]) return { canceled: true };

  const text = await fs.readFile(selected.filePaths[0], 'utf8');
  const rows = parseCsv(text);
  if (!rows.length) throw new Error('The CSV file is empty.');
  const headers = rows.shift().map(normalizeHeader);
  const required = entity === 'items'
    ? ['item_code', 'name', 'category', 'unit', 'price', 'stock_qty', 'reorder_level']
    : ['name'];
  for (const header of required) {
    if (!headers.includes(header)) throw new Error(`Missing required CSV column: ${header}`);
  }

  const result = await tx(async (conn) => {
    let imported = 0;
    let skipped = 0;
    const errors = [];

    if (entity === 'items') {
      const [categoryRows] = await conn.execute('SELECT id, name FROM categories');
      const categories = new Map(categoryRows.map((category) => [String(category.name).trim().toLowerCase(), category.id]));
      for (let index = 0; index < rows.length; index += 1) {
        const rowNumber = index + 2;
        const data = Object.fromEntries(headers.map((header, column) => [header, cleanText(rows[index][column])]));
        const price = Number(data.price);
        const stockQty = Number.parseInt(data.stock_qty, 10);
        const reorderLevel = Number.parseInt(data.reorder_level, 10);
        if (!data.item_code || !data.name || !data.category || !data.unit) {
          skipped += 1;
          errors.push(`Row ${rowNumber}: item_code, name, category and unit are required.`);
          continue;
        }
        if (Number.isNaN(price) || price < 0 || Number.isNaN(stockQty) || stockQty < 0 || Number.isNaN(reorderLevel) || reorderLevel < 0) {
          skipped += 1;
          errors.push(`Row ${rowNumber}: numeric fields must be non-negative.`);
          continue;
        }
        const [existing] = await conn.execute('SELECT id FROM items WHERE item_code=:itemCode LIMIT 1', { itemCode: data.item_code });
        if (existing.length) {
          skipped += 1;
          errors.push(`Row ${rowNumber}: item code ${data.item_code} already exists.`);
          continue;
        }
        const categoryKey = data.category.toLowerCase();
        if (!categories.has(categoryKey)) {
          const [created] = await conn.execute('INSERT INTO categories (name, description) VALUES (:name, :description)', {
            name: data.category,
            description: 'Auto-created from CSV import'
          });
          categories.set(categoryKey, created.insertId);
        }
        await conn.execute(
          `INSERT INTO items (item_code, name, category_id, unit, price, stock_qty, reorder_level, expiry_date)
           VALUES (:itemCode, :name, :categoryId, :unit, :price, :stockQty, :reorderLevel, :expiryDate)`,
          {
            itemCode: data.item_code,
            name: data.name,
            categoryId: categories.get(categoryKey),
            unit: data.unit,
            price,
            stockQty,
            reorderLevel,
            expiryDate: cleanDate(data.expiry_date)
          }
        );
        imported += 1;
      }
    } else {
      for (let index = 0; index < rows.length; index += 1) {
        const rowNumber = index + 2;
        const data = Object.fromEntries(headers.map((header, column) => [header, cleanText(rows[index][column])]));
        if (!data.name) {
          skipped += 1;
          errors.push(`Row ${rowNumber}: supplier name is required.`);
          continue;
        }
        if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
          skipped += 1;
          errors.push(`Row ${rowNumber}: supplier email is invalid.`);
          continue;
        }
        await conn.execute(
          'INSERT INTO suppliers (name, contact, email, address) VALUES (:name, :contact, :email, :address)',
          { name: data.name, contact: data.contact || null, email: data.email || null, address: data.address || null }
        );
        imported += 1;
      }
    }

    await conn.execute(
      'INSERT INTO audit_logs (user_id, action, table_name, record_id, details) VALUES (:user, :action, :tableName, :record, :details)',
      { user: currentUser.id, action: 'IMPORT', tableName: entity, record: null, details: `CSV import completed: ${imported} imported, ${skipped} skipped.` }
    );
    return { imported, skipped, errors: errors.slice(0, 5), path: selected.filePaths[0] };
  });

  return result;
});

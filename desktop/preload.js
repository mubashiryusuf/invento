const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('inventrack', {
  login: (credentials) => ipcRenderer.invoke('auth:login', credentials),
  logout: () => ipcRenderer.invoke('auth:logout'),
  pingDb: () => ipcRenderer.invoke('app:ping-db'),
  dashboard: () => ipcRenderer.invoke('data:dashboard'),
  list: (options) => ipcRenderer.invoke('data:list', options),
  lookups: () => ipcRenderer.invoke('data:lookups'),
  save: (options) => ipcRenderer.invoke('data:save', options),
  delete: (options) => ipcRenderer.invoke('data:delete', options),
  toggleUser: (options) => ipcRenderer.invoke('data:toggle-user', options),
  createTransaction: (options) => ipcRenderer.invoke('data:create-transaction', options),
  transactionDetail: (options) => ipcRenderer.invoke('data:transaction-detail', options),
  reports: (options) => ipcRenderer.invoke('data:reports', options),
  exportCsv: (options) => ipcRenderer.invoke('file:export-csv', options),
  importCsv: (options) => ipcRenderer.invoke('file:import-csv', options)
});

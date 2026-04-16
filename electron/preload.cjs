const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electronAPI', {
  updateNotificationCount: (count) => ipcRenderer.send('notification-count', count),
  showNotification: (data) => ipcRenderer.send('show-notification', data),
  onWindowShown: (callback) => ipcRenderer.on('window-shown', callback),
});

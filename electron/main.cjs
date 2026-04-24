const { app, BrowserWindow, Tray, Menu, ipcMain, nativeImage, Notification: ElectronNotification } = require('electron');
const path = require('path');
const http = require('http');
const fs = require('fs');
const zlib = require('zlib');
const { autoUpdater } = require('electron-updater');

app.commandLine.appendSwitch('autoplay-policy', 'no-user-gesture-required');

const notificationSoundPath = path.join(__dirname, 'sounds', 'notification.wav');
let soundWindow = null;
let notificationSoundDataUrl = null;

function loadNotificationSound() {
  if (fs.existsSync(notificationSoundPath)) {
    const b64 = fs.readFileSync(notificationSoundPath).toString('base64');
    notificationSoundDataUrl = `data:audio/wav;base64,${b64}`;
  }
}

function createSoundWindow() {
  if (soundWindow && !soundWindow.isDestroyed()) return;
  soundWindow = new BrowserWindow({
    show: false,
    webPreferences: { nodeIntegration: false, contextIsolation: true, backgroundThrottling: false },
  });
  soundWindow.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent('<!doctype html><html><body><script>window.__audio=null;</script></body></html>'));
}

function playNotificationSound() {
  if (!notificationSoundDataUrl) return;
  if (!soundWindow || soundWindow.isDestroyed()) createSoundWindow();
  const url = notificationSoundDataUrl;
  const js = `(() => {
    if (!window.__audio) window.__audio = new Audio(${JSON.stringify(url)});
    window.__audio.currentTime = 0;
    const p = window.__audio.play();
  })();`;
  soundWindow.webContents.executeJavaScript(js);
}

const APP_URL = "https://github.lucasvanbriemen.nl/";
const PROTOCOL = 'githubgui';

app.setAppUserModelId('nl.ltvb.github-gui');

let mainWindow = null;
let tray = null;
let flashInterval = null;
let notificationCount = 0;
let iconPath = null;
let pendingDeepLink = null;

// Single instance lock
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
  app.quit();
}

function registerProtocol() {
  if (process.defaultApp) {
    if (process.argv.length >= 2) {
      app.setAsDefaultProtocolClient(PROTOCOL, process.execPath, [path.resolve(process.argv[1])]);
    }
  } else {
    app.setAsDefaultProtocolClient(PROTOCOL);
  }
}

function parseDeepLink(argv) {
  const url = (argv || []).find((a) => typeof a === 'string' && a.startsWith(`${PROTOCOL}://`));
  if (!url) return null;
  try {
    const u = new URL(url);
    const encoded = u.searchParams.get('u');
    if (encoded === null) return '';
    return encoded;
  } catch {
    return null;
  }
}

function handleDeepLink(relativeUrl) {
  if (!mainWindow) return;
  if (relativeUrl) {
    const target = APP_URL.replace(/\/$/, '') + (relativeUrl.startsWith('/') ? relativeUrl : `/${relativeUrl}`);
    try {
      const curr = new URL(mainWindow.webContents.getURL());
      const tgt = new URL(target);
      if (curr.origin === tgt.origin && curr.pathname === tgt.pathname) {
        mainWindow.webContents.executeJavaScript(`window.location.hash = ${JSON.stringify(tgt.hash)};`);
      } else {
        mainWindow.loadURL(target);
      }
    } catch {
      mainWindow.loadURL(target);
    }
  }
  focusWindow();
}

app.on('second-instance', (_event, argv) => {
  const link = parseDeepLink(argv);
  if (link !== null) {
    handleDeepLink(link);
    return;
  }
  focusWindow();
});

app.on('open-url', (event, url) => {
  event.preventDefault();
  const link = parseDeepLink([url]);
  if (link === null) return;
  if (mainWindow) {
    handleDeepLink(link);
  } else {
    pendingDeepLink = link;
  }
});

registerProtocol();

function crc32(buf) {
  let crc = 0xFFFFFFFF;
  for (let i = 0; i < buf.length; i++) {
    crc ^= buf[i];
    for (let j = 0; j < 8; j++) {
      crc = (crc >>> 1) ^ (crc & 1 ? 0xEDB88320 : 0);
    }
  }
  return (crc ^ 0xFFFFFFFF) >>> 0;
}

function buildPng(width, height, rgba) {
  const signature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);

  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(width, 0);
  ihdr.writeUInt32BE(height, 4);
  ihdr[8] = 8;  // bit depth
  ihdr[9] = 6;  // RGBA
  ihdr[10] = 0; // compression
  ihdr[11] = 0; // filter
  ihdr[12] = 0; // interlace

  const rowBytes = width * 4;
  const raw = Buffer.alloc(height * (1 + rowBytes));
  for (let y = 0; y < height; y++) {
    raw[y * (1 + rowBytes)] = 0;
    rgba.copy(raw, y * (1 + rowBytes) + 1, y * rowBytes, (y + 1) * rowBytes);
  }

  const compressed = zlib.deflateSync(raw);

  function chunk(type, data) {
    const t = Buffer.from(type, 'ascii');
    const len = Buffer.alloc(4);
    len.writeUInt32BE(data.length);
    const c = Buffer.alloc(4);
    c.writeUInt32BE(crc32(Buffer.concat([t, data])));
    return Buffer.concat([len, t, data, c]);
  }

  return Buffer.concat([
    signature,
    chunk('IHDR', ihdr),
    chunk('IDAT', compressed),
    chunk('IEND', Buffer.alloc(0)),
  ]);
}

function generateIcon() {
  const bundled = path.join(__dirname, 'icon.png');
  if (fs.existsSync(bundled)) {
    iconPath = bundled;
    return;
  }

  const dest = path.join(app.getPath('userData'), 'icon.png');
  if (fs.existsSync(dest)) {
    iconPath = dest;
    return;
  }

  const size = 256;
  const rgba = Buffer.alloc(size * size * 4);
  const cx = size / 2, cy = size / 2, r = size / 2 - 2;

  for (let y = 0; y < size; y++) {
    for (let x = 0; x < size; x++) {
      const i = (y * size + x) * 4;
      const dist = Math.sqrt((x - cx) ** 2 + (y - cy) ** 2);
      if (dist <= r) {
        const edge = Math.min(1, r - dist + 0.5);
        rgba[i] = 45; rgba[i + 1] = 45; rgba[i + 2] = 45;
        rgba[i + 3] = Math.round(edge * 255);
      }
    }
  }

  fs.writeFileSync(dest, buildPng(size, size, rgba));
  iconPath = dest;
}

function getIcon() {
  return nativeImage.createFromPath(iconPath);
}

const DIGITS = {
  '0': [0b111, 0b101, 0b101, 0b101, 0b111],
  '1': [0b010, 0b110, 0b010, 0b010, 0b111],
  '2': [0b111, 0b001, 0b111, 0b100, 0b111],
  '3': [0b111, 0b001, 0b111, 0b001, 0b111],
  '4': [0b101, 0b101, 0b111, 0b001, 0b001],
  '5': [0b111, 0b100, 0b111, 0b001, 0b111],
  '6': [0b111, 0b100, 0b111, 0b101, 0b111],
  '7': [0b111, 0b001, 0b010, 0b010, 0b010],
  '8': [0b111, 0b101, 0b111, 0b101, 0b111],
  '9': [0b111, 0b101, 0b111, 0b001, 0b111],
};

function createOverlayBadge(count) {
  const size = 16;
  const text = count > 99 ? '99' : String(count);
  const scale = text.length === 1 ? 3 : 2;
  const charW = 3 * scale;
  const charH = 5 * scale;
  const gap = 1;
  const totalW = text.length * charW + (text.length - 1) * gap;
  const startX = Math.floor((size - totalW) / 2);
  const startY = Math.floor((size - charH) / 2);

  const buffer = Buffer.alloc(size * size * 4);

  // Red square background (fills entire 16x16 for max visibility)
  for (let y = 0; y < size; y++) {
    for (let x = 0; x < size; x++) {
      const i = (y * size + x) * 4;
      buffer[i] = 58; buffer[i + 1] = 68; buffer[i + 2] = 239;
      buffer[i + 3] = 255;
    }
  }

  // White digits
  for (let c = 0; c < text.length; c++) {
    const pattern = DIGITS[text[c]];
    if (!pattern) continue;
    const ox = startX + c * (charW + gap);
    for (let py = 0; py < 5; py++) {
      for (let px = 0; px < 3; px++) {
        if (!(pattern[py] & (4 >> px))) continue;
        for (let sy = 0; sy < scale; sy++) {
          for (let sx = 0; sx < scale; sx++) {
            const x = ox + px * scale + sx;
            const y = startY + py * scale + sy;
            if (x >= 0 && x < size && y >= 0 && y < size) {
              const i = (y * size + x) * 4;
              buffer[i] = 255; buffer[i + 1] = 255;
              buffer[i + 2] = 255; buffer[i + 3] = 255;
            }
          }
        }
      }
    }
  }

  return nativeImage.createFromBitmap(buffer, { width: size, height: size });
}

function focusWindow() {
  if (!mainWindow) return;
  mainWindow.show();
  if (mainWindow.isMinimized()) mainWindow.restore();
  mainWindow.focus();
}

function checkForUpdates() {
  autoUpdater.logger = { info: console.log, warn: console.warn, error: console.error };
  autoUpdater.autoDownload = true;
  autoUpdater.autoInstallOnAppQuit = true;

  autoUpdater.on('update-available', (info) => {
    if (ElectronNotification.isSupported()) {
      new ElectronNotification({
        title: 'GitHub GUI',
        body: `Downloading update v${info.version}...`,
        silent: true,
      }).show();
    }
  });

  autoUpdater.on('update-downloaded', (info) => {
    if (ElectronNotification.isSupported()) {
      const n = new ElectronNotification({
        title: 'GitHub GUI',
        body: `Update v${info.version} ready - click to restart`,
        silent: false,
      });
      n.on('click', () => autoUpdater.quitAndInstall());
      n.show();
    }
  });
}

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    icon: getIcon(),
    title: 'GitHub GUI',
    webPreferences: {
      preload: path.join(__dirname, 'preload.cjs'),
      nodeIntegration: false,
      contextIsolation: true,
    },
    show: false,
  });

  mainWindow.setMenuBarVisibility(false);
  mainWindow.loadURL(APP_URL);

  mainWindow.once('ready-to-show', () => mainWindow.show());

  mainWindow.on('close', (event) => {
    if (!app.isQuitting) {
      event.preventDefault();
      mainWindow.hide();
    }
  });

  mainWindow.on('focus', () => {
    mainWindow.flashFrame(false);
    stopTrayFlash();
  });

  mainWindow.on('show', () => {
    mainWindow.webContents.send('window-shown');
  });
}

function createTray() {
  tray = new Tray(getIcon());
  tray.setToolTip('GitHub GUI');
  updateTrayMenu();

  tray.on('click', () => focusWindow());
}

function updateTrayMenu() {
  if (!tray || tray.isDestroyed()) return;

  const label = notificationCount > 0
    ? `GitHub GUI (${notificationCount})`
    : 'GitHub GUI';

  const tooltip = notificationCount > 0
    ? `GitHub GUI \u2013 ${notificationCount} notification${notificationCount !== 1 ? 's' : ''}`
    : 'GitHub GUI';

  tray.setContextMenu(Menu.buildFromTemplate([
    { label, enabled: false },
    { type: 'separator' },
    { label: 'Open', click: () => focusWindow() },
    { type: 'separator' },
    { label: 'Quit', click: () => { app.isQuitting = true; app.quit(); } },
  ]));
  tray.setToolTip(tooltip);
}

function startTrayFlash() {
  if (flashInterval) return;

  const normalIcon = getIcon();
  const emptyIcon = nativeImage.createEmpty();
  let visible = true;

  flashInterval = setInterval(() => {
    if (!tray || tray.isDestroyed()) return;
    visible = !visible;
    tray.setImage(visible ? normalIcon : emptyIcon);
  }, 500);
}

function stopTrayFlash() {
  if (flashInterval) {
    clearInterval(flashInterval);
    flashInterval = null;
  }
  if (tray && !tray.isDestroyed()) {
    tray.setImage(getIcon());
  }
}

function handleNotificationUpdate(count) {
  const increased = count > notificationCount;
  notificationCount = count;

  updateTrayMenu();

  if (count > 0) {
    try {
      mainWindow?.setOverlayIcon(createOverlayBadge(count), `${count} notifications`);
    } catch {}

    if (increased && mainWindow) {
      mainWindow.flashFrame(true);
      startTrayFlash();
    }
  } else {
    try { mainWindow?.setOverlayIcon(null, ''); } catch {}
    mainWindow?.flashFrame(false);
    stopTrayFlash();
  }
}

function escapeXml(str) {
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function showDetailedNotification(data) {
  if (!ElectronNotification.isSupported()) return;

  const body = data.subject || 'New notification';

  const iconSrc = iconPath ? `file:///${iconPath.replace(/\\/g, '/')}` : '';
  const imageXml = iconSrc ? `<image placement="appLogoOverride" src="${escapeXml(iconSrc)}"/>` : '';
  const toastXml = `<?xml version="1.0" encoding="utf-8"?>
<toast scenario="reminder" activationType="foreground" launch="focus">
  <visual>
    <binding template="ToastGeneric">
      ${imageXml}
      <text>GitHub GUI</text>
      <text>${escapeXml(body)}</text>
    </binding>
  </visual>
  <audio silent="true"/>
  <actions>
    <action content="Open" activationType="foreground" arguments="focus"/>
    <action content="Dismiss" activationType="system" arguments="dismiss"/>
  </actions>
</toast>`;
  const notification = new ElectronNotification({ toastXml });

  notification.on('click', () => focusWindow());
  notification.on('action', () => focusWindow());
  notification.show();
  playNotificationSound();
}

ipcMain.on('notification-count', (_event, count) => {
  handleNotificationUpdate(count);
});

ipcMain.on('show-notification', (_event, data) => {
  showDetailedNotification(data);
});

function configureAutoStart() {
  if (app.isPackaged) {
    app.setLoginItemSettings({ openAtLogin: true });
  } else {
    app.setLoginItemSettings({
      openAtLogin: true,
      path: process.execPath,
      args: [path.resolve(__dirname, '..')]
    });
  }
}

app.whenReady().then(async () => {
  try {
    generateIcon();
    configureAutoStart();
    checkForUpdates();

    const startupLink = parseDeepLink(process.argv);

    createWindow();
    createTray();
    loadNotificationSound();
    createSoundWindow();

    const initialLink = pendingDeepLink ?? startupLink;
    if (initialLink) {
      pendingDeepLink = null;
      mainWindow.webContents.once('did-finish-load', () => handleDeepLink(initialLink));
    }
  } catch (e) {
    app.quit();
  }
});

app.on('window-all-closed', () => {
  // Keep running in tray
});

app.on('before-quit', () => {
  app.isQuitting = true;
});

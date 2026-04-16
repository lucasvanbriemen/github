const { app, BrowserWindow, Notification } = require('electron');
const path = require('path');
const fs = require('fs');

app.setAppUserModelId('nl.ltvb.github-gui');
app.commandLine.appendSwitch('autoplay-policy', 'no-user-gesture-required');

function escapeXml(str) {
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const soundPath = path.join(__dirname, '..', 'electron', 'sounds', 'notification.wav');
let soundWindow = null;
let dataUrl = null;

function loadWav() {
  const b64 = fs.readFileSync(soundPath).toString('base64');
  dataUrl = `data:audio/wav;base64,${b64}`;
  console.log('[sound] loaded', b64.length, 'b64 chars');
}

function createSoundWin() {
  soundWindow = new BrowserWindow({
    show: false,
    webPreferences: { nodeIntegration: false, contextIsolation: true, backgroundThrottling: false },
  });
  soundWindow.webContents.on('console-message', (_e, _l, msg) => console.log('[soundwin]', msg));
  soundWindow.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent('<!doctype html><html><body><script>window.__audio=null;</script></body></html>'));
  return new Promise((resolve) => soundWindow.webContents.once('did-finish-load', resolve));
}

function playSound() {
  if (!dataUrl) return;
  const js = `(() => {
    try {
      if (!window.__audio) window.__audio = new Audio(${JSON.stringify(dataUrl)});
      window.__audio.currentTime = 0;
      const p = window.__audio.play();
      if (p && p.then) p.then(() => console.log('play resolved')).catch(e => console.log('play error', e && e.message));
    } catch (e) { console.log('threw', e && e.message); }
  })();`;
  soundWindow.webContents.executeJavaScript(js)
    .then(() => console.log('[sound] exec ok'))
    .catch(e => console.log('[sound] exec err:', e));
}

app.whenReady().then(async () => {
  loadWav();
  await createSoundWin();

  const iconPath = 'C:\\Users\\vanbr\\AppData\\Roaming\\github-gui\\icon.png';
  const iconSrc = `file:///${iconPath.replace(/\\/g, '/')}`;
  const body = 'Custom sound + persistent toast';

  const toastXml = `<?xml version="1.0" encoding="utf-8"?>
<toast scenario="reminder" activationType="foreground" launch="focus">
  <visual>
    <binding template="ToastGeneric">
      <image placement="appLogoOverride" src="${escapeXml(iconSrc)}"/>
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

  const n = new Notification({ toastXml });
  n.on('show', () => console.log('[test] displayed'));
  n.on('failed', (e) => console.log('[test] FAILED:', e));
  n.on('close', () => { console.log('[test] closed'); app.quit(); });
  n.on('click', () => { console.log('[test] clicked'); app.quit(); });
  n.on('action', (_e, idx) => { console.log('[test] action:', idx); app.quit(); });
  n.show();
  playSound();

  setTimeout(() => { console.log('[test] 60s timeout'); app.quit(); }, 60000);
});

app.on('window-all-closed', (e) => e.preventDefault());

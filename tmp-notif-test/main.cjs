const { app, Notification } = require('electron');
const path = require('path');
const { spawn } = require('child_process');

app.setAppUserModelId('nl.ltvb.github-gui');

function escapeXml(str) {
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const soundPath = path.join(__dirname, '..', 'electron', 'sounds', 'notification.wav');

function playSound() {
  const cmd = `(New-Object Media.SoundPlayer '${soundPath.replace(/'/g, "''")}').PlaySync()`;
  console.log('[sound] spawning powershell; path:', soundPath);
  const child = spawn('powershell.exe', ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', cmd], {
    windowsHide: true,
  });
  child.stdout.on('data', (d) => console.log('[sound stdout]', d.toString()));
  child.stderr.on('data', (d) => console.log('[sound stderr]', d.toString()));
  child.on('error', (e) => console.log('[sound] spawn error:', e));
  child.on('exit', (code) => console.log('[sound] exit code:', code));
}

app.whenReady().then(() => {
  const iconPath = 'C:\\Users\\vanbr\\AppData\\Roaming\\github-gui\\icon.png';
  const iconSrc = `file:///${iconPath.replace(/\\/g, '/')}`;
  const body = 'Testing custom sound + persistent toast';

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

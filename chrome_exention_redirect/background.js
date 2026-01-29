// URLs that should NEVER redirect for this session
const stayUrls = new Set();

// URLs that were checked and returned no redirect
const checkedUrls = new Set();

function normalizeUrl(url) {
  const u = new URL(url);
  u.search = '';
  u.hash = '';
  return u.toString();
}

chrome.webNavigation.onBeforeNavigate.addListener(async (details) => {
  if (details.frameId !== 0) return;

  const url = details.url;

  try { console.debug('[GH-Redirect] onBeforeNavigate', url); } catch { }

  if (!url.startsWith('https://github.com/') && !url.startsWith('http://github.com/')) {
    return;
  }

  const urlObj = new URL(url);
  const normalizedUrl = normalizeUrl(url);

  // If ?stay=1 is present, lock this page for the entire session
  if (urlObj.searchParams.get('stay') === '1') {
    stayUrls.add(normalizedUrl);
    checkedUrls.add(normalizedUrl);
    try { console.debug('[GH-Redirect] stay=1 detected. Locking page for session'); } catch { }
    return;
  }

  // If this page was previously locked, always skip
  if (stayUrls.has(normalizedUrl)) {
    try { console.debug('[GH-Redirect] Page locked by prior stay=1'); } catch { }
    return;
  }

  // Skip if ?redirect=false
  if (urlObj.searchParams.get('redirect') === 'false') {
    checkedUrls.add(normalizedUrl);
    return;
  }

  // Skip if already checked
  if (checkedUrls.has(normalizedUrl)) {
    return;
  }

  try {
    const endpoint = 'https://github.lucasvanbriemen.nl/api/check_end_point';
    try { console.debug('[GH-Redirect] POST', endpoint, { url }); } catch { }

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ url })
    });

    const data = await response.json();

    if (data.redirect === true && data.URL) {
      try { console.debug('[GH-Redirect] Redirecting to', data.URL); } catch { }
      chrome.tabs.update(details.tabId, { url: data.URL });
    } else {
      checkedUrls.add(normalizedUrl);
    }
  } catch (error) {
    console.error('GitHub Redirect extension error:', error);
  }
}, {
  url: [{ hostContains: 'github.com' }]
});

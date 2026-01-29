const stayTabs = new Set();

chrome.webNavigation.onBeforeNavigate.addListener(async (details) => {
  if (details.frameId !== 0) return;

  const url = details.url;

  if (!url.startsWith('https://github.com/')) {
    return;
  }

  const urlObj = new URL(url);

  // Per-tab stay toggle via ?stay=1 / ?stay=0
  const stayParam = urlObj.searchParams.get('stay');
  if (stayParam === '1') {
    stayTabs.add(details.tabId);
  }
  
  // If this tab is marked to stay, skip redirect logic
  if (stayTabs.has(details.tabId)) {
    return;
  }

  try {
    const response = await fetch(
      'https://github.lucasvanbriemen.nl/api/check_end_point',
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url })
      }
    );

    const data = await response.json();

    if (data.redirect === true && data.URL) {
      chrome.tabs.update(details.tabId, { url: data.URL });
    }
  } catch (error) {
    console.error('GitHub Redirect extension error:', error);
  }
}, {
  url: [{ hostContains: 'github.com' }]
});

// Cleanup stay flag when tab closes
chrome.tabs.onRemoved.addListener((tabId) => {
  stayTabs.delete(tabId);
});

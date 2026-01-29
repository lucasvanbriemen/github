const stayTabs = new Set();

chrome.webNavigation.onBeforeNavigate.addListener(async (details) => {
  if (details.frameId !== 0) return;

  const url = details.url;
  const urlObj = new URL(url);
  const stayParam = urlObj.searchParams.get("stay");

  if (stayParam === '1') {
    stayTabs.add(details.tabId);
  }

  if (stayTabs.has(details.tabId)) {
    return;
  }

  const response = await fetch("https://github.lucasvanbriemen.nl/api/check_end_point", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ url })
  });

  const data = await response.json();

  if (data.redirect === true && data.URL) {
    chrome.tabs.update(details.tabId, { url: data.URL });
  }
}, {
  url: [{ hostContains: "github.com" }]
});

// Cleanup stay flag when tab closes
chrome.tabs.onRemoved.addListener((tabId) => {
  stayTabs.delete(tabId);
});

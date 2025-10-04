// Track URLs that have been checked and returned false for this session
const checkedUrls = new Set();

chrome.webNavigation.onCompleted.addListener(async (details) => {
  // Only process main frame navigation (not iframes)
  if (details.frameId !== 0) return;

  const url = details.url;

  // Check if we're on github.com
  if (!url.startsWith('https://github.com/') && !url.startsWith('http://github.com/')) {
    return;
  }

  // Check if URL has redirect=false in query params
  const urlObj = new URL(url);
  if (urlObj.searchParams.get('redirect') === 'false') {
    checkedUrls.add(url);
    return;
  }

  // Skip if we've already checked this URL and it returned false
  if (checkedUrls.has(url)) {
    return;
  }

  try {
    // Call the API
    const response = await fetch('https://github.lucasvanbriemen.nl/api/check_end_point', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ url: url })
    });

    const data = await response.json();

    // If redirect is true and URL is provided, navigate to that URL
    if (data.redirect === true && data.URL) {
      chrome.tabs.update(details.tabId, { url: data.URL });
    } else {
      // Mark this URL as checked so we don't request again
      checkedUrls.add(url);
    }
  } catch (error) {
    console.error('GitHub Redirect extension error:', error);
  }
}, {
  url: [{ hostContains: 'github.com' }]
});

// Scrolls an element into view and briefly outlines it so the user can see
// which item / comment a notification refers to.
//
// The target element may not be in the DOM yet (e.g. the item view is still
// loading its comments), so we poll for a short while using requestAnimationFrame
// before giving up.
export function flashHighlight(selector, { retries = 60 } = {}) {
  const tryScroll = (attempt) => {
    const el = document.querySelector(selector);

    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      el.classList.add('flash-highlight');
      setTimeout(() => el.classList.remove('flash-highlight'), 2000);
      return;
    }

    if (attempt < retries) {
      requestAnimationFrame(() => tryScroll(attempt + 1));
    }
  };

  requestAnimationFrame(() => tryScroll(0));
}

export function scrollToComment(commentId) {
  if (!commentId) {
    return;
  }

  flashHighlight(`#comment-${commentId}`);
}

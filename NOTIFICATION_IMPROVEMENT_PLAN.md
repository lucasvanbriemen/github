# Notification Visibility Improvement Plan

## Problem

Notifications are too easily overlooked. The current system has two key shortcomings:

1. **No notification indicator on items** - When browsing the item list (ItemOverview), there's no visual indication that an item has unseen notifications. You have to visit the Dashboard to see notifications, mentally map them to items, then go find those items.

2. **Dismissing is the only interaction** - Once you find a notification on the Dashboard, the only action is to "complete" (dismiss) it. There's no way to quickly jump from the item list to the relevant notification context, making the whole flow feel disconnected.

In short: the Electron badge tells you *something* needs attention, but the app doesn't help you find *what* or *where*.

---

## Current Flow

```
Electron badge (count) --> Dashboard (notification list) --> Click notification --> Item detail page
```

There's no reverse path: if you're browsing items, you have no idea which ones have notifications waiting.

---

## Proposed Changes

### 1. Notification badge on ListItem rows

**What:** Add a small colored dot/badge to each item in the item overview list that has one or more incomplete notifications.

**How - Backend:**
- Add a `notification_count` field to the item list API response (`ItemController::index`).
- Use `withCount` on the Notification relationship: count incomplete notifications where the item is referenced (either directly via `related_id` for `item_assigned`/`review_requested`, or indirectly through comments/reviews).
- Alternatively, add a lightweight endpoint or include notification item IDs in a single batch call.

**How - Frontend:**
- In `ListItem.svelte`, accept and display the notification count.
- Render a small badge (e.g., a colored dot or count pill) next to the item title or icon.
- Use a distinct color (e.g., the app's primary/accent color or a blue dot) that stands out from the existing state colors (green/red/purple/gray).

**Styling:**
- Small dot indicator (no count): simple and clean, just signals "this has notifications."
- Count badge (shows number): more informative, shows 1, 2, 3+ notifications.
- Recommendation: start with a dot + count approach. A colored pill like `(2)` next to the title, or a small badge overlaying the item icon.

---

### 2. Notification badge in the sidebar/header navigation

**What:** Show a notification count badge on the navigation element that leads to the Dashboard, so users always see their total notification count regardless of which page they're on.

**How:**
- The `Header.svelte` component likely has a link back to the Dashboard/home. Add a badge with the total incomplete notification count.
- Fetch the count on mount and update it in real-time via the existing Ably subscription (already in `App.svelte`).
- Store the count in a Svelte store so it's accessible from the Header without prop drilling.

---

### 3. Inline notification preview on item detail page

**What:** When you open an item that has notifications, show the notifications directly on the item detail page (e.g., a small banner or collapsible section at the top) instead of requiring you to go back to the Dashboard.

**How:**
- On the Item detail page, fetch notifications for that specific item.
- Show them in a dismissible banner/card at the top of the page.
- Allow completing notifications directly from the item page.
- This closes the loop: you see the notification *in context* of the item you're looking at.

---

### 4. Notification type indicators

**What:** Different notification types (mention, review request, assignment, comment) carry different urgency. Make them visually distinct.

**How:**
- Use small icons or color-coded badges per type:
  - Review requested: eye icon or reviewer icon
  - Mentioned: @ symbol
  - Assigned: person icon
  - Comment on assigned item: chat bubble
  - PR review: checkmark/x based on review state
- Apply these in both the Dashboard notification list and the new ListItem badges.

---

## Implementation Order

| Priority | Change | Effort | Impact |
|----------|--------|--------|--------|
| 1 | Notification badge on ListItem rows | Medium | High - directly solves the core problem |
| 2 | Notification badge in header/nav | Small | Medium - always-visible count |
| 3 | Inline notifications on item detail page | Medium | High - lets you act on notifications in context |
| 4 | Notification type indicators | Small | Low-Medium - nice clarity improvement |

---

## Files to Modify

### Priority 1 - ListItem notification badge
- `app/Http/Controllers/ItemController.php` - Include notification counts in item list response
- `app/Models/Item.php` - Add relationship/scope for counting notifications
- `resources/js/components/ListItem.svelte` - Render notification indicator
- `resources/scss/components/list-item.scss` - Badge/dot styling

### Priority 2 - Header notification badge  
- `resources/js/components/Header.svelte` - Add badge element
- `resources/js/components/stores.js` - Add notification count store
- `resources/js/components/App.svelte` - Populate store from Ably + initial fetch
- `resources/scss/components/header.scss` - Badge styling

### Priority 3 - Inline notifications on item page
- `app/Http/Controllers/NotificationController.php` - Add endpoint to fetch notifications by item ID
- `routes/api.php` - Register new route
- `resources/js/components/item/Item.svelte` - Fetch and render notification banner
- New SCSS for inline notification banner

### Priority 4 - Type indicators
- `resources/js/components/Notification.svelte` - Add type icon
- `resources/js/components/ListItem.svelte` - Show type icon in badge
- `resources/scss/components/notification.scss` - Type-specific styling

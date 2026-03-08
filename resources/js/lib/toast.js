import { writable } from 'svelte/store';

export const toasts = writable([]);

let id = 0;

export function toast(message, type = 'success') {
  const toastId = ++id;
  toasts.update(t => [...t, { id: toastId, message, type }]);
  setTimeout(() => {
    toasts.update(t => t.filter(item => item.id !== toastId));
  }, 3000);
}

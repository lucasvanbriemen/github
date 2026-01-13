import { writable } from 'svelte/store';

export const organization = writable(null);
export const repository = writable(null);

function syncFromUrl() {
  const hash = window.location.hash;
  // example: #/WebinarGeek/app
  const [, org, repo] = hash.split('/');

  organization.set(org ?? null);
  repository.set(repo ?? null);
}

syncFromUrl();

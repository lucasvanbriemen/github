import { get, writable } from 'svelte/store';

import api from '../lib/api';

export const organization = writable(null);
export const repository = writable(null);
export const repoMetadata = writable(null);

let metadataPromise = null;

export async function waitForMetadata() {
  if (metadataPromise) {
    return metadataPromise;
  }

  // If metadata is already loaded, return immediately
  const current = get(repoMetadata);
  if (current !== null) {
    return current;
  }

  // Wait for metadata to be loaded
  return new Promise((resolve) => {
    const unsubscribe = repoMetadata.subscribe(data => {
      if (data !== null) {
        unsubscribe();
        resolve(data);
      }
    });
  });
}

function syncFromUrl() {
  const hash = window.location.hash;
  // example: #/WebinarGeek/app
  const [, org, repo] = hash.split('/');

  organization.set(org ?? null);
  repository.set(repo ?? null);

  if (org && repo && get(repoMetadata) === null) {
    metadataPromise = api.get(route('organizations.repositories.metadata', { organization: org, repository: repo })).then(data => {
      repoMetadata.set(data);
      return data;
    });
  } else {
    repoMetadata.set(null);
    metadataPromise = null;
  }
}

syncFromUrl();
window.addEventListener('hashchange', syncFromUrl);

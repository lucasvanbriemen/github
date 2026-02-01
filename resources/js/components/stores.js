import { get, writable } from 'svelte/store';

import api from '../lib/api';

export const organization = writable(null);
export const repository = writable(null);
export const repoMetadata = writable(null);

function syncFromUrl() {
  const hash = window.location.hash;
  // example: #/WebinarGeek/app
  const [, org, repo] = hash.split('/');

  organization.set(org ?? null);
  repository.set(repo ?? null);

  if (org && repo && get(repoMetadata) === null) {
    api.get(route('organizations.repositories.metadata', { organization: org, repository: repo })).then(data => repoMetadata.set(data));
  } else {
    repoMetadata.set(null);
  }
}

syncFromUrl();
window.addEventListener('hashchange', syncFromUrl);

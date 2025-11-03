<script>
  import { onMount } from 'svelte';

  let organizations = [];
  let selectedOrganization = null;
  let selectedRepository = null;

  function updateSelectionFromHash() {
    const hash = window.location.hash || '';
    const parts = hash.replace(/^#\/?/, '').split('/').filter(Boolean);
    const orgName = parts[0] || null;
    const repoName = parts[1] || null;

    selectedOrganization = organizations.find(o => o.name === orgName);
    selectedRepository = selectedOrganization.repositories.find(r => r.name === repoName);
  }

  onMount(async () => {
    const res = await fetch(route('organizations.get'));
    organizations = await res.json();
    updateSelectionFromHash();
    window.addEventListener('hashchange', updateSelectionFromHash);
  });

  function selectOrganization(org) {
    selectedOrganization = org;
    selectedRepository = null;
  }

  function selectRepository(org, repo) {
    selectedOrganization = org;
    selectedRepository = repo;
    goToRepository(org, repo);
  }

  function goToRepository(org, repo) {
    window.location.href = `#/${org.name}/${repo.name}`;
  }
</script>

<header>
  {#each organizations as org}
    <div class="organization" class:selected={selectedOrganization === org}>
      <button on:click={() => selectOrganization(org)}>
        <img src="{org.avatar_url}" alt="{org.name} Avatar" width="50" height="50" />
        <span class="name">{org.name}</span>
      </button>

      <div class="repos">
        {#each org.repositories as repo}
          <a href={`#/${org.name}/${repo.name}`} class="repo" class:selected={selectedRepository === repo} on:click={() => selectRepository(org, repo)}>{repo.name}</a>
        {/each}
      </div>
    </div>
  {/each}
</header>

<style>
  @import "../../scss/components/header.scss";
</style>

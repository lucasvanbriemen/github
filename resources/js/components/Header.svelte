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
    const res = await fetch('/api/organizations');
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
  header {
    background-color: var(--background-color-one);
    padding: 0.5rem;

    display: flex;
    align-items: center;

    .organization {
      display: flex;
      align-items: center;
      position: relative;
      padding: 0.5rem;

      div, button {
        background-color: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        font-weight: bold;
        font-size: 1.2rem;
      }

      img {
        border-radius: 50%;
        margin-right: 0.5rem;
        height: 2rem;
        width: 2rem;
      }

      .repos {
        display: flex;
        gap: 1rem;
        overflow: hidden;
        max-width: 0;
        opacity: 0;
        transition: max-width 0.3s ease, opacity 0.3s ease;
      }

      &.selected {
        background-color: var(--background-color-two);
        border-radius: 0.5rem;

        .repos {
          max-width: 500px;
          opacity: 1;

          .repo {
            background-color: var(--background-color-one);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-decoration: none;

            &.selected {
              background-color: var(--primary-color);
            }
          }
        }
      }
    }
  }
</style>

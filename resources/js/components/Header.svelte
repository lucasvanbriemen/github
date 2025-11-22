<script>
  import { onMount } from 'svelte';
  import { params, push } from 'svelte-spa-router';
  import api from '../lib/api.js';

  let organizations = [];
  let selectedOrganization = null;
  let selectedRepository = null;

  onMount(async () => {
    selectedOrganization = $params?.organization || null;
    selectedRepository = $params?.repository || null;
    organizations = await api.get(route('organizations.get'));
  });

  function selectOrganization(org) {
    selectedOrganization = org;
  }

  function selectRepository(org, repo) {
    selectedOrganization = org;
    selectedRepository = repo;
    push(`/${org.name}/${repo.name}`);
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

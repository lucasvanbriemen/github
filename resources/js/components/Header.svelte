<script>
  import { onMount } from 'svelte';
  import { organization, repository } from './stores.js';

  let organizations = $state([]);

  onMount(async () => {
    organizations = await api.get(route('organizations'));
  });
</script>

<header>
  {#each organizations as org}
    <div class="organization" class:selected={$organization === org.name}>
      <button onclick={() => organization.set(org.name)}>
        <img src="{org.avatar_url}" alt="{org.name} Avatar" width="50" height="50" />
        <span class="org">{org.name}</span>
      </button>

      <div class="repos">
        {#each org.repositories as repo}
          <a href={`#/${org.name}/${repo.name}`} class="repo" class:selected={$repository === repo.name} onclick={() => repository.set(repo.name)}>{repo.name}</a>
        {/each}
      </div>
    </div>
  {/each}
</header>

<style>
  @import "../../scss/components/header.scss";
</style>

<script>
  import { onMount } from 'svelte';
  import { params } from 'svelte-spa-router';

  let organizations = $state([]);
  let selectedOrganization = $state($params?.organization || null);
  let selectedRepository = $state($params?.repository || null);

  onMount(async () => {
    organizations = await api.get(route('organizations.get'));
  });
</script>

<header>
  {#each organizations as org}
    <div class="organization" class:selected={selectedOrganization === org.name}>
      <button onclick={() => selectedOrganization = org.name}>
        <img src="{org.avatar_url}" alt="{org.name} Avatar" width="50" height="50" />
        <span class="org">{org.name}</span>
      </button>

      <div class="repos">
        {#each org.repositories as repo}
          <a href={`#/${org.name}/${repo.name}`} class="repo" class:selected={selectedRepository === repo.name} onclick={() => selectedRepository = repo.name}>{repo.name}</a>
        {/each}
      </div>
    </div>
  {/each}
</header>

<style>
  @import "../../scss/components/header.scss";
</style>

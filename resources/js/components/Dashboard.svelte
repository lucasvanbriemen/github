<script>
  import { organization, repository } from './stores';
  import { onMount } from 'svelte';

  let organizations = [];

  onMount(async () => {
    organizations = await api.get(route('organizations'));
  });

</script>

<main>
  <div class="organizations">
    {#each organizations as org}
      <button class="organization" onclick={() => organization.set(org.name)}>
        <img src="{org.avatar_url}" alt="{org.name} Avatar" width="50" height="50" />
        <span class="org">{org.name}</span>

        {#if $organization == org.name}
          {#each org.repositories as repo}
            <a href={`#/${org.name}/${repo.name}`} class="repo" onclick={() => repository.set(repo.name)}>{repo.name}</a>
          {/each}
        {/if}
      </button>
    {/each}
  </div>
</main>

<style>
  @import "../../scss/components/dashboard.scss";
</style>

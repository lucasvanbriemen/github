<script>
  import { organization, repository } from './stores';
  import { onMount } from 'svelte';

  let organizations = [];

  onMount(async () => {
    organizations = await api.get(route('organizations'));
  });

  function selectOrganization(org) {
    organization.set(org.name);
  }

  function selectRepository(repo) {
    repository.set(repo.name);
  }

</script>

<main>
  <div class="organizations">
    {#each organizations as org}
      <button class="organization" onclick={() => selectOrganization(org)}>
        <img src="{org.avatar_url}" alt="{org.name} Avatar" width="50" height="50" />
        <span class="org">{org.name}</span>

        {#if $organization == org.name}
          <div class="repositories">
            {#each org.repositories as repo}
              <a class="repository" href="#/{org.name}/{repo.name}" onclick={() => selectRepository(repo)}>{repo.name}</a>
            {/each}
          </div>
        {/if}
      </button>
    {/each}
  </div>
</main>

<style>
  @import "../../scss/components/dashboard.scss";
</style>

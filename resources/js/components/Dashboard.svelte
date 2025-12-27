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
    window.location.hash = `#/${org.name}/${repo.name}`;
  }

</script>

<main>
  <div class="organizations">
    {#each organizations as org}
      <button class="organization" onclick={() => selectOrganization(org)}>
        <img src="{org.avatar_url}" alt="{org.name} Avatar" width="50" height="50" />
        <h2 class="title">{org.name}</h2>

        {#if org.description}
          <span class="description">{org.description}</span>
        {:else}
          <span class="no-description">No description provided.</span>
        {/if}

        <div class="repositories">
          {#each org.repositories as repo}
            <div class="repository" onclick={() => selectRepository(repo)}>
              <h3 class="title">{repo.name}</h3>
              {#if repo.description}
                <span class="description">{repo.description}</span>
              {:else}
                <span class="no-description">No description provided.</span>
              {/if}
            </div>
          {/each}
        </div>
      </button>
    {/each}
  </div>
</main>

<style>
  @import "../../scss/components/dashboard.scss";
</style>

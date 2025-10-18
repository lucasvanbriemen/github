<script>
  import { onMount } from 'svelte';

  let organizations = [];

  onMount(async () => {
    const res = await fetch('/api/organizations');
    organizations = await res.json();
  });
</script>

<header>
  {#each organizations as organization}
    <div class="organization">
      <div>
        <img src="{organization.avatar_url}" alt="{organization.name} Avatar" width="50" height="50" />
        <span class="name">{organization.name}</span>
      </div>

      <div class="repos">
        {#each organization.repositories as repository}
          <span class="repo">{repository.name}</span>
        {/each}
      </div>
    </div>
  {/each}
</header>

<style>
  header {
    background-color: var(--background-color-one);
    padding: 1rem;
  }

  span {
    font-weight: bold;
    font-size: 1.2rem;
  }
</style>

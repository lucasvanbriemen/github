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
    <span>{organization.name}</span><br />
  {/each}
</header>

<style>
  header {
    background-color: #f8f9fa;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  span {
    font-weight: bold;
    font-size: 1.2rem;
  }
</style>

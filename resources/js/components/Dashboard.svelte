<script>
  import { onMount } from 'svelte';

  let organizations = [];

  onMount(async () => {
    const res = await fetch('/api/organizations');
    organizations = await res.json();
  });

</script>

{#each organizations as organization}
  <span>{organization.name}</span><br />
{/each}

<style>
</style>

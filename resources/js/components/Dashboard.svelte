<script>
  import { onMount } from 'svelte';

  let organizations = [];

  onMount(async () => {
    const res = await fetch(route('organizations.get'));
    organizations = await res.json();
  });

</script>

{#each organizations as organization}
  <span>{organization.name}</span><br />
{/each}

<style>
</style>

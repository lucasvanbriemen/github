<script>
  import { onMount } from 'svelte';

  let organizations = [];

  onMount(async () => {
    organizations = await api.get(route('organizations.get'));
  });

</script>

{#each organizations as organization}
  <span>{organization.name}</span><br />
{/each}

<style>
</style>

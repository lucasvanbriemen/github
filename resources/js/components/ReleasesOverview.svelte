<script>
  import { onMount } from 'svelte';
  import ListItem from './ListItem.svelte';
  import ListItemSkeleton from './ListItemSkeleton.svelte';
  import { organization, repository } from './stores';

  let isLoading = $state(true);
  let releases = $state([]);

  onMount(async () => {
    releases = await api.get(route('organizations.repositories.releases', { $organization, $repository }));
    isLoading = false;
  });
</script>

<div class="repo-dashboard">
  <div class="repo-main">
    {#if isLoading}
      {#each Array(3) as _}
        <ListItemSkeleton />
      {/each}
    {:else}
      {#each releases as release}
        <ListItem item={{
          type: 'release',
          state: 'open',
          title: release.title,
          number: release.number,
          created_at_human: release.updated_at,
          }} />
      {/each}
    {/if}
  </div>
</div>
  
<style lang="scss">
  @import '../../scss/components/project-listing.scss';
</style>

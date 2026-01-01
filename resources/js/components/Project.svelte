<script>
  import { onMount } from 'svelte';
  import Sidebar from './sidebar/Sidebar.svelte';
  import ListItem from './ListItem.svelte';
  import ListItemSkeleton from './ListItemSkeleton.svelte';
  import Group from './sidebar/group.svelte';
  import Switch from './Switch.svelte';

  let { params = {} } = $props();

  let isLoading = $state(true);
  let cols = $state([]);
  let showEverything = $state(false);

  onMount(async () => {
    cols = await api.get(route('organizations.repositories.project.show', {
      organization: params.organization,
      repository: params.repository,
      number: params.number,
    }));

    isLoading = false;
  });
</script>

<div class="repo-dashboard">
  <Sidebar>
    <Group title="Status">
      <Switch title="Show Everything" description="Toggle to show all items including those assigned to others." bind:input={showEverything}/>
    </Group>
  </Sidebar>
  
  <div class="repo-main">
    {#if isLoading}
      {#each Array(3) as _, index}
        <div class="column">
          <span class="title">Loading...</span>
          {#each Array(5) as __, idx}
            <ListItemSkeleton key={idx} />
          {/each}
        </div>
      {/each}
    {/if}

    {#each cols as col}
      <div class="column" class:only-me={showEverything == false}>
        <span class="title">{col.name}</span>
        {#each col.items as item}
          <ListItem {item} />
        {/each}
      </div>
    {/each}
  </div>
</div>
  
<style lang="scss">
  @import '../../scss/components/project.scss';
</style>

<script>
  import { onMount } from 'svelte';
  import Sidebar from './sidebar/Sidebar.svelte';
  import ListItem from './ListItem.svelte';
  import ListItemSkeleton from './ListItemSkeleton.svelte';
  import Group from './sidebar/group.svelte';

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

    console.log(cols);
  });
</script>

<div class="repo-dashboard">
  <Sidebar {params} activeItem="Projects">
    <Group title="Status">
      <button class="show-all" onclick={() => showEverything = true}>Show All</button>
      <button class="show-mine" onclick={() => showEverything = false}>Show Mine</button>
    </Group>
  </Sidebar>
  
  <div class="repo-main">
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

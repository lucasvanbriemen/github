<script>
  import { onMount } from 'svelte';
  import Sidebar from './sidebar/Sidebar.svelte';
  import ListItem from './ListItem.svelte';
  import ListItemSkeleton from './ListItemSkeleton.svelte';

  let { params = {} } = $props();

  let isLoading = $state(true);
  let cols = $state([]);

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
  <Sidebar {params} activeItem="Projects" showDetailsFrom="repo-dashboard" />
  
  <div class="repo-main">
    cols: <br>
    {#each cols as col}
      name - {col.name} <br>
    {/each}
  </div>
</div>
  
<style lang="scss">
  @import '../../scss/components/project-listing.scss';
</style>

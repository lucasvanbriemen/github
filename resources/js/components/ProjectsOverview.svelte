<script>
  import { onMount } from 'svelte';
  import Sidebar from './sidebar/Sidebar.svelte';
  import ListItem from './ListItem.svelte';
  import ListItemSkeleton from './ListItemSkeleton.svelte';

  let { params = {} } = $props();

  let isLoading = $state(true);
  let projects = $state([]);

  onMount(async () => {
    projects = await api.get(route('organizations.repositories.projects', {
      organization: params.organization,
      repository: params.repository,
    }));
    isLoading = false;
  });
</script>

<div class="repo-dashboard">
  <Sidebar {params} activeItem="Projects" showDetailsFrom="repo-dashboard" />
  <div class="repo-main">
    {#if isLoading}
      {#each Array(3) as _}
        <ListItemSkeleton />
      {/each}
    {:else}
      {#each projects as project}
        <ListItem item={{
          type: 'project',
          state: 'open',
          title: project.title,
          number: project.number,
          created_at_human: project.updated_at,
          }} />
      {/each}
    {/if}
  </div>
</div>
  
<style lang="scss">
  @import '../../scss/components/project-listing.scss';
</style>

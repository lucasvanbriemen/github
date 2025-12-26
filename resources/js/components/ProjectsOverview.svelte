<script>
  import { onMount } from 'svelte';
  import Sidebar from './sidebar/Sidebar.svelte';
  import ListItem from './itemOverview/ListItem.svelte';

  let { params = {} } = $props();

  let projects = $state([]);

  onMount(async () => {
    projects = await api.get(route('organizations.repositories.projects', {
      organization: params.organization,
      repository: params.repository,
    }));
  });
</script>

<div class="repo-dashboard">
  <Sidebar {params} activeItem="Projects" showDetailsFrom="repo-dashboard" />
  <div class="repo-main">
    {#each projects as project}
      <ListItem {project} />
    {/each}
  </div>
</div>
  
<style lang="scss">
  @import '../../scss/components/repository-dashboard.scss';
</style>

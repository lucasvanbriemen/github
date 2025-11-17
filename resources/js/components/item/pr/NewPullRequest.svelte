<script>
  import { onMount } from 'svelte';

  import Sidebar from '../../sidebar/Sidebar.svelte';
  import SidebarGroup from '../../sidebar/group.svelte';
  import Select from '../../Select.svelte';

  let { params = {} } = $props();

  let head_branch = $state(params.branch);
  let base_branch = $state('');

  let loading = $state(false);
  
  onMount(async () => {
    // Load branches for the repository to populate the Select components
    loading = true;
    const res = await fetch(route(`organizations.repositories.pr.metadata`, { organization: params.organization, repository: params.repository }));
    const data = await res.json();

    console.log(data);

    loading = false;
  });
</script>

<div class="new-pr">
  <Sidebar {params} selectedDropdownSection="New PR">
    <SidebarGroup title="Branch to merge">
      <Select />
    </SidebarGroup>

    <SidebarGroup title="Branch to merge into">
      <Select />
    </SidebarGroup>
  </Sidebar>

  <div class="new-pr-main">
    test
  </div>
</div>
  
<style lang="scss">
  @import '../../../../scss/components/item/pr/new-pr.scss';
</style>

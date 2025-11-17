<script>
  import { onMount } from 'svelte';

  import Sidebar from '../../sidebar/Sidebar.svelte';
  import SidebarGroup from '../../sidebar/group.svelte';
  import Select from '../../Select.svelte';
  import Input from '../../Input.svelte';

  let { params = {} } = $props();

  let head_branch = $state(params.branch);
  let base_branch = $state('');
  let possibleBranches = $state([]);
  let title = $state('');

  let assignee = $state();
  let possibleAssignees = $state([]);

  let loading = $state(false);
  
  onMount(async () => {
    // Load branches for the repository to populate the Select components
    loading = true;
    const res = await fetch(route(`organizations.repositories.pr.metadata`, { organization: params.organization, repository: params.repository }));
    const data = await res.json();

    // Ensure options are in { value, label } shape expected by <Select>
    possibleBranches = (data.branches || []).map((b) => ({ value: b, label: b }));
    possibleAssignees = (data.assignees || []).map((a) => ({ value: a.id, label: a.display_name }));
    assignee = data.default_assignee;
    base_branch = data.master_branch;

    loading = false;
  });
</script>

<div class="new-pr">
  <Sidebar {params} selectedDropdownSection="New PR">
    <SidebarGroup title="Branch to merge">
      <Select name="head_branch" value={head_branch} selectableItems={possibleBranches}  bind:selectedValue={head_branch} />
    </SidebarGroup>

    <SidebarGroup title="Branch to merge into">
      <Select name="base_branch" value={base_branch} selectableItems={possibleBranches} bind:selectedValue={base_branch} />
    </SidebarGroup>

    <SidebarGroup title="Assignee">
      <Select name="assignee" value={assignee} selectableItems={possibleAssignees} bind:selectedValue={assignee} />
    </SidebarGroup>
  </Sidebar>

  <div class="new-pr-main">
    <Input name="title" label="Title" bind:value={title} />
  </div>
</div>
  
<style lang="scss">
  @import '../../../../scss/components/item/pr/new-pr.scss';
</style>

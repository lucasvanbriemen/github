<script>
  import { onMount } from 'svelte';

  import Sidebar from '../../sidebar/Sidebar.svelte';
  import SidebarGroup from '../../sidebar/group.svelte';
  import Select from '../../Select.svelte';
  import Input from '../../Input.svelte';
  import MarkdownEditor from '../../MarkdownEditor.svelte';

  let { params = {} } = $props();

  let head_branch = $state(params.branch);
  let base_branch = $state('');
  let possibleBranches = $state([]);
  let title = $state('');
  let body = $state('');

  let assignee = $state();
  let possibleAssignees = $state([]);

  onMount(async () => {
    // Load branches for the repository to populate the Select components
    const data = await api.get(route(`organizations.repositories.pr.metadata`, { organization: params.organization, repository: params.repository }));

    // Ensure options are in { value, label } shape expected by <Select>
    possibleBranches = (data.branches || []).map((b) => ({ value: b, label: b }));
    possibleAssignees = (data.assignees || []).map((a) => ({ value: a.login, label: a.display_name }));
    assignee = data.default_assignee;
    base_branch = data.master_branch;
  });

  async function createPR() {
    const res = await api.post(route(`organizations.repositories.pr.create`, { organization: params.organization, repository: params.repository }), {
      head_branch,
      base_branch,
      title,
      body,
      assignee,
    });

    window.location.hash = `/${params.organization}/${params.repository}/prs/${Number(res.number)}`;
  }
</script>

<div class="new-pr">
  <Sidebar {params} activeItem="New PR">
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
    <MarkdownEditor bind:value={body} placeholder="Describe your changes..." />

    <div class="submit-wrapper">
      <button class="button-primary" onclick={createPR}>Create Pull Request</button>
    </div>
  </div>
</div>
  
<style lang="scss">
  @import '../../../../scss/components/item/pr/new-pr.scss';
</style>

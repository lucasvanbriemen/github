<script>
  import { onMount } from 'svelte';

  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Select from '../Select.svelte';
  import Input from '../Input.svelte';
  import Markdown from '../Markdown.svelte';
  import { organization, repository, repoMetadata } from '../stores';

  let { params = {} } = $props();

  let head_branch = $state(params.branch);
  let base_branch = $state('');
  let possibleBranches = $state([]);
  let title = $state('');
  let body = $state('');
  let type = $state(params.type);

  let templates = $state([]);
  let selectedTemplate = $state(null);
  
  let assignee = $state();
  let possibleAssignees = $state([]);

  function selectTemplate(template) {
    body = template.body;
    selectedTemplate = template;
  }
  
  onMount(async () => {
    // Ensure options are in { value, label } shape expected by <Select>
    possibleBranches = [...new Set($repoMetadata.branches || [])].map(b => ({ value: b, label: b }));    
    possibleAssignees = ($repoMetadata.assignees || []).filter(a => a).map((a) => ({ value: a.login, label: a.display_name, image: a.avatar_url }));

    assignee = $repoMetadata.default_assignee;
    base_branch = $repoMetadata.master_branch;

    templates = $repoMetadata.templates.filter(t => t.type === type);

    // Set the first template as the default selected template
    selectTemplate(templates[0]);
  });

  function createItem() {
    if (type === 'pr') {
      createPR();
    } else {
      createIssue();
    }
  }

  async function createPR() {
    const res = await api.post(route(`organizations.repositories.pr.create`, { $organization, $repository }), {
      head_branch,
      base_branch,
      title,
      body,
      assignee,
    });

    window.location.hash = `#/${$organization}/${$repository}/prs/${Number(res.number)}`;
  }

  async function createIssue() {
    const res = await api.post(route(`organizations.repositories.issues.create`, { $organization, $repository }), {
      title,
      body,
      assignee,
    });

    window.location.hash = `#/${$organization}/${$repository}/issues/${Number(res.number)}`;
  }
</script>

<div class="new-pr">
  <Sidebar>

    {#if type === 'pr'}
      <SidebarGroup title="Branch to merge">
        <Select name="head_branch" value={head_branch} selectableItems={possibleBranches} bind:selectedValue={head_branch} />
      </SidebarGroup>

      <SidebarGroup title="Branch to merge into">
        <Select name="base_branch" value={base_branch} selectableItems={possibleBranches} bind:selectedValue={base_branch} />
      </SidebarGroup>
    {/if}

    <SidebarGroup title="Assignee">
      <Select name="assignee" value={assignee} selectableItems={possibleAssignees} bind:selectedValue={assignee} />
    </SidebarGroup>
  </Sidebar>

  <div class="new-pr-main">
    <div class="inputs">
      <Input name="title" label="Title" bind:value={title} />
      <Markdown bind:content={body} isEditing={true} />

      <div class="submit-wrapper">
        <button class="button-primary" onclick={createItem}>
          Create 
          {#if type === 'pr'}
            Pull Request
          {:else}
            Issue
          {/if}
        </button>
      </div>
    </div>

    <div class="templates">
      {#each templates as template}
        <button class="template" class:selected={selectedTemplate && selectedTemplate.id === template.id} class:disabled={selectedTemplate && selectedTemplate.id} onclick={() => selectTemplate(template)}>{template.name}</button>
      {/each}
    </div>
  </div>
</div>
  
<style lang="scss">
  @import '../../../scss/components/item/pr/new-pr.scss';
</style>

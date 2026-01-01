<script>
  import { onMount, untrack } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Icon from '../Icon.svelte';
  import Select from '../Select.svelte';
  import { organization, repository } from '../stores';

  let { item, isPR, isLoading, metadata, params = {}, showWhitespace = $bindable(true) } = $props();

  function toggleHideWhitespace() {
    showWhitespace = !showWhitespace;
  }

  let labels = $state([]);
  let contributors = $state([]);

  let selectedableReviewers = $state([]);

  let linkedItems = $state([]);
  let projects = $state([]);

  function getItemProject(projectId) {
    return item.projects.find(p => p.id === projectId);
  }

  // Generate label style with proper color formatting
  function getLabelStyle(label) {
    return `background-color: #${label.color}4D; color: #${label.color}; border: 1px solid #${label.color};`;
  }

  onMount(async () => {
    linkedItems = api.get(route('organizations.repositories.item.linked.get', { $organization, $repository, number: params.number }));
    projects = api.get(route('organizations.repositories.projects', { $organization, $repository }));
  });

  function requestReviewer({selectedValue}) {
    api.post(route('organizations.repositories.pr.add.reviewers', { $organization, $repository, number: item.number }), {
      reviewers: selectedValue
    });
  }

  function updateLabels({selectedValue}) {
    api.post(route('organizations.repositories.item.label.update', { $organization, $repository, number: item.number }), {
      labels: selectedValue
    });

    const allLabels = metadata.labels;

    labels = labels.map(l => ({ ...l, selected: selectedValue.includes(l.value) }));
    item.labels = allLabels.filter(l => selectedValue.includes(l.name));
  }

  async function updateProjectStatus(projectId, newStatusId) {
    let project = projects.find(p => p.id === projectId);
    let itemProject = item.projects.find(p => p.id === projectId);

    await api.post(route('organizations.repositories.project.item.update', { $organization, $repository }), {
      projectId: projectId,
      itemId: itemProject.itemId,
      fieldId: project.status_field_id,
      statusValue: newStatusId
    });
    
    const statusOption = project.status_options.find(opt => opt.id === newStatusId);
    itemProject.status = statusOption.name;
  }

  async function removeFromProject(projectId) {
    let itemProject = item.projects.find(p => p.id === projectId);

    await api.post(route('organizations.repositories.project.item.remove', { $organization, $repository }),{
      projectId: projectId,
      itemId: itemProject.itemId
    });

    item.projects = item.projects.filter(p => p.id !== projectId);
  }

  async function addToProject(project) {
    const projectIndex = projects.findIndex(p => p.id === project.id);
    let selectedStatus = projects[projectIndex].status_options[0];

    const response = await api.post(route('organizations.repositories.project.item.add', { $organization, $repository}), {
      projectId: project.id,
      contentId: item.node_id,
      itemNumber: item.number,
      fieldId: project.status_field_id,
      statusValue: selectedStatus.id
    });

    const newProjectItem = {
      id: project.id,
      title: project.title,
      number: project.number,
      itemId: response.itemId,
      status: selectedStatus.name
    };

    item.projects = [...item.projects, newProjectItem];
  }

  $effect(() => {
    void isLoading;
    void metadata;

    untrack(() => {
      labels = metadata?.labels || [];
      // Mark labels as selected when they are present on the item
      const itemLabelNames = (item?.labels || []).map(l => l.name);
      labels = labels.map(label => ({
        value: label.name,
        label: label.name,
        selected: itemLabelNames.includes(label.name)
      }));

      contributors = metadata?.assignees || [];
      contributors = contributors.map(assignee => ({value: assignee.login, label: assignee.display_name, image: assignee.avatar_url}));
      contributors.forEach(contributor => {
        item?.requested_reviewers?.forEach(requestedReviewer => {
          if (requestedReviewer.user.login == contributor.value) {
            contributor.selected = true;
          }
        });
      });
    });
  });

</script>

<Sidebar>
  {#if !isLoading && isPR}
    <SidebarGroup title="Diff Settings">
      <div class="diff-settings">
        <label class="whitespace-toggle">
          <input type="checkbox" checked={!showWhitespace} onchange={toggleHideWhitespace} />
          <span>Hide whitespace changes</span>
        </label>
      </div>
    </SidebarGroup>
  {/if}

  {#if !isLoading}
    <SidebarGroup title="Projects">
      {#each projects as project, idx (project.id)}
        {#if getItemProject(project.id)}
          <div class="project-item">
            <div class="project-header">
              <a href="#{$organization}/{$repository}/project/{project.number}" class="project-link">{project.title}</a>
              <button onclick={() => removeFromProject(project.id)} class="remove-project">x</button>
            </div>

            <Select name="status-{idx}" selectableItems={project.status_options.map(option => ({value: option.id, label: option.name}))} selectedValue={project.status_options.find(opt => opt.name === getItemProject(project.id)?.status)?.id ?? ''} onChange={(e) => updateProjectStatus(project.id, e.selectedValue)} />
          </div>
        {:else}
          <button onclick={() => addToProject(project)} class="button-primary-outline">Add to {project.title}</button>
        {/if}
      {/each}
    </SidebarGroup>

    <SidebarGroup title="Assignees">
      {#each item.assignees as assignee}
        <div class="assignee">
          <img src={assignee.avatar_url} alt={assignee.name} />
          <span>{assignee.display_name}</span>
        </div>
      {/each}
    </SidebarGroup>

    <SidebarGroup title="Linked Items">
      {#each linkedItems as linkedItem}
        <a class="linked-item" href={linkedItem.url}>
          <Icon name={linkedItem.type} className="icon {linkedItem.state}" /> {linkedItem.title}
        </a>
      {/each}
    </SidebarGroup>

    <SidebarGroup title="Labels">
      <div class="labels">
        {#each item.labels as label}
          <span class="label" style={getLabelStyle(label)}>
            {label.name}
          </span>
        {/each}
      </div>

      <Select name="label" selectableItems={labels} onChange={updateLabels} multiple/>
    </SidebarGroup>

    {#if isPR}
      <SidebarGroup title="Reviewers">
        {#each item.requested_reviewers as reviewer}
          <div class="reviewer">
            <img src={reviewer.user.avatar_url} alt={reviewer.user.name} />
            <span>{reviewer.user.display_name}</span>
            <Icon name={reviewer.state} className={`icon review ${reviewer.state}`} />
            <Icon name="sync" className="icon sync" onclick={() => requestReviewer({selectedValue: reviewer.user.login})} />
          </div>
        {/each}

        <Select name="reviewer" selectableItems={contributors} onChange={requestReviewer} multiple={true} />
      </SidebarGroup>
    {/if}
  {/if}
</Sidebar>

  
<style lang="scss">
  @import '../../../scss/components/item/sidebar.scss';
</style>

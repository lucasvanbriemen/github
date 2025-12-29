<script>
  import { onMount, untrack } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Icon from '../Icon.svelte';
  import Select from '../Select.svelte';

  let { item, isPR, isLoading, params = {} } = $props();
  let activeItem = $state('Issues');

  let addingReviewer = $state(false);

  let organization = $derived(params.organization);
  let repository = $derived(params.repository);

  let selectedableReviewers = $state([]);
  let selectedReviewer = $state();
  let linkedItems = $state([]);
  let projects = $state([]);

  function getItemProject(projectId) {
    return item?.projects_v2?.find(p => p.id === projectId);
  }

  // Generate label style with proper color formatting
  function getLabelStyle(label) {
    return `background-color: #${label.color}4D; color: #${label.color}; border: 1px solid #${label.color};`;
  }

  onMount(async () => {
    // let repoMetadata = await api.get(route('organizations.repositories.metadata.get', {organization, repository}));
    // selectedableReviewers = repoMetadata.assignees;

    formatContributors();

    if (isPR) {
      activeItem = 'Pull Requests';
    }

    linkedItems = await api.get(route('organizations.repositories.item.linked.get', {organization, repository, number: params.number}));
    projects = await api.get(route('organizations.repositories.projects', {organization, repository}));
  });

  function requestReviewer(userId) {
    api.post(route('organizations.repositories.pr.add.reviewers', {organization, repository, number: item.number}), {
      reviewers: [userId]
    });
  }

  function handleReviewerSelected({selectedValue}) {
    requestReviewer(selectedValue);
    selectedReviewer = undefined;
  }

  function formatContributors() {
    selectedableReviewers.forEach(reviewer => {
      item?.requested_reviewers?.forEach(requestedReviewer => {
        if (requestedReviewer.user_id == reviewer.id) {
          reviewer.selected = true;
        }
      });

      reviewer.value = reviewer.login;
      reviewer.image = reviewer.avatar_url;
      reviewer.label = reviewer.display_name;
    });
  }

  async function updateProjectStatus(existingProject, newStatusId) {
    let project = projects.find(p => p.id === existingProject.id);

    await api.post(route('organizations.repositories.project.item.update', {organization, repository}), {
      projectId: existingProject.id,
      itemId: existingProject.itemId,
      fieldId: project.status_field_id,
      statusValue: newStatusId
    });
  }

  async function removeFromProject(existingProject) {
    await api.post(route('organizations.repositories.project.item.remove', {organization, repository}),{
      projectId: existingProject.id,
      itemId: existingProject.itemId
    });

    item.projects_v2 = item.projects_v2.filter(p => p.id !== existingProject.id);
  }

  async function addToProject(project) {

    const projectIndex = projects.findIndex(p => p.id === project.id);
    let selectedStatus = projects[projectIndex].status_options[0].id;

    await api.post(route('organizations.repositories.project.item.add', {organization, repository}), {
      projectId: project.id,
      contentId: item.node_id,
      itemNumber: item.number,
      fieldId: project.status_field_id,
      statusValue: selectedStatus
    });
  }

  function handleClickOutside(event) {
    if (!event.target.closest('.group') && addingReviewer) {
      addingReviewer = false;
    }
  }

  onMount(() => {
    document.addEventListener('click', handleClickOutside);
  });

  $effect(() => {
    void isLoading;

    untrack(() => {
      formatContributors();
    });
  });

</script>

<Sidebar {params} {activeItem}>
  {#if !isLoading}
    <SidebarGroup title="Projects">
        {#each projects as project, idx (project.id)}
          {#if getItemProject(project.id)}
            <div style="padding: 8px; background: #dafbe1; border: 1px solid #34d399; border-radius: 4px; font-size: 12px; margin-bottom: 6px;">
              <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                <a href="#{organization}/{repository}/project/{project.number}" style="color: #0969da; text-decoration: none; font-weight: 500; flex: 1;">
                  {project.title}
                </a>
                <button onclick={() => removeFromProject(getItemProject(project.id))} style="background: none; border: none; color: #d1242f cursor: pointer; font-size: 14px; padding: 0; margin-left: 8px;font-weight: bold;">
                  x
                </button>
              </div>

              <Select name="status-{idx}" selectableItems={project.status_options.map(option => ({value: option.id, label: option.name}))} selectedValue={getItemProject(project.id).status} onChange={(e) => updateProjectStatus(getItemProject(project.id), e.selectedValue)} />
            </div>
          {:else}
            <button onclick={() => addToProject(project)} style="background-color: transparent; border: 1px solid var(--primary-color-dark); margin-bottom: 6px;">
              Add to {project.title}
            </button>
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
    </SidebarGroup>

    {#if isPR}
      <SidebarGroup title="Reviewers">
        <Icon name="gear" className="icon gear" onclick={() => addingReviewer = !addingReviewer} size=".85rem" />

        {#each item.requested_reviewers as reviewer}
          <div class="reviewer">
            <img src={reviewer.user.avatar_url} alt={reviewer.user.name} />
            <span>{reviewer.user.display_name}</span>
            <Icon name={reviewer.state} className={`icon review ${reviewer.state}`} />
            <Icon name="sync" className="icon sync" onclick={() => requestReviewer(reviewer.user.login)} />
          </div>
        {/each}

        {#if addingReviewer}
          <div class="add-reviewer">
            <Select name="reviewer" selectableItems={selectedableReviewers} bind:selectedValue={selectedReviewer} onChange={handleReviewerSelected} multiple={true} />
          </div>
        {/if}
      </SidebarGroup>
    {/if}
  {/if}
</Sidebar>

  
<style lang="scss">
  @import '../../../scss/components/item/sidebar.scss';
</style>

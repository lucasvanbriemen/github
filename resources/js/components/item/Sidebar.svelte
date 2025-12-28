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
  let loadingProjects = $state(true);
  let selectedProjectForAdd = $state(null);
  let selectedStatus = $state(null);

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

    const projectsList = await api.get(route('organizations.repositories.projects', {organization, repository}));
    projects = projectsList.map(p => ({
      ...p
    }));
    
    loadingProjects = false;
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

  async function handleUpdateProjectStatus(existingProject, newStatusId) {
    try {
      const response = await api.post(
        route('organizations.repositories.item.update.project.status', {organization, repository}),
        {
          projectId: existingProject.id,
          itemId: existingProject.itemId,
          fieldId: existingProject.status_field_id,
          statusValue: newStatusId
        }
      );

      if (response.success) {
        // Update the local item to reflect the change
        const projIndex = item.projects_v2.findIndex(p => p.id === existingProject.id);
        if (projIndex >= 0) {
          // Find the option name from the available options
          const statusOption = existingProject.options?.find(opt => opt.id === newStatusId);
          if (statusOption) {
            item.projects_v2[projIndex].status = statusOption.name;
            item.projects_v2 = item.projects_v2; // Force reactivity
          }
        }
      } else {
        alert('Failed to update status: ' + response.message);
      }
    } catch (err) {
      alert('Error: ' + err.message);
    }
  }

  async function handleRemoveFromProject(existingProject) {
    if (!confirm(`Remove this item from "${existingProject.title}"?`)) {
      return;
    }

    try {
      const response = await api.post(
        route('organizations.repositories.item.remove.from.project', {organization, repository}),
        {
          projectId: existingProject.id,
          itemId: existingProject.itemId
        }
      );

      if (response.success) {
        // Remove the project from the list
        item.projects_v2 = item.projects_v2.filter(p => p.id !== existingProject.id);
      } else {
        alert('Failed to remove from project: ' + response.message);
      }
    } catch (err) {
      alert('Error: ' + err.message);
    }
  }

  async function handleSelectProjectToAdd(project) {
    const projectIndex = projects.findIndex(p => p.id === project.id);
    selectedProjectForAdd = projectIndex;
    selectedStatus = projects[projectIndex].status_options[0].id;
  }

  async function handleAddToProjectWithStatus() {
    const project = projects[selectedProjectForAdd];

    await api.post(route('organizations.repositories.item.add.to.project', {organization, repository}), {
      projectId: project.id,
      contentId: item.node_id,
      itemNumber: item.number,
      fieldId: project.status_field_id,
      statusValue: selectedStatus
    });

    selectedProjectForAdd = null;
    selectedStatus = null;
  }

  function cancelSelectingStatus() {
    selectedProjectForAdd = null;
    selectedStatus = null;
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
    {#if !loadingProjects && projects.length > 0}
      <SidebarGroup title="Projects">
        <!-- Show projects this item is already in -->
        {#each item.projects_v2 as existingProject (existingProject.id)}
          <div style="
            padding: 8px;
            background: #dafbe1;
            border: 1px solid #34d399;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 6px;
          ">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
              <a href="#{organization}/{repository}/project/{existingProject.number}" style="color: #0969da; text-decoration: none; font-weight: 500; flex: 1;">
                {existingProject.title}
              </a>
              <button
                onclick={() => handleRemoveFromProject(existingProject)}
                style="
                  background: none;
                  border: none;
                  color: #d1242f;
                  cursor: pointer;
                  font-size: 14px;
                  padding: 0;
                  margin-left: 8px;
                  font-weight: bold;
                "
              >
                ×
              </button>
            </div>

            {#if existingProject.options && existingProject.options.length > 0}
              <select
                value={existingProject.status}
                onchange={(e) => handleUpdateProjectStatus(existingProject, e.target.value)}
                style="
                  width: 100%;
                  padding: 4px;
                  border: 1px solid #34d399;
                  border-radius: 3px;
                  font-size: 11px;
                  background: white;
                  cursor: pointer;
                "
              >
                {#each existingProject.options as option (option.id)}
                  <option value={option.id} selected={option.name === existingProject.status}>
                    {option.name}
                  </option>
                {/each}
              </select>
            {:else}
              <span style="color: #666;">
                → {existingProject.status || 'No status'}
              </span>
            {/if}
          </div>
        {/each}

        <div style="display: flex; flex-direction: column; gap: 8px;">
          {#if selectedProjectForAdd === null}
            {#each projects as project, idx (project.id)}
              <!-- Only show button if not already on this project -->
              {#if !item.projects_v2.some(p => p.id === project.id)}
                <button onclick={() => handleSelectProjectToAdd(project)} style="background-color: transparent; border: 1px solid var(--primary-color-dark);">
                  + Add to {project.title}
                </button>
              {/if}
            {/each}
          {:else}
            <!-- Status Selector Modal -->
            <div style="padding: 12px; background: #f6f8fa; border-radius: 6px; border: 1px solid #d0d7de;">
              <div style="margin-bottom: 12px;">
                <strong style="display: block; margin-bottom: 8px;">Select status for:</strong>
                <span>{projects[selectedProjectForAdd].title}</span>
              </div>

              <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 6px;">Status:</label>
                <select
                  bind:value={selectedStatus}
                  style="
                    width: 100%;
                    padding: 6px 8px;
                    border: 1px solid #d0d7de;
                    border-radius: 6px;
                    font-size: 13px;
                    background: white;
                  "
                >
                  {#each projects[selectedProjectForAdd].status_options as option}
                    <option value={option.id}>{option.name}</option>
                  {/each}
                </select>
              </div>

              <div style="display: flex; gap: 6px;">
                <button
                  onclick={handleAddToProjectWithStatus}
                  style="
                    flex: 1;
                    padding: 6px 12px;
                    background: #0969da;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    font-size: 12px;
                    font-weight: 500;
                    cursor: pointer;
                  "
                >
                  Add
                </button>
                <button
                  onclick={cancelSelectingStatus}
                  style="
                    flex: 1;
                    padding: 6px 12px;
                    background: #d1d5da;
                    color: #333;
                    border: none;
                    border-radius: 6px;
                    font-size: 12px;
                    font-weight: 500;
                    cursor: pointer;
                  "
                >
                  Cancel
                </button>
              </div>
            </div>
          {/if}
        </div>
      </SidebarGroup>
    {/if}

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

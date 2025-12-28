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
  let projectsWithFields = $state([]);
  let addingToProject = $state(null);
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

    // Load available projects
    try {
      const projectsList = await api.get(route('organizations.repositories.projects', {organization, repository}));
      projects = projectsList.map(p => ({
        ...p,
        adding: false,
        fields: null
      }));
    } catch (e) {
      console.error('Failed to load projects:', e);
    }
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

  async function handleSelectProjectToAdd(project) {
    try {
      // Fetch fields for this project
      const projectIndex = projects.findIndex(p => p.id === project.id);
      if (projectIndex >= 0) {
        projects[projectIndex].loading = true;
      }

      const fieldsResponse = await api.get(
        route('organizations.repositories.project.fields', {
          organization,
          repository,
          number: project.number
        })
      );

      if (fieldsResponse.field && fieldsResponse.field.options) {
        projects[projectIndex].fields = fieldsResponse.field;
        projects[projectIndex].projectId = fieldsResponse.projectId;
        selectedProjectForAdd = projectIndex;
        selectedStatus = fieldsResponse.field.options[0]?.id; // Default to first option
      } else {
        alert('Could not load status options for this project');
      }
    } catch (err) {
      alert('Error loading project fields: ' + err.message);
    } finally {
      const projectIndex = projects.findIndex(p => p.id === project.id);
      if (projectIndex >= 0) {
        projects[projectIndex].loading = false;
      }
    }
  }

  async function handleAddToProjectWithStatus() {
    if (selectedProjectForAdd === null || !selectedStatus) {
      alert('Please select a status');
      return;
    }

    const project = projects[selectedProjectForAdd];

    try {
      projects[selectedProjectForAdd].adding = true;

      const response = await api.post(
        route('organizations.repositories.item.add.to.project', {organization, repository}),
        {
          projectId: project.projectId,
          contentId: item.node_id, // GitHub's global node ID
          itemNumber: item.number,
          fieldId: project.fields?.id,
          statusValue: selectedStatus
        }
      );

      if (response.success) {
        // Mark as added
        projects[selectedProjectForAdd].added = true;
        // Remove the "added" status after 2 seconds
        setTimeout(() => {
          projects[selectedProjectForAdd].added = false;
          selectedProjectForAdd = null;
          selectedStatus = null;
          projects = projects; // Force reactivity
        }, 2000);
      } else {
        // Show detailed error info
        let errorMsg = response.message || 'Unknown error';
        if (response.errors && response.errors.length > 0) {
          errorMsg += '\n\nErrors:';
          response.errors.forEach(err => {
            errorMsg += '\n- ' + (err.message || JSON.stringify(err));
          });
        }
        if (response.errorDetails) {
          errorMsg += '\n\nDetails: ' + response.errorDetails;
        }
        console.error('Add to project error:', response);
        alert('Failed to add to project:\n' + errorMsg);
      }
    } catch (err) {
      alert('Error: ' + err.message);
    } finally {
      projects[selectedProjectForAdd].adding = false;
    }
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
        {#if item.projects_v2 && item.projects_v2.length > 0}
          <div style="margin-bottom: 12px;">
            <div style="font-size: 12px; color: #666; margin-bottom: 6px; font-weight: 500;">
              On these projects:
            </div>
            {#each item.projects_v2 as existingProject (existingProject.id)}
              <div style="
                padding: 6px 8px;
                background: #dafbe1;
                border: 1px solid #34d399;
                border-radius: 4px;
                font-size: 12px;
                margin-bottom: 4px;
              ">
                <a href="#{organization}/{repository}/project/{existingProject.number}" style="color: #0969da; text-decoration: none; font-weight: 500;">
                  {existingProject.title}
                </a>
                {#if existingProject.status}
                  <span style="color: #666; margin-left: 6px;">
                    → {existingProject.status}
                  </span>
                {/if}
              </div>
            {/each}
          </div>
          <hr style="margin: 12px 0; border: none; border-top: 1px solid #d0d7de;" />
        {/if}

        <div style="display: flex; flex-direction: column; gap: 8px;">
          {#if selectedProjectForAdd === null}
            {#each projects as project, idx (project.id)}
              <!-- Only show button if not already on this project -->
              {#if !item.projects_v2 || !item.projects_v2.some(p => p.id === project.id)}
                <button
                  onclick={() => handleSelectProjectToAdd(project)}
                  disabled={project.adding || project.loading || project.added}
                  style="
                    padding: 8px 12px;
                    background: {project.added ? '#2da44f' : '#f6f8fa'};
                    color: {project.added ? 'white' : '#24292f'};
                    border: 1px solid {project.added ? '#2da44f' : '#d0d7de'};
                    border-radius: 6px;
                    cursor: {(project.adding || project.loading) ? 'wait' : 'pointer'};
                    font-size: 13px;
                    font-weight: 500;
                    transition: all 0.2s ease;
                  "
                  title={project.updated_at ? 'Updated ' + project.updated_at : ''}
                >
                  {#if project.loading}
                    <span>Loading...</span>
                  {:else if project.adding}
                    <span>Adding...</span>
                  {:else if project.added}
                    <span>✓ Added!</span>
                  {:else}
                    <span>+ Add to {project.title}</span>
                  {/if}
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
                  {#each projects[selectedProjectForAdd].fields?.options || [] as option (option.id)}
                    <option value={option.id}>{option.name}</option>
                  {/each}
                </select>
              </div>

              <div style="display: flex; gap: 6px;">
                <button
                  onclick={handleAddToProjectWithStatus}
                  disabled={projects[selectedProjectForAdd].adding}
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
                  {projects[selectedProjectForAdd].adding ? 'Adding...' : 'Add'}
                </button>
                <button
                  onclick={cancelSelectingStatus}
                  disabled={projects[selectedProjectForAdd].adding}
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

<script>
  import { onMount } from 'svelte';
  import Sidebar from './sidebar/Sidebar.svelte';
  import ListItem from './ListItem.svelte';
  import ListItemSkeleton from './ListItemSkeleton.svelte';
  import Group from './sidebar/group.svelte';

  let { params = {} } = $props();

  let isLoading = $state(true);
  let cols = $state([]);
  let showEverything = $state(false);
  let showAddForm = $state(false);
  let newItemTitle = $state('');
  let projectId = $state('');
  let fieldId = $state('');
  let isSubmitting = $state(false);

  onMount(async () => {
    const projectData = await api.get(route('organizations.repositories.project.show', {
      organization: params.organization,
      repository: params.repository,
      number: params.number,
    }));

    // Handle new response format with metadata
    if (projectData.columns) {
      cols = projectData.columns;
      projectId = projectData.projectId;
      fieldId = projectData.fieldId;
    } else {
      // Fallback for old format
      cols = projectData;
    }

    isLoading = false;

    console.log('Project data:', projectData);
  });

  async function handleAddItem() {
    if (!newItemTitle.trim() || !projectId) {
      alert('Please enter a title');
      return;
    }

    isSubmitting = true;
    try {
      const response = await api.post(
        route('organizations.repositories.project.item.add', {
          organization: params.organization,
          repository: params.repository,
          number: params.number,
        }),
        { projectId, title: newItemTitle }
      );

      if (response.success) {
        newItemTitle = '';
        showAddForm = false;
        // Refresh the project data
        const projectData = await api.get(route('organizations.repositories.project.show', {
          organization: params.organization,
          repository: params.repository,
          number: params.number,
        }));

        // Handle new response format
        if (projectData.columns) {
          cols = projectData.columns;
          projectId = projectData.projectId;
          fieldId = projectData.fieldId;
        } else {
          cols = projectData;
        }
      } else {
        alert('Failed to add item: ' + response.message);
      }
    } catch (err) {
      alert('Error: ' + err.message);
    } finally {
      isSubmitting = false;
    }
  }

  async function handleUpdateColumn(item, targetColumnName) {
    if (!projectId || !fieldId) {
      alert('Project info not available');
      return;
    }

    // Find the option ID for the target column
    const targetColumn = cols.find(col => col.name === targetColumnName);
    if (!targetColumn || !targetColumn.id) {
      alert('Could not find target column option ID');
      return;
    }

    try {
      const response = await api.patch(
        route('organizations.repositories.project.item.update', {
          organization: params.organization,
          repository: params.repository,
          number: params.number,
          itemId: item.projectItemId || item.id,
        }),
        {
          projectId,
          itemId: item.projectItemId || item.id,
          fieldId,
          value: targetColumn.id, // Use the option ID, not the name
        }
      );

      if (response.success) {
        // Refresh the project data
        const projectData = await api.get(route('organizations.repositories.project.show', {
          organization: params.organization,
          repository: params.repository,
          number: params.number,
        }));

        // Handle new response format
        if (projectData.columns) {
          cols = projectData.columns;
          projectId = projectData.projectId;
          fieldId = projectData.fieldId;
        } else {
          cols = projectData;
        }
      } else {
        let errorMsg = response.message || 'Unknown error';
        if (response.fullErrors && response.fullErrors.length > 0) {
          errorMsg += '\n\nGitHub Error: ' + response.fullErrors[0].message;
        }
        if (response.debugInfo) {
          errorMsg += '\n\nDebug Info:\n' + JSON.stringify(response.debugInfo, null, 2);
        }
        console.error('Update column error:', response);
        alert('Failed to update item:\n' + errorMsg);
      }
    } catch (err) {
      alert('Error: ' + err.message);
    }
  }
</script>

<div class="repo-dashboard">
  <Sidebar {params} activeItem="Projects">
    <Group title="Status">
      <div class="switch-container">
        <div class="switch-label">
          <h3>Show everything</h3>
          <p>Show all the items in the on the board rather than all of them</p>
        </div>
        <label class="switch">
          <input type="checkbox" bind:checked={showEverything} />
          <span class="slider"></span>
        </label>
      </div>
    </Group>
  </Sidebar>
  
  <div class="repo-main">
    <!-- Add Item Form Section -->
    <div style="padding: 20px; border-bottom: 1px solid #e0e0e0;">
      <!-- Project Metadata Section -->
      {#if projectId && fieldId}
        <div style="margin-top: 16px; padding: 12px; background: #f0f9ff; border-radius: 6px; font-size: 12px; border: 1px solid #a1d8ff;">
          <div style="margin-bottom: 8px;">
            <strong style="color: #0969da;">✓ Project Configured</strong>
          </div>
          <div style="color: #666; font-family: monospace; font-size: 11px; word-break: break-all;">
            <div><strong>Project:</strong> {projectId?.substring(0, 15)}...</div>
            <div><strong>Field:</strong> {fieldId?.substring(0, 15)}...</div>
          </div>
        </div>
      {/if}
    </div>

    {#if isLoading}
      {#each Array(3) as _, index}
        <div class="column">
          <span class="title">Loading...</span>
          {#each Array(5) as __, idx}
            <ListItemSkeleton key={idx} />
          {/each}
        </div>
      {/each}
    {/if}

    {#each cols as col}
      <div class="column" class:only-me={showEverything == false}>
        <span class="title">{col.name}</span>
        <div style="position: relative;">
          {#each col.items as item}
            <div style="position: relative; margin-bottom: 8px;">
              <ListItem {item} />
              <!-- Column Update Buttons -->
              {#if projectId && fieldId}
                <div style="display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap;">
                  {#each cols as targetCol}
                    {#if targetCol.name !== col.name}
                      <button
                        onclick={() => handleUpdateColumn(item, targetCol.name)}
                        style="padding: 4px 8px; font-size: 11px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;"
                        title="Move to {targetCol.name}"
                      >
                        → {targetCol.name}
                      </button>
                    {/if}
                  {/each}
                </div>
              {/if}
            </div>
          {/each}
        </div>
      </div>
    {/each}
  </div>
</div>
  
<style lang="scss">
  @import '../../scss/components/project.scss';
</style>

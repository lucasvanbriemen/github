<script>
  import { onMount, untrack } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Icon from '../Icon.svelte';
  import Select from '../Select.svelte';
  import Switch from '../Switch.svelte';
  import ReviewerFocusModal from './ReviewerFocusModal.svelte';
  import { organization, repository, repoMetadata } from '../stores';

  let { item, isPR, isLoading, metadata, params = {}, activeTab, files, showWhitespace = $bindable(false), selectedFileIndex = $bindable(0), selectedFile = $bindable(null) } = $props();

  let labels = $state([]);
  let contributors = $state([]);
  let focusedReviewer = $state(null);
  let showFocusModal = $state(false);

  let linkedItems = $state([]);
  let projects = $state([]);
  let linkableItems = $state([]);
  let linkSearchQuery = $state('');
  let possibleAssignees = $state([]);
  let selectedAssignees = $state([]);
  let possibleMilestones = $state([]);

  function getItemProject(projectId) {
    return item.projects.find(p => p.id === projectId);
  }

  // Generate label style with proper color formatting
  function getLabelStyle(label) {
    return `background-color: #${label.color}4D; color: #${label.color}; border: 1px solid #${label.color};`;
  }

  function shortFileName(fileName) {
    const parts = fileName.split('/');
    return parts.slice(-2).join('/');
  }

  onMount(async () => {
    updateLinkedItems();
    projects =  await api.get(route('organizations.repositories.projects', { $organization, $repository }));

    possibleAssignees = ($repoMetadata.assignees || []).filter(a => a).map((a) => ({ value: a.login, label: a.display_name, image: a.avatar_url }));
    possibleMilestones = ($repoMetadata.milestones || []).filter(m => m).map((m) => ({ value: m.number, label: m.title }));

    possibleAssignees.forEach(assignee => {
      item.assignees.forEach(itemAssignee => {
        if (itemAssignee.login == assignee.value) {
          assignee.selected = true;
        }
      });
    });

    possibleMilestones.forEach(milestone => {
      milestone.selected = item.milestone?.number == milestone.value;
    });
  });

  async function updateLinkedItems() {
    linkedItems = await api.get(route('organizations.repositories.item.linked.get', { $organization, $repository, number: params.number }));
  }

  // Update linkableItems selected state based on linkedItems (mutate to avoid infinite loop)
  $effect(() => {
    linkableItems.forEach(item => {
      item.selected = linkedItems.some(linked => linked.number === item.value);
    });
  });

  $effect(() => {
    if (item) {
      searchLinkableItems('');
    }
  });

  function requestReviewer({selectedValue}) {
    if (typeof selectedValue === 'string') {
      selectedValue = [selectedValue];
    }

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

  async function updateMilestone({selectedValue}) {
    api.post(route('organizations.repositories.item.update', { $organization, $repository, number: item.number }), {
      milestone: selectedValue
    });
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


  function searchLinkableItems(query) {
    linkSearchQuery = query;
    const url = route('organizations.repositories.item.linkable.search', { $organization, $repository, number: params.number });
    const searchUrl = query ? `${url}?search=${encodeURIComponent(query)}` : url;

    api.get(searchUrl).then((result) => {
      linkableItems = result;
    })
  }

  async function handleSelectionChange() {
    // Collect all selected items from linkableItems
    const currentSelection = linkableItems.filter(item => item.selected).map(item => item.value);
    // Get current linked items
    const previousSelection = linkedItems.map(item => item.number);

    const addedItems = currentSelection.filter(item => !previousSelection.includes(item));
    const removedItems = previousSelection.filter(item => !currentSelection.includes(item));

    const promises = [];

    if (addedItems.length > 0) {
      promises.push(
        api.post(route('organizations.repositories.item.link.bulk.create', { $organization, $repository, number: item.number }), { target_numbers: addedItems })
      );
    }

    if (removedItems.length > 0) {
      promises.push(
        api.post(route('organizations.repositories.item.link.bulk.remove', { $organization, $repository, number: item.number }), { target_numbers: removedItems })
      );
    }

    // Wait for all API calls to complete
    if (promises.length > 0) {
      await Promise.all(promises);
    }

    // Refresh linked items and search results
    await updateLinkedItems();
    searchLinkableItems(linkSearchQuery);
  }

  async function addAssignees() {
    await api.post(route('organizations.repositories.item.assignees.update', { $organization, $repository, number: item.number }), {
      assignees: selectedAssignees
    });
  }

  function getCustomButtons() {
    let key = $organization;
    key = key.toLowerCase();

    return window.ORG_RULES[key].custom_buttons;
  }

  function handleCustomButtonClick(button) {
    const currentLabelNames = (item?.labels || []).map(l => l.name);
    let newLabels = [...currentLabelNames];
    newLabels.push(button.value);

    updateLabels({ selectedValue: newLabels });
  }

  function openFocusMode(reviewer) {
    focusedReviewer = reviewer;
    showFocusModal = true;
  }

  function closeFocusMode() {
    showFocusModal = false;
    focusedReviewer = null;
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

      contributors = (metadata?.assignees || []).filter(a => a);
      contributors = contributors.map(assignee => ({value: assignee.login, label: assignee.display_name, image: assignee.avatar_url}));
      contributors.forEach(contributor => {
        item?.requested_reviewers?.forEach(requestedReviewer => {
          if (requestedReviewer.user.login == contributor.value && requestedReviewer.state === 'pending') {
            contributor.selected = true;
          }
        });
      });
    });
  });

</script>

<Sidebar>
  {#if !isLoading && isPR && activeTab === 'files'}
    <SidebarGroup title="Diff Settings">
      <Switch title="Show whitespace changes" description="Toggle to hide whitespace changes in the diff view." bind:input={showWhitespace}/>
    </SidebarGroup>

    <SidebarGroup title="Files">
      <div class="file-selector">
        {#each files as file (file.filename)}
          <button class="file-name" class:selected={selectedFile?.filename === file.filename} onclick={() => { selectedFile = file; selectedFileIndex = files.indexOf(file); }}>{shortFileName(file.filename)}</button>
        {/each}
      </div>
    </SidebarGroup>
  {/if}

  {#if !isLoading && activeTab === 'conversation'}
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

      <Select name="assignee" selectableItems={possibleAssignees} bind:selectedValue={selectedAssignees} onChange={() => { addAssignees() }} multiple={true} />
    </SidebarGroup>

    <SidebarGroup title="Linked Items">
      {#each linkedItems as linkedItem}
        <a class="linked-item" href={linkedItem.url}>
          <Icon name={linkedItem.type} className="icon {linkedItem.state}" /> {linkedItem.title}
        </a>
      {/each}

      <Select name="link-item" selectableItems={linkableItems} multiple={true} onChange={handleSelectionChange} onSearch={(query) => searchLinkableItems(query)}/>
    </SidebarGroup>

    <SidebarGroup title="Labels">
      {#if item.labels.length > 0}
        <div class="labels">
          {#each item.labels as label}
            <span class="label" style={getLabelStyle(label)}>
              {label.name}
            </span>
          {/each}
        </div>
      {/if}

      <Select name="label" selectableItems={labels} onChange={updateLabels} multiple/>
    </SidebarGroup>

    <SidebarGroup title="Quick Actions">
      <div class="custom-buttons">
        {#each getCustomButtons() as button}
          <button class="button-primary-outline" onclick={() => handleCustomButtonClick(button)}>{button.label}</button>
        {/each}
      </div>
    </SidebarGroup>

    <SidebarGroup title="Milestone">
      {#if item.milestone?.id}
        <div class="milestone">
          {item.milestone.title}
        </div>
      {/if}

      <Select name="label" selectableItems={possibleMilestones} onChange={updateMilestone} />
    </SidebarGroup>

    {#if isPR}
      <SidebarGroup title="Reviewers">
        {#each item.requested_reviewers as reviewer}
          <div class="reviewer">
            <img src={reviewer.user.avatar_url} alt={reviewer.user.name} />
            <span>{reviewer.user.display_name}</span>
            <div class="reviewer-actions">
              <Icon name={reviewer.state} className={`icon review ${reviewer.state}`} />
              <Icon name="sync" className="icon sync" onclick={() => requestReviewer({selectedValue: reviewer.user.login})} />
              <Icon name="listing" className="icon focus-button" onclick={() => openFocusMode(reviewer)} />
            </div>
          </div>
        {/each}

        <Select name="reviewer" selectableItems={contributors} onChange={requestReviewer} multiple={true} />
      </SidebarGroup>

      <ReviewerFocusModal isOpen={showFocusModal} onClose={closeFocusMode} reviewer={focusedReviewer} allComments={item.comments} {params} />
    {/if}
  {/if}
</Sidebar>

  
<style lang="scss">
  @import '../../../scss/components/item/sidebar.scss';
</style>

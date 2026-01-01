<script>
  import { onMount, untrack } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Icon from '../Icon.svelte';
  import Select from '../Select.svelte';
  import Switch from '../Switch.svelte';
  import { organization, repository } from '../stores';

  let { item, isPR, isLoading, metadata, params = {}, activeTab, files, showWhitespace = $bindable(true), selectedFileIndex = $bindable(0), selectedFile = $bindable(null) } = $props();

  let labels = $state([]);
  let contributors = $state([]);

  let linkedItems = $state([]);
  let projects = $state([]);
  let linkableItems = $state([]);
  let selectedLinkItems = $state([]);
  let isLinking = $state(false);
  let linkSearchQuery = $state('');
  let previousSelectedLinkItems = $state([]);

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

  let isSearchSelectOpen = $state(false);

  onMount(async () => {
    linkedItems = await api.get(route('organizations.repositories.item.linked.get', { $organization, $repository, number: params.number }));
    projects = await api.get(route('organizations.repositories.projects', { $organization, $repository }));
  });

  // Refresh linked items whenever item changes
  $effect(() => {
    if (item && item.number) {
      api.get(route('organizations.repositories.item.linked.get', { $organization, $repository, number: params.number })).then((result) => {
        linkedItems = result;
      });
    }
  });

  $effect(() => {
    if (isSearchSelectOpen && linkableItems.length === 0) {
      searchLinkableItems('');
    }
  });

  // Update previousSelectedLinkItems when linkedItems change
  $effect(() => {
    previousSelectedLinkItems = linkedItems.map(item => item.number);
    selectedLinkItems = linkedItems.map(item => item.number);
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

  function mergeLinkedItemsIntoSelect() {
    // Merge linkedItems into linkableItems, marking linked ones as selected
    const mergedItems = linkableItems.map(item => ({
      ...item,
      selected: linkedItems.some(linked => linked.number === item.value)
    }));

    // Add linked items that aren't in linkableItems (for already-linked items)
    linkedItems.forEach(linked => {
      if (!mergedItems.some(item => item.value === linked.number)) {
        mergedItems.unshift({
          value: linked.number,
          label: linked.title,
          selected: true
        });
      }
    });

    return mergedItems;
  }

  function searchLinkableItems(query) {
    linkSearchQuery = query;
    const url = route('organizations.repositories.item.linkable.search', { $organization, $repository, number: item.number });
    const searchUrl = query ? `${url}?search=${encodeURIComponent(query)}` : url;
    api.get(searchUrl).then((result) => {
      linkableItems = result;
    }).catch((err) => {
      console.error('Error searching linkable items:', err);
    });
  }

  function handleSelectionChange() {
    if (isLinking) return;

    const currentSelection = selectedLinkItems || [];
    const previousSelection = previousSelectedLinkItems || [];

    // Find what was added
    const addedItems = currentSelection.filter(item => !previousSelection.includes(item));

    // Find what was removed
    const removedItems = previousSelection.filter(item => !currentSelection.includes(item));

    isLinking = true;

    const promises = [];

    // Add new links
    if (addedItems.length > 0) {
      promises.push(
        api.post(
          route('organizations.repositories.item.link.bulk.create', { $organization, $repository, number: item.number }),
          { target_numbers: addedItems }
        )
      );
    }

    // Remove unlinked items
    if (removedItems.length > 0) {
      promises.push(
        api.post(
          route('organizations.repositories.item.link.bulk.remove', { $organization, $repository, number: item.number }),
          { target_numbers: removedItems }
        )
      );
    }

    Promise.all(promises).then(() => {
      // Refresh linked items
      api.get(route('organizations.repositories.item.linked.get', { $organization, $repository, number: params.number })).then((result) => {
        linkedItems = result;
      });

      // Refresh search results
      searchLinkableItems(linkSearchQuery);

      // Update previous selection
      previousSelectedLinkItems = currentSelection;
      isLinking = false;
    }).catch(() => {
      isLinking = false;
    });
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
  {#if !isLoading && isPR && activeTab === 'files'}
    <SidebarGroup title="Diff Settings">
      <Switch title="Hide Whitespace Changes" description="Toggle to hide whitespace changes in the diff view." bind:input={showWhitespace}/>
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
    </SidebarGroup>

    <SidebarGroup title="Linked Items">
      <Select name="link-item" selectableItems={mergeLinkedItemsIntoSelect()} bind:selectedValue={selectedLinkItems} multiple={true} onChange={(e) => {handleSelectionChange();}} onSearch={(query) => searchLinkableItems(query)} onMenuOpen={(isOpen) => isSearchSelectOpen = isOpen}/>

      {#each linkedItems as linkedItem (linkedItem.number)}
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
